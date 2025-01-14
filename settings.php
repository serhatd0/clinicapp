<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

$database = new Database();
$db = $database->connect();

// Kullanıcıları getir (sadece admin için)
$users = [];
if (isAdmin()) {
    $stmt = $db->prepare("
        SELECT k.*, r.ROL_ADI 
        FROM kullanicilar k 
        JOIN roller r ON k.ROL_ID = r.ID 
        ORDER BY k.AD_SOYAD
    ");
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ayarlar</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container py-4 content-area">
        <div class="row">
            <!-- Sol Menü -->
            <div class="col-md-4">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Ayarlar Menüsü</h5>
                    </div>
                    <div class="list-group list-group-flush">
                        <?php if ($_SESSION['rol_id'] == 1 || $_SESSION['rol_id'] == 3): // Admin için kullanıcı yönetimi ?>
                            <a href="#users" class="list-group-item list-group-item-action" data-bs-toggle="collapse">
                                <i class="fas fa-users me-2"></i>Kullanıcı Yönetimi
                            </a>
                        <?php endif; ?>
                        <a href="#profile" class="list-group-item list-group-item-action">
                            <i class="fas fa-user-circle me-2"></i>Profil Ayarları
                        </a>
                        <a href="#password" class="list-group-item list-group-item-action">
                            <i class="fas fa-key me-2"></i>Şifre Değiştir
                        </a>
                        <a href="logout.php" class="list-group-item list-group-item-action text-danger">
                            <i class="fas fa-sign-out-alt me-2"></i>Çıkış Yap
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Sağ İçerik -->
            <div class="col-md-8">
                <?php if ($_SESSION['rol_id'] == 1 || $_SESSION['rol_id'] == 3): // Admin için kullanıcı listesi ?>
                    <div id="users" class="collapse show">
                        <div class="card mb-4">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">Kullanıcı Listesi</h5>
                                <a href="add_user.php" class="btn btn-success btn-sm">
                                    <i class="fas fa-user-plus me-1"></i>Yeni Kullanıcı
                                </a>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Ad Soyad</th>
                                                <th>Email</th>
                                                <th>Rol</th>
                                                <th>Durum</th>
                                                <th>İşlemler</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($users as $user): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($user['AD_SOYAD']); ?></td>
                                                    <td><?php echo htmlspecialchars($user['EMAIL']); ?></td>
                                                    <td><?php echo htmlspecialchars($user['ROL_ADI']); ?></td>
                                                    <td>
                                                        <span class="badge bg-<?php echo $user['DURUM'] == 'aktif' ? 'success' : 'danger'; ?>">
                                                            <?php echo ucfirst($user['DURUM']); ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <a href="edit_user.php?id=<?php echo $user['ID']; ?>" 
                                                           class="btn btn-sm btn-primary">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                        <button class="btn btn-sm btn-danger" 
                                                                onclick="deleteUser(<?php echo $user['ID']; ?>)">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
                
                <!-- Profil Ayarları -->
                <div id="profile" class="collapse">
                    <!-- Profil ayarları formu buraya gelecek -->
                </div>
                
                <!-- Şifre Değiştirme -->
                <div id="password" class="collapse">
                    <!-- Şifre değiştirme formu buraya gelecek -->
                </div>
            </div>
        </div>
    </div>

    <?php include 'includes/nav.php'; ?>
    
    <!-- Silme Onay Modalı -->
    <div class="modal fade" id="deleteUserModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Kullanıcı Sil</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Bu kullanıcıyı silmek istediğinizden emin misiniz?</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                    <button type="button" class="btn btn-danger" id="confirmDelete">Sil</button>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let userIdToDelete = null;
        const deleteModal = new bootstrap.Modal(document.getElementById('deleteUserModal'));
        
        function deleteUser(id) {
            userIdToDelete = id;
            deleteModal.show();
        }
        
        document.getElementById('confirmDelete').addEventListener('click', function() {
            if (userIdToDelete) {
                window.location.href = 'delete_user.php?id=' + userIdToDelete;
            }
        });
    </script>
</body>
</html> 