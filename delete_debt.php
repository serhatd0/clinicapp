<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

// Borç silme yetkisi kontrolü
checkPagePermission('odeme_sil');

header('Content-Type: application/json');

try {
    $borcId = isset($_GET['id']) ? (int) $_GET['id'] : 0;

    if (!$borcId) {
        throw new Exception('Geçersiz borç ID\'si');
    }

    $database = new Database();
    $db = $database->connect();

    // Transaction başlat
    $db->beginTransaction();

    try {
        // Önce ödemeleri sil
        $stmt = $db->prepare("
            DELETE o FROM odemeler o
            JOIN taksitler t ON t.ID = o.TAKSIT_ID
            WHERE t.BORC_ID = :borc_id
        ");
        $stmt->execute([':borc_id' => $borcId]);

        // Taksitleri sil
        $stmt = $db->prepare("DELETE FROM taksitler WHERE BORC_ID = :borc_id");
        $stmt->execute([':borc_id' => $borcId]);

        // Borcu sil
        $stmt = $db->prepare("DELETE FROM hasta_borc WHERE ID = :borc_id");
        $stmt->execute([':borc_id' => $borcId]);

        // Transaction'ı onayla
        $db->commit();

        echo json_encode(['success' => true]);

    } catch (Exception $e) {
        // Hata durumunda rollback yap
        $db->rollBack();
        throw $e;
    }

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}