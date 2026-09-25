<?php
require_once 'conn.php';
$_SESSION = array();
session_destroy();
header("Location: index.php");
exit();
?>