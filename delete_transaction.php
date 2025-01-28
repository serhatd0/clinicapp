<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

// İşlem silme yetkisi kontrolü
checkPagePermission('muhasebe_islem_sil'); 