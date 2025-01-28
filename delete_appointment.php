<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

// Randevu silme yetkisi kontrolü
checkPagePermission('randevu_sil');

if (!isset($_GET['id'])) {
    header('Location: appointments.php');
    exit;
}

try {
    $database = new Database();
    $db = $database->connect();

    $appointmentId = (int) $_GET['id'];

    // Önce randevu bilgilerini al
    $stmt = $db->prepare("SELECT * FROM randevular WHERE ID = :id");
    $stmt->execute([':id' => $appointmentId]);
    $appointment = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$appointment) {
        header('Location: appointments.php?error=1');
        exit;
    }

    // Hasta ID'sini sakla
    $hastaId = $appointment['HASTA_ID'];

    // Seri randevuyu sil seçeneği varsa
    if (isset($_GET['series']) && $_GET['series'] == '1' && $appointment['ANA_RANDEVU_ID']) {
        // Tüm seriyi sil
        $stmt = $db->prepare("DELETE FROM randevular WHERE ANA_RANDEVU_ID = :main_id");
        $stmt->execute([':main_id' => $appointment['ANA_RANDEVU_ID']]);
    } else {
        // Sadece seçili randevuyu sil
        $stmt = $db->prepare("DELETE FROM randevular WHERE ID = :id");
        $stmt->execute([':id' => $appointmentId]);
    }

    // Hasta randevuları sayfasına geri dön
    header('Location: patient_appointments.php?id=' . $hastaId . '&success=2');
    exit;

} catch (Exception $e) {
    error_log($e->getMessage());
    header('Location: patient_appointments.php?id=' . $hastaId . '&error=1');
    exit;
}