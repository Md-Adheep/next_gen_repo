<?php
// api/auth.php
// POST ?action=login
// POST ?action=logout
// GET  ?action=me

require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/db.php';
cors();

$act   = $_GET['action'] ?? '';
$input = body();

/* LOGIN */
if ($act === 'login') {

    $username = trim($input['username'] ?? '');
    $password = trim($input['password'] ?? '');

    if (!$username || !$password) fail('Username and password are required.');

    $stmt = db()->prepare('SELECT * FROM users WHERE username = ? AND status="Active" LIMIT 1');
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if (!$user) fail('Username not found.');
    if (!password_verify($password, $user['password'])) fail('Incorrect password.');

    startSess();

    $sess = [
        'id' => $user['id'],
        'name' => $user['name'],
        'username' => $user['username'],
        'email' => $user['email'],
        'role' => $user['role'],
        'department' => $user['department']
    ];

    $_SESSION['ng_user'] = $sess;

    logAct($user['id'], 'LOGIN', 'Logged in');

    ok($sess, 'Login successful');
}

/* LOGOUT */
elseif ($act === 'logout') {

    $u = authUser();
    if ($u) logAct($u['id'], 'LOGOUT', 'Logged out');

    startSess();
    session_destroy();

    ok([], 'Logged out');
}

/* ME */
elseif ($act === 'me') {
    ok(needAuth());
}

else {
    fail('Unknown action.', 404);
}
