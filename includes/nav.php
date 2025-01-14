<nav class="mobile-nav">
    <ul class="nav-items">
        <li>
            <a href="index.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>">
                <i class="fas fa-home"></i>
                <span>Ana Sayfa</span>
            </a>
        </li>
        <li>
            <a href="patients.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'patients.php' ? 'active' : ''; ?>">
                <i class="fas fa-users"></i>
                <span>Hastalar</span>
            </a>
        </li>
        <li>
            <a href="appointments.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'appointments.php' ? 'active' : ''; ?>">
                <i class="fas fa-calendar-alt"></i>
                <span>Randevular</span>
            </a>
        </li>
        <li>
            <a href="settings.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'settings.php' ? 'active' : ''; ?>">
                <i class="fas fa-cog"></i>
                <span>Ayarlar</span>
            </a>
        </li>
    </ul>
</nav> 