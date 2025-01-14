<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';

// Auth'u burada include etmiyoruz
session_start();

// Zaten giriş yapmışsa ana sayfaya yönlendir
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        $error = 'Lütfen tüm alanları doldurunuz.';
    } else {
        try {
            $database = new Database();
            $db = $database->connect();
            
            $stmt = $db->prepare("SELECT * FROM kullanicilar WHERE EMAIL = :email AND DURUM = 'aktif'");
            $stmt->execute([':email' => $email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user && password_verify($password, $user['SIFRE'])) {
                $_SESSION['user_id'] = $user['ID'];
                $_SESSION['user_name'] = $user['AD_SOYAD'];
                $_SESSION['rol_id'] = $user['ROL_ID'];
                
                header('Location: index.php');
                exit;
            } else {
                $error = 'Geçersiz email veya şifre.';
            }
        } catch (Exception $e) {
            $error = 'Bir hata oluştu: ' . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Giriş Yap</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container">
        <div class="row justify-content-center align-items-center min-vh-100">
            <div class="col-md-4">
                <div class="login-logo text-center mb-4">
                    <img src="assets/images/logo.png" alt="Art Hair Logo" class="img-fluid">
                </div>
                <div class="card shadow">
                    <div class="card-body p-5">
                        <h3 class="text-center mb-4">Giriş Yap</h3>
                        
                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php endif; ?>
                        
                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" class="form-control" name="email" required>
                            </div>
                            
                            <div class="mb-4">
                                <label class="form-label">Şifre</label>
                                <input type="password" class="form-control" name="password" required>
                            </div>
                            
                            <button type="submit" class="btn btn-success w-100">
                                <i class="fas fa-sign-in-alt me-2"></i>Giriş Yap
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 