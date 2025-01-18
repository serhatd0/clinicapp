<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

// Sadece admin yetkisi olanlar düzenleyebilir
if (!isAdmin()) {
    header('Location: index.php');
    exit;
}

$database = new Database();
$db = $database->connect();

$user_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$message = '';
$error = '';

// Kullanıcı bilgilerini getir
$stmt = $db->prepare("SELECT * FROM kullanicilar WHERE ID = :id");
$stmt->execute([':id' => $user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    header('Location: settings.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $ad_soyad = $_POST['ad_soyad'] ?? '';
    $email = $_POST['email'] ?? '';
    $rol_id = $_POST['rol_id'] ?? '';
    $durum = $_POST['durum'] ?? '';
    $yeni_sifre = $_POST['yeni_sifre'] ?? '';

    if (empty($ad_soyad) || empty($email) || empty($rol_id) || empty($durum)) {
        $error = 'Gerekli alanları doldurunuz.';
    } else {
        try {
            // Email kontrolü (kendi emaili hariç)
            $stmt = $db->prepare("SELECT COUNT(*) FROM kullanicilar WHERE EMAIL = :email AND ID != :id");
            $stmt->execute([':email' => $email, ':id' => $user_id]);
            if ($stmt->fetchColumn() > 0) {
                $error = 'Bu email adresi başka bir kullanıcı tarafından kullanılıyor.';
            } else {
                $sql = "UPDATE kullanicilar SET 
                        AD_SOYAD = :ad_soyad, 
                        EMAIL = :email, 
                        ROL_ID = :rol_id, 
                        DURUM = :durum";
                $params = [
                    ':ad_soyad' => $ad_soyad,
                    ':email' => $email,
                    ':rol_id' => $rol_id,
                    ':durum' => $durum,
                    ':id' => $user_id
                ];
                
                // Eğer yeni şifre girilmişse
                if (!empty($yeni_sifre)) {
                    $sql .= ", SIFRE = :sifre";
                    $params[':sifre'] = password_hash($yeni_sifre, PASSWORD_DEFAULT);
                }
                
                $sql .= " WHERE ID = :id";
                
                $stmt = $db->prepare($sql);
                $stmt->execute($params);
                
                $message = 'Kullanıcı bilgileri güncellendi.';
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
    <title>Kullanıcı Düzenle</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container py-4 content-area">
        <div class="d-flex justify-content-between align-items-center mb-4">
          
        </div>
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h4 class="mb-0">Kullanıcı Düzenle</h4>
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
                                <input type="text" class="form-control" name="ad_soyad" 
                                       value="<?php echo htmlspecialchars($user['AD_SOYAD']); ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" class="form-control" name="email" 
                                       value="<?php echo htmlspecialchars($user['EMAIL']); ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Yeni Şifre</label>
                                <input type="password" class="form-control" name="yeni_sifre" 
                                       placeholder="Değiştirmek istemiyorsanız boş bırakın">
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Rol</label>
                                <select class="form-select" name="rol_id" required>
                                    <?php foreach ($roller as $rol): ?>
                                        <option value="<?php echo $rol['ID']; ?>" 
                                                <?php echo $rol['ID'] == $user['ROL_ID'] ? 'selected' : ''; ?>>
                                            <?php echo strtoupper(htmlspecialchars($rol['ROL_ADI'])); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Durum</label>
                                <select class="form-select" name="durum" required>
                                    <option value="aktif" <?php echo $user['DURUM'] == 'aktif' ? 'selected' : ''; ?>>Aktif</option>
                                    <option value="pasif" <?php echo $user['DURUM'] == 'pasif' ? 'selected' : ''; ?>>Pasif</option>
                                </select>
                            </div>
                            
                            <div class="d-flex justify-content-between">
                                <a href="settings.php" class="btn btn-secondary">
                                    <i class="fas fa-arrow-left me-2"></i>Geri
                                </a>
                                <button type="submit" class="btn btn-success">
                                    <i class="fas fa-save me-2"></i>Kaydet
                                </button>
                            </div>
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