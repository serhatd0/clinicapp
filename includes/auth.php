<?php
session_start();

function isLoggedIn()
{
    return isset($_SESSION['user_id']);
}

function isAdmin()
{
    return isset($_SESSION['rol_id']) && $_SESSION['rol_id'] == 1; // 1 = admin rolü
}

function requireLogin()
{
    // Eğer login sayfasındaysak kontrol etmeye gerek yok
    if (basename($_SERVER['PHP_SELF']) === 'login.php') {
        return;
    }

    if (!isLoggedIn()) {
        header('Location: login.php');
        exit;
    }
}

// Yetki kontrolü fonksiyonu
function hasPermission($yetki_key)
{
    if (!isset($_SESSION['user_id'])) {
        return false;
    }

    // Admin rolü tüm yetkilere sahip olsun
    if (isAdmin()) {
        return true;
    }

    static $database = null;
    if ($database === null) {
        $database = new Database();
    }

    // Yetki kontrolü yap ve sonucu logla
    $result = $database->checkPermission($_SESSION['user_id'], $yetki_key);
    error_log("Yetki kontrolü - Kullanıcı ID: " . $_SESSION['user_id'] .
        ", Yetki: " . $yetki_key .
        ", Sonuç: " . ($result ? 'true' : 'false'));

    return $result;
}

// Sayfa erişim kontrolü
function checkPagePermission($yetki_key)
{
    // Önce login kontrolü
    requireLogin();

    // Yetki kontrolü
    if (!hasPermission($yetki_key)) {
        error_log("Yetkisiz erişim denemesi - Kullanıcı ID: " . $_SESSION['user_id'] .
            ", Sayfa: " . $_SERVER['PHP_SELF'] .
            ", Gereken Yetki: " . $yetki_key);
        header('Location: unauthorized.php');
        exit;
    }
}

// Her sayfanın başında çağrılacak
requireLogin();