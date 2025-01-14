<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';

$database = new Database();
$db = $database->connect();

$success_message = '';
$error_messages = [];

// Hasta listesini getir
$stmt = $db->query("SELECT ID, AD_SOYAD FROM hastalar ORDER BY AD_SOYAD ASC");
$patients = $stmt->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $hastaId = $_POST['patient_id'];
        $tarih = $_POST['appointment_date'] . ' ' . $_POST['appointment_time'];
        $tekrar = $_POST['repeat_type'] ?? 'yok';
        $tekrarSayisi = !empty($_POST['repeat_count']) ? $_POST['repeat_count'] : null;
        $tekrarBitis = !empty($_POST['repeat_until']) ? $_POST['repeat_until'] : null;
        $notlar = $_POST['notes'] ?? '';

        // Randevu çakışması kontrolü
        $stmt = $db->prepare("SELECT COUNT(*) FROM randevular WHERE TARIH = :tarih AND DURUM != 'iptal'");
        $stmt->execute([':tarih' => $tarih]);
        $randevuSayisi = $stmt->fetchColumn();

        if ($randevuSayisi > 0) {
            throw new Exception("Bu saatte başka bir randevu bulunmaktadır.");
        }

        // Ana randevuyu ekle
        $stmt = $db->prepare("INSERT INTO randevular (HASTA_ID, TARIH, DURUM, NOTLAR, TEKRAR, TEKRAR_SAYISI, TEKRAR_BITIS) 
                             VALUES (:hasta_id, :tarih, 'bekliyor', :notlar, :tekrar, :tekrar_sayisi, :tekrar_bitis)");
        
        $params = [
            ':hasta_id' => $hastaId,
            ':tarih' => $tarih,
            ':notlar' => $notlar,
            ':tekrar' => $tekrar,
            ':tekrar_sayisi' => $tekrarSayisi,
            ':tekrar_bitis' => $tekrarBitis
        ];
        
        $stmt->execute($params);

        // Tekrarlanan randevuları ekle
        if ($tekrar !== 'yok' && $tekrarSayisi > 0) {
            $baseDate = new DateTime($tarih);
            
            for ($i = 1; $i <= $tekrarSayisi; $i++) {
                switch ($tekrar) {
                    case 'gunluk':
                        $baseDate->modify('+1 day');
                        break;
                    case 'haftalik':
                        $baseDate->modify('+1 week');
                        break;
                    case 'aylik':
                        $baseDate->modify('+1 month');
                        break;
                }

                // Bitiş tarihini kontrol et
                if ($tekrarBitis && $baseDate->format('Y-m-d') > $tekrarBitis) {
                    break;
                }

                $yeniTarih = $baseDate->format('Y-m-d H:i:s');
                
                // Çakışma kontrolü
                $stmt = $db->prepare("SELECT COUNT(*) FROM randevular WHERE TARIH = :tarih AND DURUM != 'iptal'");
                $stmt->execute([':tarih' => $yeniTarih]);
                if ($stmt->fetchColumn() == 0) {
                    $stmt = $db->prepare("INSERT INTO randevular (HASTA_ID, TARIH, DURUM, NOTLAR) 
                                        VALUES (:hasta_id, :tarih, 'bekliyor', :notlar)");
                    $stmt->execute([
                        ':hasta_id' => $hastaId,
                        ':tarih' => $yeniTarih,
                        ':notlar' => $notlar
                    ]);
                }
            }
        }

        // Başarılı mesajıyla appointments.php'ye yönlendir
        header('Location: appointments.php?message=created');
        exit;
        
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
    <title>Yeni Randevu</title>
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
                        <h2 class="form-title mb-0">Yeni Randevu</h2>
                        <a href="appointments.php" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left"></i> Geri
                        </a>
                    </div>

                    <form method="POST" action="" class="needs-validation" novalidate>
                        <div class="form-section">
                            <div class="mb-3">
                                <label for="patient_id" class="form-label">Hasta</label>
                                <select class="form-select" id="patient_id" name="patient_id" required>
                                    <option value="">Hasta seçiniz</option>
                                    <?php foreach ($patients as $patient): ?>
                                        <option value="<?php echo $patient['ID']; ?>">
                                            <?php echo htmlspecialchars($patient['AD_SOYAD']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="appointment_date" class="form-label">Randevu Tarihi</label>
                                <input type="date" class="form-control" id="appointment_date" 
                                       name="appointment_date" required min="<?php echo date('Y-m-d'); ?>">
                            </div>

                            <div class="mb-3">
                                <label for="appointment_time" class="form-label">Randevu Saati</label>
                                <select class="form-select" id="appointment_time" name="appointment_time" required>
                                    <option value="">Saat seçiniz</option>
                                    <?php foreach (getAvailableTimeSlots() as $slot): ?>
                                        <option value="<?php echo $slot; ?>"><?php echo $slot; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="repeat_type" class="form-label">Tekrar</label>
                                <select class="form-select" id="repeat_type" name="repeat_type">
                                    <option value="yok">Tekrar Yok</option>
                                    <option value="gunluk">Günlük</option>
                                    <option value="haftalik">Haftalık</option>
                                    <option value="aylik">Aylık</option>
                                </select>
                            </div>

                            <div id="repeatOptions" style="display: none;">
                                <div class="mb-3">
                                    <label for="repeat_count" class="form-label">Tekrar Sayısı</label>
                                    <input type="number" class="form-control" id="repeat_count" 
                                           name="repeat_count" min="1" max="52">
                                </div>

                                <div class="mb-3">
                                    <label for="repeat_until" class="form-label">Bitiş Tarihi</label>
                                    <input type="date" class="form-control" id="repeat_until" 
                                           name="repeat_until" min="<?php echo date('Y-m-d'); ?>">
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="notes" class="form-label">Notlar</label>
                                <textarea class="form-control" id="notes" name="notes" rows="3"></textarea>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-success w-100">
                            <i class="fas fa-save"></i> Randevu Oluştur
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <?php include 'includes/nav.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Tekrar seçeneğine göre ek alanları göster/gizle
        document.getElementById('repeat_type').addEventListener('change', function() {
            const repeatOptions = document.getElementById('repeatOptions');
            repeatOptions.style.display = this.value === 'yok' ? 'none' : 'block';
        });
    </script>
</body>
</html> 