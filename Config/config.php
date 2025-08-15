<?php
// config/config.php
define('DB_DSN', 'mysql:host=localhost;dbname=project;charset=utf8mb4');
define('DB_USER', 'root');
define('DB_PASS', 'uvindu');

define('UPLOAD_DIR', __DIR__ . '/../uploads/'); // ensure writable
// CORS origin for your frontend (adjust if needed)
define('FRONTEND_ORIGIN', 'http://localhost:3000');
