<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Yetkisiz Erişim</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container content-area">
        <div class="row justify-content-center">
            <div class="col-md-6 text-center">
                <div class="alert alert-danger">
                    <h4><i class="fas fa-exclamation-triangle"></i> Yetkisiz Erişim</h4>
                    <p>Bu sayfaya erişim yetkiniz bulunmamaktadır.</p>
                    <a href="index.php" class="btn btn-primary mt-3">
                        <i class="fas fa-home"></i> Ana Sayfaya Dön
                    </a>
                </div>
            </div>
        </div>
    </div>

    <?php include 'includes/nav.php'; ?>
</body>
</html> 