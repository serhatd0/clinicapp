<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

$database = new Database();
$db = $database->connect();

// Arama ve sıralama parametreleri
$search = isset($_GET['search']) ? $_GET['search'] : '';
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'desc'; // Varsayılan olarak en yeni kayıtlar

// SQL sorgusunu güncelle
$sql = "SELECT *, DATE_FORMAT(CREATED_AT, '%d.%m.%Y %H:%i') as KAYIT_TARIHI 
        FROM hastalar 
        WHERE (AD_SOYAD LIKE :search OR KIMLIK_NO LIKE :search) 
        ORDER BY CREATED_AT " . ($sort == 'asc' ? 'ASC' : 'DESC');

$stmt = $db->prepare($sql);
$stmt->execute([':search' => "%$search%"]);
$patients = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hasta Listesi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container py-4 content-area bg-white">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div class="search-bar flex-grow-1 me-3">
                <i class="fas fa-search search-icon"></i>
                <input type="text" class="search-input" placeholder="Hasta ara..." 
                       value="<?php echo htmlspecialchars($search); ?>">
            </div>
            <a href="?sort=<?php echo $sort == 'desc' ? 'asc' : 'desc'; ?><?php echo $search ? '&search='.urlencode($search) : ''; ?>" 
               class="btn btn-outline-secondary">
                <i class="fas fa-sort"></i> 
                <?php echo $sort == 'desc' ? 'En Yeni' : 'En Eski'; ?>
            </a>
        </div>

        <div class="patient-list">
            <?php foreach ($patients as $patient): ?>
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
        </div>
    </div>
    
    <?php include 'includes/nav.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Arama fonksiyonu
        let searchTimer;
        const searchInput = document.querySelector('.search-input');
        const patientList = document.querySelector('.patient-list');

        searchInput.addEventListener('input', function(e) {
            clearTimeout(searchTimer);
            const searchTerm = e.target.value;
            
            // 500ms bekle ve sonra aramayı yap
            searchTimer = setTimeout(() => {
                fetchPatients(searchTerm);
            }, 500);
        });

        function fetchPatients(searchTerm) {
            fetch(`search_patients.php?search=${encodeURIComponent(searchTerm)}`)
                .then(response => response.text())
                .then(html => {
                    patientList.innerHTML = html;
                })
                .catch(error => console.error('Error:', error));
        }

        function toggleActions(element) {
            // Tüm açık action panellerini kapat
            document.querySelectorAll('.patient-actions.show').forEach(panel => {
                if (panel !== element.nextElementSibling) {
                    panel.classList.remove('show');
                }
            });
            
            // Tıklanan hastanın action panelini aç/kapat
            element.nextElementSibling.classList.toggle('show');
        }
    </script>
</body>
</html> 