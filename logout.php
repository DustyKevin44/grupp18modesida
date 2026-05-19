<?php
session_start();
$_SESSION = array(); // Clear session data
session_destroy();   // Destroy the session completely

header("Location: login.php");
exit();
?>