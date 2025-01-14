<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';

$patientId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$patientId) {
    header('Location: patients.php');
    exit;
}

try {
    $database = new Database();
    $db = $database->connect();
    
    // Önce randevuları sil
    $stmt = $db->prepare("DELETE FROM randevular WHERE HASTA_ID = :id");
    $stmt->execute([':id' => $patientId]);
    
    // Sonra hastayı sil
    $stmt = $db->prepare("DELETE FROM hastalar WHERE ID = :id");
    $stmt->execute([':id' => $patientId]);
    
    header('Location: patients.php?message=deleted');
    exit;
    
} catch (PDOException $e) {
    error_log($e->getMessage());
    header('Location: patients.php?error=delete_failed');
    exit;
} 