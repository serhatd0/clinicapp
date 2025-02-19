<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

// Hasta listesi erişim kontrolü
checkPagePermission('hasta_listesi_erisim');

$database = new Database();
$db = $database->connect();

// Sayfalandırma parametreleri
$limit = 20; // Her seferde yüklenecek hasta sayısı
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Arama ve sıralama parametreleri
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'desc';

// SQL sorgusunu oluştur
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

        <div class="patient-list" id="patientList">
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

    <?php include 'includes/nav.php'; ?>

    <!-- JavaScript -->
    <script>
        let searchTimer;
        const searchInput = document.querySelector('.search-input');
        const patientList = document.querySelector('.patient-list');
        let page = 1;
        let loading = false;
        let hasMore = true; // Daha fazla hasta olup olmadığını kontrol etmek için

        // Arama fonksiyonu
        searchInput.addEventListener('input', function(e) {
            clearTimeout(searchTimer);
            const searchTerm = e.target.value.trim();
            page = 1; // Aramada sayfayı resetle
            hasMore = true; // Yeni aramada hasMore'u resetle
            
            searchTimer = setTimeout(() => {
                fetchPatients(searchTerm, page, false);
            }, 500);
        });

        // Sonsuz kaydırma
        window.addEventListener('scroll', function() {
            if (loading || !hasMore) return;

            if ((window.innerHeight + window.scrollY) >= document.body.offsetHeight - 100) {
                page++;
                const searchTerm = searchInput.value.trim();
                fetchPatients(searchTerm, page, true);
            }
        });

        function fetchPatients(searchTerm, pageNum, append = false) {
            loading = true;
            const sort = new URLSearchParams(window.location.search).get('sort') || 'desc';
            const url = `search_patients.php?search=${encodeURIComponent(searchTerm)}&page=${pageNum}&sort=${sort}`;
            
            fetch(url)
                .then(response => response.text())
                .then(html => {
                    if (html.trim() === '') {
                        hasMore = false;
                        return;
                    }

                    if (append) {
                        patientList.insertAdjacentHTML('beforeend', html);
                    } else {
                        patientList.innerHTML = html;
                        hasMore = true; // Yeni arama için reset
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                })
                .finally(() => {
                    loading = false;
                });
        }

        // Hasta kartı tıklama işlevi
        function toggleActions(element) {
            const actionsPanel = element.parentElement.querySelector('.patient-actions');
            
            // Diğer açık panelleri kapat
            document.querySelectorAll('.patient-actions.show').forEach(panel => {
                if (panel !== actionsPanel) {
                    panel.classList.remove('show');
                }
            });

            actionsPanel.classList.toggle('show');
        }

        // Yükleniyor göstergesi için stil
        const style = document.createElement('style');
        style.textContent = `
            .loading-spinner {
                text-align: center;
                padding: 20px;
                display: none;
            }
            .loading-spinner.show {
                display: block;
            }
        `;
        document.head.appendChild(style);

        // Yükleniyor göstergesini ekle
        const spinner = document.createElement('div');
        spinner.className = 'loading-spinner';
        spinner.innerHTML = '<div class="spinner-border text-primary" role="status"><span class="visually-hidden">Yükleniyor...</span></div>';
        patientList.parentNode.insertBefore(spinner, patientList.nextSibling);
    </script>
</body>

</html>