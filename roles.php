<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

// Rol listesi erişim kontrolü
checkPagePermission('roller_erisim');

$database = new Database();
$db = $database->connect();

// Rolleri getir
$stmt = $db->query("SELECT * FROM roller ORDER BY ROL_ADI");
$roller = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Roller</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
    <style>
        .content-area {
            padding-top: 30px !important;
            padding-bottom: 80px !important;
        }

        .page-header {
            background: #fff;
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }

        .page-title {
            font-size: 1.2rem;
            color: #212529;
            font-weight: 600;
            margin: 0;
        }

        .role-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            margin-bottom: 20px;
            overflow: hidden;
        }

        .role-actions .btn {
            padding: 8px;
            border-radius: 6px;
            width: 35px;
            height: 35px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .btn-permissions {
            background-color: #17a2b8;
            color: white;
        }

        .btn-permissions:hover {
            background-color: #138496;
            color: white;
        }

        .table {
            margin-bottom: 0;
        }

        .table th {
            background: #f8f9fa;
            font-weight: 500;
            color: #495057;
            padding: 15px;
        }

        .table td {
            padding: 15px;
            vertical-align: middle;
        }

        @media (max-width: 767px) {
            .content-area {
                padding: 15px 15px 80px 15px !important;
            }

            .page-header {
                padding: 15px;
                margin-bottom: 15px;
            }

            .role-card {
                margin: 0 -5px;
            }

            .table td {
                padding: 12px;
            }

            .role-actions {
                display: flex;
                gap: 5px;
            }
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="container content-area">
        <div class="page-header d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center gap-3">
                <a href="settings.php" class="btn btn-outline-secondary btn-sm">
                    <i class="fas fa-arrow-left"></i>
                </a>
                <h1 class="page-title">Rol Yönetimi</h1>
            </div>
            <?php if (hasPermission('rol_ekle')): ?>
                <a href="add_role.php" class="btn btn-success">
                    <i class="fas fa-plus me-2"></i>Yeni Rol
                </a>
            <?php endif; ?>
        </div>

        <div class="role-card">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Rol Adı</th>
                            <th>Açıklama</th>
                            <th style="width: 150px;">İşlemler</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($roller as $rol): ?>
                            <tr>
                                <td class="fw-medium"><?php echo htmlspecialchars($rol['ROL_ADI']); ?></td>
                                <td><?php echo htmlspecialchars($rol['ACIKLAMA'] ?? ''); ?></td>
                                <td>
                                    <div class="role-actions d-flex gap-2">
                                        <?php if (hasPermission('rol_yetki_duzenle')): ?>
                                            <a href="edit_role_permissions.php?id=<?php echo $rol['ID']; ?>" 
                                               class="btn btn-sm btn-permissions" 
                                               title="Yetkileri Düzenle">
                                                <i class="fas fa-key"></i>
                                            </a>
                                        <?php endif; ?>

                                        <?php if (hasPermission('rol_duzenle')): ?>
                                            <a href="edit_role.php?id=<?php echo $rol['ID']; ?>" 
                                               class="btn btn-sm btn-primary" 
                                               title="Düzenle">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                        <?php endif; ?>

                                        <?php if (hasPermission('rol_sil')): ?>
                                            <button class="btn btn-sm btn-danger" 
                                                    onclick="deleteRole(<?php echo $rol['ID']; ?>)"
                                                    title="Sil">
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

    <?php include 'includes/nav.php'; ?>

    <!-- Silme Onay Modalı -->
    <div class="modal fade" id="deleteRoleModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Rol Sil</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Bu rolü silmek istediğinizden emin misiniz?</p>
                    <p class="text-danger"><small>Not: Bu role sahip kullanıcılar etkilenecektir.</small></p>
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
        let roleIdToDelete = null;
        const deleteModal = new bootstrap.Modal(document.getElementById('deleteRoleModal'));

        function deleteRole(id) {
            roleIdToDelete = id;
            deleteModal.show();
        }

        document.getElementById('confirmDelete').addEventListener('click', function () {
            if (roleIdToDelete) {
                window.location.href = 'delete_role.php?id=' + roleIdToDelete;
            }
        });
    </script>
</body>
</html> 