<?php

function validateForm($data) {
    $errors = [];
    
    // Ad Soyad kontrolü
    if(empty($data['fullName'])) {
        $errors[] = "Ad Soyad alanı zorunludur.";
    }
    
    // Kimlik türü kontrolü
    if(empty($data['idType'])) {
        $errors[] = "Kimlik türü seçimi zorunludur.";
    } else {
        // TC Kimlik / Pasaport kontrolü
        if(empty($data['idNumber'])) {
            $errors[] = "Kimlik numarası zorunludur.";
        } else {
            if($data['idType'] == 'tc') {
                if(!validateTcNumber($data['idNumber'])) {
                    $errors[] = "Geçerli bir TC Kimlik numarası giriniz.";
                }
            } else {
                if(!validatePassportNumber($data['idNumber'])) {
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
    if(empty($data['birthDate'])) $errors[] = "Doğum tarihi zorunludur.";
    if(empty($data['gender'])) $errors[] = "Cinsiyet seçimi zorunludur.";
    if(empty($data['phone'])) $errors[] = "Telefon numarası zorunludur.";
    
    return $errors;
}

function validateTcNumber($tcno) {
    // Sadece 11 haneli sayı kontrolü
    return preg_match('/^[0-9]{11}$/', $tcno);
}

function validatePassportNumber($passport) {
    return preg_match('/^[A-Z0-9]{7,9}$/', $passport);
}

function saveFormData($db, $data) {
    try {
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
            ':referans' => trim($data['reference']),
            ':aciklama' => isset($data['emptyField']) ? trim($data['emptyField']) : null,
            ':created_at' => !empty($data['registerDate']) ? $data['registerDate'] : date('Y-m-d H:i:s')
        ];
        
        $stmt = $db->prepare($sql);
        $result = $stmt->execute($params);
        
        if(!$result) {
            throw new PDOException("Kayıt işlemi başarısız oldu.");
        }
        
        return true;
        
    } catch(PDOException $e) {
        error_log('Database Error: ' . $e->getMessage());
        throw new Exception("Veritabanı hatası: " . $e->getMessage());
    }
}

function validateUpdateForm($data) {
    $errors = [];
    
    // Ad Soyad kontrolü
    if(empty($data['fullName'])) {
        $errors[] = "Ad Soyad alanı zorunludur.";
    }
    
    // Kimlik kontrolü
    if(empty($data['idNumber'])) {
        $errors[] = "Kimlik numarası zorunludur.";
    } else {
        if($data['idType'] == 'tc' && !validateTcNumber($data['idNumber'])) {
            $errors[] = "Geçerli bir TC Kimlik numarası giriniz.";
        } else if($data['idType'] == 'passport' && !validatePassportNumber($data['idNumber'])) {
            $errors[] = "Geçerli bir Pasaport numarası giriniz.";
        }
    }
    
    // Diğer zorunlu alanların kontrolü
    if(empty($data['birthDate'])) $errors[] = "Doğum tarihi zorunludur.";
    if(empty($data['gender'])) $errors[] = "Cinsiyet seçimi zorunludur.";
    if(empty($data['phone'])) $errors[] = "Telefon numarası zorunludur.";
    
    return $errors;
}

function updatePatient($db, $patientId, $data) {
    try {
        $sql = "UPDATE hastalar SET 
                AD_SOYAD = :ad_soyad,
                KIMLIK_TURU = :kimlik_turu,
                KIMLIK_NO = :kimlik_no,
                DOGUM_TARIHI = :dogum_tarihi,
                CINSIYET = :cinsiyet,
                TELEFON = :telefon,
                EMAIL = :email,
                REFERANS = :referans,
                ACIKLAMA = :aciklama
                WHERE ID = :id";
        
        $stmt = $db->prepare($sql);
        return $stmt->execute([
            ':ad_soyad' => trim($data['fullName']),
            ':kimlik_turu' => trim($data['idType']),
            ':kimlik_no' => trim($data['idNumber']),
            ':dogum_tarihi' => trim($data['birthDate']),
            ':cinsiyet' => trim($data['gender']),
            ':telefon' => trim($data['phone']),
            ':email' => trim($data['email']),
            ':referans' => trim($data['reference']),
            ':aciklama' => isset($data['emptyField']) ? trim($data['emptyField']) : null,
            ':id' => $patientId
        ]);
        
    } catch (PDOException $e) {
        error_log($e->getMessage());
        throw new Exception("Güncelleme işlemi başarısız oldu.");
    }
}

function getAvailableTimeSlots() {
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

function validateAppointmentTime($time) {
    $appointmentTime = new DateTime($time);
    $startTime = new DateTime('09:00');
    $endTime = new DateTime('18:00');
    
    return $appointmentTime >= $startTime && $appointmentTime < $endTime;
}

function turkishDate($date) {
    $aylar = array(
        'January'   => 'Ocak',
        'February'  => 'Şubat',
        'March'     => 'Mart',
        'April'     => 'Nisan',
        'May'       => 'Mayıs',
        'June'      => 'Haziran',
        'July'      => 'Temmuz',
        'August'    => 'Ağustos',
        'September' => 'Eylül',
        'October'   => 'Ekim',
        'November'  => 'Kasım',
        'December'  => 'Aralık'
    );
    
    return strtr(date('d F Y', strtotime($date)), $aylar);
}

function saveUploadedPhoto($file, $patientId) {
    try {
        $uploadDir = 'uploads/photos/';
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        
        // Dosya kontrolü
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowedTypes = ['jpg', 'jpeg', 'png'];
        
        if (!in_array($extension, $allowedTypes)) {
            throw new Exception('Geçersiz dosya türü. Sadece JPG ve PNG dosyaları yükleyebilirsiniz.');
        }
        
        $maxFileSize = 10 * 1024 * 1024; // 10MB limit
        if ($file['size'] > $maxFileSize) {
            throw new Exception('Dosya boyutu çok büyük. Maksimum 10MB yükleyebilirsiniz.');
        }
        
        // Benzersiz dosya adı oluştur
        $fileName = uniqid() . '.' . $extension;
        $filePath = $uploadDir . $fileName;
        
        // Dosyayı yükle
        if (!move_uploaded_file($file['tmp_name'], $filePath)) {
            throw new Exception('Dosya yüklenirken bir hata oluştu.');
        }
        
        // GD ile görüntüyü işle
        switch($extension) {
            case 'jpg':
            case 'jpeg':
                $source = imagecreatefromjpeg($filePath);
                break;
            case 'png':
                $source = imagecreatefrompng($filePath);
                break;
            default:
                throw new Exception('Desteklenmeyen dosya formatı');
        }
        
        if (!$source) {
            throw new Exception('Görüntü işlenemedi');
        }
        
        // Görüntü boyutunu optimize et
        $width = imagesx($source);
        $height = imagesy($source);
        $maxWidth = 2000;
        
        if ($width > $maxWidth) {
            $newWidth = $maxWidth;
            $newHeight = ($height / $width) * $maxWidth;
            
            $resized = imagecreatetruecolor($newWidth, $newHeight);
            
            // PNG şeffaflığını koru
            if ($extension == 'png') {
                imagealphablending($resized, false);
                imagesavealpha($resized, true);
            }
            
            imagecopyresampled($resized, $source, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
            imagedestroy($source);
            $source = $resized;
        }
        
        // Kaliteyi ayarla ve kaydet
        switch($extension) {
            case 'jpg':
            case 'jpeg':
                imagejpeg($source, $filePath, 90);
                break;
            case 'png':
                imagepng($source, $filePath, 9);
                break;
        }
        
        imagedestroy($source);
        
        // Veritabanına kaydet
        $database = new Database();
        $db = $database->connect();
        
        $stmt = $db->prepare("INSERT INTO hasta_galerileri (HASTA_ID, DOSYA_ADI, DOSYA_YOLU, YUKLENME_TARIHI) VALUES (:hasta_id, :dosya_adi, :dosya_yolu, :yuklenme_tarihi)");
        $stmt->execute([
            ':hasta_id' => $patientId,
            ':dosya_adi' => $fileName,
            ':dosya_yolu' => $filePath,
            ':yuklenme_tarihi' => date('Y-m-d H:i:s')
        ]);
        
        return true;
        
    } catch (Exception $e) {
        error_log($e->getMessage());
        if (isset($filePath) && file_exists($filePath)) {
            unlink($filePath); // Hata durumunda yüklenen dosyayı sil
        }
        return false;
    }
} 