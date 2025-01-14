<?php
session_start();

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isset($_SESSION['rol_id']) && $_SESSION['rol_id'] == 1; // 1 = admin rolü
}

function requireLogin() {
    // Eğer login sayfasındaysak kontrol etmeye gerek yok
    if (basename($_SERVER['PHP_SELF']) === 'login.php') {
        return;
    }
    
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit;
    }
}

// Her sayfanın başında çağrılacak
requireLogin(); 