<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

// Gider ekleme yetkisi kontrolü
checkPagePermission('muhasebe_gider_ekle'); 