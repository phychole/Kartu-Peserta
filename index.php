<?php
require_once __DIR__ . '/config/bootstrap.php';
if (is_logged_in()) {
    header('Location: ' . url('/admin/dashboard.php'));
} else {
    header('Location: ' . url('/auth/login.php'));
}
exit;
?>