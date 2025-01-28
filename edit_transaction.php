<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

// İşlem düzenleme yetkisi kontrolü
checkPagePermission('muhasebe_islem_duzenle'); 