<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

// Borç listesi erişim kontrolü
checkPagePermission('odeme_listesi_erisim');

// Butonlar için yetki kontrolleri
$canAddDebt = hasPermission('odeme_ekle');
$canEditDebt = hasPermission('odeme_duzenle');
$canDeleteDebt = hasPermission('odeme_sil');

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

                                                <?php if ($canDeleteDebt): ?>
                                                    <button type="button" class="btn btn-danger btn-sm"
                                                        onclick="deleteDebt(<?php echo $borc['ID']; ?>)">
                                                        <i class="fas fa-trash"></i> Sil
                                                    </button>
                                                <?php endif; ?>
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

    <!-- Silme Onay Modal -->
    <div class="modal fade" id="deleteConfirmModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Borç Planını Sil</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Bu borç planını silmek istediğinizden emin misiniz?</p>
                    <p class="text-danger">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Bu işlem geri alınamaz ve tüm ödeme kayıtları silinecektir!
                    </p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                    <button type="button" class="btn btn-danger" id="confirmDeleteBtn">
                        <i class="fas fa-trash me-2"></i>Sil
                    </button>
                </div>
            </div>
        </div>
    </div>

    <?php include 'includes/nav.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let deleteModal;
        let borcToDelete;

        document.addEventListener('DOMContentLoaded', function () {
            deleteModal = new bootstrap.Modal(document.getElementById('deleteConfirmModal'));

            // Silme onay butonuna tıklandığında
            document.getElementById('confirmDeleteBtn').addEventListener('click', function () {
                if (borcToDelete) {
                    // AJAX ile silme işlemi
                    fetch('delete_debt.php?id=' + borcToDelete)
                        .then(response => response.json())
                        .then(data => {
                            deleteModal.hide();
                            if (data.success) {
                                // Başarılı silme işlemi
                                showAlert('success', 'Borç planı başarıyla silindi!');
                                setTimeout(() => location.reload(), 1500);
                            } else {
                                // Hata durumu
                                showAlert('danger', 'Silme işlemi başarısız: ' + data.error);
                            }
                        })
                        .catch(error => {
                            deleteModal.hide();
                            showAlert('danger', 'Bir hata oluştu: ' + error);
                        });
                }
            });
        });

        function deleteDebt(borcId) {
            borcToDelete = borcId;
            deleteModal.show();
        }

        // Alert gösterme fonksiyonu
        function showAlert(type, message) {
            const alertDiv = document.createElement('div');
            alertDiv.className = `alert alert-${type} alert-dismissible fade show floating-alert`;
            alertDiv.innerHTML = `
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            document.body.appendChild(alertDiv);

            // 3 saniye sonra alert'i kaldır
            setTimeout(() => alertDiv.remove(), 3000);
        }
    </script>
</body>

</html>