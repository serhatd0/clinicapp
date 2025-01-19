<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $database = new Database();
        $db = $database->connect();

        $stmt = $db->prepare("
            INSERT INTO cari_hareketler (TUR, TUTAR, ACIKLAMA, TARIH, KULLANICI_ID, KATEGORI_ID)
            VALUES (:tur, :tutar, :aciklama, :tarih, :kullanici_id, :kategori_id)
        ");

        $stmt->execute([
            ':tur' => $_POST['tur'],
            ':tutar' => $_POST['tutar'],
            ':aciklama' => $_POST['aciklama'],
            ':tarih' => $_POST['tarih'],
            ':kullanici_id' => $_SESSION['user_id'],
            ':kategori_id' => $_POST['kategori_id']
        ]);

        header('Location: accounting.php?success=1');
    } catch (Exception $e) {
        header('Location: accounting.php?error=1');
    }
    exit;
}