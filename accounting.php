<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

// Muhasebe erişim kontrolü
checkPagePermission('cari_erisim');

// Butonlar için yetki kontrolleri
$canAddTransaction = hasPermission('cari_ekle');
$canEditTransaction = hasPermission('cari_duzenle');
$canDeleteTransaction = hasPermission('cari_sil');

$database = new Database();
$db = $database->connect();

// Cari hareketleri getir
$stmt = $db->query("
    SELECT c.*, k.AD_SOYAD as KASIYER_ADI,
           DATE_FORMAT(c.TARIH, '%d.%m.%Y %H:%i') as ISLEM_TARIHI
    FROM cari_hareketler c
    LEFT JOIN kullanicilar k ON k.ID = c.KULLANICI_ID
    ORDER BY c.TARIH DESC
    LIMIT 50
");
$hareketler = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Toplam gelir/gider
$stmt = $db->query("
    SELECT 
        SUM(CASE WHEN TUR = 'gelir' THEN TUTAR ELSE 0 END) as TOPLAM_GELIR,
        SUM(CASE WHEN TUR = 'gider' THEN TUTAR ELSE 0 END) as TOPLAM_GIDER,
        SUM(CASE WHEN TUR = 'gelir' THEN TUTAR ELSE -TUTAR END) as BAKIYE
    FROM cari_hareketler
");
$ozet = $stmt->fetch(PDO::FETCH_ASSOC);

// Kategori bazlı analiz
$stmt = $db->query("
    SELECT 
        k.KATEGORI_ADI,
        k.TUR,
        SUM(c.TUTAR) as TOPLAM_TUTAR,
        COUNT(*) as ISLEM_SAYISI
    FROM cari_hareketler c
    JOIN cari_kategoriler k ON k.ID = c.KATEGORI_ID
    WHERE c.TARIH >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    GROUP BY k.ID
    ORDER BY TOPLAM_TUTAR DESC
");
$kategori_analiz = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Aylık trend analizi
$stmt = $db->query("
    SELECT 
        DATE_FORMAT(TARIH, '%Y-%m') as AY,
        SUM(CASE WHEN TUR = 'gelir' THEN TUTAR ELSE 0 END) as GELIR,
        SUM(CASE WHEN TUR = 'gider' THEN TUTAR ELSE 0 END) as GIDER,
        COUNT(DISTINCT CASE WHEN TUR = 'gelir' THEN DATE(TARIH) END) as GELIR_GUN_SAYISI,
        COUNT(DISTINCT CASE WHEN TUR = 'gider' THEN DATE(TARIH) END) as GIDER_GUN_SAYISI
    FROM cari_hareketler
    WHERE TARIH >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY DATE_FORMAT(TARIH, '%Y-%m')
    ORDER BY AY DESC
");
$aylik_trend = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cari İşlemler</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link href="assets/css/style.css" rel="stylesheet">
    <style>
        .summary-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .summary-card {
            border-radius: 10px;
            padding: 20px;
            color: white;
            transition: transform 0.2s;
            height: 100%;
        }

        .summary-card:hover {
            transform: translateY(-5px);
        }

        .summary-card .icon-box {
            background: rgba(255, 255, 255, 0.2);
            min-width: 60px;
            min-height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 1rem;
        }

        .summary-card .content {
            flex: 1;
            min-width: 0;
        }

        .summary-card h3 {
            font-size: clamp(1.2rem, 3vw, 1.75rem);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .summary-card.income {
            background: linear-gradient(45deg, #28a745, #34ce57);
        }

        .summary-card.expense {
            background: linear-gradient(45deg, #dc3545, #e4606d);
        }

        .summary-card.balance {
            background: linear-gradient(45deg, #007bff, #4da3ff);
        }

        .transaction-type {
            width: 80px;
        }

        .category-summary {
            background: #fff;
            transition: transform 0.2s, box-shadow 0.2s;
            border: 1px solid #dee2e6;
        }

        .category-summary:hover {
            transform: translateY(-3px);
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
        }

        .category-summary h4 {
            font-weight: 600;
        }

        /* Yuvarlak buton stili */
        .btn.rounded-circle {
            width: 40px;
            height: 40px;
            padding: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
        }

        /* Dropdown menü stili */
        .dropdown-menu {
            border: none;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
            padding: 0.5rem;
        }

        .dropdown-item {
            border-radius: 6px;
            padding: 0.5rem 1rem;
        }

        .dropdown-item:hover {
            background-color: #f8f9fa;
        }

        .dropdown-item.text-success:hover {
            background-color: rgba(40, 167, 69, 0.1);
        }

        .dropdown-item.text-danger:hover {
            background-color: rgba(220, 53, 69, 0.1);
        }
    </style>
</head>

<body>
    <?php include 'includes/header.php'; ?>

    <div class="container py-4">
        <!-- Özet Kartları -->
        <div class="summary-cards">
            <div class="summary-card income">
                <div class="d-flex align-items-center">
                    <div class="icon-box">
                        <i class="fas fa-arrow-up fa-2x"></i>
                    </div>
                    <div class="content">
                        <h6 class="mb-1">Toplam Gelir</h6>
                        <h3 class="mb-0"><?php echo number_format($ozet['TOPLAM_GELIR'], 2, ',', '.'); ?> ₺</h3>
                    </div>
                </div>
            </div>
            <div class="summary-card expense">
                <div class="d-flex align-items-center">
                    <div class="icon-box">
                        <i class="fas fa-arrow-down fa-2x"></i>
                    </div>
                    <div class="content">
                        <h6 class="mb-1">Toplam Gider</h6>
                        <h3 class="mb-0"><?php echo number_format($ozet['TOPLAM_GIDER'], 2, ',', '.'); ?> ₺</h3>
                    </div>
                </div>
            </div>
            <div class="summary-card balance">
                <div class="d-flex align-items-center">
                    <div class="icon-box">
                        <i class="fas fa-wallet fa-2x"></i>
                    </div>
                    <div class="content">
                        <h6 class="mb-1">Genel Bakiye</h6>
                        <h3 class="mb-0"><?php echo number_format($ozet['BAKIYE'], 2, ',', '.'); ?> ₺</h3>
                    </div>
                </div>
            </div>
        </div>

        <!-- Kategori Özeti -->
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Kategori Bazlı Özet (Son 30 Gün)</h5>
                </div>
                <div class="card-body">
                    <!-- Gelir Kategorileri -->
                    <h6 class="border-bottom pb-2 mb-3">
                        <i class="fas fa-arrow-up text-success me-2"></i>Gelirler
                    </h6>
                    <div class="row g-4 mb-4">
                        <?php
                        $gelir_toplam = 0;
                        foreach ($kategori_analiz as $kategori):
                            if ($kategori['TUR'] == 'gelir'):
                                $gelir_toplam += $kategori['TOPLAM_TUTAR'];
                                ?>
                                <div class="col-md-6 col-lg-4">
                                    <div class="category-summary p-3 rounded border">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <h6 class="mb-1"><?php echo htmlspecialchars($kategori['KATEGORI_ADI']); ?>
                                                </h6>
                                                <h4 class="mb-0 text-success">
                                                    <?php echo number_format($kategori['TOPLAM_TUTAR'], 2, ',', '.'); ?> ₺
                                                </h4>
                                            </div>
                                            <div class="text-end">
                                                <span class="badge bg-success">
                                                    <?php echo $kategori['ISLEM_SAYISI']; ?> İşlem
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <?php
                            endif;
                        endforeach;
                        ?>
                    </div>

                    <!-- Gider Kategorileri -->
                    <h6 class="border-bottom pb-2 mb-3">
                        <i class="fas fa-arrow-down text-danger me-2"></i>Giderler
                    </h6>
                    <div class="row g-4">
                        <?php
                        $gider_toplam = 0;
                        foreach ($kategori_analiz as $kategori):
                            if ($kategori['TUR'] == 'gider'):
                                $gider_toplam += $kategori['TOPLAM_TUTAR'];
                                ?>
                                <div class="col-md-6 col-lg-4">
                                    <div class="category-summary p-3 rounded border">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <h6 class="mb-1"><?php echo htmlspecialchars($kategori['KATEGORI_ADI']); ?>
                                                </h6>
                                                <h4 class="mb-0 text-danger">
                                                    <?php echo number_format($kategori['TOPLAM_TUTAR'], 2, ',', '.'); ?> ₺
                                                </h4>
                                            </div>
                                            <div class="text-end">
                                                <span class="badge bg-danger">
                                                    <?php echo $kategori['ISLEM_SAYISI']; ?> İşlem
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <?php
                            endif;
                        endforeach;
                        ?>
                    </div>

                    <!-- Toplam Özet -->
                    <div class="mt-4 pt-3 border-top">
                        <div class="row text-center">
                            <div class="col-md-6">
                                <h6 class="text-success mb-1">Toplam Gelir (30 Gün)</h6>
                                <h4 class="text-success"><?php echo number_format($gelir_toplam, 2, ',', '.'); ?> ₺
                                </h4>
                            </div>
                            <div class="col-md-6">
                                <h6 class="text-danger mb-1">Toplam Gider (30 Gün)</h6>
                                <h4 class="text-danger"><?php echo number_format($gider_toplam, 2, ',', '.'); ?> ₺
                                </h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- İşlem Listesi -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Cari Hareketler</h5>
                <div class="dropdown">
                    <button type="button" class="btn btn-primary rounded-circle" data-bs-toggle="dropdown"
                        aria-expanded="false">
                        <i class="fas fa-plus"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li>
                            <a class="dropdown-item text-success" href="#" onclick="showTransactionModal('gelir')">
                                <i class="fas fa-arrow-up me-2"></i>Gelir Ekle
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item text-danger" href="#" onclick="showTransactionModal('gider')">
                                <i class="fas fa-arrow-down me-2"></i>Gider Ekle
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Tarih</th>
                                <th>Tür</th>
                                <th>Açıklama</th>
                                <th>Tutar</th>
                                <th>Kasiyer</th>
                                <th>İşlemler</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($hareketler as $hareket): ?>
                                <tr>
                                    <td><?php echo $hareket['ISLEM_TARIHI']; ?></td>
                                    <td>
                                        <span
                                            class="badge bg-<?php echo $hareket['TUR'] == 'gelir' ? 'success' : 'danger'; ?> transaction-type">
                                            <?php echo ucfirst($hareket['TUR']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars($hareket['ACIKLAMA']); ?></td>
                                    <td
                                        class="fw-bold text-<?php echo $hareket['TUR'] == 'gelir' ? 'success' : 'danger'; ?>">
                                        <?php echo number_format($hareket['TUTAR'], 2, ',', '.'); ?> ₺
                                    </td>
                                    <td><?php echo htmlspecialchars($hareket['KASIYER_ADI']); ?></td>
                                    <td>
                                        <div class="btn-group">
                                            <?php if ($canEditTransaction): ?>
                                                <button type="button" class="btn btn-primary btn-sm" onclick="editTransaction(<?php echo $hareket['ID']; ?>)">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                            <?php endif; ?>
                                            
                                            <?php if ($canDeleteTransaction): ?>
                                                <button type="button" class="btn btn-sm btn-outline-danger"
                                                    onclick="deleteTransaction(<?php echo $hareket['ID']; ?>)">
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

    <?php include 'includes/transaction_modal.php'; ?>
    <?php include 'includes/nav.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>