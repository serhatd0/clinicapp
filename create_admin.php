<?php
require_once 'includes/db.php';

try {
    $database = new Database();
    $db = $database->connect();
    
    // Önce roller tablosunu kontrol edelim
    $stmt = $db->prepare("SELECT ID FROM roller WHERE ROL_ADI = 'admin'");
    $stmt->execute();
    $admin_role = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Admin rolü yoksa oluştur
    if (!$admin_role) {
        $stmt = $db->prepare("INSERT INTO roller (ROL_ADI) VALUES ('admin')");
        $stmt->execute();
        $admin_role_id = $db->lastInsertId();
    } else {
        $admin_role_id = $admin_role['ID'];
    }
    
    // Admin kullanıcısını oluştur
    $admin_email = 'admin@gmail.com';  // Kullanıcı adı: admin
    $admin_password = '123456';  // Şifre: 123456
    $hashed_password = password_hash($admin_password, PASSWORD_DEFAULT);
    
    // Önce bu email ile kullanıcı var mı kontrol et
    $stmt = $db->prepare("SELECT ID FROM kullanicilar WHERE EMAIL = :email");
    $stmt->execute([':email' => $admin_email]);
    $existing_user = $stmt->fetch();
    
    if (!$existing_user) {
        $stmt = $db->prepare("INSERT INTO kullanicilar (AD_SOYAD, EMAIL, SIFRE, ROL_ID, DURUM) 
                             VALUES (:ad_soyad, :email, :sifre, :rol_id, 'aktif')");
                             
        $stmt->execute([
            ':ad_soyad' => 'Admin Kullanıcı',
            ':email' => $admin_email,
            ':sifre' => $hashed_password,
            ':rol_id' => $admin_role_id
        ]);
        
        echo "Admin kullanıcısı başarıyla oluşturuldu.<br>";
        echo "Email: admin<br>";
        echo "Şifre: 123456";
    } else {
        echo "Bu email adresi zaten kullanımda.";
    }
    
} catch (Exception $e) {
    echo "Bir hata oluştu: " . $e->getMessage();
} 