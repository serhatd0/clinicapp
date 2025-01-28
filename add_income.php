<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

// Gelir ekleme yetkisi kontrolü
checkPagePermission('muhasebe_gelir_ekle'); 