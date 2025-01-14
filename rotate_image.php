<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';

header('Content-Type: application/json');

try {
    // JSON verisini al
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['id']) || !isset($data['direction'])) {
        throw new Exception('Geçersiz parametreler');
    }

    $photoId = (int)$data['id'];
    $direction = $data['direction'];

    // Veritabanından fotoğraf bilgilerini al
    $database = new Database();
    $db = $database->connect();
    
    $stmt = $db->prepare("SELECT * FROM hasta_galerileri WHERE ID = :id");
    $stmt->execute([':id' => $photoId]);
    $photo = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$photo) {
        throw new Exception('Fotoğraf bulunamadı');
    }

    $imagePath = $photo['DOSYA_YOLU'];
    $extension = strtolower(pathinfo($imagePath, PATHINFO_EXTENSION));

    // Görüntüyü yükle
    switch($extension) {
        case 'jpg':
        case 'jpeg':
            $source = imagecreatefromjpeg($imagePath);
            break;
        case 'png':
            $source = imagecreatefrompng($imagePath);
            break;
        case 'gif':
            $source = imagecreatefromgif($imagePath);
            break;
        default:
            throw new Exception('Desteklenmeyen dosya formatı');
    }

    // Yönüne göre döndür
    if ($direction === 'left') {
        $rotated = imagerotate($source, 90, 0);
    } else {
        $rotated = imagerotate($source, -90, 0);
    }

    // Orijinal dosyayı sil
    imagedestroy($source);

    // Yeni görüntüyü kaydet
    switch($extension) {
        case 'jpg':
        case 'jpeg':
            imagejpeg($rotated, $imagePath, 90);
            break;
        case 'png':
            imagepng($rotated, $imagePath, 9);
            break;
        case 'gif':
            imagegif($rotated, $imagePath);
            break;
    }

    imagedestroy($rotated);

    echo json_encode(['success' => true]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
} 