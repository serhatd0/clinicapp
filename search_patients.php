<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';

$database = new Database();
$db = $database->connect();

$search = isset($_GET['search']) ? $_GET['search'] : '';

if (strlen($search) >= 2) {
    $stmt = $db->prepare("
        SELECT * FROM hastalar 
        WHERE (AD_SOYAD LIKE :search OR TELEFON LIKE :search)
        AND STATUS = 1
        ORDER BY AD_SOYAD
        LIMIT 10
    ");

    $stmt->execute([':search' => '%' . $search . '%']);
    $patients = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($patients as $patient) {
        ?>
        <div class="patient-card list-group-item list-group-item-action" data-patient-id="<?php echo $patient['ID']; ?>">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="mb-1"><?php echo htmlspecialchars($patient['AD_SOYAD']); ?></h6>
                    <small class="text-muted">
                        <i class="fas fa-phone me-1"></i><?php echo htmlspecialchars($patient['TELEFON']); ?>
                    </small>
                </div>
                <div class="text-end">
                    <small class="text-muted d-block">
                        <i class="fas fa-calendar me-1"></i>
                        <?php echo date('d.m.Y', strtotime($patient['DOGUM_TARIHI'])); ?>
                    </small>
                    <small class="text-muted">
                        <i class="fas fa-id-card me-1"></i>
                        <?php echo htmlspecialchars($patient['KIMLIK_NO']); ?>
                    </small>
                </div>
            </div>
        </div>
        <?php
    }
} else {
    echo '<div class="text-center text-muted py-3">En az 2 karakter girerek arama yapın</div>';
}