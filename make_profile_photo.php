<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';

try {
    // JSON verisini al
    $data = json_decode(file_get_contents('php://input'), true);
    $photoId = $data['photo_id'] ?? 0;
    $patientId = $data['patient_id'] ?? 0;

    if (!$photoId || !$patientId) {
        throw new Exception('Geçersiz parametreler');
    }

    $database = new Database();
    $db = $database->connect();

    // Fotoğraf bilgilerini al
    $stmt = $db->prepare("SELECT DOSYA_YOLU FROM hasta_galerileri WHERE ID = :id AND HASTA_ID = :hasta_id");
    $stmt->execute([':id' => $photoId, ':hasta_id' => $patientId]);
    $photo = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$photo) {
        throw new Exception('Fotoğraf bulunamadı');
    }

    // Profiles klasörünü kontrol et ve oluştur
    $profilesDir = 'uploads/profiles/';
    if (!file_exists($profilesDir)) {
        mkdir($profilesDir, 0777, true);
    }

    // Orijinal fotoğrafın yolu
    $originalPath = $photo['DOSYA_YOLU'];
    
    // Yeni profil fotoğrafı için benzersiz isim oluştur
    $newFileName = 'profile_' . $patientId . '_' . time() . '_' . basename($originalPath);
    $newFilePath = $profilesDir . $newFileName;

    // Eski profil fotoğrafını bul ve sil
    $stmt = $db->prepare("SELECT PROFIL_RESMI FROM hastalar WHERE ID = :id");
    $stmt->execute([':id' => $patientId]);
    $oldProfile = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($oldProfile && $oldProfile['PROFIL_RESMI']) {
        $oldProfilePath = $profilesDir . $oldProfile['PROFIL_RESMI'];
        if (file_exists($oldProfilePath)) {
            unlink($oldProfilePath);
        }
    }

    // Fotoğrafı kopyala
    if (!copy($originalPath, $newFilePath)) {
        throw new Exception('Fotoğraf kopyalanırken bir hata oluştu');
    }

    // Profil fotoğrafını güncelle
    $stmt = $db->prepare("UPDATE hastalar SET PROFIL_RESMI = :foto WHERE ID = :id");
    $stmt->execute([
        ':foto' => $newFileName,
        ':id' => $patientId
    ]);

    echo json_encode(['success' => true]);

} catch (Exception $e) {
    error_log('Profil fotoğrafı güncelleme hatası: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 