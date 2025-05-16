<?php
// session start
session_start();

// Unset all session variables
$_SESSION = array();

session_destroy();

header("location: ../Retail System-Warehouse-Login.php");
exit;
?>