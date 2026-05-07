<?php
require_once dirname(__DIR__) . '/config/config.php';
session_destroy();
header('Location: ' . APP_URL . '/auth/login.php');
exit;
