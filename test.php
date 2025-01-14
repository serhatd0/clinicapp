<?php
require_once 'includes/db.php';
require_once 'includes/auth.php';

session_start();
$database = new Database();
$db = $database->connect();
$auth = Auth::getInstance($db);

$auth->logout();

header('Location: login.php');
exit;
?>