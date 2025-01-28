<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

// Sadece admin erişebilsin

$database = new Database();
$db = $database->connect();

// Kullanıcının yetkilerini kontrol et
$user_id = $_SESSION['user_id'];

// Kullanıcı bilgilerini al
$stmt = $db->prepare("
    SELECT k.*, r.ROL_ADI 
    FROM kullanicilar k
    JOIN roller r ON k.ROL_ID = r.ID
    WHERE k.ID = ?
");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Kullanıcının yetkilerini al
$stmt = $db->prepare("
    SELECT y.* 
    FROM yetkiler y
    JOIN rol_yetkileri ry ON y.id = ry.yetki_id
    JOIN kullanicilar k ON k.ROL_ID = ry.rol_id
    WHERE k.ID = ?
");
$stmt->execute([$user_id]);
$permissions = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Yetki durumunu logla
error_log("Kullanıcı ID: " . $user_id . ", Rol: " . $user['ROL_ADI']);
foreach ($permissions as $perm) {
    error_log("Yetki: " . $perm['yetki_key'] . " - " . $perm['yetki_adi']);
}

?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Yetki Kontrolü</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h3>Kullanıcı Bilgileri</h3>
        <p>ID: <?php echo $user['ID']; ?></p>
        <p>Ad Soyad: <?php echo $user['AD_SOYAD']; ?></p>
        <p>Rol: <?php echo $user['ROL_ADI']; ?></p>
        
        <h3>Yetkiler</h3>
        <table class="table">
            <thead>
                <tr>
                    <th>Yetki Anahtarı</th>
                    <th>Yetki Adı</th>
                    <th>Kategori</th>
                    <th>Test</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($permissions as $perm): ?>
                    <tr>
                        <td><?php echo $perm['yetki_key']; ?></td>
                        <td><?php echo $perm['yetki_adi']; ?></td>
                        <td><?php echo $perm['kategori']; ?></td>
                        <td>
                            <?php if (hasPermission($perm['yetki_key'])): ?>
                                <span class="text-success">Var</span>
                            <?php else: ?>
                                <span class="text-danger">Yok</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</body>
</html> 