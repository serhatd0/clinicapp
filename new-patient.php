<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

// Hasta ekleme yetkisi kontrolü
checkPagePermission('hasta_ekle');

$success_message = '';
$error_messages = [];

$database = new Database();
$db = $database->connect();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        // Yetki kontrolü tekrar yapılır
        if (!hasPermission('hasta_ekle')) {
            throw new Exception("Bu işlem için yetkiniz bulunmamaktadır.");
        }

        // Form verilerini kontrol edelim
        error_log('POST Data: ' . print_r($_POST, true));
        
        $errors = validateForm($_POST);
        
        if (empty($errors)) {
            $db->beginTransaction();
            try {
                // Hasta bilgilerini kaydet
                $stmt = $db->prepare("
                    INSERT INTO hastalar (
                        AD_SOYAD, KIMLIK_TURU, KIMLIK_NO, DOGUM_TARIHI,
                        CINSIYET, TELEFON, EMAIL, REFERANS,
                        ACIKLAMA, STATUS, CREATED_AT, PROFIL_RESMI
                    ) VALUES (
                        :ad_soyad, :kimlik_turu, :kimlik_no, :dogum_tarihi,
                        :cinsiyet, :telefon, :email, :referans,
                        :aciklama, 1, NOW(), :profil_resmi
                    )
                ");
                
                // Profil fotoğrafını işle
                $profilResmi = null;
                if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] == 0) {
                    $uploadDir = 'uploads/profiles/';
                    $photo = [
                        'name' => $_FILES['profile_image']['name'],
                        'type' => $_FILES['profile_image']['type'],
                        'tmp_name' => $_FILES['profile_image']['tmp_name'],
                        'error' => $_FILES['profile_image']['error'],
                        'size' => $_FILES['profile_image']['size']
                    ];
                    
                    try {
                        $profilResmi = saveUploadedPhoto($photo, $uploadDir);
                    } catch (Exception $e) {
                        error_log('Profil fotoğrafı yükleme hatası: ' . $e->getMessage());
                    }
                }
                
                $stmt->execute([
                    ':ad_soyad' => $_POST['fullName'],
                    ':kimlik_turu' => $_POST['idType'],
                    ':kimlik_no' => $_POST['idNumber'],
                    ':dogum_tarihi' => $_POST['birthDate'],
                    ':cinsiyet' => $_POST['gender'],
                    ':telefon' => $_POST['phone'],
                    ':email' => $_POST['email'],
                    ':referans' => $_POST['reference'],
                    ':aciklama' => $_POST['notes'],
                    ':profil_resmi' => $profilResmi
                ]);
                
                // Yeni eklenen hastanın ID'sini al
                $hastaId = $db->lastInsertId();
                
                // Fotoğrafları kaydet
                if (isset($_FILES['photos']) && !empty($_FILES['photos']['name'][0])) {
                    $uploadDir = 'uploads/patients/' . $hastaId . '/' . date('Y-m-d') . '/';
                    
                    foreach ($_FILES['photos']['tmp_name'] as $key => $tmp_name) {
                        if ($_FILES['photos']['error'][$key] == 0) {
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
                                
                                // Fotoğraf bilgilerini veritabanına kaydet
                                $stmt = $db->prepare("
                                    INSERT INTO hasta_galerileri (
                                        HASTA_ID, DOSYA_ADI, DOSYA_YOLU, 
                                        YUKLENME_TARIHI
                                    ) VALUES (
                                        :hasta_id, :dosya_adi, :dosya_yolu, 
                                        NOW()
                                    )
                                ");
                                
                                $stmt->execute([
                                    ':hasta_id' => $hastaId,
                                    ':dosya_adi' => $fileName,
                                    ':dosya_yolu' => $filePath
                                ]);
                            } catch (Exception $e) {
                                $error_messages[] = 'Fotoğraf yükleme hatası: ' . $e->getMessage();
                                $db->rollBack();
                                throw $e;
                            }
                        }
                    }
                }
                
                // Transaction'ı onayla
                $db->commit();
                
                header('Location: patient_appointments.php?id=' . $hastaId);
                exit;
            } catch (Exception $e) {
                // Hata durumunda transaction'ı geri al
                if ($db->inTransaction()) {
                    $db->rollBack();
                }
                error_log($e->getMessage());
                $error_messages[] = "Bir hata oluştu: " . $e->getMessage();
            }
        } else {
            $error_messages = $errors;
        }
    } catch (Exception $e) {
        $error_messages[] = $e->getMessage();
        error_log('Form Submission Error: ' . $e->getMessage());
    }
} else {
    // Sayfa ilk yüklendiğinde veya POST olmayan isteklerde
    if (isset($_GET['error'])) {
        switch ($_GET['error']) {
            case 'incomplete':
                $error_messages[] = "Lütfen tüm zorunlu alanları doldurun.";
                break;
            case 'validation':
                $error_messages[] = "Form bilgilerinde hata var, lütfen kontrol edin.";
                break;
            case 'photo':
                $error_messages[] = "Fotoğraf yüklenirken bir hata oluştu.";
                break;
            default:
                $error_messages[] = "Bir hata oluştu, lütfen tekrar deneyin.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Yeni Kayıt</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">

    <link href="assets/css/style.css" rel="stylesheet">
    <style>
        .profile-image:hover {
            opacity: 0.8;
            transform: scale(1.05);
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);
        }

        label[for="profile_image"] {
            position: relative;
            display: inline-block;
        }

        label[for="profile_image"]::after {
            content: '\f030';
            font-family: 'Font Awesome 5 Free';
            font-weight: 900;
            position: absolute;
            bottom: 25px;
            right: 0;
            background: #fff;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #0d6efd;
            border: 2px solid #e9ecef;
            opacity: 0;
            transition: all 0.3s ease;
        }

        label[for="profile_image"]:hover::after {
            opacity: 1;
        }
        
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div id="alertContainer"></div>
    
    <?php
    // PHP alert verilerini JavaScript'e aktaralım
    if ($success_message || !empty($error_messages)): 
    ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            <?php if ($success_message): ?>
                showAlert('<?php echo $success_message; ?>', 'success');
            <?php endif; ?>

            <?php if (!empty($error_messages)): ?>
                showAlert('<?php echo implode("<br>", $error_messages); ?>', 'danger');
            <?php endif; ?>
        });
    </script>
    <?php endif; ?>

    <div class="container-fluid py-4 content-area">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6">
                <div class="form-container">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <a href="patients.php" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left"></i> Geri
                        </a>
                    </div>

                    <form method="POST" action="" class="needs-validation" novalidate enctype="multipart/form-data">
                        <!-- Profil Fotoğrafı Seçimi -->
                        <div class="text-center mb-4">
                            <label for="profile_image" style="cursor: pointer;">
                                <img src="assets/images/default-avatar.jpg" 
                                    alt="Profil" 
                                    class="profile-image mb-3"
                                    id="profilePreview"
                                    style="width: 120px; height: 120px; border-radius: 50%; object-fit: cover; border: 3px solid #e9ecef; transition: all 0.3s ease;">
                            </label>
                            <div>
                                <input type="file" 
                                    id="profile_image" 
                                    name="profile_image" 
                                    class="d-none" 
                                    accept="image/jpeg,image/png,image/jpg"
                                    onchange="previewImage(this)">
                            </div>
                        </div>

                        <!-- Kişisel Bilgiler -->
                        <div class="form-section">
                            <h3 class="form-section-title">Kişisel Bilgiler</h3>
                            
                            <div class="mb-3">
                                <label for="fullName" class="form-label">Ad Soyad</label>
                                <input type="text" 
                                       class="form-control" 
                                       id="fullName" 
                                       name="fullName" 
                                       inputmode="text"
                                       value="<?php echo isset($_POST['fullName']) ? htmlspecialchars($_POST['fullName']) : ''; ?>" 
                                       pattern="[A-Za-zğüşıöçĞÜŞİÖÇ\s]+" 
                                       title="Sadece harf kullanabilirsiniz"
                                       placeholder="Adınız ve Soyadınız" 
                                       onkeypress="return /[A-Za-zğüşıöçĞÜŞİÖÇ\s]/i.test(event.key)"
                                       oninput="this.value = this.value.replace(/[^A-Za-zğüşıöçĞÜŞİÖÇ\s]/g, '')"
                                       required>
                                <div class="invalid-feedback">Lütfen sadece harf kullanın</div>
                            </div>

                            <div class="mb-3">
                                <label for="birthDate" class="form-label">Doğum Tarihi</label>
                                <input type="date" class="form-control" id="birthDate" name="birthDate"
                                    value="" 
                                    required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label d-block">Cinsiyet</label>
                                <div class="d-flex flex-wrap gap-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="gender" id="female" 
                                               value="kadin" <?php echo (isset($_POST['gender']) && $_POST['gender'] == 'kadin') ? 'checked' : ''; ?> 
                                               required>
                                        <label class="form-check-label" for="female">Kadın</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="gender" id="male" 
                                               value="erkek" <?php echo (isset($_POST['gender']) && $_POST['gender'] == 'erkek') ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="male">Erkek</label>
                                    </div>
                                </div>
                            </div>

                           
                        </div>

                        <!-- Kimlik Bilgileri -->
                        <div class="form-section">
                            <h3 class="form-section-title mb-4">Kimlik Bilgileri</h3>
                            
                            <div class="mb-3">
                                <div class="d-flex flex-wrap gap-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="idType" id="tcType" 
                                               value="tc" <?php echo (!isset($_POST['idType']) || $_POST['idType'] == 'tc') ? 'checked' : ''; ?>
                                               required>
                                        <label class="form-check-label" for="tcType">TC Kimlik</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="idType" id="passportType" 
                                               value="passport" <?php echo (isset($_POST['idType']) && $_POST['idType'] == 'passport') ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="passportType">Pasaport</label>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="idNumber" class="form-label" id="idNumberLabel">TC Kimlik Numarası</label>
                                <input type="text" 
                                       class="form-control" 
                                       id="idNumber" 
                                       name="idNumber"
                                       value="<?php echo isset($_POST['idNumber']) ? htmlspecialchars($_POST['idNumber']) : ''; ?>" 
                                       maxlength="11"
                                       pattern="[0-9]{11}"
                                       inputmode="numeric"
                                       oninput="this.value = this.value.replace(/[^0-9]/g, '')"
                                       placeholder="Kimlik numaranızı giriniz" 
                                       required>
                                <div class="invalid-feedback" id="idNumberFeedback">Lütfen 11 haneli TC Kimlik numaranızı giriniz</div>
                            </div>

                            <script>
                                document.querySelectorAll('input[name="idType"]').forEach(radio => {
                                    radio.addEventListener('change', function() {
                                        const idInput = document.getElementById('idNumber');
                                        const idLabel = document.getElementById('idNumberLabel');
                                        const idFeedback = document.getElementById('idNumberFeedback');
                                        
                                        if (this.value === 'tc') {
                                            idInput.pattern = '[0-9]{11}';
                                            idInput.maxLength = '11';
                                            idInput.value = idInput.value.replace(/[^0-9]/g, '');
                                            idInput.oninput = function() { this.value = this.value.replace(/[^0-9]/g, ''); };
                                            idLabel.textContent = 'TC Kimlik Numarası';
                                            idInput.placeholder = 'Kimlik numaranızı giriniz';
                                            idFeedback.textContent = 'Lütfen 11 haneli TC Kimlik numaranızı giriniz';
                                        } else {
                                            idInput.pattern = '[A-Z0-9]{7,9}';
                                            idInput.maxLength = '9';
                                            idInput.oninput = null;
                                            idInput.inputmode = 'text';
                                            idLabel.textContent = 'Pasaport Numarası';
                                            idInput.placeholder = 'Pasaport numaranızı giriniz';
                                            idFeedback.textContent = 'Geçerli bir pasaport numarası giriniz';
                                        }
                                        idInput.value = '';
                                    });
                                });
                            </script>
                        </div>

                        <!-- İletişim Bilgileri -->
                        <div class="form-section">
                            <h3 class="form-section-title">İletişim Bilgileri</h3>
                            
                            <div class="mb-3">
                                <label for="phone" class="form-label">Telefon Numarası</label>
                                <div class="input-group">
                                    <input type="text" class="form-control" id="phone" name="phone"
                                           value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>" 
                                           placeholder="5XX XXX XX XX" 
                                           required 
                                           pattern="[0-9]{11}" 
                                           inputmode="numeric"
                                           oninput="this.value = this.value.replace(/[^0-9]/g, '')"
                                           maxlength="11">
                                    <button class="btn btn-success" type="button">Doğrula</button>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="email" class="form-label">E-posta Adresi</label>
                                <input type="email" class="form-control" id="email" name="email"
                                       value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" 
                                       placeholder="ornek@email.com">
                            </div>
                        </div>

                        <!-- Diğer Bilgiler -->
                        <div class="form-section">
                            <h3 class="form-section-title">Diğer Bilgiler</h3>
                            
                            <div class="mb-3">
                                <label for="reference" class="form-label">Referans</label>
                                <select class="form-select" id="reference" name="reference">
                                    <option value="" selected disabled>Referans seçiniz</option>
                                    <option value="1" <?php echo (isset($_POST['reference']) && $_POST['reference'] == '1') ? 'selected' : ''; ?>>
                                        Referans 1
                                    </option>
                                    <option value="2" <?php echo (isset($_POST['reference']) && $_POST['reference'] == '2') ? 'selected' : ''; ?>>
                                        Referans 2
                                    </option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="emptyField" class="form-label">Ek Bilgi</label>
                                <textarea class="form-control" id="emptyField" name="emptyField" rows="3"
                                          placeholder="Eklemek istediğiniz bilgiler..."><?php echo isset($_POST['emptyField']) ? htmlspecialchars($_POST['emptyField']) : ''; ?></textarea>
                            </div>
                        </div>

                        <div class="mb-3">
                                <label for="registerDate" class="form-label">Kayıt Tarihi</label>
                                <input type="datetime-local" 
                                       class="form-control" 
                                       id="registerDate" 
                                       name="registerDate" 
                                       value="<?php echo date('Y-m-d\TH:i'); ?>"
                                       required>
                            </div>

                        <!-- Onay -->
                        <div class="form-section">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="terms" name="terms" required>
                                <label class="form-check-label" for="terms">
                                    Verdiğim tüm bilgilerin doğru olduğunu onaylıyorum.
                                </label>
                            </div>
                        </div>

                        <!-- Hasta fotoğrafı yükleme butonu -->
                        <?php if (hasPermission('hasta_duzenle')): ?>
                            <div class="mb-3">
                                <label for="photos" class="form-label">Hasta Fotoğrafları</label>
                                <input type="file" class="form-control" id="photos" name="photos[]" 
                                       accept="image/jpeg,image/png,image/jpg" multiple>
                                <small class="text-muted">Birden fazla fotoğraf seçebilirsiniz</small>
                            </div>
                        <?php endif; ?>

                        <!-- Hasta bilgilerini kaydetme butonu -->
                        <?php if (hasPermission('hasta_ekle')): ?>
                            <button type="submit" class="btn btn-success btn-submit w-100">
                                <i class="fas fa-save"></i> Kaydet
                            </button>
                        <?php endif; ?>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <?php include 'includes/nav.php'; ?>

    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Custom JS -->
    <script src="assets/js/main.js"></script>
    <script>
        function previewImage(input) {
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    document.getElementById('profilePreview').src = e.target.result;
                    // Seçilen dosya bilgilerini konsola yazdır
                    const file = input.files[0];
                    console.log('Dosya Bilgileri:', {
                        'Dosya Adı': file.name,
                        'Dosya Boyutu': (file.size / 1024 / 1024).toFixed(2) + ' MB',
                        'Dosya Tipi': file.type
                    });
                }
                
                reader.readAsDataURL(input.files[0]);
                
                // Dosya boyutu kontrolü
                const fileSize = input.files[0].size / 1024 / 1024; // MB cinsinden
                if (fileSize > 5) {
                    showAlert('Dosya boyutu 5MB\'dan büyük olamaz', 'danger');
                    input.value = '';
                    return;
                }
                
                // Dosya tipi kontrolü
                const fileType = input.files[0].type;
                const validTypes = ['image/jpeg', 'image/png', 'image/jpg'];
                if (!validTypes.includes(fileType)) {
                    showAlert('Sadece JPG ve PNG formatları desteklenir', 'danger');
                    input.value = '';
                    return;
                }
            }
        }
        
        // Alert gösterme fonksiyonu
        function showAlert(message, type = 'success') {
            const alertDiv = document.createElement('div');
            alertDiv.className = `floating-alert alert alert-${type} alert-dismissible fade show`;
            alertDiv.innerHTML = `
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            document.body.appendChild(alertDiv);
            
            // 5 saniye sonra otomatik kaybolsun
            setTimeout(() => {
                alertDiv.remove();
            }, 5000);
        }
        
        // Form submit öncesi kontrol
        document.querySelector('form').addEventListener('submit', function(e) {
            // Zorunlu alanları kontrol et
            const requiredFields = ['fullName', 'idNumber', 'birthDate', 'gender', 'phone'];
            let hasError = false;
            
            let emptyField = '';
            requiredFields.forEach(field => {
                const input = this.querySelector(`[name="${field}"]`);
                if (!input.value.trim()) {
                    emptyField = field;
                }
            });
            if (emptyField) {
                showAlert('Lütfen boş alan bırakmayın', 'danger');
                hasError = true;
            }
            
            if (hasError) {
                e.preventDefault();
                return;
            }
            
            // Profil fotoğrafı kontrolü
            const profileInput = document.querySelector('input[name="profile_image"]');
            if (profileInput && profileInput.files.length > 0) {
                const file = profileInput.files[0];
                if (file.size > 5 * 1024 * 1024) {
                    showAlert('Profil fotoğrafı 5MB\'dan büyük olamaz', 'danger');
                    e.preventDefault();
                    return;
                }
                
                const fileType = file.type;
                const validTypes = ['image/jpeg', 'image/png', 'image/jpg'];
                if (!validTypes.includes(fileType)) {
                    showAlert('Profil fotoğrafı için sadece JPG ve PNG formatları desteklenir', 'danger');
                    e.preventDefault();
                    return;
                }
            }
            
            // Diğer fotoğrafların kontrolü
            const fileInput = document.querySelector('input[type="file"]');
            if (fileInput.files.length > 0) {
                for (let i = 0; i < fileInput.files.length; i++) {
                    const file = fileInput.files[i];
                    
                    // Dosya boyutu kontrolü
                    if (file.size > 5 * 1024 * 1024) {
                        showAlert('Dosya boyutu 5MB\'dan büyük olamaz', 'danger');
                        e.preventDefault();
                        return;
                    }
                    
                    // Dosya tipi kontrolü
                    const fileType = file.type;
                    const validTypes = ['image/jpeg', 'image/png', 'image/jpg'];
                    if (!validTypes.includes(fileType)) {
                        showAlert('Sadece JPG ve PNG formatları desteklenir', 'danger');
                        e.preventDefault();
                        return;
                    }
                }
            }
        });
    </script>
</body>
</html> 