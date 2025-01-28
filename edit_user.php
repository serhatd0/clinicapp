<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

// Kullanıcı düzenleme yetkisi kontrolü
checkPagePermission('kullanici_duzenle');

if (!isset($_GET['id'])) {
    header('Location: users.php');
    exit;
}

$userId = (int)$_GET['id'];
$database = new Database();
$db = $database->connect();

// Kullanıcı bilgilerini getir
$stmt = $db->prepare("
    SELECT k.*, r.ROL_ADI 
    FROM kullanicilar k
    LEFT JOIN roller r ON k.ROL_ID = r.ID
    WHERE k.ID = ?
");
$stmt->execute([$userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    header('Location: users.php?error=not_found');
    exit;
}

// Rolleri getir
$stmt = $db->query("SELECT * FROM roller ORDER BY ROL_ADI ASC");
$roller = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Form gönderildiğinde
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $stmt = $db->prepare("
            UPDATE kullanicilar 
            SET AD_SOYAD = ?,
                EMAIL = ?,
                ROL_ID = ?,
                DURUM = ?
            WHERE ID = ?
        ");
        
        $success = $stmt->execute([
            $_POST['ad_soyad'],
            $_POST['email'],
            $_POST['rol_id'],
            $_POST['durum'],
            $userId
        ]);

        // Şifre değiştirilecekse
        if (!empty($_POST['sifre'])) {
            $stmt = $db->prepare("UPDATE kullanicilar SET SIFRE = ? WHERE ID = ?");
            $stmt->execute([password_hash($_POST['sifre'], PASSWORD_DEFAULT), $userId]);
        }

        header('Location: users.php?success=updated');
        exit;
    } catch (PDOException $e) {
        $error = "Güncelleme sırasında bir hata oluştu: " . $e->getMessage();
    }
}
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

    <div class="container py-4">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Kullanıcı Düzenle</h5>
                        <a href="users.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Geri
                        </a>
                    </div>
                    <div class="card-body">
                        <?php if (isset($error)): ?>
                            <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php endif; ?>

                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label">Ad Soyad</label>
                                <input type="text" class="form-control" name="ad_soyad" 
                                       value="<?php echo htmlspecialchars($user['AD_SOYAD']); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">E-posta</label>
                                <input type="email" class="form-control" name="email" 
                                       value="<?php echo htmlspecialchars($user['EMAIL']); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Yeni Şifre (Değiştirmek istemiyorsanız boş bırakın)</label>
                                <input type="password" class="form-control" name="sifre">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Rol</label>
                                <select class="form-select" name="rol_id" required>
                                    <?php foreach ($roller as $rol): ?>
                                        <option value="<?php echo $rol['ID']; ?>" 
                                                <?php echo $rol['ID'] == $user['ROL_ID'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($rol['ROL_ADI']); ?>
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
                            <button type="submit" class="btn btn-primary">Güncelle</button>
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