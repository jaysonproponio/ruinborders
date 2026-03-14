<?php
$current_admin_page = $current_admin_page ?? pathinfo($_SERVER['PHP_SELF'] ?? '', PATHINFO_FILENAME);
$pending_receipts_count = isset($pending_receipts_count) ? (int) $pending_receipts_count : 0;
$show_pending_badge = !empty($show_pending_badge);
$admin_fullname = trim((string) ($_SESSION['admin_fullname'] ?? 'Admin'));
$admin_initial = strtoupper(substr($admin_fullname !== '' ? $admin_fullname : 'A', 0, 1));
$admin_first_name = explode(' ', $admin_fullname !== '' ? $admin_fullname : 'Admin')[0];

$admin_nav_items = [
    ['page' => 'dashboard', 'href' => 'dashboard.php', 'icon' => 'fas fa-tachometer-alt', 'label' => 'Dashboard'],
    ['page' => 'users', 'href' => 'users.php', 'icon' => 'fas fa-users', 'label' => 'Manage Users'],
    ['page' => 'payments', 'href' => 'payments.php', 'icon' => 'fas fa-credit-card', 'label' => 'Payments'],
    ['page' => 'payment_history', 'href' => 'payment_history.php', 'icon' => 'fas fa-history', 'label' => 'Payment History'],
    ['page' => 'receipts', 'href' => 'receipts.php', 'icon' => 'fas fa-receipt', 'label' => 'Payment Receipts'],
    ['page' => 'announcements', 'href' => 'announcements.php', 'icon' => 'fas fa-bullhorn', 'label' => 'Announcements'],
    ['page' => 'logs', 'href' => 'logs.php', 'icon' => 'fas fa-clipboard-list', 'label' => 'Admin Logs'],
];
?>
<button class="mobile-menu-toggle" type="button" onclick="toggleSidebar()" aria-label="Toggle sidebar">
    <i class="fas fa-bars"></i>
</button>

<div class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <div class="brand-lockup">
            <div class="brand-mark" aria-hidden="true">
                <span></span>
                <span></span>
            </div>
            <p class="brand-name">Ruin Borders</p>
            <p class="brand-caption">Admin Panel</p>
        </div>
    </div>

    <ul class="sidebar-menu">
        <?php foreach ($admin_nav_items as $item): ?>
            <li>
                <a href="<?php echo htmlspecialchars($item['href']); ?>"
                    class="<?php echo $current_admin_page === $item['page'] ? 'active' : ''; ?>">
                    <i class="<?php echo htmlspecialchars($item['icon']); ?>"></i>
                    <?php echo htmlspecialchars($item['label']); ?>
                    <?php if ($item['page'] === 'receipts'): ?>
                        <span id="badge-receipts" class="notification-badge"
                            style="display: <?php echo $show_pending_badge ? 'flex' : 'none'; ?>;">
                            <?php echo $pending_receipts_count; ?>
                        </span>
                    <?php endif; ?>
                </a>
            </li>
        <?php endforeach; ?>
    </ul>

    <div class="sidebar-profile">
        <div class="profile-info" onclick="toggleProfileDropdown(event)">
            <div class="profile-icon"><?php echo htmlspecialchars($admin_initial); ?></div>
            <div class="profile-details">
                <span class="profile-name"><?php echo htmlspecialchars($admin_first_name); ?></span>
                <span class="profile-role">Administrator</span>
            </div>
            <i class="fas fa-chevron-up"></i>
        </div>
        <div class="profile-dropdown" id="profileDropdown" onclick="event.stopPropagation()">
            <a href="profile.php">
                <i class="fas fa-user-circle"></i>
                <span>Profile</span>
            </a>
            <a href="#" onclick="toggleDarkMode(event)" id="darkModeBtn">
                <i class="fas fa-moon"></i>
                <span>Switch to Dark Mode</span>
            </a>
            <a href="../auth/logout.php" class="logout-text">
                <i class="fas fa-sign-out-alt"></i>
                Logout
            </a>
        </div>
    </div>
</div>
