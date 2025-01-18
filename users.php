<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

// Sadece admin yetkisi kontrolü
if (!isAdmin()) {
    header('Location: settings.php');
    exit;
}

$database = new Database();
$db = $database->connect();

// Kullanıcıları getir
$stmt = $db->prepare("
    SELECT k.*, r.ROL_ADI 
    FROM kullanicilar k 
    JOIN roller r ON k.ROL_ID = r.ID 
    ORDER BY k.AD_SOYAD
");
$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
    <style>
        body {
            background-color: #f8f9fa;
        }

        .content-area {
            padding-top: 30px !important;
        }

        .page-header {
            background: #fff;
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }

        .page-title {
            font-size: 1rem;
            color: #212529;
            font-weight: 600;
        }

        .btn-success {
            padding: 10px 20px;
            font-size: 1rem;
        }

        .btn-outline-secondary {
            border: none;
            background: #f1f3f5;
            color: #495057;
        }

        .btn-outline-secondary:hover {
            background: #e9ecef;
            border: none;
            color: #212529;
        }

        .btn-sm {
            padding: 8px 12px;
            font-size: 0.9rem;
        }

        .user-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
            margin-bottom: 20px;
            overflow: hidden;
        }

        .user-table {
            margin: 0;
        }

        .user-table th {
            background: #f8f9fa;
            font-weight: 500;
            color: #495057;
            padding: 15px;
            border-color: #e9ecef;
        }

        .user-table td {
            padding: 15px;
            vertical-align: middle;
            border-color: #e9ecef;
        }

        .user-table tr:hover {
            background-color: #f8f9fa;
        }

        .user-info {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .user-name {
            font-weight: 500;
            color: #212529;
            font-size: 1.1rem;
        }

        .user-email {
            color: #6c757d;
            font-size: 0.95rem;
        }

        .user-actions {
            display: flex;
            gap: 8px;
        }

        .user-actions .btn {
            padding: 8px;
            border-radius: 6px;
        }

        .badge {
            padding: 6px 12px;
            font-size: 0.9rem;
            font-weight: 500;
        }

        @media (max-width: 767px) {
            .content-area {
                padding: 20px 15px 80px 15px !important;
            }

            .page-header {
                padding: 15px;
                margin-bottom: 15px;
                display: flex;
                align-items: center;
                gap: 15px;
            }

            .page-title {
                font-size: 1.2rem;
                margin: 0;
            }

            .btn-success {
                padding: 12px 24px;
                font-size: 1.1rem;
                height: 45px;
                width: 45px;
                padding: 0;
                display: flex;
                align-items: center;
                justify-content: center;
            }

            .user-list {
                display: flex;
                flex-direction: column;
                gap: 10px;
                padding: 5px;
            }

            .user-item {
                background: white;
                border-radius: 10px;
                padding: 20px;
                box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
                border: 1px solid rgba(0, 0, 0, 0.05);
            }

            .user-item-header {
                display: flex;
                justify-content: space-between;
                align-items: flex-start;
                margin-bottom: 15px;
            }

            .user-item-info {
                margin-bottom: 15px;
                font-size: 1rem;
            }

            .user-item-actions {
                display: flex;
                justify-content: flex-end;
                gap: 10px;
            }

            .user-item-actions .btn {
                font-size: 1.1rem;
                width: 45px;
                height: 45px;
                display: flex;
                align-items: center;
                justify-content: center;
                border: none;
            }

            .btn-primary {
                background: #e3f2fd;
                color: #0d6efd;
            }

            .btn-danger {
                background: #fee2e2;
                color: #dc3545;
            }

            .btn-sm {
                padding: 0;
            }

            .user-name {
                font-size: 1.2rem;
                margin-bottom: 4px;
            }

            .user-email {
                font-size: 1rem;
            }

            .badge {
                padding: 8px 14px;
                font-size: 1rem;
            }

            .table-responsive {
                display: none;
            }
        }

        @media (min-width: 768px) {
            .user-list {
                display: none;
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
                <h1 class="page-title mb-0">Kullanıcı Yönetimi</h1>
            </div>
            <a href="add_user.php" class="btn btn-success">
                <i class="fas fa-user-plus "></i>
            </a>
        </div>

        <!-- Mobil Görünüm -->
        <div class="user-list">
            <?php foreach ($users as $user): ?>
                <div class="user-item">
                    <div class="user-item-header">
                        <div class="user-info">
                            <span class="user-name"><?php echo htmlspecialchars($user['AD_SOYAD']); ?></span>
                            <span class="user-email"><?php echo htmlspecialchars($user['EMAIL']); ?></span>
                        </div>
                        <span class="badge bg-<?php echo $user['DURUM'] == 'aktif' ? 'success' : 'danger'; ?>">
                            <?php echo ucfirst($user['DURUM']); ?>
                        </span>
                    </div>
                    <div class="user-item-info">
                        <small class="text-muted">Rol: <?php echo htmlspecialchars($user['ROL_ADI']); ?></small>
                    </div>
                    <div class="user-item-actions">
                        <a href="edit_user.php?id=<?php echo $user['ID']; ?>" class="btn btn-sm btn-primary">
                            <i class="fas fa-edit"></i>
                        </a>
                        <button class="btn btn-sm btn-danger" onclick="deleteUser(<?php echo $user['ID']; ?>)">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Masaüstü Görünüm -->
        <div class="user-card">
            <div class="table-responsive">
                <table class="table table-hover user-table">
                    <thead>
                        <tr>
                            <th>Ad Soyad</th>
                            <th>Email</th>
                            <th>Rol</th>
                            <th>Durum</th>
                            <th style="width: 120px;">İşlemler</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td data-label="Ad Soyad">
                                    <div class="user-info">
                                        <span class="user-name"><?php echo htmlspecialchars($user['AD_SOYAD']); ?></span>
                                        <span
                                            class="user-email d-md-none"><?php echo htmlspecialchars($user['EMAIL']); ?></span>
                                    </div>
                                </td>
                                <td data-label="Email" class="d-none d-md-table-cell">
                                    <?php echo htmlspecialchars($user['EMAIL']); ?>
                                </td>
                                <td data-label="Rol"><?php echo htmlspecialchars($user['ROL_ADI']); ?></td>
                                <td data-label="Durum">
                                    <span class="badge bg-<?php echo $user['DURUM'] == 'aktif' ? 'success' : 'danger'; ?>">
                                        <?php echo ucfirst($user['DURUM']); ?>
                                    </span>
                                </td>
                                <td data-label="İşlemler">
                                    <div class="user-actions">
                                        <a href="edit_user.php?id=<?php echo $user['ID']; ?>"
                                            class="btn btn-sm btn-primary">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <button class="btn btn-sm btn-danger"
                                            onclick="deleteUser(<?php echo $user['ID']; ?>)">
                                            <i class="fas fa-trash"></i>
                                        </button>
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