<?php
require_once 'includes/db.php';
require_once 'includes/auth.php';

// Sadece admin erişebilsin
if (!isAdmin()) {
    header('Location: index.php');
    exit;
}

$database = new Database();
$db = $database->connect();

try {
    // ACIKLAMA sütunu var mı kontrol et
    $stmt = $db->query("SHOW COLUMNS FROM roller LIKE 'ACIKLAMA'");
    if ($stmt->rowCount() == 0) {
        // ACIKLAMA sütunu yoksa ekle
        $db->exec("ALTER TABLE roller ADD COLUMN ACIKLAMA TEXT DEFAULT NULL");
        echo "ACIKLAMA sütunu eklendi.<br>";
    }

    // CREATED_AT sütunu var mı kontrol et
    $stmt = $db->query("SHOW COLUMNS FROM roller LIKE 'CREATED_AT'");
    if ($stmt->rowCount() == 0) {
        // CREATED_AT sütunu yoksa ekle
        $db->exec("ALTER TABLE roller ADD COLUMN CREATED_AT TIMESTAMP DEFAULT CURRENT_TIMESTAMP");
        echo "CREATED_AT sütunu eklendi.<br>";
    }

    echo "Veritabanı güncellemesi başarıyla tamamlandı.";
} catch (PDOException $e) {
    echo "Hata oluştu: " . $e->getMessage();
}
?> 