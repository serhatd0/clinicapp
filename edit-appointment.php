<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';

$database = new Database();
$db = $database->connect();

$success_message = '';
$error_messages = [];
$appointment_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$appointment_id) {
    header('Location: appointments.php');
    exit;
}

// Randevu bilgilerini getir
$stmt = $db->prepare("SELECT r.*, h.AD_SOYAD 
                      FROM randevular r 
                      LEFT JOIN hastalar h ON r.HASTA_ID = h.ID 
                      WHERE r.ID = :id");
$stmt->execute([':id' => $appointment_id]);
$appointment = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$appointment) {
    header('Location: appointments.php');
    exit;
}

// POST işlemleri...
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        // Mevcut POST işlemleri...
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
    <title>Randevu Düzenle</title>
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

    <div class="container py-4 content-area">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6">
                <div class="form-container">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h2 class="form-title mb-0">Randevu Düzenle</h2>
                        <a href="appointments.php" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left"></i> Geri
                        </a>
                    </div>

                    <form method="POST" action="" class="needs-validation" novalidate>
                        <div class="form-section">
                            <div class="mb-3">
                                <label class="form-label">Hasta</label>
                                <input type="text" class="form-control" value="<?php echo htmlspecialchars($appointment['AD_SOYAD']); ?>" readonly>
                            </div>

                            <div class="mb-3">
                                <label for="appointment_date" class="form-label">Randevu Tarihi</label>
                                <input type="date" class="form-control" id="appointment_date" 
                                       name="appointment_date" required 
                                       value="<?php echo date('Y-m-d', strtotime($appointment['TARIH'])); ?>">
                            </div>

                            <div class="mb-3">
                                <label for="appointment_time" class="form-label">Randevu Saati</label>
                                <select class="form-select" id="appointment_time" name="appointment_time" required>
                                    <?php 
                                    $currentTime = date('H:i', strtotime($appointment['TARIH']));
                                    foreach (getAvailableTimeSlots() as $slot): 
                                    ?>
                                        <option value="<?php echo $slot; ?>" 
                                                <?php echo $slot === $currentTime ? 'selected' : ''; ?>>
                                            <?php echo $slot; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="repeat_type" class="form-label">Tekrar</label>
                                <select class="form-select" id="repeat_type" name="repeat_type">
                                    <option value="yok" <?php echo $appointment['TEKRAR'] == 'yok' ? 'selected' : ''; ?>>Tekrar Yok</option>
                                    <option value="gunluk" <?php echo $appointment['TEKRAR'] == 'gunluk' ? 'selected' : ''; ?>>Günlük</option>
                                    <option value="haftalik" <?php echo $appointment['TEKRAR'] == 'haftalik' ? 'selected' : ''; ?>>Haftalık</option>
                                    <option value="aylik" <?php echo $appointment['TEKRAR'] == 'aylik' ? 'selected' : ''; ?>>Aylık</option>
                                </select>
                            </div>

                            <div id="repeatOptions" style="display: <?php echo $appointment['TEKRAR'] != 'yok' ? 'block' : 'none'; ?>">
                                <div class="mb-3">
                                    <label for="repeat_count" class="form-label">Tekrar Sayısı</label>
                                    <input type="number" class="form-control" id="repeat_count" 
                                           name="repeat_count" min="1" max="52"
                                           value="<?php echo $appointment['TEKRAR_SAYISI']; ?>">
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="notes" class="form-label">Notlar</label>
                                <textarea class="form-control" id="notes" name="notes" rows="3"><?php echo htmlspecialchars($appointment['NOTLAR']); ?></textarea>
                            </div>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-success flex-grow-1">
                                <i class="fas fa-save"></i> Güncelle
                            </button>
                            <button type="button" class="btn btn-danger" onclick="confirmDelete(<?php echo $appointment_id; ?>)">
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
        document.getElementById('repeat_type').addEventListener('change', function() {
            const repeatOptions = document.getElementById('repeatOptions');
            repeatOptions.style.display = this.value === 'yok' ? 'none' : 'block';
        });

        function confirmDelete(id) {
            if (confirm('Bu randevuyu silmek istediğinizden emin misiniz?')) {
                window.location.href = 'delete_appointment.php?id=' + id;
            }
        }
    </script>
</body>
</html> 