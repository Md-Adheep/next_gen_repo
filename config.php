<?php
// ── Database ─────────────────────────────────────────────────
define('DB_HOST',    'localhost');
define('DB_PORT',    3306);
define('DB_NAME',    'nextgen_db');
define('DB_USER', 'nextgen_user');          //- change
define('DB_PASS', 'NextGen@12345!');       // ← change
define('DB_CHARSET', 'utf8mb4');

// ── App ──────────────────────────────────────────────────────
define('APP_NAME',        'NextGen Technologies');
define('SESSION_LIFETIME', 28800);  // 8 hours
define('CORS_ORIGIN',     '*');     // set to your domain in production
