<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

$patientId = isset($_GET['patient']) ? (int) $_GET['patient'] : 0;
$database = new Database();
$db = $database->connect();

// Hasta seçili değilse veya geçersiz hasta ID'si varsa
if (!$patientId) {
    ?>
    <!DOCTYPE html>
    <html lang="tr">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Hasta Seçimi</title>
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
                        <div class="card-header">
                            <h5 class="mb-0">Hasta Seçimi</h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-4">
                                <div class="input-group">
                                    <input type="text" id="searchInput" class="form-control" 
                                           placeholder="Hasta adı veya telefon numarası ile arama yapın...">
                                    <button class="btn btn-outline-secondary" type="button">
                                        <i class="fas fa-search"></i>
                                    </button>
                                </div>
                            </div>
                            
                            <div id="searchResults" class="list-group">
                                <!-- Arama sonuçları buraya gelecek -->
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <?php include 'includes/nav.php'; ?>

        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
        <script>
        const searchInput = document.getElementById('searchInput');
        const searchResults = document.getElementById('searchResults');
        let searchTimeout;
        
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            const query = this.value.trim();
            
            if (query.length < 2) {
                searchResults.innerHTML = '';
                return;
            }
            
            searchTimeout = setTimeout(() => {
                fetch('search_patients.php?search=' + encodeURIComponent(query))
                    .then(response => response.text())
                    .then(html => {
                        searchResults.innerHTML = html;
                        
                        // Her sonuca tıklanabilir link ekle
                        document.querySelectorAll('.patient-card').forEach(card => {
                            card.addEventListener('click', function() {
                                const patientId = this.dataset.patientId;
                                window.location.href = 'payment.php?patient=' + patientId;
                            });
                        });
                    });
            }, 300);
        });
        </script>
    </body>
    </html>
    <?php
    exit;
}

// Hasta bilgilerini getir
$stmt = $db->prepare("SELECT * FROM hastalar WHERE ID = :id");
$stmt->execute([':id' => $patientId]);
$patient = $stmt->fetch(PDO::FETCH_ASSOC);

// Borç bilgilerini getir
$stmt = $db->prepare("
    SELECT hb.*, 
           COALESCE(SUM(t.TUTAR), 0) as TAKSIT_TOPLAMI,
           COUNT(t.ID) as TAKSIT_SAYISI,
           (
               SELECT COUNT(*) 
               FROM taksitler t2 
               WHERE t2.BORC_ID = hb.ID AND t2.DURUM = 'odendi'
           ) as ODENEN_TAKSIT
    FROM hasta_borc hb
    LEFT JOIN taksitler t ON t.BORC_ID = hb.ID
    WHERE hb.HASTA_ID = :hasta_id
    GROUP BY hb.ID
    ORDER BY hb.OLUSTURMA_TARIHI DESC, hb.ID DESC
");
$stmt->execute([':hasta_id' => $patientId]);
$borclar = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Aktif borç planını bul
$aktif_borc = null;
foreach ($borclar as $borc) {
    if ($borc['AKTIF'] == 1) {
        $aktif_borc = $borc;
        break;
    }
}

// Taksitleri getir
$stmt = $db->prepare("
    SELECT t.*
    FROM taksitler t
    WHERE t.BORC_ID = :borc_id
    ORDER BY t.TAKSIT_NO ASC
");

// Seçili borç ID'si varsa onu kullan, yoksa aktif borcu kullan
$selected_borc_id = isset($_GET['borc_id']) ? (int)$_GET['borc_id'] : ($aktif_borc['ID'] ?? 0);
$stmt->execute([':borc_id' => $selected_borc_id]);
$taksitler = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Seçili borç bilgilerini getir
$selected_borc = null;
if ($selected_borc_id) {
    foreach ($borclar as $borc) {
        if ($borc['ID'] == $selected_borc_id) {
            $selected_borc = $borc;
            break;
        }
    }
}

// Yeni borç ekleme
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_debt'])) {
    try {
        $db->beginTransaction();

        // Borç kaydı oluştur
        $stmt = $db->prepare("
            INSERT INTO hasta_borc (HASTA_ID, TOPLAM_BORC, KALAN_BORC, AKTIF) 
            VALUES (:hasta_id, :toplam_borc, :toplam_borc, 1)
        ");
        $stmt->execute([
            ':hasta_id' => $patientId,
            ':toplam_borc' => $_POST['total_amount']
        ]);
        $borcId = $db->lastInsertId();

        // Taksitleri oluştur
        $taksitSayisi = $_POST['installment_count'];
        $taksitTutari = $_POST['total_amount'] / $taksitSayisi;
        $vadeTarihi = new DateTime();

        for ($i = 1; $i <= $taksitSayisi; $i++) {
            $stmt = $db->prepare("
                INSERT INTO taksitler (
                    BORC_ID, 
                    TAKSIT_NO, 
                    TUTAR, 
                    VADE_TARIHI
                ) VALUES (
                    :borc_id, 
                    :taksit_no, 
                    :tutar, 
                    :vade_tarihi
                )
            ");
            $stmt->execute([
                ':borc_id' => $borcId,
                ':taksit_no' => $i,
                ':tutar' => $taksitTutari,
                ':vade_tarihi' => $vadeTarihi->format('Y-m-d')
            ]);
            $vadeTarihi->modify('+1 month');
        }

        $db->commit();
        header("Location: payment.php?patient=" . $patientId . "&success=1");
        exit;
    } catch (Exception $e) {
        $db->rollBack();
        $error = $e->getMessage();
    }
}

// Taksit ödemesi
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['taksit_id']) && isset($_POST['tutar'])) {
    try {
        $db->beginTransaction();

        $taksitId = (int)$_POST['taksit_id'];
        $odemeTutari = (float)$_POST['tutar'];
        $odemeTuru = $_POST['odeme_turu'];
        
        // Önce taksit ve borç bilgilerini alalım
        $stmt = $db->prepare("
            SELECT t.*, hb.KALAN_BORC, hb.ID as BORC_ID
            FROM taksitler t
            JOIN hasta_borc hb ON hb.ID = t.BORC_ID
            WHERE t.ID = :taksit_id
        ");
        $stmt->execute([':taksit_id' => $taksitId]);
        $taksit = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Taksiti güncelle
        $stmt = $db->prepare("
            UPDATE taksitler 
            SET DURUM = CASE WHEN :odeme_tutari >= TUTAR THEN 'odendi' ELSE DURUM END,
                ODENEN_TUTAR = ODENEN_TUTAR + :odeme_tutari
            WHERE ID = :taksit_id
        ");
        
        $stmt->execute([
            ':taksit_id' => $taksitId,
            ':odeme_tutari' => $odemeTutari
        ]);

        // Borç tablosunu güncelle
        $stmt = $db->prepare("
            UPDATE hasta_borc 
            SET KALAN_BORC = KALAN_BORC - :odeme_tutari
            WHERE ID = :borc_id
        ");
        
        $stmt->execute([
            ':borc_id' => $taksit['BORC_ID'],
            ':odeme_tutari' => $odemeTutari
        ]);
        
        // Kalan taksitlerin tutarlarını güncelle
        $stmt = $db->prepare("
            SELECT COUNT(*) as kalan_taksit_sayisi
            FROM taksitler 
            WHERE BORC_ID = :borc_id 
            AND DURUM = 'bekliyor'
            AND ID != :taksit_id
        ");
        $stmt->execute([
            ':borc_id' => $taksit['BORC_ID'],
            ':taksit_id' => $taksitId
        ]);
        $kalanTaksitSayisi = $stmt->fetch(PDO::FETCH_ASSOC)['kalan_taksit_sayisi'];
        
        if ($kalanTaksitSayisi > 0) {
            $yeniKalanBorc = $taksit['KALAN_BORC'] - $odemeTutari;
            $yeniTaksitTutari = $yeniKalanBorc / $kalanTaksitSayisi;
            
            // Kalan taksitlerin tutarlarını güncelle
            $stmt = $db->prepare("
                UPDATE taksitler 
                SET TUTAR = :yeni_tutar
                WHERE BORC_ID = :borc_id 
                AND DURUM = 'bekliyor'
                AND ID != :taksit_id
            ");
            
            $stmt->execute([
                ':yeni_tutar' => $yeniTaksitTutari,
                ':borc_id' => $taksit['BORC_ID'],
                ':taksit_id' => $taksitId
            ]);
        }

        // Cari hareket kaydı oluştur
        $stmt = $db->prepare("
            INSERT INTO cari_hareketler (
                TUR, 
                TUTAR, 
                ACIKLAMA, 
                TARIH, 
                KULLANICI_ID, 
                KATEGORI_ID
            )
            SELECT 
                'gelir' as TUR,
                :tutar as TUTAR,
                CONCAT(
                    h.AD_SOYAD, 
                    ' - Taksit Ödemesi (', 
                    :odeme_turu,
                    ') - ',
                    t.TAKSIT_NO,
                    '/',
                    hb.TAKSIT_SAYISI,
                    '. Taksit'
                ) as ACIKLAMA,
                NOW() as TARIH,
                :kullanici_id as KULLANICI_ID,
                (SELECT ID FROM cari_kategoriler WHERE KATEGORI_ADI = 'Hasta Ödemeleri' LIMIT 1) as KATEGORI_ID
            FROM taksitler t
            JOIN hasta_borc hb ON hb.ID = t.BORC_ID
            JOIN hastalar h ON h.ID = hb.HASTA_ID
            WHERE t.ID = :taksit_id
        ");
        
        $stmt->execute([
            ':tutar' => $odemeTutari,
            ':odeme_turu' => $odemeTuru,
            ':kullanici_id' => $_SESSION['user_id'],
            ':taksit_id' => $taksitId
        ]);

        $db->commit();
        header("Location: payment.php?patient=" . $patientId);
        exit;
    } catch (Exception $e) {
        $db->rollBack();
        echo "Hata oluştu: " . $e->getMessage();
    }
}

// Yeni plan ekleme
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_new_plan'])) {
    try {
        $db->beginTransaction();
        
        // Mevcut planı pasife çek
        if ($aktif_borc) {
            $stmt = $db->prepare("UPDATE hasta_borc SET AKTIF = 0 WHERE ID = :id");
            $stmt->execute([':id' => $aktif_borc['ID']]);
        }
        
        // İlk taksit tarihi kontrolü
        $ilkTaksitTarihi = isset($_POST['first_payment_date']) ? $_POST['first_payment_date'] : date('Y-m-d');
        
        // Yeni plan oluştur
        $stmt = $db->prepare("
            INSERT INTO hasta_borc (
                HASTA_ID, 
                PLAN_ADI, 
                TOPLAM_BORC, 
                KALAN_BORC, 
                AKTIF,
                OLUSTURMA_TARIHI,
                TAKSIT_SAYISI
            ) VALUES (
                :hasta_id, 
                :plan_adi, 
                :toplam_borc, 
                :toplam_borc, 
                1,
                NOW(),
                :taksit_sayisi
            )
        ");
        
        $stmt->execute([
            ':hasta_id' => $patientId,
            ':plan_adi' => $_POST['plan_name'],
            ':toplam_borc' => $_POST['total_amount'],
            ':taksit_sayisi' => $_POST['installment_count']
        ]);
        
        $borcId = $db->lastInsertId();
        $taksitSayisi = $_POST['installment_count'];
        $taksitTutari = $_POST['total_amount'] / $taksitSayisi;
        $vadeTarihi = new DateTime($ilkTaksitTarihi);
        
        // Taksitleri oluştur
        for ($i = 1; $i <= $taksitSayisi; $i++) {
            $stmt = $db->prepare("
                INSERT INTO taksitler (
                    BORC_ID, 
                    TAKSIT_NO, 
                    TUTAR, 
                    VADE_TARIHI,
                    DURUM
                ) VALUES (
                    :borc_id, 
                    :taksit_no, 
                    :tutar, 
                    :vade_tarihi,
                    'bekliyor'
                )
            ");
            
            $stmt->execute([
                ':borc_id' => $borcId,
                ':taksit_no' => $i,
                ':tutar' => $taksitTutari,
                ':vade_tarihi' => $vadeTarihi->format('Y-m-d')
            ]);
            
            $vadeTarihi->modify('+1 month');
        }
        
        $db->commit();
        header("Location: payment.php?patient=" . $patientId . "&success=1");
        exit;
    } catch (Exception $e) {
        $db->rollBack();
        error_log("Ödeme Planı Oluşturma Hatası: " . $e->getMessage());
        $error = $e->getMessage();
    }
}

// Plan düzenleme
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit_plan'])) {
    try {
        $db->beginTransaction();
        
        foreach ($_POST['dates'] as $taksitId => $date) {
            if (isset($_POST['amounts'][$taksitId])) {
                $stmt = $db->prepare("
                    UPDATE taksitler 
                    SET VADE_TARIHI = :vade_tarihi,
                        TUTAR = :tutar
                    WHERE ID = :id AND DURUM != 'odendi'
                ");
                $stmt->execute([
                    ':vade_tarihi' => $date,
                    ':tutar' => $_POST['amounts'][$taksitId],
                    ':id' => $taksitId
                ]);
            }
        }
        
        $db->commit();
        header("Location: payment.php?patient=" . $patientId . "&success=3");
        exit;
    } catch (Exception $e) {
        $db->rollBack();
        $error = $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ödeme İşlemleri</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>

<body>
    <?php include 'includes/header.php'; ?>

    <div class="container py-4">
        <div class="row">
            <div class="col-md-12 mb-4">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Hasta Bilgileri</h5>
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <p class="mb-0">Ad Soyad: <?php echo htmlspecialchars($patient['AD_SOYAD']); ?></p>
                                <?php if ($aktif_borc): ?>
                                    <p class="mb-0">Aktif Plan: <?php echo htmlspecialchars($aktif_borc['PLAN_ADI']); ?></p>
                                    <p class="mb-0">Toplam Borç: <?php echo number_format($aktif_borc['TOPLAM_BORC'], 2, ',', '.'); ?> ₺</p>
                                    <p>Kalan Borç: <?php echo number_format($aktif_borc['KALAN_BORC'], 2, ',', '.'); ?> ₺</p>
                                <?php endif; ?>
                            </div>
                            <div>
                                <button type="button" class="btn btn-success" onclick="showNewPlanModal()">
                                    <i class="fas fa-plus"></i> Yeni Ödeme Planı
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Aktif Ödeme Planı -->
            <?php if ($selected_borc): ?>
                <div class="col-md-12 mb-4">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">
                                <?php echo htmlspecialchars($selected_borc['PLAN_ADI']); ?>
                                <?php if ($selected_borc['AKTIF']): ?>
                                    <span class="badge bg-success ms-2">Aktif Plan</span>
                                <?php endif; ?>
                            </h5>
                            <button type="button" class="btn btn-primary btn-sm" onclick="showEditPlanModal()">
                                <i class="fas fa-edit"></i> Planı Düzenle
                            </button>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <p class="mb-1">Toplam Borç: <?php echo number_format($selected_borc['TOPLAM_BORC'], 2, ',', '.'); ?> ₺</p>
                                <p class="mb-1">Kalan Borç: <?php echo number_format($selected_borc['KALAN_BORC'], 2, ',', '.'); ?> ₺</p>
                            </div>
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Taksit No</th>
                                            <th>Vade Tarihi</th>
                                            <th>Tutar</th>
                                            <th>Durum</th>
                                            <th>İşlem</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($taksitler as $taksit): ?>
                                            <tr>
                                                <td><?php echo $taksit['TAKSIT_NO']; ?></td>
                                                <td><?php echo date('d.m.Y', strtotime($taksit['VADE_TARIHI'])); ?></td>
                                                <td>
                                                    <?php echo number_format($taksit['TUTAR'], 2, ',', '.'); ?> ₺
                                                    <?php if ($taksit['ODENEN_TUTAR'] > 0 && $taksit['ODENEN_TUTAR'] < $taksit['TUTAR']): ?>
                                                        <br>
                                                        <small class="text-success">
                                                            (<?php echo number_format($taksit['ODENEN_TUTAR'], 2, ',', '.'); ?> ₺ ödendi)
                                                        </small>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <span
                                                        class="badge bg-<?php echo getTaksitStatusColor($taksit['DURUM']); ?>">
                                                        <?php 
                                                        if ($taksit['ODENEN_TUTAR'] >= $taksit['TUTAR']) {
                                                            echo "Ödendi";
                                                        } elseif ($taksit['ODENEN_TUTAR'] > 0) {
                                                            echo "Kısmi Ödeme";
                                                        } else {
                                                            echo getTaksitStatusText($taksit['DURUM']); 
                                                        }
                                                        ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php if ($taksit['ODENEN_TUTAR'] < $taksit['TUTAR'] && $taksit['DURUM'] != 'odendi'): ?>
                                                        <button type="button" class="btn btn-sm btn-success"
                                                            onclick="showTransactionModal('gelir', <?php echo $taksit['ID']; ?>, <?php echo $taksit['TUTAR']; ?>)">
                                                            <i class="fas fa-money-bill-wave"></i> Öde
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
            <?php endif; ?>

            <!-- Geçmiş Ödeme Planları -->
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Ödeme Geçmişi</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Plan Adı</th>
                                        <th>Oluşturma Tarihi</th>
                                        <th>Toplam Tutar</th>
                                        <th>Kalan Tutar</th>
                                        <th>Taksit Durumu</th>
                                        <th>Durum</th>
                                        <th>İşlemler</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($borclar as $borc): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($borc['PLAN_ADI']); ?></td>
                                            <td><?php echo date('d.m.Y', strtotime($borc['OLUSTURMA_TARIHI'])); ?></td>
                                            <td><?php echo number_format($borc['TOPLAM_BORC'], 2, ',', '.'); ?> ₺</td>
                                            <td><?php echo number_format($borc['KALAN_BORC'], 2, ',', '.'); ?> ₺</td>
                                            <td>
                                                <?php echo $borc['ODENEN_TAKSIT']; ?>/<?php echo $borc['TAKSIT_SAYISI']; ?>
                                                <div class="progress" style="height: 5px;">
                                                    <div class="progress-bar" role="progressbar" 
                                                         style="width: <?php echo ($borc['TAKSIT_SAYISI'] > 0 ? ($borc['ODENEN_TAKSIT'] / $borc['TAKSIT_SAYISI']) * 100 : 0); ?>%">
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?php echo $borc['AKTIF'] ? 'success' : 'secondary'; ?>">
                                                    <?php echo $borc['AKTIF'] ? 'Aktif' : 'Pasif'; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="btn-group">
                                                    <a href="?patient=<?php echo $patientId; ?>&borc_id=<?php echo $borc['ID']; ?>" 
                                                       class="btn btn-sm btn-primary">
                                                        <i class="fas fa-edit"></i> Düzenle
                                                    </a>
                                                    <button type="button" class="btn btn-sm btn-info" 
                                                            onclick="showPlanDetails(<?php echo $borc['ID']; ?>)">
                                                        <i class="fas fa-eye"></i> Detay
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

    <!-- İşlem Ekleme Modal -->
    <div class="modal fade" id="transactionModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="transactionModalTitle">Yeni İşlem</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="tur" id="islemTuru">
                        <input type="hidden" name="taksit_id" id="taksitId">
                        <input type="hidden" name="kalan_borc" id="kalanBorc">
                        
                        <div class="mb-3">
                            <label class="form-label">Tutar</label>
                            <div class="input-group">
                                <input type="number" class="form-control" name="tutar" id="tutarInput" 
                                    step="0.01" min="0" required onchange="validateTotalPayment(this)">
                                <span class="input-group-text">₺</span>
                            </div>
                            <small class="form-text text-muted">Kalan toplam borç: <span id="kalanBorcText"></span> ₺</small>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Ödeme Türü</label>
                            <select class="form-select" name="odeme_turu" required>
                                <option value="nakit">Nakit</option>
                                <option value="kredi_karti">Kredi Kartı</option>
                                <option value="havale">Havale/EFT</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                        <button type="submit" class="btn btn-success" id="submitBtn">Kaydet</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Yeni Plan Modal -->
    <div class="modal fade" id="newPlanModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Yeni Ödeme Planı</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" id="newPlanForm">
                    <div class="modal-body">
                        <input type="hidden" name="add_new_plan" value="1">
                        
                        <div class="mb-3">
                            <label class="form-label">Plan Adı</label>
                            <input type="text" class="form-control" name="plan_name" required
                                placeholder="Örn: Saç Ekimi Ödemesi">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Toplam Borç Tutarı (₺)</label>
                            <input type="number" step="0.01" min="0" class="form-control" name="total_amount" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Taksit Sayısı</label>
                            <select class="form-select" name="installment_count" required>
                                <?php for($i = 1; $i <= 24; $i++): ?>
                                    <option value="<?php echo $i; ?>"><?php echo $i; ?> Taksit</option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">İlk Taksit Tarihi</label>
                            <input type="date" class="form-control" name="first_payment_date" 
                                   value="<?php echo date('Y-m-d'); ?>"
                                   required min="<?php echo date('Y-m-d'); ?>">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                        <button type="submit" class="btn btn-success">Plan Oluştur</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Plan Düzenleme Modal -->
    <div class="modal fade" id="editPlanModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Ödeme Planını Düzenle</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" id="editPlanForm">
                    <div class="modal-body">
                        <input type="hidden" name="edit_plan" value="1">
                        
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Taksit No</th>
                                        <th>Vade Tarihi</th>
                                        <th>Tutar</th>
                                        <th>Durum</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($taksitler as $taksit): ?>
                                        <tr>
                                            <td><?php echo $taksit['TAKSIT_NO']; ?></td>
                                            <td>
                                                <input type="date" class="form-control" 
                                                       name="dates[<?php echo $taksit['ID']; ?>]"
                                                       value="<?php echo date('Y-m-d', strtotime($taksit['VADE_TARIHI'])); ?>"
                                                       <?php echo $taksit['DURUM'] == 'odendi' ? 'disabled' : ''; ?>>
                                            </td>
                                            <td>
                                                <input type="number" step="0.01" class="form-control"
                                                       name="amounts[<?php echo $taksit['ID']; ?>]"
                                                       value="<?php echo $taksit['TUTAR']; ?>"
                                                       <?php echo $taksit['DURUM'] == 'odendi' ? 'disabled' : ''; ?>>
                                            </td>
                                            <td><?php echo getTaksitStatusText($taksit['DURUM']); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                        <button type="submit" class="btn btn-primary">Değişiklikleri Kaydet</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Plan Detay Modal -->
    <div class="modal fade" id="planDetailsModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Ödeme Planı Detayları</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="planDetailContent">
                        <!-- AJAX ile yüklenecek içerik -->
                        <div class="text-center">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Yükleniyor...</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Kapat</button>
                </div>
            </div>
        </div>
    </div>

    <?php include 'includes/nav.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function showPaymentModal(taksitId, tutar) {
            document.getElementById('installment_id').value = taksitId;
            document.getElementById('payment_amount').value = tutar;
            new bootstrap.Modal(document.getElementById('paymentModal')).show();
        }

        function showNewPlanModal() {
            document.getElementById('newPlanForm').reset();
            document.querySelector('input[name="first_payment_date"]').min = new Date().toISOString().split('T')[0];
            new bootstrap.Modal(document.getElementById('newPlanModal')).show();
        }
        
        function showEditPlanModal() {
            new bootstrap.Modal(document.getElementById('editPlanModal')).show();
        }
        
        // Taksit tutarlarını otomatik güncelle
        document.querySelectorAll('input[name^="amounts"]').forEach(input => {
            input.addEventListener('change', function() {
                updateRemainingPayments(this);
            });
        });
        
        function updateRemainingPayments(changedInput) {
            const remainingInputs = Array.from(document.querySelectorAll('input[name^="amounts"]:not([disabled])'))
                .filter(input => input !== changedInput);
            
            if (remainingInputs.length === 0) return;
            
            const totalDebt = <?php echo $selected_borc['KALAN_BORC'] ?? 0; ?>;
            const changedAmount = parseFloat(changedInput.value) || 0;
            const remainingAmount = totalDebt - changedAmount;
            const amountPerInstallment = remainingAmount / remainingInputs.length;
            
            remainingInputs.forEach(input => {
                input.value = amountPerInstallment.toFixed(2);
            });
        }

        // Form gönderilmeden önce kontrol
        document.getElementById('newPlanForm').addEventListener('submit', function(e) {
            const totalAmount = parseFloat(this.querySelector('[name="total_amount"]').value);
            const installmentCount = parseInt(this.querySelector('[name="installment_count"]').value);
            
            if (totalAmount <= 0) {
                alert('Lütfen geçerli bir tutar giriniz');
                e.preventDefault();
                return false;
            }
            
            if (installmentCount <= 0) {
                alert('Lütfen geçerli bir taksit sayısı seçiniz');
                e.preventDefault();
                return false;
            }
            
            return true;
        });

        function showPlanDetails(borcId) {
            const modal = new bootstrap.Modal(document.getElementById('planDetailsModal'));
            const contentDiv = document.getElementById('planDetailContent');
            
            // Modal'ı göster
            modal.show();
            
            // AJAX isteği gönder
            fetch(`get_plan_details.php?borc_id=${borcId}`)
                .then(response => response.text())
                .then(html => {
                    contentDiv.innerHTML = html;
                })
                .catch(error => {
                    contentDiv.innerHTML = `<div class="alert alert-danger">
                        Detaylar yüklenirken bir hata oluştu: ${error.message}
                    </div>`;
                });
        }

        function showTransactionModal(tur, taksitId = null, tutar = null) {
            document.getElementById('islemTuru').value = tur;
            document.getElementById('transactionModalTitle').textContent = 'Ödeme Al';
            
            const submitBtn = document.getElementById('submitBtn');
            if (submitBtn) {
                submitBtn.className = 'btn btn-success';
            }
            
            // Taksit ödemesi için
            if (taksitId && tutar) {
                document.getElementById('taksitId').value = taksitId;
                document.getElementById('tutarInput').value = '';
                document.getElementById('kalanBorc').value = <?php echo $aktif_borc['KALAN_BORC'] ?? 0; ?>;
                document.getElementById('kalanBorcText').textContent = <?php echo $aktif_borc['KALAN_BORC'] ?? 0; ?>;
            }
            
            new bootstrap.Modal(document.getElementById('transactionModal')).show();
        }

        function validateTotalPayment(input) {
            const kalanBorc = parseFloat(document.getElementById('kalanBorc').value);
            const girilenTutar = parseFloat(input.value);
            
            if (girilenTutar > kalanBorc) {
                alert('Girilen tutar, kalan toplam borçtan büyük olamaz!');
                input.value = kalanBorc;
            }
        }
    </script>
</body>

</html>