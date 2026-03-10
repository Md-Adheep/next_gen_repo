<?php
// check.php - Open this in browser to diagnose your setup
// http://localhost/yourfolder/check.php
// DELETE after fixing!

echo "<style>body{font-family:sans-serif;padding:24px;max-width:700px;} 
.ok{color:green;font-weight:bold;} .fail{color:red;font-weight:bold;} 
.warn{color:orange;font-weight:bold;}
pre{background:#f4f4f4;padding:12px;border-radius:8px;font-size:13px;}
h2{margin-top:24px;}</style>";

echo "<h1>🔍 NextGen Diagnostics</h1>";

// 1. PHP version
echo "<h2>1. PHP</h2>";
echo "<span class='ok'>✅ PHP is running — version: " . PHP_VERSION . "</span><br>";

// 2. Required files
echo "<h2>2. Required files (must all be in SAME folder as this file)</h2>";
$files = ['config.php','db.php','helpers.php','auth.php'];
foreach($files as $f){
    $exists = file_exists(__DIR__.'/'.$f);
    echo ($exists ? "<span class='ok'>✅" : "<span class='fail'>❌") . " $f</span><br>";
}

// 3. DB connection
echo "<h2>3. Database connection</h2>";
if(file_exists(__DIR__.'/config.php')){
    require_once __DIR__.'/config.php';
    $dsn = "mysql:host=".DB_HOST.";port=".DB_PORT.";dbname=".DB_NAME.";charset=".DB_CHARSET;
    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        echo "<span class='ok'>✅ Database connected! (nextgen_db)</span><br>";
        
        // Check tables
        $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
        echo "<br><strong>Tables found:</strong> ";
        echo $tables ? implode(', ',$tables) : "<span class='fail'>NONE - run schema.sql first!</span>";
        echo "<br>";
        
        // Check admin user
        if(in_array('users',$tables)){
            $u = $pdo->query("SELECT username,role,status FROM users WHERE username='admin' LIMIT 1")->fetch();
            if($u){
                echo "<br><span class='ok'>✅ Admin user found</span> (role: {$u['role']}, status: {$u['status']})<br>";
                
                // Fix password
                echo "<br><h2>4. Fix admin password → <code>Admin@123</code></h2>";
                $hash = password_hash('Admin@123', PASSWORD_BCRYPT);
                $pdo->prepare("UPDATE users SET password=? WHERE username='admin'")->execute([$hash]);
                echo "<span class='ok'>✅ Password updated to Admin@123</span><br>";
                echo "<br><strong>Login with:</strong> username <code>admin</code> / password <code>Admin@123</code><br>";
            } else {
                echo "<span class='fail'>❌ No admin user! Import schema.sql first.</span><br>";
            }
        }
    } catch(PDOException $e){
        echo "<span class='fail'>❌ DB Connection failed: ".htmlspecialchars($e->getMessage())."</span><br>";
        echo "<br><pre>Fix config.php:
DB_USER = 'root'          (or your MySQL username)
DB_PASS = ''              (empty for XAMPP, or your password)
DB_NAME = 'nextgen_db'    (must create this database first)</pre>";
        
        // Try without database name to check if MySQL itself works
        try {
            $pdo2 = new PDO("mysql:host=".DB_HOST.";port=".DB_PORT, DB_USER, DB_PASS);
            echo "<span class='warn'>⚠️ MySQL is running but database 'nextgen_db' doesn't exist.</span><br>";
            echo "→ Create it: <code>CREATE DATABASE nextgen_db;</code> then import schema.sql<br>";
        } catch(PDOException $e2){
            echo "<span class='fail'>❌ MySQL itself is unreachable: ".htmlspecialchars($e2->getMessage())."</span><br>";
            echo "→ Start MySQL in XAMPP/WAMP control panel<br>";
        }
    }
} else {
    echo "<span class='fail'>❌ config.php not found in: ".__DIR__."</span><br>";
}

echo "<br><hr><p style='color:#999;font-size:12px;'>⚠️ DELETE check.php after fixing!</p>";
