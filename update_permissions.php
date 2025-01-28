<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

// Sadece admin erişebilsin
if (!isAdmin()) {
    header('Location: unauthorized.php');
    exit;
}

$database = new Database();
$db = $database->connect();

// Anasayfa erişim kontrolü
checkPagePermission('anasayfa_erisim');

try {
    // İlk önce rol_yetkileri tablosunu temizle
    $db->exec("DELETE FROM rol_yetkileri");
    
    // Sonra yetkiler tablosunu temizle
    $db->exec("DELETE FROM yetkiler");
    
    // Yeni yetkileri ekle
    $yetkiler = [
        // Ana Sayfa Yetkileri
        ['anasayfa_erisim', 'Anasayfa Erişimi', 'Ana Sayfa'],
        
        // Hasta Yönetimi
        ['hasta_listesi_erisim', 'Hasta Listesi Erişimi', 'Hasta Yönetimi'],
        ['hasta_ekle', 'Hasta Ekleme', 'Hasta Yönetimi'],
        ['hasta_duzenle', 'Hasta Düzenleme', 'Hasta Yönetimi'],
        ['hasta_sil', 'Hasta Silme', 'Hasta Yönetimi'],
        ['hasta_fotograf_yukle', 'Hasta Fotoğrafı Yükleme', 'Hasta Yönetimi'],
        ['hasta_fotograf_sil', 'Hasta Fotoğrafı Silme', 'Hasta Yönetimi'],
        
        // Randevu Yönetimi
        ['randevu_listesi_erisim', 'Randevu Listesi Erişimi', 'Randevu Yönetimi'],
        ['randevu_ekle', 'Randevu Ekleme', 'Randevu Yönetimi'],
        ['randevu_duzenle', 'Randevu Düzenleme', 'Randevu Yönetimi'],
        ['randevu_sil', 'Randevu Silme', 'Randevu Yönetimi'],
        
        // Ödeme Yönetimi
        ['odeme_listesi_erisim', 'Ödeme Listesi Erişimi', 'Ödeme Yönetimi'],
        ['odeme_ekle', 'Ödeme Ekleme', 'Ödeme Yönetimi'],
        ['odeme_duzenle', 'Ödeme Düzenleme', 'Ödeme Yönetimi'],
        ['odeme_sil', 'Ödeme Silme', 'Ödeme Yönetimi'],
        
        // Kullanıcı Yönetimi
        ['kullanicilar_erisim', 'Kullanıcı Listesi Erişimi', 'Kullanıcı Yönetimi'],
        ['kullanici_ekle', 'Kullanıcı Ekleme', 'Kullanıcı Yönetimi'],
        ['kullanici_duzenle', 'Kullanıcı Düzenleme', 'Kullanıcı Yönetimi'],
        ['kullanici_sil', 'Kullanıcı Silme', 'Kullanıcı Yönetimi'],
        
        // Rol Yönetimi
        ['roller_erisim', 'Rol Listesi Erişimi', 'Rol Yönetimi'],
        ['rol_ekle', 'Rol Ekleme', 'Rol Yönetimi'],
        ['rol_duzenle', 'Rol Düzenleme', 'Rol Yönetimi'],
        ['rol_sil', 'Rol Silme', 'Rol Yönetimi'],
        ['rol_yetki_duzenle', 'Rol Yetkileri Düzenleme', 'Rol Yönetimi'],
        
        // Ayarlar
        ['ayarlar_erisim', 'Ayarlar Erişimi', 'Ayarlar'],
        ['ayarlar_duzenle', 'Ayarlar Düzenleme', 'Ayarlar'],
        ['randevu_sablon_erisim', 'Randevu Şablonları Erişimi', 'Ayarlar'],
        ['randevu_sablon_duzenle', 'Randevu Şablonları Düzenleme', 'Ayarlar'],
        
        // Profil Yönetimi
        ['profil_erisim', 'Profil Erişimi', 'Profil'],
        ['profil_duzenle', 'Profil Düzenleme', 'Profil'],
        ['sifre_degistir', 'Şifre Değiştirme', 'Profil'],
        
        // Cari İşlemler
        ['cari_erisim', 'Cari İşlemler Erişimi', 'Cari İşlemler'],
        ['cari_ekle', 'Cari İşlem Ekleme', 'Cari İşlemler'],
        ['cari_duzenle', 'Cari İşlem Düzenleme', 'Cari İşlemler'],
        ['cari_sil', 'Cari İşlem Silme', 'Cari İşlemler'],
        
        // Raporlar
        ['rapor_erisim', 'Raporlar Erişimi', 'Raporlar'],
        ['gunluk_rapor', 'Günlük Rapor Görüntüleme', 'Raporlar'],
        ['aylik_rapor', 'Aylık Rapor Görüntüleme', 'Raporlar'],
        ['yillik_rapor', 'Yıllık Rapor Görüntüleme', 'Raporlar'],
        ['ozel_rapor', 'Özel Rapor Oluşturma', 'Raporlar'],
        
        // Muhasebe yönetimi yetkileri
        ['muhasebe_erisim', 'Muhasebe Erişimi', 'Muhasebe'],
        ['muhasebe_rapor_goruntule', 'Muhasebe Raporları Görüntüleme', 'Muhasebe'],
        ['muhasebe_gelir_ekle', 'Gelir Ekleme', 'Muhasebe'],
        ['muhasebe_gider_ekle', 'Gider Ekleme', 'Muhasebe'],
        ['muhasebe_islem_duzenle', 'İşlem Düzenleme', 'Muhasebe'],
        ['muhasebe_islem_sil', 'İşlem Silme', 'Muhasebe'],
        ['muhasebe_kategori_yonetimi', 'Kategori Yönetimi', 'Muhasebe'],
    ];
    
    $stmt = $db->prepare("INSERT INTO yetkiler (yetki_key, yetki_adi, kategori) VALUES (?, ?, ?)");
    
    foreach ($yetkiler as $yetki) {
        $stmt->execute($yetki);
    }
    
    // Admin rolüne tüm yetkileri otomatik olarak ekle
    $admin_rol_id = 1; // Admin rolünün ID'si
    
    // Yeni eklenen yetkilerin ID'lerini al
    $stmt = $db->query("SELECT id FROM yetkiler");
    $yetki_ids = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    // Admin rolüne yetkileri ekle
    $stmt = $db->prepare("INSERT INTO rol_yetkileri (rol_id, yetki_id) VALUES (?, ?)");
    foreach ($yetki_ids as $yetki_id) {
        $stmt->execute([$admin_rol_id, $yetki_id]);
    }
    
    echo "Tüm yetkiler başarıyla güncellendi ve admin rolüne atandı.";
} catch (PDOException $e) {
    echo "Hata oluştu: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Yetki Güncelleme</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-body text-center">
                        <h5 class="card-title mb-3">Yetki Güncelleme İşlemi</h5>
                        <a href="roles.php" class="btn btn-primary">Roller Sayfasına Dön</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html> 