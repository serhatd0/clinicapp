<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';

$database = new Database();
$db = $database->connect();

// Arama ve sıralama parametreleri
$search = isset($_GET['search']) ? $_GET['search'] : '';
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'desc';

// SQL sorgusunu güncelle
$sql = "SELECT *, DATE_FORMAT(CREATED_AT, '%d.%m.%Y %H:%i') as KAYIT_TARIHI 
        FROM hastalar 
        WHERE (AD_SOYAD LIKE :search OR KIMLIK_NO LIKE :search) 
        ORDER BY CREATED_AT " . ($sort == 'asc' ? 'ASC' : 'DESC');

$stmt = $db->prepare($sql);
$stmt->execute([':search' => "%$search%"]);
$patients = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Hasta listesini döndür
foreach ($patients as $patient): ?>
    <div class="patient-item">
        <div class="patient-info" onclick="toggleActions(this)">
            <div class="patient-name"><?php echo htmlspecialchars($patient['AD_SOYAD']); ?></div>
            <div class="patient-details">
                <i class="fas fa-phone"></i> <?php echo htmlspecialchars($patient['TELEFON']); ?> •
                <i class="fas fa-calendar-plus"></i> <?php echo htmlspecialchars($patient['KAYIT_TARIHI']); ?>
            </div>
        </div>
        <div class="patient-actions">
            <a href="edit.php?patient=<?php echo $patient['ID']; ?>" class="action-button">
                <i class="fas fa-edit"></i>
                <span>Düzenle</span>
            </a>
            <a href="gallery.php?patient=<?php echo $patient['ID']; ?>" class="action-button">
                <i class="fas fa-camera"></i>
                <span>Galeri</span>
            </a>
            <a href="appointments.php?patient=<?php echo $patient['ID']; ?>" class="action-button">
                <i class="fas fa-calendar"></i>
                <span>Randevu</span>
            </a>
            <a href="payment.php?patient=<?php echo $patient['ID']; ?>" class="action-button">
                <i class="fas fa-dollar-sign"></i>
                <span>Ödeme</span>
            </a>
        </div>
    </div>
<?php endforeach; ?> 