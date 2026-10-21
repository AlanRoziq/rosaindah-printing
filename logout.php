<?php
require_once __DIR__ . '/config/customer-auth.php';
customer_logout();
header('Location: ' . page_url('index.php'));
exit;
