<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';

$database = new Database();
$db = $database->connect();

// Seçili tarih veya bugün
$selectedDate = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');

// Randevuları getir
$sql = "SELECT r.*, h.AD_SOYAD FROM randevular r 
        LEFT JOIN hastalar h ON r.HASTA_ID = h.ID 
        WHERE DATE(r.TARIH) = :tarih 
        ORDER BY r.TARIH ASC";
$stmt = $db->prepare($sql);
$stmt->execute([':tarih' => $selectedDate]);
$appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);

$message = '';
if (isset($_GET['message'])) {
    switch ($_GET['message']) {
        case 'created':
            $message = 'Randevu başarıyla oluşturuldu!';
            break;
        // Diğer mesaj durumları buraya eklenebilir
    }
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
    
    <div class="container py-4 content-area">
        <div class="calendar-container">
            <input type="date" class="form-control" value="<?php echo $selectedDate; ?>" 
                   onchange="window.location.href='appointments.php?date=' + this.value">
        </div>

        <div class="appointment-list">
            <?php foreach ($appointments as $appointment): ?>
                <div class="appointment-item">
                    <div class="appointment-time">
                        <?php echo date('H:i', strtotime($appointment['TARIH'])); ?>
                    </div>
                    <div class="appointment-patient">
                        <?php echo htmlspecialchars($appointment['AD_SOYAD']); ?>
                    </div>
                    <div class="appointment-actions">
                        <a href="edit-appointment.php?id=<?php echo $appointment['ID']; ?>" 
                           class="btn btn-sm btn-outline-primary">
                            <i class="fas fa-edit"></i>
                        </a>
                        <div class="appointment-status <?php echo $appointment['DURUM'] == 'onaylandi' ? 'status-confirmed' : 'status-pending'; ?>">
                            <?php echo $appointment['DURUM'] == 'onaylandi' ? 'Onaylandı' : 'Bekliyor'; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Yeni Randevu Butonu -->
        <button class="btn btn-success w-100 mt-3" onclick="window.location.href='new-appointment.php'">
            <i class="fas fa-plus"></i> Yeni Randevu
        </button>
    </div>

    <?php include 'includes/nav.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <div id="alertContainer"></div>

    <?php if ($message): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            showAlert('<?php echo $message; ?>', 'success');
        });
    </script>
    <?php endif; ?>
</body>
</html> 