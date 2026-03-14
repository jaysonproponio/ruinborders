<?php
$current_user_page = $current_user_page ?? pathinfo($_SERVER['PHP_SELF'] ?? '', PATHINFO_FILENAME);
$user_fullname = trim((string) ($user['fullname'] ?? ($_SESSION['user_fullname'] ?? 'User')));
$user_first_name = explode(' ', $user_fullname !== '' ? $user_fullname : 'User')[0];
$user_room_number = (string) ($user['room_number'] ?? '');
$user_profile_picture = trim((string) ($user['profile_picture'] ?? ''));
$user_initial = strtoupper(substr($user_fullname !== '' ? $user_fullname : 'U', 0, 1));
$has_new_payments = !empty($has_new_payments);
$has_new_receipts = !empty($has_new_receipts);
$has_new_announcements = !empty($has_new_announcements);

$user_nav_items = [
    ['page' => 'dashboard', 'href' => 'dashboard.php', 'icon' => 'fas fa-tachometer-alt', 'label' => 'Dashboard', 'badge_id' => null, 'badge_visible' => false],
    ['page' => 'payments', 'href' => 'payments.php', 'icon' => 'fas fa-credit-card', 'label' => 'Payment Status', 'badge_id' => 'badge-payments', 'badge_visible' => $has_new_payments],
    ['page' => 'receipts', 'href' => 'receipts.php', 'icon' => 'fas fa-receipt', 'label' => 'Upload Receipt', 'badge_id' => 'badge-receipts', 'badge_visible' => $has_new_receipts],
    ['page' => 'announcements', 'href' => 'announcements.php', 'icon' => 'fas fa-bullhorn', 'label' => 'Announcements', 'badge_id' => 'badge-announcements', 'badge_visible' => $has_new_announcements],
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
            <p class="brand-caption">User Dashboard</p>
        </div>
    </div>

    <ul class="sidebar-menu">
        <?php foreach ($user_nav_items as $item): ?>
            <li>
                <a href="<?php echo htmlspecialchars($item['href']); ?>"
                    class="<?php echo $current_user_page === $item['page'] ? 'active' : ''; ?>">
                    <i class="<?php echo htmlspecialchars($item['icon']); ?>"></i>
                    <?php echo htmlspecialchars($item['label']); ?>
                    <?php if (!empty($item['badge_id'])): ?>
                        <span id="<?php echo htmlspecialchars($item['badge_id']); ?>" class="notification-badge"
                            style="display: <?php echo $item['badge_visible'] ? 'flex' : 'none'; ?>;">!</span>
                    <?php endif; ?>
                </a>
            </li>
        <?php endforeach; ?>
    </ul>

    <div class="sidebar-profile">
        <div class="profile-info" onclick="toggleProfileDropdown(event)">
            <div class="profile-icon">
                <?php if ($user_profile_picture !== ''): ?>
                    <img src="../uploads/profiles/<?php echo htmlspecialchars($user_profile_picture); ?>" alt="Profile">
                <?php else: ?>
                    <?php echo htmlspecialchars($user_initial); ?>
                <?php endif; ?>
            </div>
            <div class="profile-details" style="line-height: 1.2;">
                <span class="profile-name" style="display: block; font-weight: 600;">
                    <?php echo htmlspecialchars($user_first_name); ?>
                </span>
                <hr style="border: none; border-top: 1px solid rgba(0,0,0,0.1); margin: 4px 0; width: 100%;">
                <span class="profile-role" style="font-size: 0.8rem; color: #666;">
                    Room <?php echo htmlspecialchars($user_room_number); ?>
                </span>
            </div>
            <i class="fas fa-chevron-up"></i>
        </div>
        <div class="profile-dropdown" id="profileDropdown" onclick="event.stopPropagation()">
            <a href="profile.php"><i class="fas fa-user"></i> Profile</a>
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
