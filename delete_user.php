<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

// Sadece admin yetkisi olanlar silebilir
if (!isAdmin()) {
    header('Location: index.php');
    exit;
}

$user_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($user_id) {
    try {
        $database = new Database();
        $db = $database->connect();
        
        // Kullanıcının kendisini silmesini engelle
        if ($user_id == $_SESSION['user_id']) {
            header('Location: settings.php?error=self_delete');
            exit;
        }
        
        $stmt = $db->prepare("DELETE FROM kullanicilar WHERE ID = :id");
        $stmt->execute([':id' => $user_id]);
        
        header('Location: settings.php?message=user_deleted');
    } catch (Exception $e) {
        header('Location: settings.php?error=delete_failed');
    }
} else {
    header('Location: settings.php');
}
exit; 