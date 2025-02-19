<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';

$database = new Database();
$db = $database->connect();

$hasta_id = isset($_GET['patient']) ? (int) $_GET['patient'] : 0;

if (!$hasta_id) {
    header('Location: patients.php');
    exit;
}

// Hasta bilgilerini getir
$stmt = $db->prepare("SELECT * FROM hastalar WHERE ID = :id");
$stmt->execute([':id' => $hasta_id]);
$hasta = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$hasta) {
    header('Location: patients.php');
    exit;
}

// Fotoğrafları tarihe göre grupla
$stmt = $db->prepare("SELECT g.*, DATE(g.YUKLENME_TARIHI) as TARIH,
                            (SELECT TARIH_ACIKLAMA 
                             FROM hasta_galerileri 
                             WHERE HASTA_ID = :hasta_id 
                             AND DATE(YUKLENME_TARIHI) = DATE(g.YUKLENME_TARIHI) 
                             LIMIT 1) as TARIH_ACIKLAMA
                     FROM hasta_galerileri g
                     WHERE g.HASTA_ID = :hasta_id 
                     ORDER BY g.YUKLENME_TARIHI DESC");
$stmt->execute([':hasta_id' => $hasta_id]);
$fotograflar = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fotoğrafları tarihe göre grupla
$gruplu_fotograflar = [];
foreach ($fotograflar as $foto) {
    $tarih = $foto['TARIH'];
    if (!isset($gruplu_fotograflar[$tarih])) {
        $gruplu_fotograflar[$tarih] = [];
    }
    $gruplu_fotograflar[$tarih][] = $foto;
}

// Dosya yükleme işlemi
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['photos'])) {
    $uploadDate = isset($_POST['upload_date']) ? $_POST['upload_date'] : date('Y-m-d');
    $dateDescription = $_POST['date_description'] ?? null;
    $uploadDir = 'uploads/patients/' . $hasta_id . '/' . $uploadDate . '/';

    foreach ($_FILES['photos']['tmp_name'] as $key => $tmp_name) {
        if ($_FILES['photos']['error'][$key] == 0) {
            // Fotoğraf bilgilerini hazırla
            $photo = [
                'name' => $_FILES['photos']['name'][$key],
                'type' => $_FILES['photos']['type'][$key],
                'tmp_name' => $_FILES['photos']['tmp_name'][$key],
                'error' => $_FILES['photos']['error'][$key],
                'size' => $_FILES['photos']['size'][$key]
            ];

            try {
                $fileName = saveUploadedPhoto($photo, $uploadDir);
                $filePath = $uploadDir . $fileName;
                $stmt = $db->prepare("INSERT INTO hasta_galerileri 
                                    (HASTA_ID, DOSYA_ADI, DOSYA_YOLU, YUKLENME_TARIHI, TARIH_ACIKLAMA) 
                                    VALUES (:hasta_id, :dosya_adi, :dosya_yolu, :yuklenme_tarihi, :tarih_aciklama)");
                $stmt->execute([
                    ':hasta_id' => $hasta_id,
                    ':dosya_adi' => $fileName,
                    ':dosya_yolu' => $filePath,
                    ':yuklenme_tarihi' => $uploadDate,
                    ':tarih_aciklama' => $dateDescription
                ]);
            } catch (Exception $e) {
                error_log('Fotoğraf yükleme hatası: ' . $e->getMessage());
                continue;
            }
        }
    }
    header('Location: gallery.php?patient=' . $hasta_id . '&message=uploaded');
    exit;
}
?>

<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hasta Galerisi - <?php echo htmlspecialchars($hasta['AD_SOYAD']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/ekko-lightbox/5.3.0/ekko-lightbox.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fancyapps/ui@5.0/dist/fancybox/fancybox.css" />
    <link href="https://cdn.jsdelivr.net/npm/glightbox/dist/css/glightbox.min.css" rel="stylesheet">
    <style>
        .date-info {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .date-info .description {
            color: #666;
            font-style: italic;
            margin-top: 5px;
        }

        .gallery-item img {
            transition: transform 0.3s ease;
        }

        .gallery-item:hover img {
            transform: scale(1.05);
        }

        .photo-checkbox {
            position: absolute;
            top: 10px;
            left: 10px;
            z-index: 2;
        }

        .photo-checkbox .form-check-input {
            width: 22px;
            height: 22px;
            background-color: rgba(255, 255, 255, 0.9);
            border: 2px solid rgba(255, 255, 255, 0.9);
            cursor: pointer;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .photo-checkbox .form-check-input:checked {
            background-color: #28a745;
            border-color: #28a745;
        }

        .delete-selected {
            display: none;
            margin-left: 10px;
        }

        .gallery-item {
            position: relative;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .gallery-item-actions {
            position: absolute;
            top: 10px;
            right: 10px;
            z-index: 2;
        }

        .gallery-item-actions .btn {
            width: 36px;
            height: 36px;
            padding: 0;
            line-height: 36px;
            text-align: center;
            background-color: rgba(220, 53, 69, 0.9);
            color: white;
            border-radius: 4px;
            border: none;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
            font-size: 16px;
        }

        .btn-group .btn {
            width: 40px;
            height: 40px;
            padding: 0;
            line-height: 40px;
            text-align: center;
            border-radius: 4px;
            margin-left: 5px;
            font-size: 18px;
        }

        .btn-group .btn-success {
            background-color: #28a745;
        }

        .btn-group .btn-danger {
            background-color: #dc3545;
        }

        .gallery-item-actions .btn:hover,
        .btn-group .btn:hover {
            opacity: 1;
            transform: scale(1.05);
            transition: all 0.2s ease;
        }

        .select-all-wrapper {
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: #f8f9fa;
            padding: 10px 15px;
            border-radius: 8px;
            margin-bottom: 15px;
        }
    </style>
</head>

<body>
    <?php include 'includes/header.php'; ?>

    <div class="container py-4 content-area">
        <div class="card mb-4">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h2 class="mb-1"><?php echo htmlspecialchars($hasta['AD_SOYAD']); ?></h2>
                        <div class="text-muted">
                            <i class="fas fa-images me-1"></i> Hasta Galerisi
                        </div>
                    </div>
                    <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#uploadModal">
                        <i class="fas fa-plus "></i>
                    </button>
                </div>
            </div>
        </div>

        <?php if (empty($gruplu_fotograflar)): ?>
            <div class="card">
                <div class="card-body text-center py-5">
                    <i class="fas fa-images fa-3x text-muted mb-3"></i>
                    <p class="text-muted mb-0">Henüz fotoğraf eklenmemiş</p>
                </div>
            </div>
        <?php else: ?>
            <?php foreach ($gruplu_fotograflar as $tarih => $fotos): ?>
                <div class="gallery-date-group mb-4">
                    <div class="date-info">
                        <div class="d-flex justify-content-between align-items-center">
                            <h3 class="gallery-date mb-0">
                                <i class="fas fa-calendar-day"></i>
                                <?php echo turkishDate($tarih); ?>
                            </h3>
                            <div class="btn-group" style="margin-left: 10px;">
                                <button class="btn btn-sm btn-success mr-2" onclick="quickUpload('<?php echo $tarih; ?>')"
                                    title="Bu tarihe fotoğraf ekle">
                                    <i class="fas fa-plus"></i>
                                </button>
                                <button class="btn btn-sm btn-danger" onclick="deleteAllPhotos('<?php echo $tarih; ?>')"
                                    title="Bu tarihin tüm fotoğraflarını sil">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                        <?php if (!empty($fotos[0]['TARIH_ACIKLAMA'])): ?>
                            <div class="description">
                                <?php echo nl2br(htmlspecialchars($fotos[0]['TARIH_ACIKLAMA'])); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="row g-3">
                        <div class="col-12 mb-2">
                            <div class="select-all-wrapper">
                                <div class="form-check">
                                    <input class="form-check-input select-all" type="checkbox"
                                        data-date="<?php echo $tarih; ?>">
                                    <label class="form-check-label">Tümünü Seç</label>
                                </div>
                                <button class="btn btn-danger btn-sm delete-selected" data-date="<?php echo $tarih; ?>">
                                    <i class="fas fa-trash-alt me-1"></i> Seçilenleri Sil
                                </button>
                            </div>
                        </div>
                        <?php foreach ($fotos as $foto): ?>
                            <div class="col-6 col-md-4 col-lg-3">
                                <div class="gallery-item">
                                    <div class="form-check photo-checkbox">
                                        <input class="form-check-input photo-select" type="checkbox"
                                            value="<?php echo $foto['ID']; ?>" data-date="<?php echo $tarih; ?>">
                                    </div>
                                    <a href="<?php echo $foto['DOSYA_YOLU']; ?>" class="glightbox"
                                        data-gallery="gallery-<?php echo $tarih; ?>"
                                        data-description="<?php echo turkishDate($tarih); ?>">
                                        <img src="<?php echo $foto['DOSYA_YOLU']; ?>" class="img-fluid" alt="Hasta Fotoğrafı"
                                            data-photo-id="<?php echo $foto['ID']; ?>">
                                    </a>
                                    <div class="gallery-item-actions">
                                        <div class="btn-group">
                                            <button class="btn btn-sm btn-success mr-2" onclick="makeProfilePhoto(<?php echo $foto['ID']; ?>)"
                                                title="Profil Fotoğrafı Yap">
                                                <i class="fas fa-user-circle"></i>
                                            </button>
                                            <button class="btn btn-danger" onclick="deletePhoto(<?php echo $foto['ID']; ?>)"
                                                title="Sil">
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Fotoğraf Yükleme Modal -->
    <div class="modal fade" id="uploadModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Fotoğraf Yükle</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" enctype="multipart/form-data" id="uploadForm">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Yükleme Tarihi</label>
                            <input type="date" class="form-control" name="upload_date"
                                value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Tarih Açıklaması</label>
                            <textarea class="form-control" name="date_description" rows="3"
                                placeholder="Bu tarihe ait genel bir açıklama yazın..."></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Fotoğraflar</label>
                            <input type="file" class="form-control" name="photos[]" multiple accept="image/*" required
                                id="photoInput">
                        </div>
                        <div id="photoPreview" class="row g-3">
                            <!-- Fotoğraf önizlemeleri burada gösterilecek -->
                        </div>
                    </div>
                    <div class="modal-footer" style="background: white;">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-upload me-1"></i>Yükle
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Hızlı Yükleme Modalı -->
    <div class="modal fade" id="quickUploadModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Fotoğraf Ekle</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" enctype="multipart/form-data" id="quickUploadForm">
                    <div class="modal-body" style="max-height: 70vh; overflow-y: auto;">
                        <input type="hidden" name="upload_date" id="quickUploadDate">
                        <div class="mb-3">
                            <label class="form-label">Fotoğraflar</label>
                            <input type="file" class="form-control" name="photos[]" multiple accept="image/*" required>
                        </div>
                        <div id="quickPhotoPreview" class="row g-3">
                            <!-- Fotoğraf önizlemeleri burada gösterilecek -->
                        </div>
                    </div>
                    <div class="modal-footer" style="background: white;">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-upload me-1"></i>Yükle
                        </button>
                    </div>
                    <div style="height: 80px;"></div>
                </form>
            </div>
        </div>
    </div>

    <!-- Silme Onay Modalı -->
    <div class="modal fade" id="deleteConfirmModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-0">
                    <h5 class="modal-title">Fotoğrafı Sil</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center pb-4">
                    <i class="fas fa-exclamation-triangle text-warning fa-3x mb-3"></i>
                    <p class="mb-1 delete-message">Bu fotoğrafı silmek istediğinizden emin misiniz?</p>
                    <small class="text-muted">Bu işlem geri alınamaz.</small>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                    <button type="button" class="btn btn-danger" id="confirmDelete">
                        <i class="fas fa-trash-alt me-2"></i>Sil
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Profil Fotoğrafı Onay Modalı -->
    <div class="modal fade" id="profilePhotoModal" tabindex="-1">
        <div class="modal-dialog modal-sm">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Profil Fotoğrafı</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Bu fotoğrafı profil fotoğrafı yapmak istediğinizden emin misiniz?</p>
                    <div class="text-center mb-3">
                        <img id="previewProfilePhoto" src="" alt="Profil Fotoğrafı" 
                             style="max-width: 200px; max-height: 200px; border-radius: 8px;">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                    <button type="button" class="btn btn-success" id="confirmProfilePhoto">
                        <i class="fas fa-check me-1"></i>Onayla
                    </button>
                </div>
            </div>
        </div>
    </div>

    <?php include 'includes/nav.php'; ?>

    <!-- Önce jQuery yüklenmeli -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <!-- Sonra Bootstrap ve diğer bağımlılıklar -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/ekko-lightbox/5.3.0/ekko-lightbox.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@fancyapps/ui@5.0/dist/fancybox/fancybox.umd.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/glightbox/dist/js/glightbox.min.js"></script>

    <!-- En son kendi JS dosyamız -->
    <script src="assets/js/main.js"></script>

    <script>
        // GLightbox ayarları
        const lightbox = GLightbox({
            touchNavigation: true,
            loop: true,
            autoplayVideos: true,
            zoomable: true,
            draggable: true,
            openEffect: 'zoom',
            closeEffect: 'fade',
            cssEfects: {
                fade: { in: 'fadeIn', out: 'fadeOut' },
                zoom: { in: 'zoomIn', out: 'zoomOut' }
            }
        });

        // Fotoğraf önizleme
        document.getElementById('photoInput').addEventListener('change', function (e) {
            const container = document.getElementById('photoPreview');
            container.innerHTML = '';

            Array.from(this.files).forEach((file) => {
                const reader = new FileReader();
                reader.onload = function (e) {
                    const col = document.createElement('div');
                    col.className = 'col-md-4';
                    col.innerHTML = `
                        <div class="card">
                            <img src="${e.target.result}" class="card-img-top" alt="Önizleme">
                        </div>
                    `;
                    container.appendChild(col);
                }
                reader.readAsDataURL(file);
            });
        });

        function deletePhoto(id) {
            showDeleteConfirm('Bu fotoğrafı silmek istediğinizden emin misiniz?', function () {
                window.location.href = 'delete_photo.php?id=' + id;
            });
        }

        // Hızlı yükleme fonksiyonu
        function quickUpload(date) {
            document.getElementById('quickUploadDate').value = date;
            const quickUploadModal = new bootstrap.Modal(document.getElementById('quickUploadModal'));
            quickUploadModal.show();
        }

        // Hızlı yükleme önizleme
        document.querySelector('#quickUploadModal input[type="file"]').addEventListener('change', function (e) {
            const container = document.getElementById('quickPhotoPreview');
            container.innerHTML = '';

            Array.from(this.files).forEach((file) => {
                const reader = new FileReader();
                reader.onload = function (e) {
                    const col = document.createElement('div');
                    col.className = 'col-6';
                    col.innerHTML = `
                        <div class="card">
                            <img src="${e.target.result}" class="card-img-top" alt="Önizleme">
                        </div>
                    `;
                    container.appendChild(col);
                }
                reader.readAsDataURL(file);
            });
        });

        // Silme işlemleri için modal ve yönetimi
        const deleteModal = new bootstrap.Modal(document.getElementById('deleteConfirmModal'));
        let deleteCallback = null;

        function showDeleteConfirm(message, callback) {
            document.querySelector('.delete-message').textContent = message;
            deleteCallback = callback;
            deleteModal.show();
        }

        document.getElementById('confirmDelete').addEventListener('click', function () {
            if (deleteCallback) {
                deleteCallback();
                deleteModal.hide();
            }
        });

        // Tarih bazlı toplu silme
        function deleteAllPhotos(date) {
            showDeleteConfirm('Bu tarihe ait tüm fotoğrafları silmek istediğinizden emin misiniz?', function () {
                window.location.href = 'delete_photo.php?date=' + date + '&patient=<?php echo $hasta_id; ?>';
            });
        }

        // Çoklu seçim işlemleri
        document.querySelectorAll('.select-all').forEach(checkbox => {
            checkbox.addEventListener('change', function () {
                const date = this.dataset.date;
                const photoCheckboxes = document.querySelectorAll(`.photo-select[data-date="${date}"]`);
                const deleteButton = document.querySelector(`.delete-selected[data-date="${date}"]`);

                photoCheckboxes.forEach(cb => cb.checked = this.checked);
                deleteButton.style.display = this.checked ? 'block' : 'none';
            });
        });

        document.querySelectorAll('.photo-select').forEach(checkbox => {
            checkbox.addEventListener('change', function () {
                const date = this.dataset.date;
                const deleteButton = document.querySelector(`.delete-selected[data-date="${date}"]`);
                const checkedPhotos = document.querySelectorAll(`.photo-select[data-date="${date}"]:checked`);
                const selectAll = document.querySelector(`.select-all[data-date="${date}"]`);
                const allPhotos = document.querySelectorAll(`.photo-select[data-date="${date}"]`);

                deleteButton.style.display = checkedPhotos.length > 0 ? 'block' : 'none';
                selectAll.checked = checkedPhotos.length === allPhotos.length;
                selectAll.indeterminate = checkedPhotos.length > 0 && checkedPhotos.length < allPhotos.length;
            });
        });

        document.querySelectorAll('.delete-selected').forEach(button => {
            button.addEventListener('click', function () {
                const date = this.dataset.date;
                const checkedPhotos = document.querySelectorAll(`.photo-select[data-date="${date}"]:checked`);
                const photoIds = Array.from(checkedPhotos).map(cb => cb.value);

                if (photoIds.length) {
                    showDeleteConfirm(`${photoIds.length} fotoğrafı silmek istediğinizden emin misiniz?`, function () {
                        window.location.href = 'delete_photo.php?ids=' + photoIds.join(',');
                    });
                }
            });
        });

        function rotateImage(imageId, direction) {
            // Döndürme işlemi için loading göster
            const imgElement = document.querySelector(`[data-photo-id="${imageId}"]`);
            if (imgElement) {
                imgElement.style.opacity = '0.5';
            }

            // AJAX isteği gönder
            fetch('rotate_image.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    id: imageId,
                    direction: direction
                })
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Sayfayı yenilemeden resmi güncelle
                        const img = document.querySelector(`[data-photo-id="${imageId}"]`);
                        if (img) {
                            // Cache'i önlemek için timestamp ekle
                            const newSrc = img.src.split('?')[0] + '?t=' + new Date().getTime();
                            img.src = newSrc;
                            img.style.opacity = '1';
                        }
                    } else {
                        alert('Resim döndürülürken bir hata oluştu');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Bir hata oluştu');
                });
        }

        // Profil fotoğrafı modal kontrolü
        const profilePhotoModal = new bootstrap.Modal(document.getElementById('profilePhotoModal'));
        let currentPhotoId = null;

        function makeProfilePhoto(photoId) {
            // Fotoğraf önizlemesini ayarla
            const imgElement = document.querySelector(`[data-photo-id="${photoId}"]`);
            const previewImg = document.getElementById('previewProfilePhoto');
            previewImg.src = imgElement.src;
            
            // Fotoğraf ID'sini sakla
            currentPhotoId = photoId;
            
            // Modalı göster
            profilePhotoModal.show();
        }

        // Onay butonuna tıklandığında
        document.getElementById('confirmProfilePhoto').addEventListener('click', function() {
            const button = this;
            const originalText = button.innerHTML;
            
            // Butonu devre dışı bırak ve yükleniyor göster
            button.disabled = true;
            button.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>İşleniyor...';
            
            fetch('make_profile_photo.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    photo_id: currentPhotoId,
                    patient_id: <?php echo $hasta_id; ?>
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Modalı kapat
                    profilePhotoModal.hide();
                    
                    // Başarı mesajı göster
                    showAlert('Profil fotoğrafı başarıyla güncellendi', 'success');
                    
                    // Sayfayı yenile
                    setTimeout(() => {
                        location.reload();
                    }, 1500);
                } else {
                    throw new Error(data.message || 'Bir hata oluştu');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showAlert('Bir hata oluştu: ' + error.message, 'danger');
                
                // Butonu eski haline getir
                button.disabled = false;
                button.innerHTML = originalText;
            });
        });

        // Fotoğraf yükleme formları için yükleme durumu kontrolü
        document.getElementById('uploadForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Yükle butonunu bul
            const submitButton = this.querySelector('button[type="submit"]');
            const originalText = submitButton.innerHTML;
            
            // Butonu devre dışı bırak ve yükleniyor göster
            submitButton.disabled = true;
            submitButton.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Yükleniyor...';
            
            // Formu gönder
            this.submit();
        });

        // Hızlı yükleme formu için de aynı işlemi yap
        document.getElementById('quickUploadForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Yükle butonunu bul
            const submitButton = this.querySelector('button[type="submit"]');
            const originalText = submitButton.innerHTML;
            
            // Butonu devre dışı bırak ve yükleniyor göster
            submitButton.disabled = true;
            submitButton.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Yükleniyor...';
            
            // Formu gönder
            this.submit();
        });
    </script>
</body>

</html>