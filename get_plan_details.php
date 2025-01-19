<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';

$borcId = isset($_GET['borc_id']) ? (int) $_GET['borc_id'] : 0;

try {
    $database = new Database();
    $db = $database->connect();

    // Borç bilgilerini getir
    $stmt = $db->prepare("
        SELECT hb.*, h.AD_SOYAD
        FROM hasta_borc hb
        JOIN hastalar h ON h.ID = hb.HASTA_ID
        WHERE hb.ID = :id
    ");
    $stmt->execute([':id' => $borcId]);
    $borc = $stmt->fetch(PDO::FETCH_ASSOC);

    // Taksitleri getir
    $stmt = $db->prepare("
        SELECT t.*, 
               COALESCE(SUM(o.TUTAR), 0) as ODENEN_TUTAR,
               GROUP_CONCAT(
                   CONCAT(
                       DATE_FORMAT(o.ODEME_TARIHI, '%d.%m.%Y'), 
                       ' - ',
                       o.TUTAR,
                       'TL (',
                       o.ODEME_TURU,
                       ')'
                   ) SEPARATOR '<br>'
               ) as ODEME_GECMISI
        FROM taksitler t
        LEFT JOIN odemeler o ON o.TAKSIT_ID = t.ID
        WHERE t.BORC_ID = :borc_id
        GROUP BY t.ID
        ORDER BY t.TAKSIT_NO ASC
    ");
    $stmt->execute([':borc_id' => $borcId]);
    $taksitler = $stmt->fetchAll(PDO::FETCH_ASSOC);

    ?>
    <div class="plan-details">
        <div class="mb-4">
            <h6>Plan Bilgileri</h6>
            <table class="table table-sm">
                <tr>
                    <th>Plan Adı:</th>
                    <td><?php echo htmlspecialchars($borc['PLAN_ADI']); ?></td>
                </tr>
                <tr>
                    <th>Oluşturma Tarihi:</th>
                    <td><?php echo date('d.m.Y', strtotime($borc['OLUSTURMA_TARIHI'])); ?></td>
                </tr>
                <tr>
                    <th>Toplam Tutar:</th>
                    <td><?php echo number_format($borc['TOPLAM_BORC'], 2, ',', '.'); ?> ₺</td>
                </tr>
                <tr>
                    <th>Kalan Tutar:</th>
                    <td><?php echo number_format($borc['KALAN_BORC'], 2, ',', '.'); ?> ₺</td>
                </tr>
            </table>
        </div>

        <h6>Taksit Detayları</h6>
        <div class="table-responsive">
            <table class="table table-sm">
                <thead>
                    <tr>
                        <th>Taksit No</th>
                        <th>Vade Tarihi</th>
                        <th>Tutar</th>
                        <th>Durum</th>
                        <th>Ödeme Geçmişi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($taksitler as $taksit): ?>
                        <tr>
                            <td><?php echo $taksit['TAKSIT_NO']; ?></td>
                            <td><?php echo date('d.m.Y', strtotime($taksit['VADE_TARIHI'])); ?></td>
                            <td><?php echo number_format($taksit['TUTAR'], 2, ',', '.'); ?> ₺</td>
                            <td>
                                <span class="badge bg-<?php echo getTaksitStatusColor($taksit['DURUM']); ?>">
                                    <?php echo getTaksitStatusText($taksit['DURUM']); ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($taksit['ODEME_GECMISI']): ?>
                                    <small><?php echo $taksit['ODEME_GECMISI']; ?></small>
                                <?php else: ?>
                                    <small class="text-muted">Ödeme yapılmamış</small>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php
} catch (Exception $e) {
    echo '<div class="alert alert-danger">Bir hata oluştu: ' . htmlspecialchars($e->getMessage()) . '</div>';
}