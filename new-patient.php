<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';

$success_message = '';
$error_messages = [];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        // Form verilerini kontrol edelim
        error_log('POST Data: ' . print_r($_POST, true));
        
        $errors = validateForm($_POST);
        
        if (empty($errors)) {
            $database = new Database();
            $db = $database->connect();

            if (saveFormData($db, $_POST)) {
                $success_message = "Kayıt başarıyla oluşturuldu!";
                $_POST = array();
            }
        } else {
            $error_messages = $errors;
        }
    } catch (Exception $e) {
        $error_messages[] = $e->getMessage();
        error_log('Form Submission Error: ' . $e->getMessage());
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
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/themes/material_green.css">
    <link href="assets/css/style.css" rel="stylesheet">
    <style>
        .profile-image:hover {
            opacity: 0.8;
            transform: scale(1.05);
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
        }
        
        /* Profil fotoğrafı seçme alanı için overlay efekti */
        label[for="profile_image"] {
            position: relative;
            display: inline-block;
        }
        
        label[for="profile_image"]::after {
            content: '\f030';  /* Kamera ikonu */
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
                                       value="<?php echo isset($_POST['birthDate']) ? htmlspecialchars($_POST['birthDate']) : ''; ?>" 
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
                                <input type="datetime-local" class="form-control" id="registerDate" name="registerDate"
                                       value="<?php echo isset($_POST['registerDate']) ? htmlspecialchars($_POST['registerDate']) : date('Y-m-d\TH:i'); ?>" 
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

                        <!-- Submit Button -->
                        <button type="submit" class="btn btn-success btn-submit w-100">Kaydet</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <?php include 'includes/nav.php'; ?>

    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://npmcdn.com/flatpickr/dist/l10n/tr.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/plugins/monthSelect/index.js"></script>
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
                    showAlert('Dosya boyutu çok büyük. Maksimum 5MB yükleyebilirsiniz.', 'danger');
                    input.value = '';
                    return;
                }
                
                // Dosya tipi kontrolü
                const fileType = input.files[0].type;
                const validTypes = ['image/jpeg', 'image/png', 'image/jpg'];
                if (!validTypes.includes(fileType)) {
                    showAlert('Sadece JPG ve PNG formatları desteklenir.', 'danger');
                    input.value = '';
                    return;
                }
            }
        }
        
        // Form submit öncesi kontrol
        document.querySelector('form').addEventListener('submit', function(e) {
            // Form verilerini konsola yazdır
            const formData = new FormData(this);
            console.log('Form Verileri:');
            for (let [key, value] of formData.entries()) {
                console.log(key, ':', value);
            }
            
            // Dosya bilgilerini konsola yazdır
            const fileInput = document.getElementById('profile_image');
            if (fileInput.files.length > 0) {
                console.log('Yüklenecek Dosya:', {
                    'Dosya Adı': fileInput.files[0].name,
                    'Dosya Boyutu': (fileInput.files[0].size / 1024 / 1024).toFixed(2) + ' MB',
                    'Dosya Tipi': fileInput.files[0].type
                });
            }
            
            return true;
        });
    </script>
</body>
</html> 