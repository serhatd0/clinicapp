<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

if (!isAdmin()) {
    header('Location: index.php');
    exit;
}

$database = new Database();
$db = $database->connect();

$rol_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Rol bilgilerini al
$stmt = $db->prepare("SELECT * FROM roller WHERE ID = ?");
$stmt->execute([$rol_id]);
$rol = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$rol) {
    header('Location: roles.php');
    exit;
}

// Yetkileri kategorilere göre grupla
$stmt = $db->query("SELECT * FROM yetkiler ORDER BY kategori, yetki_adi");
$yetkiler = $stmt->fetchAll(PDO::FETCH_ASSOC);
$kategoriler = [];
foreach ($yetkiler as $yetki) {
    $kategoriler[$yetki['kategori']][] = $yetki;
}

// Rolün mevcut yetkilerini al
$stmt = $db->prepare("SELECT yetki_id FROM rol_yetkileri WHERE rol_id = ?");
$stmt->execute([$rol_id]);
$mevcut_yetkiler = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Form gönderildiğinde
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Önce tüm yetkileri kaldır
        $stmt = $db->prepare("DELETE FROM rol_yetkileri WHERE rol_id = ?");
        $stmt->execute([$rol_id]);
        
        // Seçili yetkileri ekle
        if (isset($_POST['yetkiler']) && is_array($_POST['yetkiler'])) {
            $stmt = $db->prepare("INSERT INTO rol_yetkileri (rol_id, yetki_id) VALUES (?, ?)");
            foreach ($_POST['yetkiler'] as $yetki_id) {
                $stmt->execute([$rol_id, $yetki_id]);
            }
        }
        
        $message = 'Yetkiler başarıyla güncellendi.';
    } catch (Exception $e) {
        $error = 'Bir hata oluştu: ' . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rol Yetkileri - <?php echo htmlspecialchars($rol['ROL_ADI']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        .permission-group {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .permission-item {
            padding: 8px;
            border-bottom: 1px solid #eee;
        }
        .permission-item:last-child {
            border-bottom: none;
        }
        .form-switch {
            padding-left: 2.5em;
        }
    </style>
</head>
<body>
    <div class="container py-4">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Rol Yetkileri: <?php echo htmlspecialchars($rol['ROL_ADI']); ?></h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <?php foreach ($kategoriler as $kategori => $kategori_yetkileri): ?>
                                <div class="permission-group">
                                    <h6 class="mb-3"><?php echo htmlspecialchars($kategori); ?></h6>
                                    <?php foreach ($kategori_yetkileri as $yetki): ?>
                                        <div class="permission-item">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" 
                                                       name="yetkiler[]" 
                                                       value="<?php echo $yetki['id']; ?>"
                                                       <?php echo in_array($yetki['id'], $mevcut_yetkiler) ? 'checked' : ''; ?>>
                                                <label class="form-check-label">
                                                    <?php echo htmlspecialchars($yetki['yetki_adi']); ?>
                                                </label>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endforeach; ?>
                            
                            <div class="d-flex justify-content-between mt-4">
                                <a href="roles.php" class="btn btn-secondary">
                                    <i class="fas fa-arrow-left me-2"></i>Geri
                                </a>
                                <div>
                                    <button type="button" class="btn btn-danger me-2" id="btnSil">Sil</button>
                                    <button type="submit" class="btn btn-success">
                                        <i class="fas fa-save me-2"></i>Kaydet
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 