<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';

$database = new Database();
$db = $database->connect();

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'desc';
$limit = 20;
$offset = ($page - 1) * $limit;

// SQL sorgusu
$sql = "SELECT *, DATE_FORMAT(CREATED_AT, '%d.%m.%Y %H:%i') as KAYIT_TARIHI 
        FROM hastalar";

if (!empty($search)) {
    $sql .= " WHERE (AD_SOYAD LIKE :search OR KIMLIK_NO LIKE :search)";
}

$sql .= " ORDER BY CREATED_AT " . ($sort == 'asc' ? 'ASC' : 'DESC');
$sql .= " LIMIT :limit OFFSET :offset";

$stmt = $db->prepare($sql);
if (!empty($search)) {
    $stmt->bindValue(':search', "%$search%", PDO::PARAM_STR);
}
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();

$patients = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Hiç hasta yoksa boş döndür
if (empty($patients)) {
    exit;
}

// Hastaları listele
foreach ($patients as $patient): ?>
    <div class="patient-item">
        <div class="patient-main" onclick="toggleActions(this)">
            <div class="patient-image">
                <img src="<?php echo !empty($patient['PROFIL_RESMI']) ? 'uploads/profiles/' . $patient['PROFIL_RESMI'] : 'assets/images/default-avatar.jpg'; ?>"
                     alt="<?php echo htmlspecialchars($patient['AD_SOYAD']); ?>"
                     class="rounded-circle patient-profile-image"
                     onclick="showFullImage(this.src)">
            </div>
            <div class="patient-info">
                <div class="patient-name"><?php echo htmlspecialchars($patient['AD_SOYAD']); ?></div>
                <div class="patient-details">
                    <i class="fas fa-phone"></i> <?php echo htmlspecialchars($patient['TELEFON']); ?> •
                    <i class="fas fa-calendar-plus"></i> <?php echo htmlspecialchars($patient['KAYIT_TARIHI']); ?>
                </div>
            </div>
        </div>
        <div class="patient-actions">
            <a href="edit.php?patient=<?php echo $patient['ID']; ?>" class="btn">
                <i class="fas fa-edit"></i>
            </a>
            <a href="gallery.php?patient=<?php echo $patient['ID']; ?>" class="btn">
                <i class="fas fa-camera"></i>
            </a>
            <a href="patient_appointments.php?id=<?php echo $patient['ID']; ?>" class="btn">
                <i class="fas fa-calendar"></i>
            </a>
            <a href="payment.php?patient=<?php echo $patient['ID']; ?>" class="btn">
                <i class="fas fa-dollar-sign"></i>
            </a>
        </div>
    </div>
<?php endforeach;