<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';

$database = new Database();
$db = $database->connect();

// Tüm borçları getir
$stmt = $db->prepare("
    SELECT 
        hb.*,
        h.AD_SOYAD as HASTA_ADI,
        (SELECT COUNT(*) FROM taksitler t WHERE t.BORC_ID = hb.ID AND t.DURUM = 'odendi') as ODENEN_TAKSIT,
        (SELECT COUNT(*) FROM taksitler t WHERE t.BORC_ID = hb.ID) as TOPLAM_TAKSIT
    FROM hasta_borc hb
    JOIN hastalar h ON h.ID = hb.HASTA_ID
    ORDER BY hb.OLUSTURMA_TARIHI DESC
");
$stmt->execute();
$borclar = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tüm Borçlar</title>
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
                    <div class="card-body">
                        <h5 class="card-title">Tüm Borçlar</h5>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Hasta Adı</th>
                                        <th>Toplam Borç</th>
                                        <th>Kalan Borç</th>
                                        <th>Taksit Durumu</th>
                                        <th>Oluşturma Tarihi</th>
                                        <th>İşlemler</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($borclar as $borc): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($borc['HASTA_ADI']); ?></td>
                                            <td><?php echo number_format($borc['TOPLAM_BORC'], 2, ',', '.'); ?> ₺</td>
                                            <td><?php echo number_format($borc['KALAN_BORC'], 2, ',', '.'); ?> ₺</td>
                                            <td>
                                                <?php echo $borc['ODENEN_TAKSIT']; ?>/<?php echo $borc['TOPLAM_TAKSIT']; ?>
                                                <div class="progress" style="height: 5px;">
                                                    <div class="progress-bar" role="progressbar"
                                                        style="width: <?php echo ($borc['ODENEN_TAKSIT'] / $borc['TOPLAM_TAKSIT']) * 100; ?>%">
                                                    </div>
                                                </div>
                                            </td>
                                            <td><?php echo date('d.m.Y', strtotime($borc['OLUSTURMA_TARIHI'])); ?></td>
                                            <td>
                                                <a href="payment.php?patient=<?php echo $borc['HASTA_ID']; ?>"
                                                    class="btn btn-sm btn-primary">
                                                    <i class="fas fa-eye"></i> Detay
                                                </a>
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

    <?php include 'includes/nav.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>