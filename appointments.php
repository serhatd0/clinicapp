<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';

$database = new Database();
$db = $database->connect();

// Seçili tarih veya bugün
$selectedDate = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');
$currentDate = new DateTime($selectedDate);

// Haftanın başlangıç ve bitiş tarihlerini hesapla
$weekStart = clone $currentDate;
$weekStart->modify('monday this week');
$weekEnd = clone $weekStart;
$weekEnd->modify('+6 days');

// Haftalık randevuları getir
$sql = "SELECT r.*, h.AD_SOYAD, h.TELEFON 
        FROM randevular r 
        LEFT JOIN hastalar h ON r.HASTA_ID = h.ID 
        WHERE DATE(r.TARIH) BETWEEN :start_date AND :end_date 
        ORDER BY r.TARIH ASC";
$stmt = $db->prepare($sql);
$stmt->execute([
    ':start_date' => $weekStart->format('Y-m-d'),
    ':end_date' => $weekEnd->format('Y-m-d')
]);
$appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Randevuları günlere göre grupla
$appointmentsByDay = [];
foreach ($appointments as $appointment) {
    $date = date('Y-m-d', strtotime($appointment['TARIH']));
    if (!isset($appointmentsByDay[$date])) {
        $appointmentsByDay[$date] = [];
    }
    $appointmentsByDay[$date][] = $appointment;
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Randevular</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container-fluid py-4 content-area">
        <div class="weekly-calendar">
            <div class="calendar-toolbar">
                <div class="calendar-navigation">
                    <button class="btn btn-light" onclick="changeWeek('prev')">
                        <i class="fas fa-chevron-left"></i>
                    </button>
                    <span class="mx-3">
                        <?php echo turkishDate($weekStart->format('Y-m-d')); ?> - 
                        <?php echo turkishDate($weekEnd->format('Y-m-d')); ?>
                    </span>
                    <button class="btn btn-light" onclick="changeWeek('next')">
                        <i class="fas fa-chevron-right"></i>
                    </button>
                </div>
                <div class="calendar-views">
                    <button class="btn" onclick="changeView('day')">Gün</button>
                    <button class="btn active">Hafta</button>
                    <button class="btn" onclick="changeView('month')">Ay</button>
                </div>
            </div>

            <div class="week-header">
                <div class="time-col"></div>
                <?php
                $currentDay = clone $weekStart;
                for ($i = 0; $i < 7; $i++): 
                    $isToday = $currentDay->format('Y-m-d') === date('Y-m-d');
                ?>
                    <div class="day-col <?php echo $isToday ? 'today' : ''; ?>">
                        <div class="day-name"><?php echo $currentDay->format('l'); ?></div>
                        <div class="day-date"><?php echo $currentDay->format('d M'); ?></div>
                    </div>
                <?php 
                    $currentDay->modify('+1 day');
                endfor; 
                ?>
            </div>

            <div class="week-grid">
                <div class="time-slots">
                    <?php 
                    // 09:00'dan 18:00'a kadar 15'er dakika
                    for ($hour = 9; $hour < 18; $hour++) {
                        for ($minute = 0; $minute < 60; $minute += 15) {
                            $isHour = $minute === 0;
                            $timeClass = $isHour ? 'time-slot hour' : 'time-slot';
                            ?>
                            <div class="<?php echo $timeClass; ?>">
                                <?php 
                                if ($isHour) {
                                    echo sprintf('%02d:00', $hour);
                                }
                                ?>
                            </div>
                            <?php
                        }
                    }
                    ?>
                </div>

                <?php
                $currentDay = clone $weekStart;
                for ($i = 0; $i < 7; $i++):
                    $dayDate = $currentDay->format('Y-m-d');
                ?>
                    <div class="day-column">
                        <?php
                        if (isset($appointmentsByDay[$dayDate])) {
                            foreach ($appointmentsByDay[$dayDate] as $index => $appointment) {
                                $startTime = strtotime($appointment['TARIH']);
                                $hour = date('G', $startTime);
                                $minute = (int)date('i', $startTime);
                                
                                // Pozisyonu hesapla
                                $minutesSince9am = ($hour - 9) * 60 + $minute;
                                $top = $minutesSince9am;
                                $height = 45;
                                
                                // Çakışan randevuları bul
                                $overlappingAppointments = [];
                                foreach ($appointmentsByDay[$dayDate] as $checkIndex => $checkAppointment) {
                                    if ($checkIndex === $index) continue;
                                    
                                    $checkStartTime = strtotime($checkAppointment['TARIH']);
                                    $checkEndTime = strtotime('+45 minutes', $checkStartTime);
                                    
                                    // Mevcut randevunun başlangıç ve bitiş zamanlarını hesapla
                                    $startTime = strtotime($appointment['TARIH']);
                                    $endTime = strtotime('+45 minutes', $startTime);
                                    
                                    if ($startTime < $checkEndTime && $checkStartTime < $endTime) {
                                        $overlappingAppointments[] = $checkIndex;
                                    }
                                }
                                
                                // Çakışma sınıfını belirle
                                $overlapClass = '';
                                $overlappingCount = count($overlappingAppointments);
                                
                                if ($overlappingCount === 1) {
                                    $overlapClass = 'overlap-2-' . ($index < min($overlappingAppointments) ? '1' : '2');
                                } elseif ($overlappingCount === 2) {
                                    if ($index === min(array_merge([$index], $overlappingAppointments))) {
                                        $overlapClass = 'overlap-3-1';
                                    } elseif ($index === max(array_merge([$index], $overlappingAppointments))) {
                                        $overlapClass = 'overlap-3-3';
                                    } else {
                                        $overlapClass = 'overlap-3-2';
                                    }
                                }
                                
                                $statusClass = 'status-' . $appointment['DURUM'];
                            ?>
                                <div class="appointment-card <?php echo $statusClass . ' ' . $overlapClass; ?>" 
                                     style="top: <?php echo $minutesSince9am; ?>px; height: <?php echo $height; ?>px;"
                                     onclick="window.location.href='edit-appointment.php?id=<?php echo $appointment['ID']; ?>'">
                                    <div class="time"><?php echo date('H:i', $startTime); ?></div>
                                    <div class="title">
                                        <?php 
                                        echo htmlspecialchars($appointment['AD_SOYAD']);
                                        if (!empty($appointment['NOTLAR'])) {
                                            echo ' - ' . htmlspecialchars($appointment['NOTLAR']);
                                        }
                                        ?>
                                    </div>
                                </div>
                            <?php
                            }
                        }
                        ?>
                    </div>
                <?php
                    $currentDay->modify('+1 day');
                endfor;
                ?>
            </div>
        </div>

        <a href="new-appointment.php" class="new-appointment-btn">
            <i class="fas fa-plus"></i>
        </a>
    </div>

    <?php include 'includes/nav.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function changeWeek(action) {
            const currentDate = new Date('<?php echo $selectedDate; ?>');
            
            if (action === 'prev') {
                currentDate.setDate(currentDate.getDate() - 7);
            } else {
                currentDate.setDate(currentDate.getDate() + 7);
            }
            
            const formattedDate = currentDate.toISOString().split('T')[0];
            window.location.href = 'appointments.php?date=' + formattedDate;
        }

        function changeView(view) {
            // Görünüm değiştirme fonksiyonu (gün/hafta/ay)
            // İleride implement edilecek
        }
    </script>

    <div class="mobile-appointments">
        <?php
        $currentDay = clone $weekStart;
        for ($i = 0; $i < 7; $i++):
            $dayDate = $currentDay->format('Y-m-d');
            if (isset($appointmentsByDay[$dayDate]) && !empty($appointmentsByDay[$dayDate])):
        ?>
            <div class="mobile-date-group">
                <div class="mobile-date-header">
                    <?php 
                    // Türkçe gün ve tarih formatı
                    setlocale(LC_TIME, 'tr_TR.UTF-8');
                    echo strftime('%d %B %A', strtotime($dayDate)); 
                    ?>
                </div>
                <?php 
                // O güne ait randevuları saat sırasına göre sırala
                usort($appointmentsByDay[$dayDate], function($a, $b) {
                    return strtotime($a['TARIH']) - strtotime($b['TARIH']);
                });
                
                foreach ($appointmentsByDay[$dayDate] as $appointment): 
                    $startTime = strtotime($appointment['TARIH']);
                    $duration = isset($appointment['SURE']) ? $appointment['SURE'] : 30; // Varsayılan süre 30 dakika
                    $endTime = date('H:i', strtotime("+{$duration} minutes", $startTime));
                ?>
                    <div class="mobile-appointment-card">
                        <div class="mobile-appointment-time">
                            <?php 
                            echo date('H:i', $startTime) . ' - ' . date('H:i', $endTime); 
                            ?>
                        </div>
                        <div class="mobile-appointment-info">
                            <div class="mobile-appointment-patient">
                                <?php echo htmlspecialchars($appointment['AD_SOYAD']); ?>
                            </div>
                            <div class="mobile-appointment-service">
                                <?php echo htmlspecialchars($appointment['NOTLAR'] ?? ''); ?>
                            </div>
                        </div>
                        <div class="mobile-appointment-actions">
                            <button class="btn-options" onclick="showOptions(<?php echo $appointment['ID']; ?>)">
                                <i class="fas fa-ellipsis-v"></i>
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php
            endif;
            $currentDay->modify('+1 day');
        endfor;
        ?>
    </div>
</body>
</html> 