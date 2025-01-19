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
  `PROFIL_RESMI` varchar(255) DEFAULT 'default-avatar.jpg',
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
  `SABLON_ID` int(11) DEFAULT NULL,
  `ANA_RANDEVU_ID` bigint(20) DEFAULT NULL,
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

-- Cari hareketler tablosu
CREATE TABLE IF NOT EXISTS `cari_hareketler` (
  `ID` bigint(20) NOT NULL AUTO_INCREMENT,
  `TUR` enum('gelir','gider') NOT NULL,
  `TUTAR` decimal(10,2) NOT NULL,
  `ACIKLAMA` text DEFAULT NULL,
  `TARIH` datetime NOT NULL,
  `KULLANICI_ID` bigint(20) NOT NULL,
  `KATEGORI_ID` bigint(20) NOT NULL,
  `CREATED_AT` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`ID`),
  KEY `KULLANICI_ID` (`KULLANICI_ID`),
  KEY `KATEGORI_ID` (`KATEGORI_ID`),
  CONSTRAINT `cari_hareketler_ibfk_1` FOREIGN KEY (`KULLANICI_ID`) REFERENCES `kullanicilar` (`ID`) ON DELETE CASCADE,
  CONSTRAINT `cari_hareketler_ibfk_2` FOREIGN KEY (`KATEGORI_ID`) REFERENCES `cari_kategoriler` (`ID`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Cari kategoriler tablosu (opsiyonel)
CREATE TABLE IF NOT EXISTS `cari_kategoriler` (
  `ID` bigint(20) NOT NULL AUTO_INCREMENT,
  `KATEGORI_ADI` varchar(100) NOT NULL,
  `TUR` enum('gelir','gider') NOT NULL,
  `CREATED_AT` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Örnek kategoriler
INSERT INTO `cari_kategoriler` (`KATEGORI_ADI`, `TUR`) VALUES
('Hasta Ödemeleri', 'gelir'),
('Nakit Tahsilat', 'gelir'),
('Kredi Kartı Tahsilatı', 'gelir'),
('Diğer Gelirler', 'gelir'),
('Kira', 'gider'),
('Elektrik Faturası', 'gider'),
('Su Faturası', 'gider'),
('Doğalgaz Faturası', 'gider'),
('İnternet Faturası', 'gider'),
('Telefon Faturası', 'gider'),
('Personel Maaşları', 'gider'),
('Personel SGK', 'gider'),
('Personel Yemek', 'gider'),
('Temizlik Malzemeleri', 'gider'),
('Medikal Malzemeler', 'gider'),
('Bakım Onarım', 'gider'),
('Vergi Ödemeleri', 'gider'),
('Muhasebe Ödemeleri', 'gider'),
('Sigorta Ödemeleri', 'gider'),
('Reklam ve Pazarlama', 'gider'),
('Kırtasiye Malzemeleri', 'gider'),
('Diğer Giderler', 'gider');

CREATE TABLE IF NOT EXISTS `taksitler` (
  `ID` bigint(20) NOT NULL AUTO_INCREMENT,
  `BORC_ID` bigint(20) NOT NULL,
  `TAKSIT_NO` int(11) NOT NULL,
  `TUTAR` decimal(10,2) NOT NULL,
  `ODENEN_TUTAR` decimal(10,2) DEFAULT 0.00,
  `VADE_TARIHI` date NOT NULL,
  `DURUM` enum('bekliyor','odendi','gecikti') NOT NULL DEFAULT 'bekliyor',
  `CREATED_AT` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`ID`),
  KEY `BORC_ID` (`BORC_ID`),
  CONSTRAINT `taksitler_ibfk_1` FOREIGN KEY (`BORC_ID`) REFERENCES `hasta_borc` (`ID`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `hasta_borc` (
  `ID` bigint(20) NOT NULL AUTO_INCREMENT,
  `HASTA_ID` bigint(20) NOT NULL,
  `PLAN_ADI` varchar(100) DEFAULT NULL,
  `TOPLAM_BORC` decimal(10,2) NOT NULL,
  `KALAN_BORC` decimal(10,2) NOT NULL,
  `AKTIF` tinyint(1) NOT NULL DEFAULT 1,
  `OLUSTURMA_TARIHI` datetime NOT NULL,
  `TAKSIT_SAYISI` int(11) NOT NULL DEFAULT 1,
  PRIMARY KEY (`ID`),
  KEY `HASTA_ID` (`HASTA_ID`),
  CONSTRAINT `hasta_borc_ibfk_1` FOREIGN KEY (`HASTA_ID`) REFERENCES `hastalar` (`ID`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;