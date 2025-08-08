<?php
// Bắt đầu session
session_start();

// Hủy tất cả các biến session
$_SESSION = array();

// Hủy session
session_destroy();

header("Location: login.html");
exit();
?>