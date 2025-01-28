<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

// Rol ekleme yetkisi kontrolü
checkPagePermission('rol_ekle');

$database = new Database();
$db = $database->connect();

$message = '';
$error = '';

// Form gönderildiğinde
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $rol_adi = $_POST['rol_adi'] ?? '';
    $aciklama = $_POST['aciklama'] ?? '';

    if (empty($rol_adi)) {
        $error = 'Rol adı gereklidir.';
    } else {
        try {
            // Aynı isimde rol var mı kontrol et
            $stmt = $db->prepare("SELECT COUNT(*) FROM roller WHERE ROL_ADI = ?");
            $stmt->execute([$rol_adi]);
            if ($stmt->fetchColumn() > 0) {
                $error = 'Bu rol adı zaten kullanılıyor.';
            } else {
                $stmt = $db->prepare("INSERT INTO roller (ROL_ADI, ACIKLAMA) VALUES (?, ?)");
                $stmt->execute([$rol_adi, $aciklama]);
                
                $message = 'Rol başarıyla eklendi.';
                
                // Formu temizle
                $rol_adi = '';
                $aciklama = '';
            }
        } catch (Exception $e) {
            $error = 'Bir hata oluştu: ' . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Yeni Rol Ekle</title>
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

        .role-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            margin-bottom: 20px;
        }

        @media (max-width: 767px) {
            .content-area {
                padding: 15px 15px 80px 15px !important;
            }

            .page-header {
                padding: 15px;
                margin-bottom: 15px;
            }

            .role-card {
                margin: 0 -5px;
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
                <h1 class="page-title">Yeni Rol Ekle</h1>
            </div>
        </div>

        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="role-card">
                    <div class="card-body p-4">
                        <?php if ($message): ?>
                            <div class="alert alert-success"><?php echo $message; ?></div>
                        <?php endif; ?>

                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php endif; ?>

                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label">Rol Adı</label>
                                <input type="text" class="form-control" name="rol_adi" 
                                       value="<?php echo htmlspecialchars($rol_adi ?? ''); ?>" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Açıklama</label>
                                <textarea class="form-control" name="aciklama" rows="3"
                                          ><?php echo htmlspecialchars($aciklama ?? ''); ?></textarea>
                            </div>

                            <div class="d-flex justify-content-between">
                                <a href="roles.php" class="btn btn-secondary">
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