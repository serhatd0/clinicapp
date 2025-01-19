<nav class="mobile-nav">
    <ul class="nav-items">

        <li>
            <a href="patients.php"
                class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'patients.php' ? 'active' : ''; ?>">
                <i class="fas fa-users"></i>

            </a>
        </li>
        <li>
            <a href="appointments.php"
                class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'appointments.php' ? 'active' : ''; ?>">
                <i class="fas fa-calendar-alt"></i>

            </a>
        </li>
        <li>
            <a href="index.php"
                class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>">
                <i class="fas fa-home"></i>

            </a>
        </li>
        <li>
            <a href="accounting.php"
                class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'accounting.php' ? 'active' : ''; ?>">
                <i class="fas fa-file-invoice-dollar"></i>

            </a>
        </li>
        <li>
            <a href="settings.php"
                class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'settings.php' ? 'active' : ''; ?>">
                <i class="fas fa-cog"></i>

            </a>
        </li>

    </ul>
</nav>