<?php
session_start();
require_once '../config/config.php';

// Log admin logout before destroying session
try {
    if (isset($_SESSION['admin_id'])) {
        $database = new Database();
        $db = $database->getConnection();
        logAdminAction($db, $_SESSION['admin_id'], 'logout', 'Admin logged out');
    }
} catch (Throwable $e) {}

session_destroy();
redirect('auth/login.php');
?>
