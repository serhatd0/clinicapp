<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

// Randevu düzenleme yetkisi kontrolü
checkPagePermission('randevu_duzenle');

$database = new Database();
$db = $database->connect();

// Randevu bilgilerini getir
$stmt = $db->prepare("
    SELECT r.*, h.AD_SOYAD, 
    (SELECT COUNT(*) FROM randevular WHERE ANA_RANDEVU_ID = r.ANA_RANDEVU_ID) as total_appointments,
    (SELECT MIN(ID) FROM randevular WHERE ANA_RANDEVU_ID = r.ANA_RANDEVU_ID) as first_appointment_id
    FROM randevular r 
    LEFT JOIN hastalar h ON r.HASTA_ID = h.ID 
    WHERE r.ID = :id
");
$stmt->execute([':id' => $_GET['id']]);
$appointment = $stmt->fetch(PDO::FETCH_ASSOC);

// İlk randevu mu kontrolü
$isFirstAppointment = $appointment['ID'] == $appointment['first_appointment_id'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $database = new Database();
        $db = $database->connect();
        
        // Tüm seriyi güncelle seçeneği kontrol ediliyor
        if (isset($_POST['update_series']) && $_POST['update_series'] == '1' && $appointment['ANA_RANDEVU_ID']) {
            $success = updateAppointmentSeries($db, $appointment['ID'], $_POST);
        } else {
            // Tek randevu güncelleme
            $stmt = $db->prepare("
                UPDATE randevular 
                SET TARIH = :tarih,
                    DURUM = :durum,
                    NOTLAR = :notlar
                WHERE ID = :id
            ");
            
            $success = $stmt->execute([
                ':tarih' => $_POST['appointment_date'] . ' ' . $_POST['appointment_time'],
                ':durum' => $_POST['status'],
                ':notlar' => isset($_POST['notes']) ? $_POST['notes'] : null,
                ':id' => $appointment['ID']
            ]);
        }

        if ($success) {
            header('Location: appointments.php?success=1');
            exit;
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
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
    <style>
        .appointment-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        
        .patient-info {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #e9ecef;
        }
        
        .patient-avatar {
            width: 50px;
            height: 50px;
            background: #e9ecef;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
            color: #6c757d;
        }
        
        .appointment-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .detail-group {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
        }
        
        .detail-label {
            font-size: 0.9rem;
            color: #6c757d;
            margin-bottom: 5px;
        }
        
        .detail-value {
            font-weight: 500;
            color: #212529;
        }
        
        .series-info {
            background: #e8f5e9;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .series-info.warning {
            background: #fff3e0;
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container py-4 content-area ">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="appointment-card">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h2 class="form-title mb-0">Randevu Düzenle</h2>
                        <a href="appointments.php" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left"></i> Geri
                        </a>
                    </div>

                    <div class="patient-info">
                        <div class="patient-avatar">
                            <i class="fas fa-user"></i>
                        </div>
                        <div>
                            <h5 class="mb-1"><?php echo htmlspecialchars($appointment['AD_SOYAD']); ?></h5>
                            <div class="text-muted">Hasta ID: <?php echo $appointment['HASTA_ID']; ?></div>
                        </div>
                    </div>

                    <?php if ($appointment['total_appointments'] > 1): ?>
                        <div class="series-info <?php echo $isFirstAppointment ? '' : 'warning'; ?>">
                            <i class="fas fa-info-circle me-2"></i>
                            <?php if ($isFirstAppointment): ?>
                                Bu randevu <?php echo $appointment['total_appointments']; ?> randevuluk bir serinin ilk randevusudur.
                            <?php else: ?>
                                Bu randevu <?php echo $appointment['total_appointments']; ?> randevuluk bir serinin parçasıdır.
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="" class="needs-validation" novalidate>
                        <div class="appointment-details">
                            <div class="detail-group">
                                <label for="appointment_date" class="detail-label">Randevu Tarihi</label>
                                <input type="date" class="form-control" id="appointment_date" 
                                       name="appointment_date" required 
                                       value="<?php echo date('Y-m-d', strtotime($appointment['TARIH'])); ?>"
                                       onchange="checkSunday(this)">
                            </div>

                            <div class="detail-group">
                                <label for="appointment_time" class="detail-label">Randevu Saati</label>
                                <select class="form-select" id="appointment_time" name="appointment_time" required>
                                    <?php 
                                    $currentTime = date('H:i', strtotime($appointment['TARIH']));
                                    foreach (getAvailableTimeSlots() as $slot): 
                                    ?>
                                        <option value="<?php echo $slot; ?>" 
                                                <?php echo $slot == $currentTime ? 'selected' : ''; ?>>
                                            <?php echo $slot; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="detail-group">
                                <label for="status" class="detail-label">Durum</label>
                                <select class="form-select" id="status" name="status" required>
                                    <option value="bekliyor" <?php echo $appointment['DURUM'] == 'bekliyor' ? 'selected' : ''; ?>>Bekliyor</option>
                                    <option value="onaylandi" <?php echo $appointment['DURUM'] == 'onaylandi' ? 'selected' : ''; ?>>Onaylandı</option>
                                    <option value="iptal" <?php echo $appointment['DURUM'] == 'iptal' ? 'selected' : ''; ?>>İptal</option>
                                </select>
                            </div>
                        </div>

                        <?php if ($isFirstAppointment && $appointment['total_appointments'] > 1): ?>
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" id="update_all" name="update_all" value="1">
                                <label class="form-check-label" for="update_all">
                                    Tüm seri randevuların durumunu güncelle
                                </label>
                            </div>
                        <?php endif; ?>

                        <?php if ($appointment['ANA_RANDEVU_ID']): ?>
                            <div class="mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="update_series" value="1" id="updateSeries">
                                    <label class="form-check-label" for="updateSeries">
                                        Tüm randevu serisini güncelle
                                    </label>
                                </div>
                            </div>
                        <?php endif; ?>

                        <div class="d-flex gap-2 mb-5">
                            <button type="submit" class="btn btn-success flex-grow-1">
                                <i class="fas fa-save me-2"></i>Kaydet
                            </button>
                            <a href="#" 
                               class="btn btn-danger"
                               onclick="confirmDelete(<?php echo $appointment['ID']; ?>, <?php echo $appointment['ANA_RANDEVU_ID'] ? 'true' : 'false'; ?>)">
                                <i class="fas fa-trash"></i>
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <?php include 'includes/nav.php'; ?>

    <script>
        function confirmDelete(id, hasSeries) {
            let message = 'Bu randevuyu silmek istediğinizden emin misiniz?';
            let url = 'delete_appointment.php?id=' + id;
            
            if (hasSeries) {
                if (confirm('Bu randevu bir serinin parçası. Tüm seriyi silmek ister misiniz?')) {
                    url += '&series=1';
                }
            }
            
            if (confirm(message)) {
                window.location.href = url;
            }
        }
        
        function checkSunday(input) {
            const date = new Date(input.value);
            if (date.getDay() === 0) { // 0 = Pazar
                alert('Pazar günü randevu alınamaz!');
                date.setDate(date.getDate() + 1);
                input.value = date.toISOString().split('T')[0];
            }
        }
    </script>
</body>
</html> 