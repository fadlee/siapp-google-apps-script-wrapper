<?php
require_once dirname(__DIR__) . '/../config/AuthManager.php';

// Perform logout
AuthManager::logout();

// Redirect to login page with a success message
header('Location: /cp/login.php?message=logged_out');
exit();
