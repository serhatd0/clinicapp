<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';

$database = new Database();
$db = $database->connect();

try {
    // Tek fotoğraf silme
    if (isset($_GET['id'])) {
        $photo_id = (int)$_GET['id'];
        
        $stmt = $db->prepare("SELECT * FROM hasta_galerileri WHERE ID = :id");
        $stmt->execute([':id' => $photo_id]);
        $photo = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($photo) {
            // Dosyayı sil
            if (file_exists($photo['DOSYA_YOLU'])) {
                unlink($photo['DOSYA_YOLU']);
            }
            
            // Veritabanından kaydı sil
            $stmt = $db->prepare("DELETE FROM hasta_galerileri WHERE ID = :id");
            $stmt->execute([':id' => $photo_id]);
            
            header('Location: gallery.php?patient=' . $photo['HASTA_ID'] . '&message=deleted');
        }
    }
    // Tarih bazlı toplu silme
    else if (isset($_GET['date']) && isset($_GET['patient'])) {
        $date = $_GET['date'];
        $patient_id = (int)$_GET['patient'];
        
        // O tarihe ait fotoğrafları getir
        $stmt = $db->prepare("SELECT * FROM hasta_galerileri WHERE HASTA_ID = :hasta_id AND DATE(YUKLENME_TARIHI) = :tarih");
        $stmt->execute([':hasta_id' => $patient_id, ':tarih' => $date]);
        $photos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Dosyaları ve kayıtları sil
        foreach ($photos as $photo) {
            if (file_exists($photo['DOSYA_YOLU'])) {
                unlink($photo['DOSYA_YOLU']);
            }
        }
        
        $stmt = $db->prepare("DELETE FROM hasta_galerileri WHERE HASTA_ID = :hasta_id AND DATE(YUKLENME_TARIHI) = :tarih");
        $stmt->execute([':hasta_id' => $patient_id, ':tarih' => $date]);
        
        header('Location: gallery.php?patient=' . $patient_id . '&message=deleted');
    }
    // Seçili fotoğrafları silme
    else if (isset($_GET['ids'])) {
        $ids = array_map('intval', explode(',', $_GET['ids']));
        
        // Fotoğrafları getir
        $stmt = $db->prepare("SELECT * FROM hasta_galerileri WHERE ID IN (" . implode(',', $ids) . ")");
        $stmt->execute();
        $photos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Dosyaları ve kayıtları sil
        foreach ($photos as $photo) {
            if (file_exists($photo['DOSYA_YOLU'])) {
                unlink($photo['DOSYA_YOLU']);
            }
        }
        
        $stmt = $db->prepare("DELETE FROM hasta_galerileri WHERE ID IN (" . implode(',', $ids) . ")");
        $stmt->execute();
        
        header('Location: gallery.php?patient=' . $photos[0]['HASTA_ID'] . '&message=deleted');
    }
    else {
        header('Location: patients.php');
    }
} catch (Exception $e) {
    error_log($e->getMessage());
    header('Location: gallery.php?patient=' . ($photo['HASTA_ID'] ?? $_GET['patient']) . '&error=delete_failed');
}
exit; 