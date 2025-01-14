<?php
require_once 'includes/auth.php';

// Oturumu sonlandır
session_start();
session_destroy();

// Çıkış yapıldıktan sonra login sayfasına yönlendir
header('Location: login.php');
exit; 