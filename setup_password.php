<?php
// ============================================================
//  setup_password.php
//  Run this ONCE after importing schema.sql to set admin password
//  Open in browser: http://localhost/nextgen/setup_password.php
//  DELETE this file after running it!
// ============================================================

require_once __DIR__ . '/db.php';

$password  = 'Admin@123';
$hash      = password_hash($password, PASSWORD_BCRYPT);

try {
    $pdo = db();

    // Update admin password
    $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE username = 'admin'");
    $stmt->execute([$hash]);

    if ($stmt->rowCount() > 0) {
        echo "<div style='font-family:sans-serif;padding:30px;'>";
        echo "<h2 style='color:green;'>✅ Password set successfully!</h2>";
        echo "<p><strong>Username:</strong> admin</p>";
        echo "<p><strong>Password:</strong> Admin@123</p>";
        echo "<p><strong>Hash:</strong> <code>" . htmlspecialchars($hash) . "</code></p>";
        echo "<br><p style='color:red;font-weight:bold;'>⚠️ DELETE this file (setup_password.php) now for security!</p>";
        echo "<br><a href='login.html' style='background:#7C3AED;color:#fff;padding:10px 20px;border-radius:8px;text-decoration:none;'>→ Go to Login</a>";
        echo "</div>";
    } else {
        echo "<div style='font-family:sans-serif;padding:30px;'>";
        echo "<h2 style='color:orange;'>⚠️ No admin user found.</h2>";
        echo "<p>Make sure you imported schema.sql first.</p>";
        echo "<p>Run: <code>mysql -u root -p nextgen_db < schema.sql</code></p>";
        echo "</div>";
    }
} catch (Exception $e) {
    echo "<div style='font-family:sans-serif;padding:30px;background:#FEF2F2;'>";
    echo "<h2 style='color:red;'>❌ Database Connection Error</h2>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<h3>How to fix:</h3>";
    echo "<ol>";
    echo "<li>Open <code>config.php</code></li>";
    echo "<li>Set <strong>DB_USER</strong> to your MySQL username (usually <code>root</code>)</li>";
    echo "<li>Set <strong>DB_PASS</strong> to your MySQL password (empty <code>''</code> for XAMPP)</li>";
    echo "<li>Make sure MySQL is running (check XAMPP/WAMP control panel)</li>";
    echo "<li>Make sure you ran <code>schema.sql</code> to create the database</li>";
    echo "</ol>";
    echo "</div>";
}
