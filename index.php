<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

$database = new Database();
$db = $database->connect();

// Seçili tarih kontrolü
$selectedDate = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');

// Seçili tarihin 5 gün öncesi ve 5 gün sonrasını al
$currentDate = new DateTime($selectedDate);
$startDate = (clone $currentDate)->modify('-5 days');
$endDate = (clone $currentDate)->modify('+5 days');

// Tarih aralığındaki günleri al
$days = [];
$currentDay = clone $startDate;
while ($currentDay <= $endDate) {
    $days[] = [
        'date' => $currentDay->format('Y-m-d'),
        'day' => $currentDay->format('d'),
        'day_name' => getTurkishDayName($currentDay),
        'month' => getTurkishMonth($currentDay),
        'is_today' => $currentDay->format('Y-m-d') === date('Y-m-d'),
        'is_selected' => $currentDay->format('Y-m-d') === $selectedDate,
        'is_past' => $currentDay->format('Y-m-d') < date('Y-m-d')
    ];
    $currentDay->modify('+1 day');
}

// Seçili güne ait randevuları getir
$appointments = getAppointmentsByDate($db, $selectedDate);

// Son ödemeleri getir
$stmt = $db->prepare("
    SELECT o.*, t.TAKSIT_NO, hb.PLAN_ADI, h.AD_SOYAD,
           DATE_FORMAT(o.ODEME_TARIHI, '%d.%m.%Y') as ODEME_TARIHI_FORMAT
    FROM odemeler o
    JOIN taksitler t ON t.ID = o.TAKSIT_ID
    JOIN hasta_borc hb ON hb.ID = t.BORC_ID
    JOIN hastalar h ON h.ID = hb.HASTA_ID
    ORDER BY o.ODEME_TARIHI DESC
    LIMIT 10
");
$stmt->execute();
$sonOdemeler = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Bugünkü ödemelerin toplamı
$stmt = $db->prepare("
    SELECT COALESCE(SUM(o.TUTAR), 0) as BUGUN_TOPLAM,
           COUNT(*) as BUGUN_ISLEM
    FROM odemeler o
    WHERE DATE(o.ODEME_TARIHI) = CURDATE()
");
$stmt->execute();
$bugunOzet = $stmt->fetch(PDO::FETCH_ASSOC);

// Vadesi geçmiş ödemeler
$stmt = $db->prepare("
    SELECT COUNT(*) as GECIKEN_TAKSIT,
           COALESCE(SUM(t.TUTAR), 0) as GECIKEN_TUTAR
    FROM taksitler t
    WHERE t.DURUM != 'odendi'
    AND t.VADE_TARIHI < CURDATE()
");
$stmt->execute();
$gecikmeOzet = $stmt->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Anasayfa</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }

        .days-container {
            position: relative;
            margin-bottom: 20px;
        }

        .scroll-button {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: white;
            border: 1px solid rgba(0, 0, 0, 0.1);
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            z-index: 2;
            transition: all 0.3s ease;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }

        .scroll-button:hover {
            background: #f8f9fa;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .scroll-button.left {
            left: 5px;
        }

        .scroll-button.right {
            right: 5px;
        }

        @media (max-width: 767px) {
            .scroll-button {
                display: none;
            }
        }

        .days-slider {
            overflow-x: auto;
            white-space: nowrap;
            -webkit-overflow-scrolling: touch;
            margin: 0;
            padding: 20px 15px;
            background: white;
            border-radius: 15px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
            position: relative;
            cursor: grab;
            scroll-behavior: smooth;
            -ms-overflow-style: none;
            scrollbar-width: none;
        }

        .days-slider::after {
            content: '';
            position: absolute;
            right: 0;
            top: 0;
            bottom: 0;
            width: 50px;
            background: linear-gradient(to right, transparent, white);
            pointer-events: none;
        }

        .days-slider::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 50px;
            background: linear-gradient(to left, transparent, white);
            pointer-events: none;
            z-index: 1;
        }

        .days-slider::-webkit-scrollbar {
            display: none;
        }

        .day-item {
            display: inline-flex;
            flex-direction: column;
            align-items: center;
            padding: 15px;
            border-radius: 12px;
            margin-right: 15px;
            cursor: pointer;
            transition: all 0.3s ease;
            min-width: 90px;
            text-decoration: none;
            color: #495057;
            border: 1px solid transparent;
            user-select: none;
            -webkit-user-select: none;
        }

        .day-item:hover {
            background: #e9ecef;
            color: #212529;
        }

        .day-item.active {
            background: #e3f2fd;
            color: #0d6efd;
            border-color: #0d6efd;
        }

        .day-item.past {
            opacity: 0.6;
        }

        .day-number {
            font-size: 1.8rem;
            font-weight: 600;
            margin-bottom: 5px;
            line-height: 1;
        }

        .day-name {
            font-size: 0.85rem;
            font-weight: 500;
            text-transform: uppercase;
        }

        .day-month {
            font-size: 0.75rem;
            color: #6c757d;
            margin-top: 2px;
        }

        .appointment-list {
            background: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }

        .appointment-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #e9ecef;
        }

        .appointment-date {
            font-size: 1.1rem;
            font-weight: 500;
            color: #212529;
        }

        .appointment-item {
            display: flex;
            align-items: center;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 10px;
            background: white;
            transition: all 0.3s ease;
            border: 1px solid #e9ecef;
            text-decoration: none;
            color: inherit;
        }

        .appointment-item:hover {
            transform: translateY(-1px);
            box-shadow: 0 3px 5px rgba(0, 0, 0, 0.05);
        }

        .appointment-time {
            font-size: 1.1rem;
            font-weight: 600;
            color: white;
            min-width: 80px;
            padding-right: 15px;
            background-color: #0d6efd;
        }

        .appointment-info {
            flex: 1;
            margin-left: 15px;
        }

        .appointment-name {
            font-weight: 500;
            margin-bottom: 5px;
            color: #212529;
        }

        .appointment-status {
            font-size: 0.85rem;
        }

        .appointment-status .badge {
            font-weight: normal;
            padding: 5px 10px;
        }

        .quick-actions {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
            margin-top: 20px;
        }

        .quick-action-btn {
            padding: 20px;
            border-radius: 15px;
            text-align: center;
            transition: all 0.3s ease;
            text-decoration: none;
            color: white;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .quick-action-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 8px rgba(0, 0, 0, 0.15);
            color: white;
        }

        .quick-action-btn i {
            font-size: 2rem;
        }

        .quick-action-btn.primary {
            background: #0d6efd;
        }

        .quick-action-btn.success {
            background: #198754;
        }

        @media (max-width: 767px) {
            .content-area {
                padding: 15px 15px 80px 15px !important;
            }

            .day-item {
                min-width: 70px;
                padding: 10px;
            }

            .day-number {
                font-size: 1.5rem;
            }

            .appointment-header {
                flex-direction: column;
                gap: 10px;
            }

            .appointment-list {
                padding: 15px;
                margin: 0 -5px;
                border-radius: 15px;
            }

            .appointment-item {
                display: flex;
                align-items: center;
                gap: 10px;
                padding: 12px;
                margin: 0 0 10px 0;
                background: white;
                border: 1px solid #e9ecef;
                position: relative;
            }

            .appointment-time {
                min-width: 60px;
                padding-right: 0;
                color: white;
            }

            .appointment-info {
                flex: 1;
            }

            .appointment-name {
                font-size: 1rem;
            }

            .appointment-status {
                margin-top: 2px;
            }

            .appointment-status .badge {
                padding: 6px 10px;
                font-size: 0.8rem;
                font-weight: normal;
            }

            .appointment-actions {
                display: none;
            }
        }

        .bg-gradient {
            background: linear-gradient(to right bottom, #ffffff, #f8f9fa);
        }

        .card {
            transition: transform 0.2s ease-in-out;
        }

        .card:hover {
            transform: translateY(-3px);
        }

        .table> :not(caption)>*>* {
            padding: 1rem;
        }

        .badge {
            padding: 0.5rem 0.8rem;
            font-weight: 500;
        }
    </style>
</head>

<body>
    <?php include 'includes/header.php'; ?>

    <div class="container py-4 content-area">
        <!-- Günler Container -->
        <div class="days-container">
            <button class="scroll-button left" id="scrollLeft">
                <i class="fas fa-chevron-left"></i>
            </button>
            <div class="days-slider">
                <?php foreach ($days as $day): ?>
                    <a href="?date=<?php echo $day['date']; ?>"
                        class="day-item <?php echo $day['is_selected'] ? 'active' : ''; ?> <?php echo $day['is_past'] ? 'past' : ''; ?>">
                        <span class="day-number"><?php echo $day['day']; ?></span>
                        <span class="day-name"><?php echo $day['day_name']; ?></span>
                        <span class="day-month"><?php echo $day['month']; ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
            <button class="scroll-button right" id="scrollRight">
                <i class="fas fa-chevron-right"></i>
            </button>
        </div>

        <!-- Randevular -->
        <div class="appointment-list">
            <div class="appointment-header">
                <div class="appointment-date">
                    <?php echo turkishDate($selectedDate); ?> Randevuları
                </div>
                <div class="appointment-count">
                    <span class="badge bg-primary"><?php echo count($appointments); ?> Randevu</span>
                </div>
            </div>

            <?php if ($appointments): ?>
                <?php foreach ($appointments as $appointment): ?>
                    <a href="edit-appointment.php?id=<?php echo $appointment['ID']; ?>" class="appointment-item">
                        <div class="appointment-time">
                            <?php echo $appointment['SAAT']; ?>
                        </div>
                        <div class="appointment-info">
                            <div class="appointment-name">
                                <?php echo htmlspecialchars($appointment['HASTA_ADI']); ?>
                            </div>
                            <?php if (!empty($appointment['NOTLAR'])): ?>
                                <div class="appointment-note">
                                    <i class="fas fa-comment-medical me-1"></i>
                                    <?php echo mb_strimwidth(htmlspecialchars($appointment['NOTLAR']), 0, 25, "..."); ?>
                                </div>
                            <?php endif; ?>
                            <div class="appointment-status">
                                <span class="badge bg-<?php echo getStatusColor($appointment['DURUM']); ?>">
                                    <?php echo getStatusText($appointment['DURUM']); ?>
                                </span>
                            </div>
                        </div>

                    </a>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="text-center text-muted py-4">
                    <i class="fas fa-calendar-xmark mb-3 fa-2x"></i>
                    <p>Bu tarihte randevu bulunmuyor</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Hızlı İşlemler -->
        <div class="quick-actions">
            <a href="new-appointment.php" class="quick-action-btn primary">
                <i class="fas fa-calendar-plus"></i>
                <span>Yeni Randevu</span>
            </a>
            <a href="new-patient.php" class="quick-action-btn success">
                <i class="fas fa-user-plus"></i>
                <span>Yeni Hasta</span>
            </a>
        </div>

        <!-- Ödemeler Bölümü -->
        <div class="mt-4">
            <h4 class="mb-4">
                <i class="fas fa-money-bill-wave me-2"></i>
                Ödemeler Özeti
            </h4>

            <!-- Ödeme İstatistikleri -->
            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <div class="card h-100 border-0 shadow-sm bg-gradient">
                        <div class="card-body d-flex flex-column">
                            <div class="d-flex align-items-center mb-3">
                                <div class="rounded-circle bg-success bg-opacity-25 p-3 me-3">
                                    <i class="fas fa-coins text-success fa-2x"></i>
                                </div>
                                <h6 class="card-title mb-0">Bugünkü Ödemeler</h6>
                            </div>
                            <h3 class="text-success mb-1">
                                <?php echo number_format($bugunOzet['BUGUN_TOPLAM'], 2, ',', '.'); ?> ₺
                            </h3>
                            <small class="text-muted">
                                <i class="fas fa-exchange-alt me-1"></i>
                                <?php echo $bugunOzet['BUGUN_ISLEM']; ?> işlem
                            </small>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card h-100 border-0 shadow-sm bg-gradient">
                        <div class="card-body d-flex flex-column">
                            <div class="d-flex align-items-center mb-3">
                                <div class="rounded-circle bg-danger bg-opacity-25 p-3 me-3">
                                    <i class="fas fa-exclamation-circle text-danger fa-2x"></i>
                                </div>
                                <h6 class="card-title mb-0">Vadesi Geçen</h6>
                            </div>
                            <h3 class="text-danger mb-1">
                                <?php echo number_format($gecikmeOzet['GECIKEN_TUTAR'], 2, ',', '.'); ?> ₺
                            </h3>
                            <small class="text-muted">
                                <i class="fas fa-clock me-1"></i>
                                <?php echo $gecikmeOzet['GECIKEN_TAKSIT']; ?> taksit
                            </small>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card h-100 border-0 shadow-sm bg-gradient">
                        <div class="card-body d-flex flex-column">
                            <div class="d-flex align-items-center mb-3">
                                <div class="rounded-circle bg-primary bg-opacity-25 p-3 me-3">
                                    <i class="fas fa-tasks text-primary fa-2x"></i>
                                </div>
                                <h6 class="card-title mb-0">Hızlı İşlemler</h6>
                            </div>
                            <div class="d-flex gap-2 mt-auto">
                                <a href="all_debts.php" class="btn btn-primary btn-sm flex-grow-1">
                                    <i class="fas fa-list me-1"></i>
                                    Tüm Ödemeler
                                </a>
                                <a href="payment.php" class="btn btn-outline-primary btn-sm flex-grow-1">
                                    <i class="fas fa-plus me-1"></i>
                                    Yeni Ödeme
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Son Ödemeler -->
            <div class="card border-0 shadow-sm">
                <div
                    class="card-header bg-white border-bottom-0 d-flex justify-content-between align-items-center py-3">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-history text-primary me-2"></i>
                        <h5 class="mb-0">Son Ödemeler</h5>
                    </div>
                    <a href="all_debts.php" class="btn btn-outline-primary btn-sm">
                        <i class="fas fa-external-link-alt me-1"></i>
                        Tümünü Gör
                    </a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr class="bg-light">
                                    <th>Tarih</th>
                                    <th>Hasta</th>
                                    <th>Plan</th>
                                    <th>Taksit</th>
                                    <th>Tutar</th>
                                    <th>Ödeme Türü</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($sonOdemeler as $odeme): ?>
                                    <tr class="align-middle">
                                        <td><?php echo $odeme['ODEME_TARIHI_FORMAT']; ?></td>
                                        <td><?php echo htmlspecialchars($odeme['AD_SOYAD']); ?></td>
                                        <td><?php echo htmlspecialchars($odeme['PLAN_ADI']); ?></td>
                                        <td><?php echo $odeme['TAKSIT_NO']; ?>. Taksit</td>
                                        <td class="fw-bold text-success">
                                            <?php echo number_format($odeme['TUTAR'], 2, ',', '.'); ?> ₺
                                        </td>
                                        <td>
                                            <span class="badge bg-<?php echo getOdemeTuruColor($odeme['ODEME_TURU']); ?>">
                                                <?php echo getOdemeTuruText($odeme['ODEME_TURU']); ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include 'includes/nav.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Kaydırma butonları
        const scrollLeftBtn = document.getElementById('scrollLeft');
        const scrollRightBtn = document.getElementById('scrollRight');
        const scrollAmount = 300; // Her tıklamada kaydırma miktarı

        scrollLeftBtn.addEventListener('click', () => {
            slider.scrollBy({
                left: -scrollAmount,
                behavior: 'smooth'
            });
        });

        scrollRightBtn.addEventListener('click', () => {
            slider.scrollBy({
                left: scrollAmount,
                behavior: 'smooth'
            });
        });

        // Günler slider'ı için kaydırma özelliği
        const slider = document.querySelector('.days-slider');
        let isDown = false;
        let startX;
        let scrollLeft;

        // Otomatik olarak seçili güne kaydır
        document.addEventListener('DOMContentLoaded', () => {
            const activeDay = document.querySelector('.day-item.active');
            if (activeDay) {
                // Mobilde direkt ortala, masaüstünde smooth scroll
                if (window.innerWidth <= 767) {
                    activeDay.scrollIntoView({
                        block: 'nearest',
                        inline: 'center'
                    });
                } else {
                    setTimeout(() => {
                        activeDay.scrollIntoView({
                            behavior: 'smooth',
                            block: 'nearest',
                            inline: 'center'
                        });
                    }, 100);
                }
            }
        });
    </script>
</body>

</html>