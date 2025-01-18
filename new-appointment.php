<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';

$database = new Database();
$db = $database->connect();

// URL'den hasta ID'sini al
$selectedPatientId = isset($_GET['patient']) ? (int)$_GET['patient'] : null;

// Seçili hasta varsa bilgilerini getir
$selectedPatient = null;
if ($selectedPatientId) {
    $stmt = $db->prepare("SELECT ID, AD_SOYAD FROM hastalar WHERE ID = :id");
    $stmt->execute([':id' => $selectedPatientId]);
    $selectedPatient = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Hasta listesini getir
$stmt = $db->query("SELECT ID, AD_SOYAD FROM hastalar ORDER BY AD_SOYAD ASC");
$patients = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Şablonları getir
$stmt = $db->query("SELECT * FROM randevu_sablonlari ORDER BY SIRA ASC");
$templates = $stmt->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $hastaId = $_POST['patient_id'];
        $tarih = $_POST['appointment_date'];
        $saat = $_POST['appointment_time'];
        $isRecurring = isset($_POST['is_recurring']) && $_POST['is_recurring'] == '1';
        
        if (createAppointment($db, $hastaId, $tarih, $saat, $isRecurring)) {
            header('Location: appointments.php?message=created');
            exit;
        } else {
            throw new Exception("Randevu oluşturulurken bir hata oluştu.");
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
    <title>Yeni Randevu</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
    <style>
        .appointment-preview {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 15px;
            margin-top: 20px;
        }
        
        .preview-item {
            background: white;
            border-radius: 8px;
            padding: 12px;
            margin-bottom: 10px;
            border-left: 3px solid #28a745;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .preview-date {
            min-width: 100px;
            font-weight: 500;
        }
        
        .preview-time {
            min-width: 70px;
            color: #28a745;
        }
        
        .preview-service {
            flex: 1;
            color: #495057;
        }
        
        .preview-warning {
            color: #dc3545;
            font-size: 0.9rem;
            margin-top: 5px;
        }
        
        .preview-item {
            background: white;
            border-radius: 8px;
            padding: 12px;
            margin-bottom: 10px;
            border-left: 3px solid #28a745;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .preview-date-time {
            display: flex;
            gap: 10px;
            align-items: center;
        }
        
        .preview-date-time input,
        .preview-date-time select {
            width: auto;
        }
        
        .appointment-date,
        .appointment-time {
            padding: 4px 8px;
            font-size: 0.9rem;
        }
        
        .form-select-lg {
            background-color: #e9ecef;
            font-weight: 500;
            color: #212529;
            cursor: not-allowed;
        }
        
        .form-select-lg option {
            font-weight: normal;
        }

        @media (max-width: 767px) {
            .content-area {
                padding: 15px 15px 80px 15px !important;
            }
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container py-4 content-area">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="form-container">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h2 class="form-title mb-0">Yeni Randevu Serisi</h2>
                        <a href="appointments.php" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left"></i> Geri
                        </a>
                    </div>

                    <form method="POST" id="appointmentForm">
                        <div class="form-section">
                            <div class="mb-4">
                                <label class="form-label">Hasta Seçimi</label>
                                <select class="form-select <?php echo $selectedPatientId ? 'form-select-lg' : ''; ?>" 
                                        name="patient_id" 
                                        required 
                                        <?php echo $selectedPatientId ? 'disabled' : ''; ?>>
                                    <option value="">Hasta seçiniz...</option>
                                    <?php foreach ($patients as $patient): ?>
                                        <option value="<?php echo $patient['ID']; ?>"
                                                <?php echo ($selectedPatientId == $patient['ID'] || 
                                                          (isset($_POST['patient_id']) && $_POST['patient_id'] == $patient['ID'])) 
                                                          ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($patient['AD_SOYAD']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <?php if ($selectedPatientId): ?>
                                    <input type="hidden" name="patient_id" value="<?php echo $selectedPatientId; ?>">
                                <?php endif; ?>
                            </div>

                            <div class="mb-3">
                                <label for="appointment_date" class="form-label">İşlem Tarihi</label>
                                <input type="date" class="form-control" id="appointment_date" 
                                       name="appointment_date" required min="<?php echo date('Y-m-d'); ?>"
                                       onchange="updatePreview()">
                            </div>

                            <div class="mb-3">
                                <label for="appointment_time" class="form-label">Randevu Saati</label>
                                <select class="form-select" id="appointment_time" name="appointment_time" required
                                        onchange="updatePreview()">
                                    <option value="">Saat seçiniz</option>
                                    <?php foreach (getAvailableTimeSlots() as $slot): ?>
                                        <option value="<?php echo $slot; ?>"><?php echo $slot; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Tekrarlı Randevu</label>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="is_recurring" id="recurring_yes" value="1" onchange="togglePreview()">
                                    <label class="form-check-label" for="recurring_yes">
                                        Evet
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="is_recurring" id="recurring_no" value="0" checked onchange="togglePreview()">
                                    <label class="form-check-label" for="recurring_no">
                                        Hayır
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="appointment-preview" id="previewContainer" style="display: none;">
                            <h4 class="mb-3">Randevu Serisi Önizleme</h4>
                            <div id="previewList">
                                <!-- JavaScript ile doldurulacak -->
                            </div>
                        </div>

                        <button type="submit" class="btn btn-success w-100 mt-3">
                            <i class="fas fa-calendar-check me-2"></i>Randevuları Oluştur
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <?php include 'includes/nav.php'; ?>

    <script>
        const templates = <?php echo json_encode($templates); ?>;
        
        function updatePreview() {
            const isRecurring = document.querySelector('input[name="is_recurring"]:checked').value === "1";
            const date = document.getElementById('appointment_date').value;
            const time = document.getElementById('appointment_time').value;
            const previewContainer = document.getElementById('previewContainer');
            const previewList = document.getElementById('previewList');
            
            if (!date || !time || !isRecurring) {
                previewContainer.style.display = 'none';
                return;
            }
            
            previewContainer.style.display = 'block';
            previewList.innerHTML = '';
            
            templates.forEach((template, index) => {
                const appointmentDate = new Date(date);
                appointmentDate.setDate(appointmentDate.getDate() + template.GUN - 1);
                
                // Pazar günü kontrolü
                while (appointmentDate.getDay() === 0) {
                    appointmentDate.setDate(appointmentDate.getDate() + 1);
                }
                
                const div = document.createElement('div');
                div.className = 'preview-item';
                div.innerHTML = `
                    <div class="preview-service flex-grow-1">${template.ISLEM_ADI}</div>
                    <div class="preview-date-time">
                        <input type="date" 
                               class="form-control form-control-sm appointment-date" 
                               name="appointment_dates[${index}]" 
                               value="${appointmentDate.toISOString().split('T')[0]}"
                               min="${date}"
                               onchange="checkSunday(this)">
                        <select class="form-control form-control-sm appointment-time" 
                                name="appointment_times[${index}]">
                            ${generateTimeOptions(time)}
                        </select>
                    </div>
                    <div class="preview-warning" style="display: none; color: #dc3545; font-size: 0.8rem;">
                        Pazar günü seçilemez
                    </div>
                `;
                
                previewList.appendChild(div);
            });
        }

        function generateTimeOptions(selectedTime) {
            const times = [];
            for (let hour = 9; hour < 18; hour++) {
                for (let minute = 0; minute < 60; minute += 15) {
                    const timeStr = `${hour.toString().padStart(2, '0')}:${minute.toString().padStart(2, '0')}`;
                    times.push(`<option value="${timeStr}" ${timeStr === selectedTime ? 'selected' : ''}>${timeStr}</option>`);
                }
            }
            return times.join('');
        }

        function checkSunday(input) {
            const date = new Date(input.value);
            const warningDiv = input.parentElement.nextElementSibling;
            
            if (date.getDay() === 0) { // Pazar
                warningDiv.style.display = 'block';
                // Bir sonraki pazartesiye ayarla
                date.setDate(date.getDate() + 1);
                input.value = date.toISOString().split('T')[0];
            } else {
                warningDiv.style.display = 'none';
            }
        }

        function togglePreview() {
            const isRecurring = document.querySelector('input[name="is_recurring"]:checked').value === "1";
            if (isRecurring) {
                updatePreview();
            } else {
                document.getElementById('previewContainer').style.display = 'none';
            }
        }
    </script>
</body>
</html> 