<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

// Borç düzenleme yetkisi kontrolü
checkPagePermission('odeme_duzenle');

$database = new Database();
$db = $database->connect();

$borcId = isset($_GET['id']) ? (int) $_GET['id'] : 0;

// Borç bilgilerini getir
$stmt = $db->prepare("
    SELECT hb.*, h.AD_SOYAD
    FROM hasta_borc hb
    JOIN hastalar h ON h.ID = hb.HASTA_ID
    WHERE hb.ID = :id
");
$stmt->execute([':id' => $borcId]);
$borc = $stmt->fetch(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        // Borç bilgilerini güncelle
        $stmt = $db->prepare("
            UPDATE hasta_borc 
            SET ACIKLAMA = :aciklama,
                TOPLAM_BORC = :toplam_borc,
                KALAN_BORC = :kalan_borc
            WHERE ID = :id
        ");

        $stmt->execute([
            ':aciklama' => $_POST['aciklama'],
            ':toplam_borc' => $_POST['toplam_borc'],
            ':kalan_borc' => $_POST['kalan_borc'],
            ':id' => $borcId
        ]);

        header('Location: all_debts.php?message=updated');
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
    <title>Borç Düzenle</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>

<body>
    <?php include 'includes/header.php'; ?>

    <div class="container py-4">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Borç Düzenle</h5>
                        <a href="all_debts.php" class="btn btn-outline-secondary btn-sm">
                            <i class="fas fa-arrow-left me-1"></i>Geri
                        </a>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label">Hasta</label>
                                <input type="text" class="form-control"
                                    value="<?php echo htmlspecialchars($borc['AD_SOYAD']); ?>" readonly>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Açıklama</label>
                                <input type="text" class="form-control" name="aciklama"
                                    value="<?php echo htmlspecialchars($borc['ACIKLAMA']); ?>" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Toplam Borç</label>
                                <input type="number" class="form-control" name="toplam_borc" step="0.01"
                                    value="<?php echo $borc['TOPLAM_BORC']; ?>" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Kalan Borç</label>
                                <input type="number" class="form-control" name="kalan_borc" step="0.01"
                                    value="<?php echo $borc['KALAN_BORC']; ?>" required>
                            </div>

                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i>Kaydet
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include 'includes/nav.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>