<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';

$database = new Database();
$db = $database->connect();

// Seçili tarih veya bugün
$selectedDate = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');
$currentDate = new DateTime($selectedDate);

// Görünüm tipini al
$view = $_GET['view'] ?? 'week';

// Tarihleri görünüm tipine göre ayarla
switch($view) {
    case 'day':
        $startDate = $currentDate->format('Y-m-d');
        $endDate = $startDate;
        break;
    case 'month':
        $currentDate->modify('first day of this month');
        $startDate = $currentDate->format('Y-m-d');
        $currentDate->modify('last day of this month');
        $endDate = $currentDate->format('Y-m-d');
        break;
    default: // week
        $weekStart = clone $currentDate;
        $weekStart->modify('monday this week');
        $weekEnd = clone $weekStart;
        $weekEnd->modify('+6 days');
        $startDate = $weekStart->format('Y-m-d');
        $endDate = $weekEnd->format('Y-m-d');
        break;
}

// Randevuları getir
$sql = "SELECT r.*, h.AD_SOYAD, h.TELEFON 
        FROM randevular r 
        LEFT JOIN hastalar h ON r.HASTA_ID = h.ID 
        WHERE DATE(r.TARIH) BETWEEN :start_date AND :end_date 
        ORDER BY r.TARIH ASC";
$stmt = $db->prepare($sql);
$stmt->execute([
    ':start_date' => $startDate,
    ':end_date' => $endDate
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
                    <button class="btn" onclick="changeWeek('prev')">
                        <i class="fas fa-chevron-left"></i>
                    </button>
                    <div class="date-picker-container">
                        <input type="date" 
                               class="form-control date-picker" 
                               value="<?php echo $selectedDate; ?>" 
                               onchange="goToDate(this.value)"
                               style="display: none;">
                        <span class="mx-3 selected-date" onclick="showDatePicker()">
                            <?php echo turkishDate($currentDate->format('Y-m-d')); ?>
                        </span>
                    </div>
                    <button class="btn" onclick="changeWeek('next')">
                        <i class="fas fa-chevron-right"></i>
                    </button>
                </div>
            </div>

            <div class="week-header">
                <div class="time-col"></div>
                <?php
                switch($view) {
                    case 'day':
                        // Tek gün gösterimi
                        $isToday = $currentDate->format('Y-m-d') === date('Y-m-d');
                        ?>
                        <div class="day-col <?php echo $isToday ? 'today' : ''; ?>">
                            <div class="day-name"><?php echo getTurkishDayName($currentDate); ?></div>
                            <div class="day-date"><?php echo $currentDate->format('d M'); ?></div>
                        </div>
                        <?php
                        break;
                        
                    case 'month':
                        // Ayın tüm günlerini göster
                        $firstDay = clone $currentDate;
                        $firstDay->modify('first day of this month');
                        $lastDay = clone $firstDay;
                        $lastDay->modify('last day of this month');
                        
                        for($day = clone $firstDay; $day <= $lastDay; $day->modify('+1 day')):
                            $isToday = $day->format('Y-m-d') === date('Y-m-d');
                            ?>
                            <div class="day-col <?php echo $isToday ? 'today' : ''; ?>">
                                <div class="day-name"><?php echo getTurkishDayName($day); ?></div>
                                <div class="day-date"><?php echo $day->format('d M'); ?></div>
                            </div>
                            <?php
                        endfor;
                        break;
                        
                    default: // week
                        // Haftalık görünüm
                        $weekStart = clone $currentDate;
                        $weekStart->modify('monday this week');
                        
                        for ($i = 0; $i < 7; $i++): 
                            $currentDay = clone $weekStart;
                            $currentDay->modify("+$i days");
                            $isToday = $currentDay->format('Y-m-d') === date('Y-m-d');
                            ?>
                            <div class="day-col <?php echo $isToday ? 'today' : ''; ?>">
                                <div class="day-name"><?php echo getTurkishDayName($currentDay); ?></div>
                                <div class="day-date"><?php echo $currentDay->format('d') . ' ' . getTurkishMonth($currentDay); ?></div>
                            </div>
                            <?php
                        endfor;
                        break;
                }
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
            <i class="fas fa-plus" style="margin: 0;"></i>
        </a>
    </div>

    <?php include 'includes/nav.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function changeView(view) {
            const currentUrl = new URL(window.location.href);
            currentUrl.searchParams.set('view', view);
            window.location.href = currentUrl.toString();
        }

        function changeWeek(action) {
            const currentDate = new Date('<?php echo $selectedDate; ?>');
            const view = '<?php echo $_GET['view'] ?? 'week'; ?>';
            
            switch(view) {
                case 'day':
                    if (action === 'prev') {
                        currentDate.setDate(currentDate.getDate() - 1);
                    } else {
                        currentDate.setDate(currentDate.getDate() + 1);
                    }
                    break;
                case 'month':
                    if (action === 'prev') {
                        currentDate.setMonth(currentDate.getMonth() - 1);
                    } else {
                        currentDate.setMonth(currentDate.getMonth() + 1);
                    }
                    break;
                default: // week
                    if (action === 'prev') {
                        currentDate.setDate(currentDate.getDate() - 7);
                    } else {
                        currentDate.setDate(currentDate.getDate() + 7);
                    }
                    break;
            }
            
            const formattedDate = currentDate.toISOString().split('T')[0];
            const currentUrl = new URL(window.location.href);
            currentUrl.searchParams.set('date', formattedDate);
            window.location.href = currentUrl.toString();
        }

        function changeDate(action) {
            const currentDate = new Date('<?php echo $selectedDate; ?>');
            
            if (action === 'prev') {
                currentDate.setDate(currentDate.getDate() - 1);
            } else {
                currentDate.setDate(currentDate.getDate() + 1);
            }
            
            const formattedDate = currentDate.toISOString().split('T')[0];
            window.location.href = 'appointments.php?date=' + formattedDate;
        }

        function showDatePicker() {
            const datePicker = document.querySelector('.date-picker');
            datePicker.showPicker(); // Modern tarayıcılar için
        }

        function goToDate(date) {
            window.location.href = 'appointments.php?date=' + date;
        }

        // Mobil görünüm için de aynı fonksiyonları ekleyelim
        document.addEventListener('DOMContentLoaded', function() {
            // Mobil header'a da tarih seçici ekleyelim
            const mobileHeader = document.querySelector('.mobile-header');
            const dateText = mobileHeader.querySelector('h5');
            
            const datePickerContainer = document.createElement('div');
            datePickerContainer.className = 'date-picker-container';
            
            const datePicker = document.createElement('input');
            datePicker.type = 'date';
            datePicker.className = 'form-control date-picker';
            datePicker.value = '<?php echo $selectedDate; ?>';
            datePicker.onchange = function() { goToDate(this.value); };
            
            datePickerContainer.appendChild(datePicker);
            datePickerContainer.appendChild(dateText);
            
            // Eski h5'i kaldırıp yeni container'ı ekleyelim
            dateText.parentNode.replaceChild(datePickerContainer, dateText);
        });

        function showMobileForm() {
            document.querySelector('.mobile-new-appointment').classList.add('show');
        }

        function closeMobileForm() {
            document.querySelector('.mobile-new-appointment').classList.remove('show');
        }

        // Mobilde randevu butonuna tıklandığında formu göster
        document.querySelector('.new-appointment-btn').addEventListener('click', function(e) {
            if (window.innerWidth <= 767) {
                e.preventDefault();
                showMobileForm();
            }
        });
    </script>

    <div class="mobile-appointments">
        <div class="mobile-header">
            <button class="btn" onclick="changeDate('prev')">
                <i class="fas fa-chevron-left"></i>
            </button>
            <div class="date-picker-container">
                <input type="date" 
                       class="form-control date-picker" 
                       value="<?php echo $selectedDate; ?>" 
                       onchange="goToDate(this.value)"
                       style="display: none;">
                <div class="selected-date" onclick="showDatePicker()">
                    <?php echo turkishDate($selectedDate); ?>
                </div>
            </div>
            <button class="btn" onclick="changeDate('next')">
                <i class="fas fa-chevron-right"></i>
            </button>
        </div>

        <?php
        // Önceki ay kontrolü
        $firstAppointmentDate = reset($appointments)['TARIH'] ?? null;
        if ($firstAppointmentDate) {
            $firstMonth = date('Y-m', strtotime($firstAppointmentDate));
            $currentMonth = date('Y-m', strtotime($selectedDate));
            if ($firstMonth < $currentMonth) {
                echo '<div class="month-divider">Önceki Ay</div>';
            }
        }

        // Seçili güne ait randevuları göster
        $dayDate = $selectedDate;
        if (isset($appointmentsByDay[$dayDate]) && !empty($appointmentsByDay[$dayDate])):
            echo '<div class="date-header"></div>';
            
            usort($appointmentsByDay[$dayDate], function($a, $b) {
                return strtotime($a['TARIH']) - strtotime($b['TARIH']);
            });
            
            foreach ($appointmentsByDay[$dayDate] as $appointment): 
                $startTime = strtotime($appointment['TARIH']);
                $endTime = strtotime('+45 minutes', $startTime);
                $statusClass = 'status-' . $appointment['DURUM'];
            ?>
                <div class="mobile-appointment-card <?php echo $statusClass; ?> mb-2" 
                     onclick="window.location.href='edit-appointment.php?id=<?php echo $appointment['ID']; ?>'">
                    <div class="mobile-appointment-time">
                        <?php echo date('H:i', $startTime); ?> - <?php echo date('H:i', $endTime); ?>
                    </div>
                    <div class="mobile-appointment-info">
                        <div class="mobile-appointment-patient">
                            <?php echo htmlspecialchars($appointment['AD_SOYAD']); ?>
                        </div>
                        <?php if (!empty($appointment['NOTLAR'])): ?>
                            <div class="mobile-appointment-service">
                                <?php echo htmlspecialchars($appointment['NOTLAR']); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="mobile-appointment-actions">
                        <i class="fas fa-ellipsis-v"></i>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="no-appointments">
                <i class="fas fa-calendar-times mb-2"></i>
                <p>Bu tarihte randevu bulunmuyor</p>
            </div>
        <?php endif; 
        
        // Sonraki ay kontrolü
        $lastAppointmentDate = end($appointments)['TARIH'] ?? null;
        if ($lastAppointmentDate) {
            $lastMonth = date('Y-m', strtotime($lastAppointmentDate));
            $currentMonth = date('Y-m', strtotime($selectedDate));
            if ($lastMonth > $currentMonth) {
                echo '<div class="month-divider">Sonraki Ay</div>';
            }
        }
        ?>
    </div>

    <!-- Mobil randevu ekleme formu -->
    <div class="mobile-new-appointment">
        <div class="form-header">
            <div class="form-title">Yeni Randevu</div>
            <button type="button" class="close-btn" onclick="closeMobileForm()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <form id="mobileNewAppointmentForm" method="POST" action="new-appointment.php">
            <div class="form-group">
                <label>Hasta</label>
                <select class="form-control" name="patient_id" required>
                    <option value="">Hasta Seçin</option>
                    <?php foreach($patients as $patient): ?>
                        <option value="<?php echo $patient['ID']; ?>">
                            <?php echo htmlspecialchars($patient['AD_SOYAD']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Tarih</label>
                <input type="date" class="form-control" name="appointment_date" required>
            </div>
            <div class="form-group">
                <label>Saat</label>
                <input type="time" class="form-control" name="appointment_time" required>
            </div>
            <button type="submit" class="submit-btn">
                Randevu Oluştur
            </button>
        </form>
    </div>
</body>
</html> 