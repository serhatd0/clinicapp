<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

// Rol silme yetkisi kontrolü
checkPagePermission('rol_sil');

// Admin rolünün silinmesini engelle
if (isset($_GET['id']) && $_GET['id'] == 1) {
    header('Location: roles.php?error=admin_delete');
    exit;
}

if (!isset($_GET['id'])) {
    header('Location: roles.php');
    exit;
}

try {
    $database = new Database();
    $db = $database->connect();
    
    $roleId = (int)$_GET['id'];
    
    // Önce bu role sahip kullanıcı var mı kontrol et
    $stmt = $db->prepare("SELECT COUNT(*) FROM kullanicilar WHERE ROL_ID = ?");
    $stmt->execute([$roleId]);
    $userCount = $stmt->fetchColumn();
    
    if ($userCount > 0) {
        // Bu role sahip kullanıcı varsa silme işlemini engelle
        header('Location: roles.php?error=has_users');
        exit;
    }
    
    // Önce rol_yetkileri tablosundan ilgili kayıtları sil
    $stmt = $db->prepare("DELETE FROM rol_yetkileri WHERE rol_id = ?");
    $stmt->execute([$roleId]);
    
    // Sonra rolü sil
    $stmt = $db->prepare("DELETE FROM roller WHERE ID = ?");
    $stmt->execute([$roleId]);
    
    header('Location: roles.php?success=deleted');
    exit;
    
} catch (PDOException $e) {
    error_log("Rol silme hatası: " . $e->getMessage());
    header('Location: roles.php?error=delete_failed');
    exit;
} 