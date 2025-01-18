<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';

$success_message = '';
$error_messages = [];
$patient = null;

// Hasta ID kontrolü
$patientId = isset($_GET['patient']) ? (int)$_GET['patient'] : 0;

if (!$patientId) {
    header('Location: patients.php');
    exit;
}

$database = new Database();
$db = $database->connect();

// Hasta bilgilerini getir
try {
    $stmt = $db->prepare("SELECT * FROM hastalar WHERE ID = :id");
    $stmt->execute([':id' => $patientId]);
    $patient = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$patient) {
        header('Location: patients.php');
        exit;
    }
} catch (PDOException $e) {
    error_log($e->getMessage());
    header('Location: patients.php');
    exit;
}

// Form gönderildiğinde
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $errors = validateUpdateForm($_POST);
        
        if (empty($errors)) {
            if (updatePatient($db, $patientId, $_POST)) {
                $success_message = "Hasta bilgileri başarıyla güncellendi!";
                // Güncel bilgileri yeniden yükle
                $stmt = $db->prepare("SELECT * FROM hastalar WHERE ID = :id");
                $stmt->execute([':id' => $patientId]);
                $patient = $stmt->fetch(PDO::FETCH_ASSOC);
            }
        } else {
            $error_messages = $errors;
        }
    } catch (Exception $e) {
        $error_messages[] = $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hasta Düzenle</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div id="alertContainer"></div>
    
    <?php if ($success_message || !empty($error_messages)): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            <?php if ($success_message): ?>
                showAlert('<?php echo $success_message; ?>', 'success');
            <?php endif; ?>
            
            <?php if (!empty($error_messages)): ?>
                showAlert('<?php echo implode("<br>", $error_messages); ?>', 'danger');
            <?php endif; ?>
        });
    </script>
    <?php endif; ?>

    <div class="container-fluid py-4 content-area">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6">
                <div class="form-container">
                    <div class="d-flex justify-content-between align-items-center mb-4">

                        <a href="patients.php" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left"></i> Geri
                        </a> 
                    </div>
                    
                    <form method="POST" action="" class="needs-validation" novalidate>
                        <!-- Kişisel Bilgiler -->
                        <div class="form-section">
                            <h3 class="form-section-title">Kişisel Bilgiler</h3>
                            
                            <div class="mb-3">
                                <label for="fullName" class="form-label">Ad Soyad</label>
                                <input type="text" class="form-control" id="fullName" name="fullName" 
                                       value="<?php echo htmlspecialchars($patient['AD_SOYAD']); ?>" required>
                            </div>

                            <div class="mb-3">
                                <label for="birthDate" class="form-label">Doğum Tarihi</label>
                                <input type="date" class="form-control" id="birthDate" name="birthDate"
                                       value="<?php echo htmlspecialchars($patient['DOGUM_TARIHI']); ?>" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label d-block">Cinsiyet</label>
                                <div class="d-flex gap-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="gender" id="female" 
                                               value="kadin" <?php echo $patient['CINSIYET'] == 'kadin' ? 'checked' : ''; ?> required>
                                        <label class="form-check-label" for="female">Kadın</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="gender" id="male" 
                                               value="erkek" <?php echo $patient['CINSIYET'] == 'erkek' ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="male">Erkek</label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Kimlik Bilgileri -->
                        <div class="form-section">
                            <h3 class="form-section-title">Kimlik Bilgileri</h3>
                            
                            <div class="mb-3">
                                <div class="d-flex gap-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="idType" id="tcType" 
                                               value="tc" <?php echo $patient['KIMLIK_TURU'] == 'tc' ? 'checked' : ''; ?> required>
                                        <label class="form-check-label" for="tcType">TC Kimlik</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="idType" id="passportType" 
                                               value="passport" <?php echo $patient['KIMLIK_TURU'] == 'passport' ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="passportType">Pasaport</label>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="idNumber" class="form-label" id="idNumberLabel">Kimlik Numarası</label>
                                <input type="text" class="form-control" id="idNumber" name="idNumber"
                                       value="<?php echo htmlspecialchars($patient['KIMLIK_NO']); ?>" required>
                            </div>
                        </div>

                        <!-- İletişim Bilgileri -->
                        <div class="form-section">
                            <h3 class="form-section-title">İletişim Bilgileri</h3>
                            
                            <div class="mb-3">
                                <label for="phone" class="form-label">Telefon Numarası</label>
                                <input type="tel" class="form-control" id="phone" name="phone"
                                       value="<?php echo htmlspecialchars($patient['TELEFON']); ?>" required>
                            </div>

                            <div class="mb-3">
                                <label for="email" class="form-label">E-posta Adresi</label>
                                <input type="email" class="form-control" id="email" name="email"
                                       value="<?php echo htmlspecialchars($patient['EMAIL']); ?>">
                            </div>
                        </div>

                        <!-- Diğer Bilgiler -->
                        <div class="form-section">
                            <h3 class="form-section-title">Diğer Bilgiler</h3>
                            
                            <div class="mb-3">
                                <label for="reference" class="form-label">Referans</label>
                                <select class="form-select" id="reference" name="reference">
                                    <option value="">Referans seçiniz</option>
                                    <option value="1" <?php echo $patient['REFERANS'] == '1' ? 'selected' : ''; ?>>Referans 1</option>
                                    <option value="2" <?php echo $patient['REFERANS'] == '2' ? 'selected' : ''; ?>>Referans 2</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="emptyField" class="form-label">Ek Bilgi</label>
                                <textarea class="form-control" id="emptyField" name="emptyField" rows="3"><?php echo htmlspecialchars($patient['ACIKLAMA']); ?></textarea>
                            </div>
                        </div>

                        <!-- Submit Button -->
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-success flex-grow-1">
                                <i class="fas fa-save"></i> Kaydet
                            </button>
                            <button type="button" class="btn btn-danger" onclick="confirmDelete()">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <?php include 'includes/nav.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/main.js"></script>
    <script>
        function confirmDelete(patientId) {
            var deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
            deleteModal.show();
        }

        document.addEventListener('DOMContentLoaded', function() {
            <?php if (isset($_GET['show_delete_modal']) && $_GET['show_delete_modal'] === 'true'): ?>
                var deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
                deleteModal.show();
            <?php endif; ?>
        });
    </script>

    <!-- Silme Onay Modalı -->
    <div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteModalLabel">Hasta Kaydını Sil</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-0">Bu hasta kaydını silmek istediğinizden emin misiniz? Bu işlem geri alınamaz.</p>
                    <p class="text-danger mb-0 mt-2">
                        <i class="fas fa-exclamation-triangle"></i> 
                        Bu işlem hastaya ait tüm randevuları da silecektir.
                    </p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                    <a href="delete_patient.php?id=<?php echo $patientId; ?>" class="btn btn-danger">
                        <i class="fas fa-trash"></i> Evet, Sil
                    </a>
                </div>
            </div>
        </div>
    </div>
</body>
</html> 