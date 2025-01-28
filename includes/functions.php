<?php

function validateForm($data)
{
    $errors = [];

    // Ad Soyad kontrolü
    if (empty($data['fullName'])) {
        $errors[] = "Ad Soyad alanı zorunludur.";
    }

    // Kimlik türü kontrolü
    if (empty($data['idType'])) {
        $errors[] = "Kimlik türü seçimi zorunludur.";
    } else {
        // TC Kimlik / Pasaport kontrolü
        if (empty($data['idNumber'])) {
            $errors[] = "Kimlik numarası zorunludur.";
        } else {
            if ($data['idType'] == 'tc') {
                if (!validateTcNumber($data['idNumber'])) {
                    $errors[] = "Geçerli bir TC Kimlik numarası giriniz.";
                }
            } else {
                if (!validatePassportNumber($data['idNumber'])) {
                    $errors[] = "Geçerli bir Pasaport numarası giriniz.";
                }
            }
        }
    }

    // Email kontrolü
    /*if(!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Geçerli bir e-posta adresi giriniz.";
    }*/

    // Diğer zorunlu alanların kontrolü
    if (empty($data['birthDate']))
        $errors[] = "Doğum tarihi zorunludur.";
    if (empty($data['gender']))
        $errors[] = "Cinsiyet seçimi zorunludur.";
    if (empty($data['phone']))
        $errors[] = "Telefon numarası zorunludur.";

    return $errors;
}

function validateTcNumber($tcno)
{
    // Sadece 11 haneli sayı kontrolü
    return preg_match('/^[0-9]{11}$/', $tcno);
}

function validatePassportNumber($passport)
{
    return preg_match('/^[A-Z0-9]{7,9}$/', $passport);
}

function saveFormData($db, $data)
{
    try {
        $profileImage = 'default-avatar.jpg'; // Varsayılan değer

        // Profil resmi yükleme işlemi
        if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = 'uploads/profiles/';

            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            $allowedTypes = ['image/jpeg', 'image/png', 'image/jpg'];
            if (!in_array($_FILES['profile_image']['type'], $allowedTypes)) {
                throw new Exception('Sadece JPG ve PNG formatları desteklenir.');
            }

            if ($_FILES['profile_image']['size'] > 5 * 1024 * 1024) {
                throw new Exception('Dosya boyutu çok büyük. Maksimum 5MB yükleyebilirsiniz.');
            }

            $extension = pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION);
            $fileName = uniqid() . '.' . $extension;
            $filePath = $uploadDir . $fileName;

            if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $filePath)) {
                // EXIF bilgisini al ve resmi düzelt
                $image = imagecreatefromstring(file_get_contents($filePath));
                if ($extension != 'png') {
                    $exif = @exif_read_data($filePath);
                    if (!empty($exif['Orientation'])) {
                        switch ($exif['Orientation']) {
                            case 3:
                                $image = imagerotate($image, 180, 0);
                                break;
                            case 6:
                                $image = imagerotate($image, -90, 0);
                                break;
                            case 8:
                                $image = imagerotate($image, 90, 0);
                                break;
                        }
                    }
                }

                // Boyutları al ve yeniden boyutlandır
                $width = imagesx($image);
                $height = imagesy($image);

                // Maksimum boyutlar
                $maxWidth = 800;
                $maxHeight = 800;

                // Yeni boyutları hesapla
                if ($width > $maxWidth || $height > $maxHeight) {
                    $ratio = min($maxWidth / $width, $maxHeight / $height);
                    $newWidth = round($width * $ratio);
                    $newHeight = round($height * $ratio);

                    $resized = imagecreatetruecolor($newWidth, $newHeight);

                    // PNG şeffaflığını koru
                    if ($extension == 'png') {
                        imagealphablending($resized, false);
                        imagesavealpha($resized, true);
                    }

                    imagecopyresampled($resized, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
                    $image = $resized;
                }

                // Resmi kaydet
                switch (strtolower($extension)) {
                    case 'jpg':
                    case 'jpeg':
                        imagejpeg($image, $filePath, 85);
                        break;
                    case 'png':
                        imagepng($image, $filePath, 8);
                        break;
                }

                imagedestroy($image);
                $profileImage = $fileName;
            }
        } else {
            error_log('No file uploaded or upload error: ' . print_r($_FILES['profile_image']['error'] ?? 'not set', true));
        }

        $sql = "INSERT INTO hastalar (
            AD_SOYAD,
            KIMLIK_TURU,
            KIMLIK_NO,
            DOGUM_TARIHI,
            CINSIYET,
            TELEFON,
            EMAIL,
            REFERANS,
            ACIKLAMA,
            PROFIL_RESMI,
            CREATED_AT
        ) VALUES (
            :ad_soyad,
            :kimlik_turu,
            :kimlik_no,
            :dogum_tarihi,
            :cinsiyet,
            :telefon,
            :email,
            :referans,
            :aciklama,
            :profil_resmi,
            :created_at
        )";

        $params = [
            ':ad_soyad' => trim($data['fullName']),
            ':kimlik_turu' => trim($data['idType']),
            ':kimlik_no' => trim($data['idNumber']),
            ':dogum_tarihi' => trim($data['birthDate']),
            ':cinsiyet' => trim($data['gender']),
            ':telefon' => trim($data['phone']),
            ':email' => trim($data['email']),
            ':referans' => isset($data['reference']) ? trim($data['reference']) : null,
            ':aciklama' => isset($data['emptyField']) ? trim($data['emptyField']) : null,
            ':profil_resmi' => $profileImage,
            ':created_at' => !empty($data['registerDate']) ? $data['registerDate'] : date('Y-m-d H:i:s')
        ];

        error_log('SQL Parametreleri: ' . print_r($params, true));

        $stmt = $db->prepare($sql);
        $result = $stmt->execute($params);

        if (!$result) {
            throw new PDOException("Kayıt işlemi başarısız oldu.");
        }

        return true;

    } catch (PDOException $e) {
        error_log('Database Error: ' . $e->getMessage());
        throw new Exception("Veritabanı hatası: " . $e->getMessage());
    }
}

function validateUpdateForm($data)
{
    $errors = [];

    // Ad Soyad kontrolü
    if (empty($data['fullName'])) {
        $errors[] = "Ad Soyad alanı zorunludur.";
    }

    // Kimlik kontrolü
    if (empty($data['idNumber'])) {
        $errors[] = "Kimlik numarası zorunludur.";
    } else {
        if ($data['idType'] == 'tc' && !validateTcNumber($data['idNumber'])) {
            $errors[] = "Geçerli bir TC Kimlik numarası giriniz.";
        } else if ($data['idType'] == 'passport' && !validatePassportNumber($data['idNumber'])) {
            $errors[] = "Geçerli bir Pasaport numarası giriniz.";
        }
    }

    // Diğer zorunlu alanların kontrolü
    if (empty($data['birthDate']))
        $errors[] = "Doğum tarihi zorunludur.";
    if (empty($data['gender']))
        $errors[] = "Cinsiyet seçimi zorunludur.";
    if (empty($data['phone']))
        $errors[] = "Telefon numarası zorunludur.";

    return $errors;
}

function updatePatient($db, $patientId, $data)
{
    try {
        // Mevcut profil resmini veritabanından al
        $stmt = $db->prepare("SELECT PROFIL_RESMI FROM hastalar WHERE ID = :id");
        $stmt->execute([':id' => $patientId]);
        $profileImage = $stmt->fetchColumn();

        if (!$profileImage) {
            $profileImage = 'default-avatar.jpg';
        }

        // Yeni profil resmi yükleme işlemi
        if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = 'uploads/profiles/';
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            $allowedTypes = ['image/jpeg', 'image/png', 'image/jpg'];
            if (!in_array($_FILES['profile_image']['type'], $allowedTypes)) {
                throw new Exception('Sadece JPG ve PNG formatları desteklenir.');
            }

            if ($_FILES['profile_image']['size'] > 5 * 1024 * 1024) {
                throw new Exception('Dosya boyutu çok büyük. Maksimum 5MB yükleyebilirsiniz.');
            }

            $extension = pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION);
            $fileName = uniqid() . '.' . $extension;
            $filePath = $uploadDir . $fileName;

            if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $filePath)) {
                // EXIF bilgisini al ve resmi düzelt
                $image = imagecreatefromstring(file_get_contents($filePath));
                if ($extension != 'png') {
                    $exif = @exif_read_data($filePath);
                    if (!empty($exif['Orientation'])) {
                        switch ($exif['Orientation']) {
                            case 3:
                                $image = imagerotate($image, 180, 0);
                                break;
                            case 6:
                                $image = imagerotate($image, -90, 0);
                                break;
                            case 8:
                                $image = imagerotate($image, 90, 0);
                                break;
                        }
                    }
                }

                $width = imagesx($image);
                $height = imagesy($image);

                // Maksimum boyutlar
                $maxWidth = 800;
                $maxHeight = 800;

                // Yeni boyutları hesapla
                if ($width > $maxWidth || $height > $maxHeight) {
                    $ratio = min($maxWidth / $width, $maxHeight / $height);
                    $newWidth = round($width * $ratio);
                    $newHeight = round($height * $ratio);

                    $resized = imagecreatetruecolor($newWidth, height: $newHeight);

                    // PNG şeffaflığını koru
                    if ($extension == 'png') {
                        imagealphablending($resized, false);
                        imagesavealpha($resized, true);
                    }

                    imagecopyresampled($resized, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
                    $image = $resized;
                }

                // Resmi kaydet
                switch (strtolower($extension)) {
                    case 'jpg':
                    case 'jpeg':
                        imagejpeg($image, $filePath, 85);
                        break;
                    case 'png':
                        imagepng($image, $filePath, 8);
                        break;
                }

                imagedestroy($image);

                // Eski fotoğrafı sil (default-avatar.jpg değilse)
                if ($profileImage != 'default-avatar.jpg' && file_exists($uploadDir . $profileImage)) {
                    unlink($uploadDir . $profileImage);
                }
                $profileImage = $fileName;
            }
        }

        $sql = "UPDATE hastalar SET 
            AD_SOYAD = :ad_soyad,
            KIMLIK_TURU = :kimlik_turu,
            KIMLIK_NO = :kimlik_no,
            DOGUM_TARIHI = :dogum_tarihi,
            CINSIYET = :cinsiyet,
            TELEFON = :telefon,
            EMAIL = :email,
            REFERANS = :referans,
            ACIKLAMA = :aciklama,
            PROFIL_RESMI = :profil_resmi,
            CREATED_AT = :created_at
            WHERE ID = :id";

        $params = [
            ':ad_soyad' => trim($data['fullName']),
            ':kimlik_turu' => trim($data['idType']),
            ':kimlik_no' => trim($data['idNumber']),
            ':dogum_tarihi' => trim($data['birthDate']),
            ':cinsiyet' => trim($data['gender']),
            ':telefon' => trim($data['phone']),
            ':email' => trim($data['email']),
            ':referans' => isset($data['reference']) ? trim($data['reference']) : null,
            ':aciklama' => isset($data['emptyField']) ? trim($data['emptyField']) : null,
            ':profil_resmi' => $profileImage,
            ':created_at' => !empty($data['registerDate']) ? $data['registerDate'] : date('Y-m-d H:i:s'),
            ':id' => $patientId
        ];

        $stmt = $db->prepare($sql);
        return $stmt->execute($params);

    } catch (Exception $e) {
        error_log($e->getMessage());
        throw new Exception("Güncelleme hatası: " . $e->getMessage());
    }
}

function getAvailableTimeSlots()
{
    $slots = [];
    $start = new DateTime('09:00');
    $end = new DateTime('18:00');
    $interval = new DateInterval('PT15M'); // 15 dakikalık aralıklar

    while ($start < $end) {
        $slots[] = $start->format('H:i');
        $start->add($interval);
    }

    return $slots;
}

function validateAppointmentTime($time)
{
    $appointmentTime = new DateTime($time);
    $startTime = new DateTime('09:00');
    $endTime = new DateTime('18:00');

    return $appointmentTime >= $startTime && $appointmentTime < $endTime;
}

function turkishDate($date)
{
    $aylar = array(
        'January' => 'Ocak',
        'February' => 'Şubat',
        'March' => 'Mart',
        'April' => 'Nisan',
        'May' => 'Mayıs',
        'June' => 'Haziran',
        'July' => 'Temmuz',
        'August' => 'Ağustos',
        'September' => 'Eylül',
        'October' => 'Ekim',
        'November' => 'Kasım',
        'December' => 'Aralık'
    );

    return strtr(date('d F Y', strtotime($date)), $aylar);
}

function saveUploadedPhoto($file, $uploadDir)
{
    try {
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $allowedTypes = ['image/jpeg', 'image/png', 'image/jpg'];
        if (!in_array($file['type'], $allowedTypes)) {
            throw new Exception('Sadece JPG ve PNG formatları desteklenir.');
        }

        if ($file['size'] > 5 * 1024 * 1024) {
            throw new Exception('Dosya boyutu çok büyük. Maksimum 5MB yükleyebilirsiniz.');
        }

        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $fileName = uniqid() . '.' . $extension;
        $filePath = $uploadDir . $fileName;

        if (move_uploaded_file($file['tmp_name'], $filePath)) {
            // Görüntüyü doğru şekilde yükle
            switch ($extension) {
                case 'jpg':
                case 'jpeg':
                    $image = imagecreatefromjpeg($filePath);
                    break;
                case 'png':
                    $image = imagecreatefrompng($filePath);
                    break;
                default:
                    throw new Exception('Desteklenmeyen dosya formatı');
            }

            // EXIF yönlendirmesini kontrol et ve düzelt
            if ($extension != 'png') {
                $exif = @exif_read_data($filePath);
                if ($exif && isset($exif['Orientation'])) {
                    switch ($exif['Orientation']) {
                        case 3:
                            $image = imagerotate($image, 180, 0);
                            break;
                        case 6:
                            $image = imagerotate($image, -90, 0);
                            break;
                        case 8:
                            $image = imagerotate($image, 90, 0);
                            break;
                    }
                }
            }

            $width = imagesx($image);
            $height = imagesy($image);

            // Maksimum boyutlar
            $maxWidth = 1200;
            $maxHeight = 1200;

            // Yeni boyutları hesapla
            if ($width > $maxWidth || $height > $maxHeight) {
                $ratio = min($maxWidth / $width, $maxHeight / $height);
                $newWidth = round($width * $ratio);
                $newHeight = round($height * $ratio);

                $resized = imagecreatetruecolor($newWidth, $newHeight);

                // PNG şeffaflığını koru
                if ($extension == 'png') {
                    imagealphablending($resized, false);
                    imagesavealpha($resized, true);
                    $transparent = imagecolorallocatealpha($resized, 0, 0, 0, 127);
                    imagefilledrectangle($resized, 0, 0, $newWidth, $newHeight, $transparent);
                }

                imagecopyresampled($resized, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
                imagedestroy($image);
                $image = $resized;
            }

            // Resmi kaydet
            switch ($extension) {
                case 'jpg':
                case 'jpeg':
                    imagejpeg($image, $filePath, 90);
                    break;
                case 'png':
                    imagepng($image, $filePath, 6);
                    break;
            }

            imagedestroy($image);

            // Dosya izinlerini ayarla
            chmod($filePath, 0644);

            return $fileName;
        }

        throw new Exception('Dosya yüklenirken bir hata oluştu.');
    } catch (Exception $e) {
        error_log('Fotoğraf kaydetme hatası: ' . $e->getMessage());
        // Hata durumunda dosyayı temizle
        if (isset($filePath) && file_exists($filePath)) {
            unlink($filePath);
        }
        throw $e;
    }
}

function createAppointmentSeries($db, $patientId, $startDate, $startTime)
{
    try {
        // Şablonları getir
        $stmt = $db->query("SELECT * FROM randevu_sablonlari ORDER BY SIRA ASC");
        $templates = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Ana randevu ID'sini tutacak değişken
        $mainAppointmentId = null;

        foreach ($templates as $index => $template) {
            // POST'tan gelen tarih ve saati kullan
            $appointmentDate = $_POST['appointment_dates'][$index] ?? $startDate;
            $appointmentTime = $_POST['appointment_times'][$index] ?? $startTime;

            // Randevu tarih ve saatini birleştir
            $appointmentDateTime = $appointmentDate . ' ' . $appointmentTime;

            // Randevuyu kaydet
            $stmt = $db->prepare("INSERT INTO randevular (
                HASTA_ID, 
                TARIH, 
                DURUM, 
                NOTLAR,
                SABLON_ID,
                ANA_RANDEVU_ID,
                CREATED_AT
            ) VALUES (
                :hasta_id,
                :tarih,
                'bekliyor',
                :notlar,
                :sablon_id,
                :ana_randevu_id,
                NOW()
            )");

            $params = [
                ':hasta_id' => $patientId,
                ':tarih' => $appointmentDateTime,
                ':notlar' => $template['ISLEM_ADI'],
                ':sablon_id' => $template['ID'],
                ':ana_randevu_id' => $mainAppointmentId
            ];

            $stmt->execute($params);

            if ($mainAppointmentId === null) {
                $mainAppointmentId = $db->lastInsertId();
                $stmt = $db->prepare("UPDATE randevular SET ANA_RANDEVU_ID = :id WHERE ID = :id");
                $stmt->execute([':id' => $mainAppointmentId]);
            }
        }

        return true;
    } catch (Exception $e) {
        error_log($e->getMessage());
        return false;
    }
}

function createAppointment($db, $patientId, $date, $time, $isRecurring = false)
{
    try {
        if ($isRecurring) {
            return createAppointmentSeries($db, $patientId, $date, $time);
        } else {
            // Tek randevu oluştur
            $appointmentDateTime = $date . ' ' . $time;

            $stmt = $db->prepare("INSERT INTO randevular (
                HASTA_ID, 
                TARIH, 
                DURUM,
                CREATED_AT
            ) VALUES (
                :hasta_id,
                :tarih,
                'bekliyor',
                NOW()
            )");

            return $stmt->execute([
                ':hasta_id' => $patientId,
                ':tarih' => $appointmentDateTime
            ]);
        }
    } catch (Exception $e) {
        error_log($e->getMessage());
        return false;
    }
}

function getTurkishDayName($date)
{
    $days = [
        'Monday' => 'Pazartesi',
        'Tuesday' => 'Salı',
        'Wednesday' => 'Çarşamba',
        'Thursday' => 'Perşembe',
        'Friday' => 'Cuma',
        'Saturday' => 'Cumartesi',
        'Sunday' => 'Pazar'
    ];

    return $days[$date->format('l')] ?? $date->format('l');
}

function getTurkishMonth($date)
{
    $months = [
        '01' => 'Ocak',
        '02' => 'Şubat',
        '03' => 'Mart',
        '04' => 'Nisan',
        '05' => 'Mayıs',
        '06' => 'Haziran',
        '07' => 'Temmuz',
        '08' => 'Ağustos',
        '09' => 'Eylül',
        '10' => 'Ekim',
        '11' => 'Kasım',
        '12' => 'Aralık'
    ];

    return $months[$date->format('m')] ?? $date->format('M');
}

function savePatient($db, $data)
{
    try {
        $profileImage = null;

        // Profil resmi yükleme işlemi
        if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = 'uploads/profiles/';
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            $allowedTypes = ['image/jpeg', 'image/png', 'image/jpg'];
            if (!in_array($_FILES['profile_image']['type'], $allowedTypes)) {
                throw new Exception('Sadece JPG ve PNG formatları desteklenir.');
            }

            if ($_FILES['profile_image']['size'] > 5 * 1024 * 1024) {
                throw new Exception('Dosya boyutu çok büyük. Maksimum 5MB yükleyebilirsiniz.');
            }

            $extension = pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION);
            $fileName = uniqid() . '.' . $extension;
            $filePath = $uploadDir . $fileName;

            if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $filePath)) {
                optimizeImage($filePath);
                $profileImage = $fileName;
            }
        }

        // Hasta bilgilerini kaydet
        $sql = "INSERT INTO hastalar (
            AD_SOYAD, PROFIL_RESMI, TELEFON, EMAIL, DOGUM_TARIHI, 
            CINSIYET, KIMLIK_NO, KIMLIK_TURU, CREATED_AT
        ) VALUES (
            :ad_soyad, :profil_resmi, :telefon, :email, :dogum_tarihi,
            :cinsiyet, :kimlik_no, :kimlik_turu, NOW()
        )";

        $stmt = $db->prepare($sql);
        $stmt->execute([
            ':ad_soyad' => $data['fullName'],
            ':profil_resmi' => $profileImage,
            ':telefon' => $data['phone'],
            ':email' => $data['email'],
            ':dogum_tarihi' => $data['birthDate'],
            ':cinsiyet' => $data['gender'],
            ':kimlik_no' => $data['identityNo'],
            ':kimlik_turu' => $data['identityType']
        ]);

        return true;
    } catch (Exception $e) {
        error_log($e->getMessage());
        return false;
    }
}

function optimizeImage($filePath)
{
    $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
    list($width, $height) = getimagesize($filePath);
    $maxDimension = 800;

    // Boyutlandırma gerekli mi?
    if ($width > $maxDimension || $height > $maxDimension) {
        if ($width > $height) {
            $newWidth = $maxDimension;
            $newHeight = ($height / $width) * $maxDimension;
        } else {
            $newHeight = $maxDimension;
            $newWidth = ($width / $height) * $maxDimension;
        }

        $image = null;
        switch ($extension) {
            case 'jpeg':
            case 'jpg':
                $image = imagecreatefromjpeg($filePath);
                break;
            case 'png':
                $image = imagecreatefrompng($filePath);
                break;
        }

        if ($image) {
            $newImage = imagecreatetruecolor($newWidth, $newHeight);

            // PNG şeffaflığını koru
            if ($extension == 'png') {
                imagealphablending($newImage, false);
                imagesavealpha($newImage, true);
            }

            imagecopyresampled($newImage, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

            switch ($extension) {
                case 'jpeg':
                case 'jpg':
                    imagejpeg($newImage, $filePath, 85);
                    break;
                case 'png':
                    imagepng($newImage, $filePath, 8);
                    break;
            }

            imagedestroy($image);
            imagedestroy($newImage);
        }
    }
}

function updateAppointmentSeries($db, $appointmentId, $data)
{
    try {
        // Ana randevu ID'sini bul
        $stmt = $db->prepare("SELECT r.*, rs.GUN 
            FROM randevular r 
            LEFT JOIN randevu_sablonlari rs ON r.SABLON_ID = rs.ID 
            WHERE r.ID = :id");
        $stmt->execute([':id' => $appointmentId]);
        $currentAppointment = $stmt->fetch(PDO::FETCH_ASSOC);

        $mainId = $currentAppointment['ANA_RANDEVU_ID'];

        if ($mainId) {
            // Serideki tüm randevuları getir
            $stmt = $db->prepare("
                SELECT r.*, rs.GUN, rs.SIRA 
                FROM randevular r 
                LEFT JOIN randevu_sablonlari rs ON r.SABLON_ID = rs.ID 
                WHERE r.ANA_RANDEVU_ID = :main_id 
                ORDER BY rs.SIRA ASC
            ");
            $stmt->execute([':main_id' => $mainId]);
            $appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Güncellenen randevunun sırasını bul
            $currentIndex = array_search($appointmentId, array_column($appointments, 'ID'));

            // Yeni tarih
            $newDateTime = new DateTime($data['appointment_date'] . ' ' . $data['appointment_time']);

            // Her randevuyu güncelle
            foreach ($appointments as $index => $appointment) {
                if ($index === $currentIndex) {
                    // Güncellenen randevu için yeni tarih kullan
                    $appointmentDate = $newDateTime;
                } else {
                    // Diğer randevular için gün farkını koru
                    $dayDiff = $appointment['GUN'] - $appointments[$currentIndex]['GUN'];
                    $appointmentDate = clone $newDateTime;
                    $appointmentDate->modify("$dayDiff days");

                    // Pazar günü kontrolü
                    while ($appointmentDate->format('N') == 7) { // 7 = Pazar
                        $appointmentDate->modify('+1 day');
                    }
                }

                $stmt = $db->prepare("
                    UPDATE randevular 
                    SET TARIH = :tarih,
                        DURUM = :durum
                    WHERE ID = :id
                ");

                $stmt->execute([
                    ':tarih' => $appointmentDate->format('Y-m-d H:i:s'),
                    ':durum' => $data['status'],
                    ':id' => $appointment['ID']
                ]);
            }

            return true;
        }

        return false;

    } catch (Exception $e) {
        error_log($e->getMessage());
        return false;
    }
}

/**
 * Randevu durumuna göre renk sınıfı döndürür
 */
function getStatusColor($status)
{
    switch ($status) {
        case 'onaylandi':
            return 'success';
        case 'bekliyor':
            return 'warning';
        case 'iptal':
            return 'danger';
        default:
            return 'secondary';
    }
}

/**
 * Randevu durumuna göre metin döndürür
 */
function getStatusText($status)
{
    switch ($status) {
        case 'onaylandi':
            return 'Onaylandı';
        case 'bekliyor':
            return 'Bekliyor';
        case 'iptal':
            return 'İptal';
        default:
            return 'Belirsiz';
    }
}

/**
 * Belirli bir tarihe ait randevuları getirir
 */
function getAppointmentsByDate($db, $date = null)
{
    try {
        $sql = "
            SELECT r.*, 
                   h.AD_SOYAD as HASTA_ADI,
                   r.NOTLAR,
                   DATE_FORMAT(r.TARIH, '%H:%i') as SAAT
            FROM randevular r
            LEFT JOIN hastalar h ON r.HASTA_ID = h.ID
            WHERE DATE(r.TARIH) = :date
            ORDER BY r.TARIH ASC
        ";

        $stmt = $db->prepare($sql);
        $stmt->execute([':date' => $date ?: date('Y-m-d')]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log($e->getMessage());
        return [];
    }
}

/**
 * Haftanın günlerini getirir
 */
function getWeekDays($startDate = null)
{
    if (!$startDate) {
        $startDate = date('Y-m-d');
    }

    $days = [];
    $start = new DateTime($startDate);
    $start->modify('this week');

    for ($i = 0; $i < 7; $i++) {
        $date = clone $start;
        $date->modify("+$i days");

        $days[] = [
            'date' => $date->format('Y-m-d'),
            'day' => $date->format('d'),
            'day_name' => strftime('%A', $date->getTimestamp()),
            'is_today' => $date->format('Y-m-d') === date('Y-m-d'),
            'is_past' => $date->format('Y-m-d') < date('Y-m-d')
        ];
    }

    return $days;
}

function getTaksitStatusColor($durum)
{
    if (strpos($durum, 'Kısmi') !== false) {
        return 'warning';
    } elseif ($durum == 'odendi' || strpos($durum, 'Ödendi') !== false) {
        return 'success';
    }

    switch ($durum) {
        case 'bekliyor':
            return 'secondary';
        case 'gecikti':
            return 'danger';
        default:
            return 'secondary';
    }
}

function getTaksitStatusText($status)
{
    switch ($status) {
        case 'odendi':
            return 'Ödendi';
        case 'bekliyor':
            return 'Bekliyor';
        case 'gecikti':
            return 'Gecikti';
        default:
            return 'Belirsiz';
    }
}

function getOdemeTuruText($tur)
{
    $turler = [
        'nakit' => 'Nakit',
        'kredi_karti' => 'Kredi Kartı',
        'havale' => 'Havale/EFT'
    ];
    return $turler[$tur] ?? $tur;
}

function getOdemeTuruColor($tur)
{
    $renkler = [
        'nakit' => 'success',
        'kredi_karti' => 'primary',
        'havale' => 'info'
    ];
    return $renkler[$tur] ?? 'secondary';
}

function processAndSaveImage($file, $uploadDir, $oldImage = null)
{
    try {
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $allowedTypes = ['image/jpeg', 'image/png', 'image/jpg'];
        if (!in_array($file['type'], $allowedTypes)) {
            throw new Exception('Sadece JPG ve PNG formatları desteklenir.');
        }

        if ($file['size'] > 5 * 1024 * 1024) {
            throw new Exception('Dosya boyutu çok büyük. Maksimum 5MB yükleyebilirsiniz.');
        }

        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $fileName = uniqid() . '.' . $extension;
        $filePath = $uploadDir . $fileName;

        if (move_uploaded_file($file['tmp_name'], $filePath)) {
            // EXIF bilgisini al ve resmi düzelt
            $image = imagecreatefromstring(file_get_contents($filePath));
            if ($extension != 'png') {
                $exif = @exif_read_data($filePath);
                if (!empty($exif['Orientation'])) {
                    switch ($exif['Orientation']) {
                        case 3:
                            $image = imagerotate($image, 180, 0);
                            break;
                        case 6:
                            $image = imagerotate($image, -90, 0);
                            break;
                        case 8:
                            $image = imagerotate($image, 90, 0);
                            break;
                    }
                }
            }

            $width = imagesx($image);
            $height = imagesy($image);

            // Maksimum boyutlar
            $maxWidth = 800;
            $maxHeight = 800;

            // Yeni boyutları hesapla
            if ($width > $maxWidth || $height > $maxHeight) {
                $ratio = min($maxWidth / $width, $maxHeight / $height);
                $newWidth = round($width * $ratio);
                $newHeight = round($height * $ratio);

                $resized = imagecreatetruecolor($newWidth, $newHeight);

                // PNG şeffaflığını koru
                if ($extension == 'png') {
                    imagealphablending($resized, false);
                    imagesavealpha($resized, true);
                }

                imagecopyresampled($resized, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
                $image = $resized;
            }

            // Resmi kaydet
            switch (strtolower($extension)) {
                case 'jpg':
                case 'jpeg':
                    imagejpeg($image, $filePath, 85);
                    break;
                case 'png':
                    imagepng($image, $filePath, 8);
                    break;
            }

            imagedestroy($image);

            // Eski fotoğrafı sil
            if ($oldImage && $oldImage != 'default-avatar.jpg' && file_exists($uploadDir . $oldImage)) {
                unlink($uploadDir . $oldImage);
            }

            return $fileName;
        }

        throw new Exception('Dosya yüklenirken bir hata oluştu.');
    } catch (Exception $e) {
        error_log('Resim işleme hatası: ' . $e->getMessage());
        throw $e;
    }
}

function savePatientPhotos($db, $patientId, $photos)
{
    try {
        $uploadDir = 'uploads/gallery/';
        $savedPhotos = [];

        foreach ($photos['tmp_name'] as $key => $tmpName) {
            if ($photos['error'][$key] === UPLOAD_ERR_OK) {
                $photo = [
                    'name' => $photos['name'][$key],
                    'type' => $photos['type'][$key],
                    'tmp_name' => $tmpName,
                    'error' => $photos['error'][$key],
                    'size' => $photos['size'][$key]
                ];

                $fileName = processAndSaveImage($photo, $uploadDir);

                // Veritabanına kaydet
                $stmt = $db->prepare("
                    INSERT INTO hasta_galerileri (HASTA_ID, DOSYA_YOLU, YUKLENME_TARIHI)
                    VALUES (:hasta_id, :dosya_yolu, NOW())
                ");

                $stmt->execute([
                    ':hasta_id' => $patientId,
                    ':dosya_yolu' => $uploadDir . $fileName
                ]);

                $savedPhotos[] = $fileName;
            }
        }

        return $savedPhotos;
    } catch (Exception $e) {
        error_log('Fotoğraf kaydetme hatası: ' . $e->getMessage());
        throw $e;
    }
}