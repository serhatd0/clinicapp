if (isset($_GET['id'])) {
    $appointmentId = (int)$_GET['id'];
    
    // Ana randevu ID'sini bul
    $stmt = $db->prepare("SELECT ANA_RANDEVU_ID FROM randevular WHERE ID = :id");
    $stmt->execute([':id' => $appointmentId]);
    $mainId = $stmt->fetchColumn();
    
    if ($mainId) {
        // Tüm seriyi sil
        $stmt = $db->prepare("DELETE FROM randevular WHERE ANA_RANDEVU_ID = :main_id");
        $stmt->execute([':main_id' => $mainId]);
    }
} 