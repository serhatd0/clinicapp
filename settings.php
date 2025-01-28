<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

// Ayarlar erişim kontrolü
checkPagePermission('ayarlar_erisim');

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
    <style>
        .settings-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0;
        }

        .page-header {
            background: #fff;
            padding: 20px;
            margin-bottom: 20px;
            border-bottom: 1px solid #e9ecef;
        }

        .page-title {
            font-size: 1.5rem;
            margin: 0;
            color: #212529;
        }

        .settings-menu {
            background: #fff;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }

        .settings-menu .list-group-item {
            border: none;
            padding: 15px 20px;
            color: #495057;
            transition: all 0.2s;
        }

        .settings-menu .list-group-item:hover {
            background-color: #f8f9fa;
            color: #28a745;
        }

        .settings-menu .list-group-item.active {
            background-color: #e8f5e9;
            color: #28a745;
            font-weight: 500;
        }

        .settings-content {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
            margin-bottom: 20px;
        }

        .settings-content .card-header {
            background: none;
            padding: 20px;
            border-bottom: 1px solid #e9ecef;
        }

        .settings-content .card-body {
            padding: 20px;
        }

        .user-table {
            margin: 0;
            font-size: 0.95rem;
        }

        .user-table th,
        .user-table td {
            padding: 12px 15px;
            vertical-align: middle;
            border-color: #e9ecef;
        }

        .user-table th {
            background: #f8f9fa;
            font-weight: 500;
            color: #495057;
        }

        .user-actions .btn {
            padding: 6px 12px;
            border-radius: 6px;
        }

        @media (max-width: 767px) {
            .settings-menu {
                margin-bottom: 20px;
            }

            .settings-content {
                margin-bottom: 70px;
            }

            .page-header {
                padding: 15px;
                margin-bottom: 15px;
            }

            .user-table td {
                white-space: normal;
                padding: 10px;
            }

            .user-actions {
                display: flex;
                gap: 5px;
            }

            .user-info {
                display: flex;
                flex-direction: column;
                gap: 3px;
            }

            .user-email {
                font-size: 0.9rem;
                color: #6c757d;
            }

            .settings-content .card-header,
            .settings-content .card-body {
                padding: 15px;
            }
        }
    </style>
</head>

<body>
    <?php include 'includes/header.php'; ?>

    <div class="settings-container content-area py-4">
        <div class="page-header">
            <h1 class="page-title">Ayarlar</h1>
        </div>

        <div class="row g-4">
            <div class="col-md-12 col-lg-12 mx-auto">
                <div class="card settings-menu">
                    <div class="list-group list-group-flush">
                        <?php if (hasPermission('roller_erisim')): ?>
                            <a href="roles.php" class="list-group-item list-group-item-action">
                                <i class="fas fa-user-tag me-2"></i>Rol Yönetimi
                            </a>
                        <?php endif; ?>
                        
                        <?php if (hasPermission('kullanicilar_erisim')): ?>
                            <a href="users.php" class="list-group-item list-group-item-action">
                                <i class="fas fa-users me-2"></i>Kullanıcı Yönetimi
                            </a>
                        <?php endif; ?>
                        
                        <?php if (hasPermission('ayarlar_erisim')): ?>
                            <a href="template_settings.php" class="list-group-item list-group-item-action">
                                <i class="fas fa-calendar-alt me-2"></i>Randevu Şablonları
                            </a>
                        <?php endif; ?>
                        
                        <a href="profile.php" class="list-group-item list-group-item-action">
                            <i class="fas fa-user-circle me-2"></i>Profil Ayarları
                        </a>
                        
                        <a href="change_password.php" class="list-group-item list-group-item-action">
                            <i class="fas fa-key me-2"></i>Şifre Değiştir
                        </a>
                        
                        <a href="logout.php" class="list-group-item list-group-item-action text-danger">
                            <i class="fas fa-sign-out-alt me-2"></i>Çıkış Yap
                        </a>
                    </div>
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

        document.getElementById('confirmDelete').addEventListener('click', function () {
            if (userIdToDelete) {
                window.location.href = 'delete_user.php?id=' + userIdToDelete;
            }
        });
    </script>
</body>

</html>