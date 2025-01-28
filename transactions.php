<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

// Cari işlemler erişim kontrolü
checkPagePermission('cari_erisim');

// Butonlar için yetki kontrolleri
$canAddTransaction = hasPermission('cari_ekle');
$canEditTransaction = hasPermission('cari_duzenle');
$canDeleteTransaction = hasPermission('cari_sil');

// Debug için yetki durumunu logla
error_log("Cari Erişim Yetki Kontrolü - Kullanıcı ID: " . $_SESSION['user_id'] . 
          ", Yetki Durumu: " . (hasPermission('cari_erisim') ? 'true' : 'false'));

$database = new Database();
$db = $database->connect(); 