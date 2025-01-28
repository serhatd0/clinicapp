<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

// Muhasebe raporu görüntüleme yetkisi kontrolü
checkPagePermission('muhasebe_rapor_goruntule'); 