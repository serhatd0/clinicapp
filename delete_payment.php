<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

// Ödeme silme yetkisi kontrolü
checkPagePermission('odeme_sil'); 