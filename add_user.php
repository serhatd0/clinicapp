<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

// Kullanıcı ekleme yetkisi kontrolü
checkPagePermission('kullanici_ekle');

// Sadece admin yetkisi olanlar kullanıcı ekleyebilir
if (!isAdmin()) {
    header('Location: index.php');
    exit;
}

$database = new Database();
$db = $database->connect();

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $ad_soyad = $_POST['ad_soyad'] ?? '';
    $email = $_POST['email'] ?? '';
    $sifre = $_POST['sifre'] ?? '';
    $rol_id = $_POST['rol_id'] ?? '';

    if (empty($ad_soyad) || empty($email) || empty($sifre) || empty($rol_id)) {
        $error = 'Tüm alanları doldurunuz.';
    } else {
        try {
            // Email kontrolü
            $stmt = $db->prepare("SELECT COUNT(*) FROM kullanicilar WHERE EMAIL = :email");
            $stmt->execute([':email' => $email]);
            if ($stmt->fetchColumn() > 0) {
                $error = 'Bu email adresi zaten kullanılıyor.';
            } else {
                // Şifreyi hashle
                $hashed_password = password_hash($sifre, PASSWORD_DEFAULT);
                
                $stmt = $db->prepare("INSERT INTO kullanicilar (AD_SOYAD, EMAIL, SIFRE, ROL_ID, DURUM) 
                                    VALUES (:ad_soyad, :email, :sifre, :rol_id, 'aktif')");
                                    
                $stmt->execute([
                    ':ad_soyad' => $ad_soyad,
                    ':email' => $email,
                    ':sifre' => $hashed_password,
                    ':rol_id' => $rol_id
                ]);
                
                $message = 'Kullanıcı başarıyla eklendi.';
            }
        } catch (Exception $e) {
            $error = 'Bir hata oluştu: ' . $e->getMessage();
        }
    }
}

// Rolleri getir
$stmt = $db->query("SELECT * FROM roller");
$roller = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kullanıcı Ekle</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container py-4 content-area">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h4 class="mb-0">Yeni Kullanıcı Ekle</h4>
                    </div>
                    <div class="card-body">
                        <?php if ($message): ?>
                            <div class="alert alert-success"><?php echo $message; ?></div>
                        <?php endif; ?>
                        
                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php endif; ?>

                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label">Ad Soyad</label>
                                <input type="text" class="form-control" name="ad_soyad" required>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" class="form-control" name="email" required>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Şifre</label>
                                <input type="password" class="form-control" name="sifre" required>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Rol</label>
                                <select class="form-select" name="rol_id" required>
                                    <option value="">Rol Seçin</option>
                                    <?php foreach ($roller as $rol): ?>
                                        <option value="<?php echo $rol['ID']; ?>">
                                            <?php echo strtoupper(htmlspecialchars($rol['ROL_ADI'])); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <button type="submit" class="btn btn-success">
                                <i class="fas fa-user-plus me-2"></i>Kullanıcı Ekle
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include 'includes/nav.php'; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 