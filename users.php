<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

// Kullanıcı listesi erişim kontrolü
checkPagePermission('kullanicilar_erisim');

// Butonlar için yetki kontrolleri
$canAddUser = hasPermission('kullanici_ekle');
$canEditUser = hasPermission('kullanici_duzenle');
$canDeleteUser = hasPermission('kullanici_sil');

$database = new Database();
$db = $database->connect();

// Kullanıcıları getir - SQL sorgusunu güncelle
$stmt = $db->query("
    SELECT k.*, r.ROL_ADI,
           k.EMAIL as KULLANICI_ADI
    FROM kullanicilar k 
    LEFT JOIN roller r ON k.ROL_ID = r.ID 
    ORDER BY k.AD_SOYAD ASC
");
$kullanicilar = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Rolleri getir
$stmt = $db->query("SELECT * FROM roller ORDER BY ROL_ADI ASC");
$roller = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kullanıcı Yönetimi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="container py-4">
        <div class="row">
            <div class="col-12">
                <!-- Geri Butonu -->
                <div class="mb-3">
                    <a href="index.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Geri
                    </a>
                </div>

                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Kullanıcı Listesi</h5>
                        <?php if ($canAddUser): ?>
                            <button type="button" class="btn btn-success" onclick="showAddUserModal()">
                                <i class="fas fa-plus me-2"></i>Yeni Kullanıcı
                            </button>
                        <?php endif; ?>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Ad Soyad</th>
                                        <th>E-posta</th>
                                        <th>Rol</th>
                                        <th>Durum</th>
                                        <th style="width: 150px;">İşlemler</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($kullanicilar as $kullanici): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($kullanici['AD_SOYAD']); ?></td>
                                            <td><?php echo htmlspecialchars($kullanici['EMAIL']); ?></td>
                                            <td><?php echo htmlspecialchars($kullanici['ROL_ADI']); ?></td>
                                            <td>
                                                <span class="badge bg-<?php echo $kullanici['DURUM'] == 'aktif' ? 'success' : 'danger'; ?>">
                                                    <?php echo ucfirst($kullanici['DURUM']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="btn-group">
                                                    <?php if ($canEditUser): ?>
                                                        <button type="button" class="btn btn-primary btn-sm" 
                                                                onclick="editUser(<?php echo $kullanici['ID']; ?>)">
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                    <?php endif; ?>
                                                    
                                                    <?php if ($canDeleteUser): ?>
                                                        <button type="button" class="btn btn-danger btn-sm" 
                                                                onclick="deleteUser(<?php echo $kullanici['ID']; ?>)">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Kullanıcı Ekleme Modal -->
    <div class="modal fade" id="addUserModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Yeni Kullanıcı Ekle</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="addUserForm" method="POST" action="add_user.php">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Ad Soyad</label>
                            <input type="text" class="form-control" name="ad_soyad" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">E-posta</label>
                            <input type="email" class="form-control" name="email" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Şifre</label>
                            <input type="password" class="form-control" name="sifre" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Rol</label>
                            <select class="form-select" name="rol_id" required>
                                <?php foreach ($roller as $rol): ?>
                                    <option value="<?php echo $rol['ID']; ?>">
                                        <?php echo htmlspecialchars($rol['ROL_ADI']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                        <button type="submit" class="btn btn-success">Kaydet</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <?php include 'includes/nav.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function showAddUserModal() {
            new bootstrap.Modal(document.getElementById('addUserModal')).show();
        }

        function editUser(id) {
            window.location.href = 'edit_user.php?id=' + id;
        }

        function deleteUser(id) {
            if (confirm('Bu kullanıcıyı silmek istediğinizden emin misiniz?')) {
                window.location.href = 'delete_user.php?id=' + id;
            }
        }
    </script>
</body>
</html>