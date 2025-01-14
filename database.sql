CREATE TABLE IF NOT EXISTS `hastalar` (
  `ID` bigint(20) NOT NULL AUTO_INCREMENT,
  `AD_SOYAD` varchar(100) NOT NULL,
  `DOGUM_TARIHI` date NOT NULL,
  `KIMLIK_NO` varchar(20) NOT NULL,
  `CINSIYET` enum('kadin','erkek') NOT NULL,
  `TELEFON` varchar(20) NOT NULL,
  `EMAIL` varchar(100) NOT NULL,
  `REFERANS` varchar(50) DEFAULT NULL,
  `ACIKLAMA` text DEFAULT NULL,
  `CREATED_AT` datetime NOT NULL,
  `STATUS` tinyint(1) DEFAULT 1,
  `KIMLIK_TURU` enum('tc','passport') NOT NULL,
  PRIMARY KEY (`ID`),
  UNIQUE KEY `KIMLIK_NO` (`KIMLIK_NO`),
  UNIQUE KEY `EMAIL` (`EMAIL`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `randevular` (
  `ID` bigint(20) NOT NULL AUTO_INCREMENT,
  `HASTA_ID` bigint(20) NOT NULL,
  `TARIH` datetime NOT NULL,
  `DURUM` enum('bekliyor','onaylandi','iptal') NOT NULL DEFAULT 'bekliyor',
  `NOTLAR` text DEFAULT NULL,
  `CREATED_AT` timestamp NOT NULL DEFAULT current_timestamp(),
  `TEKRAR` enum('yok','gunluk','haftalik','aylik') NOT NULL DEFAULT 'yok',
  `TEKRAR_SAYISI` int DEFAULT NULL,
  `TEKRAR_BITIS` date DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `HASTA_ID` (`HASTA_ID`),
  CONSTRAINT `randevular_ibfk_1` FOREIGN KEY (`HASTA_ID`) REFERENCES `hastalar` (`ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `hasta_galerileri` (
  `ID` bigint(20) NOT NULL AUTO_INCREMENT,
  `HASTA_ID` bigint(20) NOT NULL,
  `DOSYA_ADI` varchar(255) NOT NULL,
  `DOSYA_YOLU` varchar(255) NOT NULL,
  `YUKLENME_TARIHI` datetime NOT NULL,
  `ACIKLAMA` text DEFAULT NULL,
  `TARIH_ACIKLAMA` text DEFAULT NULL,
  `CREATED_AT` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`ID`),
  KEY `HASTA_ID` (`HASTA_ID`),
  CONSTRAINT `hasta_galerileri_ibfk_1` FOREIGN KEY (`HASTA_ID`) REFERENCES `hastalar` (`ID`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Kullanıcı rolleri tablosu
CREATE TABLE IF NOT EXISTS `roller` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `ROL_ADI` varchar(50) NOT NULL,
  `YETKILER` text NOT NULL,
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Varsayılan rolleri ekle
INSERT INTO `roller` (`ROL_ADI`, `YETKILER`) VALUES
('doktor', 'hasta_ekle,hasta_duzenle,hasta_sil,randevu_ekle,randevu_duzenle,randevu_sil,galeri_ekle,galeri_sil,rapor_goruntule'),
('sekreter', 'hasta_ekle,hasta_duzenle,randevu_ekle,randevu_duzenle,galeri_ekle');

-- Kullanıcılar tablosu
CREATE TABLE IF NOT EXISTS `kullanicilar` (
  `ID` bigint(20) NOT NULL AUTO_INCREMENT,
  `AD_SOYAD` varchar(100) NOT NULL,
  `EMAIL` varchar(100) NOT NULL,
  `SIFRE` varchar(255) NOT NULL,
  `ROL_ID` int(11) NOT NULL,
  `SON_GIRIS` datetime DEFAULT NULL,
  `DURUM` enum('aktif','pasif') NOT NULL DEFAULT 'aktif',
  `CREATED_AT` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`ID`),
  UNIQUE KEY `EMAIL` (`EMAIL`),
  KEY `ROL_ID` (`ROL_ID`),
  CONSTRAINT `kullanicilar_ibfk_1` FOREIGN KEY (`ROL_ID`) REFERENCES `roller` (`ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Oturum tablosu
CREATE TABLE IF NOT EXISTS `oturumlar` (
  `ID` bigint(20) NOT NULL AUTO_INCREMENT,
  `KULLANICI_ID` bigint(20) NOT NULL,
  `TOKEN` varchar(255) NOT NULL,
  `IP_ADRESI` varchar(45) NOT NULL,
  `TARAYICI` varchar(255) NOT NULL,
  `CREATED_AT` timestamp NOT NULL DEFAULT current_timestamp(),
  `EXPIRES_AT` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`ID`),
  KEY `KULLANICI_ID` (`KULLANICI_ID`),
  CONSTRAINT `oturumlar_ibfk_1` FOREIGN KEY (`KULLANICI_ID`) REFERENCES `kullanicilar` (`ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Önce eski kaydı silelim
DELETE FROM `kullanicilar` WHERE `EMAIL` = 'serhat';

-- Yeni admin kullanıcısını ekleyelim (email: admin, şifre: 123456)
INSERT INTO `kullanicilar` (`AD_SOYAD`, `EMAIL`, `SIFRE`, `ROL_ID`, `DURUM`) VALUES
('Admin', 'admin', '$2y$10$YourNewHashHere', 
(SELECT ID FROM roller WHERE ROL_ADI = 'doktor'), 'aktif');

-- Önce tabloları temizleyelim
TRUNCATE TABLE `oturumlar`;
DELETE FROM `kullanicilar`;

-- Yeni şifre ile admin kullanıcısı oluşturalım
INSERT INTO `kullanicilar` (`AD_SOYAD`, `EMAIL`, `SIFRE`, `ROL_ID`, `DURUM`) VALUES
('Admin', 'admin', '$2y$10$YourNewHashHere', 
(SELECT ID FROM roller WHERE ROL_ADI = 'doktor'), 'aktif');