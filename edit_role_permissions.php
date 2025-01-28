<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

// Yetki kontrolü
checkPagePermission('rol_yetki_duzenle');

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

// Mevcut yetkileri al
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
        
        // Yeni yetkileri al
        $stmt = $db->prepare("SELECT yetki_id FROM rol_yetkileri WHERE rol_id = ?");
        $stmt->execute([$rol_id]);
        $mevcut_yetkiler = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
    } catch (Exception $e) {
        $error = 'Bir hata oluştu: ' . $e->getMessage();
    }
}

// Tüm yetkileri kategorilere göre grupla
$stmt = $db->query("SELECT * FROM yetkiler ORDER BY kategori, yetki_adi");
$yetkiler = $stmt->fetchAll(PDO::FETCH_ASSOC);

$kategoriler = [];
foreach ($yetkiler as $yetki) {
    $kategoriler[$yetki['kategori']][] = $yetki;
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
    <link href="assets/css/style.css" rel="stylesheet">
    <style>
        .content-area {
            padding-top: 30px !important;
            padding-bottom: 80px !important;
        }

        .page-header {
            background: #fff;
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }

        .page-title {
            font-size: 1.2rem;
            color: #212529;
            font-weight: 600;
            margin: 0;
        }

        .permission-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            overflow: hidden;
        }

        .permission-header {
            padding: 15px 20px;
            border-bottom: 1px solid #eee;
            background: #f8f9fa;
            border-radius: 10px 10px 0 0;
        }

        .permission-body {
            padding: 20px;
        }

        .permission-item {
            margin-bottom: 10px;
            padding: 10px;
            border-radius: 5px;
            transition: background-color 0.2s;
            border: 1px solid #eee;
        }

        .permission-item:last-child {
            margin-bottom: 0;
        }

        .permission-item:hover {
            background-color: #f8f9fa;
        }

        .form-switch {
            padding-left: 2.5em;
        }

        .form-switch .form-check-input {
            width: 3em;
            height: 1.5em;
            margin-left: -2.5em;
            cursor: pointer;
        }

        .form-switch .form-check-input:checked {
            background-color: #198754;
            border-color: #198754;
        }

        .form-switch .form-check-label {
            cursor: pointer;
        }

        .btn-group {
            gap: 10px;
        }

        @media (max-width: 767px) {
            .content-area {
                padding: 15px 15px 80px 15px !important;
            }

            .page-header {
                padding: 15px;
                margin-bottom: 15px;
            }

            .permission-card {
                margin: 0 -5px;
            }

            .permission-header {
                padding: 12px 15px;
            }

            .permission-body {
                padding: 15px;
            }

            .permission-item {
                padding: 8px;
            }
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="container content-area">
        <div class="page-header d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center gap-3">
                <a href="roles.php" class="btn btn-outline-secondary btn-sm">
                    <i class="fas fa-arrow-left"></i>
                </a>
                <h1 class="page-title">
                    <i class="fas fa-key me-2"></i>
                    <?php echo htmlspecialchars($rol['ROL_ADI']); ?> Yetkileri
                </h1>
            </div>
        </div>

        <?php if (isset($message)): ?>
            <div class="alert alert-success"><?php echo $message; ?></div>
        <?php endif; ?>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <form method="POST">
            <?php foreach ($kategoriler as $kategori => $kategori_yetkileri): ?>
                <div class="permission-card">
                    <div class="permission-header">
                        <h5 class="mb-0"><?php echo htmlspecialchars($kategori); ?></h5>
                    </div>
                    <div class="permission-body">
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
                </div>
            <?php endforeach; ?>

            <div class="d-flex justify-content-between mt-4">
                <a href="roles.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left me-2"></i>Geri
                </a>
                <div class="btn-group">
                    <button type="button" class="btn btn-danger" id="btnSil">
                        <i class="fas fa-trash me-2"></i>Tümünü Sil
                    </button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-save me-2"></i>Kaydet
                    </button>
                </div>
            </div>
        </form>
    </div>

    <?php include 'includes/nav.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Tümünü sil butonu
        document.getElementById('btnSil').addEventListener('click', function() {
            if (confirm('Tüm yetkileri kaldırmak istediğinizden emin misiniz?')) {
                const checkboxes = document.querySelectorAll('input[type="checkbox"]');
                checkboxes.forEach(checkbox => checkbox.checked = false);
            }
        });
    </script>
</body>
</html> 