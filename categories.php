<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

// Kategori yönetimi yetkisi kontrolü
checkPagePermission('muhasebe_kategori_yonetimi'); 