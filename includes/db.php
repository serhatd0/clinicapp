<?php
class Database
{
    private $host = "localhost";
    private $db_name = "clinic";
    private $username = "root";
    private $password = "";
    private $conn;

    public function __construct()
    {
        $this->connect(); // Sınıf oluşturulduğunda otomatik bağlan
    }

    public function connect()
    {
        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name,
                $this->username,
                $this->password
            );
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->exec("set names utf8");
            return $this->conn;
        } catch (PDOException $e) {
            error_log("Veritabanı bağlantı hatası: " . $e->getMessage());
            return null;
        }
    }

    public function getConnection()
    {
        if ($this->conn === null) {
            $this->connect();
        }
        return $this->conn;
    }

    // Yetki kontrolü için yeni fonksiyonlar
    public function checkPermission($userId, $permissionKey)
    {
        try {
            $conn = $this->getConnection();
            if ($conn === null) {
                error_log("Veritabanı bağlantısı kurulamadı - Kullanıcı ID: " . $userId);
                return false;
            }

            // SQL sorgusunu güncelleyelim ve daha detaylı hale getirelim
            $sql = "SELECT COUNT(*) as count
                    FROM rol_yetkileri ry
                    JOIN yetkiler y ON ry.yetki_id = y.id
                    JOIN kullanicilar k ON k.ROL_ID = ry.rol_id
                    WHERE k.ID = ? 
                    AND y.yetki_key = ? 
                    AND y.durum = 1
                    AND k.DURUM = 'aktif'";

            $stmt = $conn->prepare($sql);
            $stmt->execute([$userId, $permissionKey]);

            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $hasPermission = ($result['count'] > 0);

            // Sonucu logla
            error_log("Yetki kontrolü detayı - Kullanıcı ID: " . $userId .
                ", Yetki: " . $permissionKey .
                ", Sonuç: " . ($hasPermission ? 'true' : 'false'));

            return $hasPermission;
        } catch (PDOException $e) {
            error_log("Yetki kontrolü hatası: " . $e->getMessage());
            return false;
        }
    }

    public function getUserPermissions($userId)
    {
        try {
            $conn = $this->getConnection();
            if ($conn === null) {
                return [];
            }

            $sql = "SELECT y.yetki_key, y.yetki_adi, y.aciklama
                    FROM rol_yetkileri ry
                    JOIN yetkiler y ON ry.yetki_id = y.id
                    JOIN kullanicilar k ON k.ROL_ID = ry.rol_id
                    WHERE k.ID = ?";

            $stmt = $conn->prepare($sql);
            $stmt->execute([$userId]);

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Yetki listesi hatası: " . $e->getMessage());
            return [];
        }
    }
}