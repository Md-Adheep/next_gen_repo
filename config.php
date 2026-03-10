<?php
// ── Database ─────────────────────────────────────────────────
define('DB_HOST',    'localhost');
define('DB_PORT',    3306);
define('DB_NAME',    'nextgen_db');
// ⚠️  XAMPP/WampServer default → root + empty password
// ⚠️  Change these to match YOUR MySQL setup
define('DB_USER', 'root');             // ← your MySQL username
define('DB_PASS', '');                 // ← your MySQL password (empty for XAMPP default)
define('DB_CHARSET', 'utf8mb4');

// ── App ──────────────────────────────────────────────────────
define('APP_NAME',        'NextGen Technologies');
define('SESSION_LIFETIME', 28800);  // 8 hours
define('CORS_ORIGIN',     '*');     // set to your domain in production
