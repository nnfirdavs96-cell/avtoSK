<?php
define('APP_NAME', 'АвтоЗапчасть');
define('APP_ROOT', dirname(__DIR__));

if (!defined('APP_URL')) {
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
    define('APP_URL', rtrim($scheme . '://' . $host, '/'));
}
define('UPLOAD_PATH', APP_ROOT . '/assets/uploads/');
define('UPLOAD_URL',  APP_URL  . '/assets/uploads/');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once APP_ROOT . '/config/database.php';
require_once APP_ROOT . '/includes/functions.php';
