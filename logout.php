<?php
require_once '../config/session.php';

// Logout user
logout();

// Redirect to login page
header('Location: login.php');
exit();
?>
