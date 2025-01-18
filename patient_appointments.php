<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

$patient_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if (!$patient_id) {
    header('Location: patients.php');
    exit;
}

$database = new Database();
$db = $database->connect();

// Hasta bilgilerini getir
$stmt = $db->prepare("SELECT * FROM hastalar WHERE ID = :id");
$stmt->execute([':id' => $patient_id]);
$patient = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$patient) {
    header('Location: patients.php');
    exit;
}

// Randevuları getir
$stmt = $db->prepare("
    SELECT r.*, 
           DATE_FORMAT(r.TARIH, '%d.%m.%Y') as RANDEVU_TARIHI,
           DATE_FORMAT(r.TARIH, '%H:%i') as RANDEVU_SAATI
    FROM randevular r
    WHERE r.HASTA_ID = :hasta_id 
    ORDER BY r.TARIH ASC
");
$stmt->execute([':hasta_id' => $patient_id]);
$appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Aktif ve geçmiş randevuları ayır
$activeAppointments = array_filter($appointments, function ($app) {
    $appointmentDateTime = strtotime($app['TARIH']);
    $now = strtotime('now');
    return $appointmentDateTime > $now;
});

$pastAppointments = array_filter($appointments, function ($app) {
    $appointmentDateTime = strtotime($app['TARIH']);
    $now = strtotime('now');
    return $appointmentDateTime <= $now;
});

// Tarihe göre sırala
$activeAppointments = array_values($activeAppointments);
$pastAppointments = array_values($pastAppointments);

// Aktif randevuları yakın tarihe göre sırala
usort($activeAppointments, function ($a, $b) {
    return strtotime($a['TARIH']) - strtotime($b['TARIH']);
});

// Geçmiş randevuları son tarihten eskiye doğru sırala
usort($pastAppointments, function ($a, $b) {
    return strtotime($b['TARIH']) - strtotime($a['TARIH']);
});
?>

<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hasta Randevuları</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }

        .content-area {
            padding-top: 30px !important;
            padding-bottom: 100px !important;
        }

        .patient-info-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
            border: 1px solid rgba(0, 0, 0, 0.05);
        }

        .patient-name {
            font-size: 1.4rem;
            font-weight: 600;
            color: #212529;
            margin-bottom: 10px;
        }

        .patient-details {
            display: flex;
            gap: 20px;
            color: #6c757d;
            font-size: 0.9rem;
        }

        .appointment-tabs {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
            border: 1px solid rgba(0, 0, 0, 0.05);
        }

        .nav-tabs {
            border: none;
            margin-bottom: 20px;
            gap: 10px;
            flex-wrap: nowrap;
            overflow-x: auto;
            padding-bottom: 5px;
        }

        .nav-tabs .nav-link {
            border: none;
            color: #6c757d;
            padding: 10px 20px;
            border-radius: 6px;
            white-space: nowrap;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .nav-tabs .nav-link .badge {
            padding: 5px 10px;
            font-size: 0.8rem;
            font-weight: 500;
        }

        .nav-tabs .nav-link.active {
            background-color: #e8f5e9;
            color: #28a745;
            font-weight: 500;
        }

        .tab-content {
            min-height: 200px;
        }

        .appointment-card {
            background: #fff;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 15px;
            border: 1px solid rgba(0, 0, 0, 0.05);
            transition: all 0.2s ease;
            position: relative;
            overflow: hidden;
            opacity: 1;
        }

        #past .appointment-card {
            opacity: 0.8;
        }

        #past .appointment-card:hover {
            opacity: 1;
        }

        .appointment-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }

        .appointment-date {
            font-weight: 500;
            color: #212529;
            font-size: 0.95rem;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .appointment-date i {
            color: #6c757d;
            width: 16px;
        }

        .appointment-status {
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 0.85rem;
            font-weight: 500;
        }

        .appointment-status.bg-success {
            background: rgba(40, 167, 69, 0.1) !important;
            color: #28a745 !important;
        }

        .appointment-status.bg-warning {
            background: rgba(255, 193, 7, 0.1) !important;
            color: #ffa000 !important;
        }

        .appointment-status.bg-danger {
            background: rgba(220, 53, 69, 0.1) !important;
            color: #dc3545 !important;
        }

        .appointment-status.bg-secondary {
            background: rgba(108, 117, 125, 0.1) !important;
            color: #6c757d !important;
        }

        .appointment-notes {
            background: #f8f9fa;
            padding: 10px;
            border-radius: 6px;
            margin: 10px 0;
        }

        .appointment-actions {
            display: flex;
            gap: 8px;
            margin-top: 15px;
            justify-content: flex-end;
        }

        .appointment-actions .btn {
            padding: 8px 16px;
            font-size: 0.9rem;
            border-radius: 6px;
            transition: all 0.2s ease;
        }

        #past .appointment-actions .btn {
            opacity: 0.7;
        }

        #past .appointment-card:hover .appointment-actions .btn {
            opacity: 1;
        }

        .btn-outline-primary {
            border-color: #e3f2fd;
            color: #0d6efd;
        }

        .btn-outline-primary:hover {
            background: #e3f2fd;
            border-color: #e3f2fd;
            color: #0d6efd;
        }

        .btn-outline-danger {
            border-color: #fee2e2;
            color: #dc3545;
        }

        .btn-outline-danger:hover {
            background: #fee2e2;
            border-color: #fee2e2;
            color: #dc3545;
        }

        .appointment-card::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 4px;
            background: #e9ecef;
        }

        .appointment-card.status-onaylandi::before {
            background: #28a745;
        }

        .appointment-card.status-bekliyor::before {
            background: #ffa000;
        }

        .appointment-card.status-iptal::before {
            background: #dc3545;
        }

        .btn-success {
            padding: 10px 20px;
            border-radius: 6px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .btn-success:hover {
            background-color: #218838;
            border-color: #1e7e34;
        }

        .modal-content {
            border: none;
            border-radius: 10px;
        }

        .modal-header {
            border-bottom: 1px solid #e9ecef;
            padding: 20px;
        }

        .modal-body {
            padding: 20px;
        }

        .modal-footer {
            border-top: 1px solid #e9ecef;
            padding: 15px 20px;
        }

        .appointment-info {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
        }

        .appointment-info i {
            width: 20px;
            color: #6c757d;
        }

        @media (max-width: 767px) {
            .content-area {
                padding: 20px 15px 100px 15px !important;
            }

            .btn-success {
                width: 45px;
                height: 45px;
                padding: 0;
                display: flex;
                align-items: center;
                justify-content: center;
            }

            .btn-success i {
                margin: 0;
                font-size: 1.1rem;
            }

            .appointment-tabs {
                padding: 15px;
                margin: 0 -5px;
                border-radius: 15px;
            }

            .nav-tabs {
                margin: -5px -5px 15px -5px;
                padding: 0 5px 10px 5px;
            }

            .nav-tabs .nav-link {
                padding: 8px 15px;
                font-size: 0.9rem;
                min-width: auto;
            }

            .appointment-card {
                padding: 15px;
                margin: 0 -5px 15px -5px;
            }

            .appointment-date {
                flex-direction: column;
                gap: 8px;
                align-items: flex-start;
            }

            .appointment-date div {
                display: flex;
                align-items: center;
                gap: 8px;
            }
        }
    </style>
</head>

<body>
    <?php include 'includes/header.php'; ?>

    <div class="container content-area">
        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
                <?php if ($_GET['success'] == '2'): ?>
                    Randevu başarıyla silindi.
                <?php endif; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
                <?php if ($_GET['error'] == '1'): ?>
                    Randevu silinirken bir hata oluştu.
                <?php endif; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <!-- Geri Butonu ve Başlık -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div class="d-flex align-items-center gap-3">
                <a href="patients.php" class="btn btn-outline-secondary btn-sm">
                    <i class="fas fa-arrow-left"></i>
                </a>
                <h1 class="page-title mb-0">Hasta Randevuları</h1>
            </div>
            <a href="new-appointment.php?patient=<?php echo $patient_id; ?>" class="btn btn-success">
                <i class="fas fa-plus me-md-2"></i>
                <span class="d-none d-md-inline">Yeni Randevu</span>
            </a>
        </div>

        <!-- Hasta Bilgi Kartı -->
        <div class="patient-info-card">
            <div class="patient-name">
                <?php echo htmlspecialchars($patient['AD_SOYAD']); ?>
            </div>
            <div class="patient-details">
                <div>
                    <i class="fas fa-id-card me-2"></i>
                    <?php echo htmlspecialchars($patient['KIMLIK_NO']); ?>
                </div>
                <div>
                    <i class="fas fa-phone me-2"></i>
                    <?php echo htmlspecialchars($patient['TELEFON']); ?>
                </div>
                <div>
                    <i class="fas fa-envelope me-2"></i>
                    <?php echo htmlspecialchars($patient['EMAIL']); ?>
                </div>
            </div>
        </div>

        <!-- Randevu Sekmeleri -->
        <div class="appointment-tabs">
            <ul class="nav nav-tabs" role="tablist">
                <li class="nav-item">
                    <a class="nav-link active" data-bs-toggle="tab" href="#active">
                        Aktif Randevular
                        <span class="badge bg-success ms-2"><?php echo count($activeAppointments); ?></span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-bs-toggle="tab" href="#past">
                        Geçmiş Randevular
                        <span class="badge bg-secondary ms-2"><?php echo count($pastAppointments); ?></span>
                    </a>
                </li>
            </ul>

            <div class="tab-content">
                <!-- Aktif Randevular -->
                <div class="tab-pane fade show active" id="active">
                    <?php if ($activeAppointments): ?>
                        <?php foreach ($activeAppointments as $appointment): ?>
                            <div class="appointment-card status-<?php echo $appointment['DURUM']; ?>"
                                data-appointment-id="<?php echo $appointment['ID']; ?>">
                                <div class="appointment-header">
                                    <div class="appointment-date">
                                        <div>
                                            <i class="fas fa-calendar"></i>
                                            <?php echo $appointment['RANDEVU_TARIHI']; ?>
                                        </div>
                                        <div>
                                            <i class="fas fa-clock"></i>
                                            <?php echo $appointment['RANDEVU_SAATI']; ?>
                                        </div>
                                    </div>
                                    <span class="appointment-status bg-<?php echo getStatusColor($appointment['DURUM']); ?>">
                                        <?php echo getStatusText($appointment['DURUM']); ?>
                                    </span>
                                </div>
                                <?php if ($appointment['NOTLAR']): ?>
                                    <div class="appointment-notes text-muted">
                                        <small><i
                                                class="fas fa-note-sticky me-2"></i><?php echo htmlspecialchars($appointment['NOTLAR']); ?></small>
                                    </div>
                                <?php endif; ?>
                                <div class="appointment-actions">
                                    <a href="edit-appointment.php?id=<?php echo $appointment['ID']; ?>"
                                        class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-edit me-2"></i>Düzenle
                                    </a>
                                    <button onclick="deleteAppointment(<?php echo $appointment['ID']; ?>)"
                                        class="btn btn-sm btn-outline-danger">
                                        <i class="fas fa-trash me-2"></i>İptal Et
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="text-center text-muted py-4">
                            <i class="fas fa-calendar-xmark mb-3 fa-2x"></i>
                            <p>Aktif randevu bulunmuyor</p>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Geçmiş Randevular -->
                <div class="tab-pane fade" id="past">
                    <?php if ($pastAppointments): ?>
                        <?php foreach ($pastAppointments as $appointment): ?>
                            <div class="appointment-card status-<?php echo $appointment['DURUM']; ?>"
                                data-appointment-id="<?php echo $appointment['ID']; ?>">
                                <div class="appointment-header">
                                    <div class="appointment-date">
                                        <div>
                                            <i class="fas fa-calendar"></i>
                                            <?php echo $appointment['RANDEVU_TARIHI']; ?>
                                        </div>
                                        <div>
                                            <i class="fas fa-clock"></i>
                                            <?php echo $appointment['RANDEVU_SAATI']; ?>
                                        </div>
                                    </div>
                                    <span class="appointment-status bg-<?php echo getStatusColor($appointment['DURUM']); ?>">
                                        <?php echo getStatusText($appointment['DURUM']); ?>
                                    </span>
                                </div>
                                <?php if ($appointment['NOTLAR']): ?>
                                    <div class="appointment-notes text-muted">
                                        <small><i
                                                class="fas fa-note-sticky me-2"></i><?php echo htmlspecialchars($appointment['NOTLAR']); ?></small>
                                    </div>
                                <?php endif; ?>
                                <div class="appointment-actions">
                                    <a href="edit-appointment.php?id=<?php echo $appointment['ID']; ?>"
                                        class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-edit me-2"></i>Düzenle
                                    </a>
                                    <button onclick="deleteAppointment(<?php echo $appointment['ID']; ?>)"
                                        class="btn btn-sm btn-outline-danger">
                                        <i class="fas fa-trash me-2"></i>İptal Et
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="text-center text-muted py-4">
                            <i class="fas fa-calendar-xmark mb-3 fa-2x"></i>
                            <p>Geçmiş randevu bulunmuyor</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <?php include 'includes/nav.php'; ?>

    <!-- Silme Onay Modalı -->
    <div class="modal fade" id="deleteAppointmentModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Randevu İptali</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Bu randevuyu iptal etmek istediğinizden emin misiniz?</p>
                    <div class="appointment-info mt-3">
                        <div class="d-flex align-items-center text-muted mb-2">
                            <i class="fas fa-calendar me-2"></i>
                            <span id="modalDate"></span>
                        </div>
                        <div class="d-flex align-items-center text-muted">
                            <i class="fas fa-clock me-2"></i>
                            <span id="modalTime"></span>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Vazgeç</button>
                    <button type="button" class="btn btn-danger" id="confirmDelete">
                        <i class="fas fa-trash me-2"></i>İptal Et
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let appointmentToDelete = null;
        const deleteModal = new bootstrap.Modal(document.getElementById('deleteAppointmentModal'));

        function deleteAppointment(id) {
            // Randevu bilgilerini bul
            const appointmentCard = document.querySelector(`[data-appointment-id="${id}"]`);
            const date = appointmentCard.querySelector('.appointment-date div:first-child').textContent.trim();
            const time = appointmentCard.querySelector('.appointment-date div:last-child').textContent.trim();

            // Modal içeriğini güncelle
            document.getElementById('modalDate').textContent = date;
            document.getElementById('modalTime').textContent = time;

            appointmentToDelete = id;
            deleteModal.show();
        }

        document.getElementById('confirmDelete').addEventListener('click', function () {
            if (appointmentToDelete) {
                window.location.href = 'delete_appointment.php?id=' + appointmentToDelete;
            }
        });
    </script>
</body>

</html>