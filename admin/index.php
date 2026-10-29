<?php
require_once __DIR__ . '/../config/auth.php';

if (admin_logged_in()) {
    header('Location: ' . page_url('admin/dashboard.php'));
    exit;
}

header('Location: ' . page_url('admin/login.php'));
exit;
