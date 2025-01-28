<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

// Randevu şablonları erişim kontrolü
checkPagePermission('randevu_sablon_erisim');

// Sadece yetkili kullanıcılar erişebilir
if (!($_SESSION['rol_id'] == 1 || $_SESSION['rol_id'] == 3)) {
    header('Location: settings.php');
    exit;
}

$database = new Database();
$db = $database->connect();

// Şablonları getir
$stmt = $db->query("SELECT * FROM randevu_sablonlari ORDER BY SIRA ASC");
$templates = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Şablon ekleme/güncelleme işlemi
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $db->beginTransaction();

        if (isset($_POST['add_template'])) {
            $stmt = $db->prepare("
                INSERT INTO randevu_sablonlari (ISLEM_ADI, GUN, SIRA) 
                VALUES (:islem_adi, :gun, :sira)
            ");

            $stmt->execute([
                ':islem_adi' => $_POST['islem_adi'],
                ':gun' => $_POST['gun'],
                ':sira' => $_POST['sira']
            ]);

            $message = "Şablon başarıyla eklendi.";
        } elseif (isset($_POST['update_template'])) {
            $stmt = $db->prepare("
                UPDATE randevu_sablonlari 
                SET ISLEM_ADI = :islem_adi,
                    GUN = :gun,
                    SIRA = :sira
                WHERE ID = :id
            ");

            $stmt->execute([
                ':islem_adi' => $_POST['islem_adi'],
                ':gun' => $_POST['gun'],
                ':sira' => $_POST['sira'],
                ':id' => $_POST['template_id']
            ]);

            $message = "Şablon başarıyla güncellendi.";
        }

        $db->commit();
        header("Location: template_settings.php?success=1");
        exit;
    } catch (Exception $e) {
        $db->rollBack();
        $error = $e->getMessage();
    }
}

// Şablon silme işlemi
if (isset($_GET['delete_template'])) {
    try {
        $stmt = $db->prepare("DELETE FROM randevu_sablonlari WHERE ID = :id");
        $stmt->execute([':id' => $_GET['delete_template']]);

        header("Location: template_settings.php?success=2");
        exit;
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Randevu Şablonları</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>

<body>
    <?php include 'includes/header.php'; ?>

    <div class="container py-4">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="mb-0">Randevu Şablonları</h5>
                            <small class="text-muted">Randevu serisi için işlem şablonlarını yönetin</small>
                        </div>
                        <button type="button" class="btn btn-success" onclick="showAddTemplateModal()">
                            <i class="fas fa-plus me-2"></i>Yeni Şablon
                        </button>
                    </div>
                    <div class="card-body">
                        <?php if (isset($_GET['success'])): ?>
                            <div class="alert alert-success alert-dismissible fade show">
                                <?php
                                if ($_GET['success'] == 1)
                                    echo "Şablon başarıyla kaydedildi.";
                                if ($_GET['success'] == 2)
                                    echo "Şablon başarıyla silindi.";
                                ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>

                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Sıra</th>
                                        <th>İşlem Adı</th>
                                        <th>Gün Aralığı</th>
                                        <th>İşlemler</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($templates as $template): ?>
                                        <tr>
                                            <td><?php echo $template['SIRA']; ?></td>
                                            <td><?php echo htmlspecialchars($template['ISLEM_ADI']); ?></td>
                                            <td><?php echo $template['GUN']; ?>. gün</td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <button type="button" class="btn btn-outline-primary"
                                                        onclick="showEditTemplateModal(<?php echo htmlspecialchars(json_encode($template)); ?>)">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-outline-danger"
                                                        onclick="deleteTemplate(<?php echo $template['ID']; ?>)">
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
            </div>
        </div>
    </div>

    <?php include 'includes/template_modal.php'; ?>
    <?php include 'includes/nav.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>