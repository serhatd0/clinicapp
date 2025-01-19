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
    <script src="assets/js/main.js"></script>
    <style>
        .patient-actions.show {
            display: flex;
        }

        .patient-actions {
            display: none;
            /* Varsayılan olarak gizli */
            padding: 12px 15px;
            gap: 12px;
            background: #f8f9fa;
            border-top: 1px solid #e9ecef;
            width: 100%;
            justify-content: center;
            align-items: center;
        }

        .patient-actions .btn {
            flex: 1;
            padding: 12px 15px;
            color: #495057;
            background: white;
            border: 1px solid #dee2e6;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
        }

        .patient-actions .btn:hover {
            background: #f8f9fa;
            color: #212529;
        }

        .patient-actions .btn i {
            font-size: 1.2rem;
        }

        @media (max-width: 767px) {
            .patient-actions {
                padding: 15px;
                gap: 15px;
            }

            .patient-actions .btn {
                padding: 15px;
            }

            .patient-actions .btn i {
                font-size: 1.3rem;
            }
        }

        .sort-button {
            height: 40px;
            display: flex;
            align-items: center;
            gap: 6px;
            padding: 0 15px;
            white-space: nowrap;
            font-size: 0.9rem;
        }

        .sort-button i {
            font-size: 0.85rem;
        }

        .patient-avatar {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            object-fit: cover;
            margin-right: 15px;
            border: 2px solid #e9ecef;
        }

        .patient-item {
            display: flex;
            flex-direction: column;
            padding: 0;
            border-bottom: 1px solid #e9ecef;
            cursor: pointer;
        }

        .patient-main {
            display: flex;
            align-items: center;
            padding: 15px;
        }

        .patient-info {
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .patient-image {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            object-fit: cover;
            margin-right: 15px;
            border: 2px solid #e9ecef;
            cursor: pointer;
            transition: transform 0.3s ease;
        }

        .patient-image:hover {
            transform: scale(1.1);
        }

        .patient-profile-image {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            object-fit: cover;
            margin-right: 15px;
            border: 2px solid #e9ecef;
            cursor: pointer;
            transition: transform 0.3s ease;
        }

        .patient-profile-image:hover {
            transform: scale(1.1);
        }
    </style>
</head>

<body>
    <?php include 'includes/header.php'; ?>

    <div class="container py-4 content-area bg-white">
        <div class="d-flex justify-content-between align-items-center gap-3 mb-3">
            <div class="search-bar flex-grow-1 me-3">
                <i class="fas fa-search search-icon"></i>
                <input type="text" class="search-input" placeholder="Hasta ara..."
                    value="<?php echo htmlspecialchars($search); ?>">
            </div>
            <a href="?sort=<?php echo $sort == 'desc' ? 'asc' : 'desc'; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>"
                class="btn btn-outline-secondary sort-button">
                <i class="fas fa-sort"></i>
                <?php echo $sort == 'desc' ? 'En Yeni' : 'En Eski'; ?>
            </a>
        </div>

        <div class="patient-list">
            <?php foreach ($patients as $patient): ?>
                <div class="patient-item">
                    <div class="patient-main" onclick="toggleActions(this)">
                        <div class="patient-image">
                            <img src="<?php echo !empty($patient['PROFIL_RESMI']) ? 'uploads/profiles/' . $patient['PROFIL_RESMI'] : 'assets/images/default-avatar.jpg'; ?>"
                                alt="<?php echo htmlspecialchars($patient['AD_SOYAD']); ?>"
                                class="rounded-circle patient-profile-image"
                                style="width: 80px; height: 80px; object-fit: cover; cursor: pointer; transition: transform 0.3s ease;"
                                onclick="showFullImage(this.src)">
                        </div>
                        <div class="patient-info">
                            <div class="patient-name"><?php echo htmlspecialchars($patient['AD_SOYAD']); ?></div>
                            <div class="patient-details">
                                <i class="fas fa-phone"></i> <?php echo htmlspecialchars($patient['TELEFON']); ?> •
                                <i class="fas fa-calendar-plus"></i>
                                <?php echo htmlspecialchars($patient['KAYIT_TARIHI']); ?>
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
            <?php endforeach; ?>
        </div>
        <a href="new-patient.php" class="new-appointment-btn">
            <i class="fas fa-plus" style="margin: 0;"></i>
        </a>
    </div>

    <!-- Quick Actions -->




    <?php include 'includes/nav.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Arama fonksiyonu
        let searchTimer;
        const searchInput = document.querySelector('.search-input');
        const patientList = document.querySelector('.patient-list');

        searchInput.addEventListener('input', function (e) {
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
                if (panel !== element.parentElement.querySelector('.patient-actions')) {
                    panel.classList.remove('show');
                }
            });

            // Tıklanan hastanın action panelini aç/kapat
            element.parentElement.querySelector('.patient-actions').classList.toggle('show');
        }
    </script>
</body>

</html>