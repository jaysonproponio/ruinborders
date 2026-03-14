<?php
require_once '../config/config.php';

if (isset($_GET['ajax']) && $_GET['ajax'] == 'get_payments') {
    $user_id = $_GET['user_id'];
    $year = $_GET['year'];

    $database = new Database();
    $db = $database->getConnection();

    $query = "SELECT * FROM payments WHERE user_id = :user_id AND year = :year";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->bindParam(':year', $year);
    $stmt->execute();
    $payments = $stmt->fetch(PDO::FETCH_ASSOC);

    header('Content-Type: application/json');
    echo json_encode($payments ?: new stdClass());
    exit;
}

if (!isAdmin()) {
    redirect('auth/login.php');
}

$database = new Database();
$db = $database->getConnection();

// Pending receipts notification logic (persistent per session until count increases)
try {
    $q = $db->prepare("SELECT COUNT(*) as count FROM payment_receipts WHERE status = 'pending'");
    $q->execute();
    $pending_receipts_count = (int) $q->fetch(PDO::FETCH_ASSOC)['count'];
} catch (Throwable $e) {
    $pending_receipts_count = 0;
}
$last_seen = isset($_SESSION['pending_receipts_seen_count']) ? (int) $_SESSION['pending_receipts_seen_count'] : 0;
$show_pending_badge = $pending_receipts_count > $last_seen;
$_SESSION['pending_receipts_seen_count'] = max($pending_receipts_count, $last_seen);

// Get statistics
$stats = [];

// Total boarders (active only)
$query = "SELECT COUNT(*) as total FROM users WHERE status = 'active'";
$stmt = $db->prepare($query);
$stmt->execute();
$stats['total_users'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Total payments this year
$query = "SELECT COUNT(*) as total FROM payments WHERE year = YEAR(CURDATE())";
$stmt = $db->prepare($query);
$stmt->execute();
$stats['payments_this_year'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Pending receipts
$query = "SELECT COUNT(*) as total FROM payment_receipts WHERE status = 'pending'";
$stmt = $db->prepare($query);
$stmt->execute();
$stats['pending_receipts'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Recent announcements
$query = "SELECT * FROM announcements ORDER BY created_at DESC LIMIT 5";
$stmt = $db->prepare($query);
$stmt->execute();
$recent_announcements = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get all users with their payment status for current year
// Get available years for filter
$available_years = [];
try {
    $y_query = $db->query("SELECT DISTINCT year FROM payments ORDER BY year DESC");
    $available_years = $y_query->fetchAll(PDO::FETCH_COLUMN);
} catch (Throwable $e) {
}

// Ensure current year is always available
$current_real_year = date('Y');
if (!in_array($current_real_year, $available_years)) {
    array_unshift($available_years, $current_real_year);
    // Sort again just in case
    rsort($available_years);
}

// Handle Year Filter
$selected_year = isset($_GET['year']) ? (int) $_GET['year'] : (int) $current_real_year;

// Get all active users with their payment status for selected year
$query = "SELECT u.*, p.january, p.february, p.march, p.april, p.may, p.june, 
                 p.july, p.august, p.september, p.october, p.november, p.december 
          FROM users u 
          LEFT JOIN payments p ON u.id = p.user_id AND p.year = :year 
          WHERE u.status = 'active'
          ORDER BY u.room_number, u.fullname";
$stmt = $db->prepare($query);
$stmt->bindParam(':year', $selected_year);
$stmt->execute();
$all_users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Distinct rooms for filters
$rooms = [];
try {
    $r = $db->query("SELECT DISTINCT room_number FROM users WHERE status = 'active' ORDER BY room_number");
    $rooms = $r->fetchAll(PDO::FETCH_COLUMN) ?: [];
} catch (Throwable $e) {
}

// Define months and colors for each user
$months = [
    'january',
    'february',
    'march',
    'april',
    'may',
    'june',
    'july',
    'august',
    'september',
    'october',
    'november',
    'december'
];
$month_names = [
    'Jan',
    'Feb',
    'Mar',
    'Apr',
    'May',
    'Jun',
    'Jul',
    'Aug',
    'Sep',
    'Oct',
    'Nov',
    'Dec'
];

// Per-month totals for the Payment Overview footer rows
$col_paid_counts = array_fill_keys($months, 0);
$col_unpaid_counts = array_fill_keys($months, 0);
foreach ($all_users as $u) {
    foreach ($months as $m) {
        $amount = $u[$m] ?? 0;
        if ($amount > 0) {
            $col_paid_counts[$m]++;
        } else {
            $col_unpaid_counts[$m]++;
        }
    }
}

// Room filter for interactive statistics
$selected_room = isset($_GET['room']) ? trim($_GET['room']) : '';
$filtered_users = array_filter($all_users, function ($u) use ($selected_room) {
    if ($selected_room === '' || $selected_room === 'all')
        return true;
    return (string) $u['room_number'] === (string) $selected_room;
});

// Compute stats for selected room
$room_total_boarders = count($filtered_users);
$room_paid_cells = 0;
$room_unpaid_cells = 0;
$room_total_amount = 0;
foreach ($filtered_users as $u) {
    foreach ($months as $m) {
        $amount = $u[$m] ?? 0;
        if ($amount > 0) {
            $room_paid_cells++;
        } else {
            $room_unpaid_cells++;
        }
        $room_total_amount += (float) $amount;
    }
}

// Per-month paid/unpaid for selected room (for bar chart)
$room_paid_counts = array_fill_keys($months, 0);
$room_unpaid_counts = array_fill_keys($months, 0);
foreach ($filtered_users as $u) {
    foreach ($months as $m) {
        $amount = $u[$m] ?? 0;
        if ($amount > 0) {
            $room_paid_counts[$m]++;
        } else {
            $room_unpaid_counts[$m]++;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Ruin Boarders</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }

        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            width: 250px;
            height: 100vh;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            padding: 20px 0;
            z-index: 1000;
            transition: transform 0.3s ease;
        }

        .sidebar-header {
            text-align: center;
            padding: 0 20px 30px;
            border-bottom: 1px solid #e1e5e9;
            margin-bottom: 20px;
        }

        .sidebar-header h2 {
            color: #333;
            font-size: 1.5rem;
            font-weight: 700;
            background: linear-gradient(135deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .sidebar-menu {
            list-style: none;
        }

        .sidebar-menu li {
            margin-bottom: 5px;
        }

        .sidebar-menu a {
            display: flex;
            align-items: center;
            padding: 15px 20px;
            color: #666;
            text-decoration: none;
            transition: all 0.3s ease;
            border-radius: 0 25px 25px 0;
            margin-right: 20px;
        }

        .sidebar-menu a:hover,
        .sidebar-menu a.active {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            transform: translateX(5px);
        }

        .sidebar-menu i {
            margin-right: 10px;
            width: 20px;
        }

        .sidebar-menu li {
            position: relative;
        }

        .notification-badge {
            position: absolute;
            top: 8px;
            right: 8px;
            background: #dc3545;
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            font-weight: 600;
        }

        .main-content {
            margin-left: 250px;
            padding: 30px;
            min-height: 100vh;
        }

        .header {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 20px 30px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }

        .header h1 {
            color: #333;
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 10px;
        }

        .header p {
            color: #666;
            font-size: 1rem;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(135deg, #667eea, #764ba2);
        }

        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: white;
            margin-bottom: 15px;
        }

        .stat-icon.users {
            background: linear-gradient(135deg, #667eea, #764ba2);
        }

        .stat-icon.payments {
            background: linear-gradient(135deg, #f093fb, #f5576c);
        }

        .stat-icon.pending {
            background: linear-gradient(135deg, #4facfe, #00f2fe);
        }

        .stat-icon.amount {
            background: linear-gradient(135deg, #28a745, #85e085);
        }

        .stat-icon.check {
            background: linear-gradient(135deg, #17a2b8, #7bdcf2);
        }

        .stat-icon.times {
            background: linear-gradient(135deg, #ff416c, #ff4b2b);
        }

        .stat-number {
            font-size: 2.5rem;
            font-weight: 700;
            color: #333;
            margin-bottom: 5px;
        }

        .stat-label {
            color: #666;
            font-size: 0.9rem;
            font-weight: 500;
        }

        .content-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 30px;
        }

        .card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }

        .card-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #e1e5e9;
        }

        .card-title {
            font-size: 1.3rem;
            font-weight: 600;
            color: #333;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
        }

        .announcement-link {
            text-decoration: none;
            display: block;
            color: inherit;
        }

        .announcement-item {
            padding: 15px;
            border-left: 4px solid #667eea;
            background: #f8f9fa;
            border-radius: 8px;
            margin-bottom: 15px;
            cursor: pointer;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .announcement-item:hover {
            transform: translateX(5px);
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.1);
        }

        .announcement-title {
            font-weight: 600;
            color: #333;
            margin-bottom: 5px;
        }

        .announcement-content-preview {
            font-size: 0.85rem;
            color: #666;
            margin-bottom: 5px;
        }

        .announcement-date {
            font-size: 0.8rem;
            color: #666;
        }

        .mobile-menu-toggle {
            display: none;
            position: fixed;
            top: 20px;
            left: 20px;
            z-index: 1001;
            background: rgba(255, 255, 255, 0.9);
            border: none;
            border-radius: 8px;
            padding: 10px;
            cursor: pointer;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 2000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(5px);
        }

        .modal-content {
            background-color: #fff;
            margin: 5% auto;
            padding: 30px;
            border-radius: 15px;
            width: 90%;
            max-width: 500px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
            animation: slideDown 0.3s ease-out;
        }

        @keyframes slideDown {
            from {
                transform: translateY(-50px);
                opacity: 0;
            }

            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .modal-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }

        .modal-header h3 {
            font-size: 1.5rem;
            color: #333;
        }

        .close {
            font-size: 28px;
            font-weight: bold;
            color: #aaa;
            cursor: pointer;
            transition: color 0.3s;
        }

        .close:hover {
            color: #333;
        }

        .modal-payment-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
        }

        .modal-payment-item {
            padding: 15px;
            border-radius: 10px;
            border: 1px solid #eee;
            text-align: center;
        }

        .modal-payment-item.paid {
            background: #e8f5e8;
            border-color: #2e7d32;
            color: #2e7d32;
        }

        .modal-payment-item.unpaid {
            background: #fff5f5;
            border-color: #e53e3e;
            color: #e53e3e;
        }

        .modal-payment-month {
            font-weight: 600;
            margin-bottom: 5px;
            text-transform: capitalize;
        }

        .modal-payment-amount {
            font-size: 1.1rem;
            font-weight: 700;
        }

        .clickable-row {
            cursor: pointer;
            transition: background 0.2s;
        }

        .clickable-row:hover {
            background: #f0f4ff !important;
        }

        .overview-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 800px;
        }

        .table-scroll {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }

        .overview-table th {
            padding: 12px;
            border-bottom: 2px solid #e1e5e9;
            font-weight: 600;
            color: var(--neu-text);
            background: transparent;
            font-size: inherit;
        }

        .overview-table th.month-header {
            min-width: 60px;
            text-align: center;
        }

        .overview-table td {
            padding: 12px;
            border-bottom: 1px solid #e1e5e9;
            color: var(--neu-text);
            background: transparent;
        }

        .overview-table .col-name {
            min-width: 180px;
            width: 180px;
        }

        .name-cover {
            display: block;
            width: 100%;
            position: relative;
            z-index: 2;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .overview-table td.month-cell {
            padding: 8px;
            text-align: center;
            border-left: 1px solid #e1e5e9;
        }

        .month-value {
            min-height: 24px;
            padding: 4px 8px;
            border-radius: 999px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            font-weight: 600;
            background: rgba(37, 49, 66, 0.08);
            color: var(--neu-text);
        }

        .month-value.is-empty {
            background: transparent;
        }

        .paid-unpaid-row td {
            padding: 12px;
            font-weight: 600;
            background: transparent;
        }

        .paid-total-label,
        .paid-total-value {
            color: var(--neu-text);
        }

        .unpaid-total-label,
        .unpaid-total-value {
            color: var(--neu-text-soft);
        }

        /* History Modal Synchronized Styles */
        .history-modal-content {
            max-width: 600px;
        }

        .history-nav {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 20px;
            background: #f8f9fa;
            padding: 10px 15px;
            border-radius: 8px;
        }

        .history-year {
            font-size: 1.2rem;
            font-weight: 700;
            color: #333;
        }

        .history-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
        }

        .history-item {
            padding: 15px;
            border-radius: 10px;
            border: 1px solid #eee;
            text-align: center;
        }

        .history-item.paid {
            background: #e8f5e8;
            border-color: #2e7d32;
            color: #2e7d32;
        }

        .history-item.unpaid {
            background: #fff5f5;
            border-color: #e53e3e;
            color: #e53e3e;
        }

        .history-month {
            font-weight: 600;
            margin-bottom: 5px;
            text-transform: capitalize;
        }

        .history-amount {
            font-size: 1.1rem;
            font-weight: 700;
        }

        .nav-btn {
            background: white;
            border: 1px solid #ddd;
            padding: 5px 12px;
            border-radius: 5px;
            cursor: pointer;
            transition: all 0.3s;
        }

        .nav-btn:hover:not(:disabled) {
            background: #667eea;
            color: white;
            border-color: #667eea;
        }

        .nav-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }

            .sidebar.open {
                transform: translateX(0);
            }

            .main-content {
                margin-left: 0;
                padding: 20px;
            }

            .mobile-menu-toggle {
                display: block;
            }

            .content-grid {
                grid-template-columns: 1fr;
            }

            .modal-payment-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .table-scroll {
                overflow-x: auto;
                overflow-y: hidden;
                touch-action: pan-x;
            }

            .overview-table {
                min-width: 980px;
            }

            .overview-table th,
            .overview-table td {
                white-space: nowrap;
            }

            .overview-table .col-name {
                position: sticky;
                z-index: 3;
                min-width: 210px;
                width: 210px;
                max-width: 210px;
                padding: 0;
                background: var(--neu-surface-flat);
                background-image: linear-gradient(145deg, var(--neu-surface-flat), var(--neu-surface-flat));
                background-clip: padding-box;
            }

            .overview-table .col-name {
                left: 0;
                box-shadow: 6px 0 10px rgba(37, 49, 66, 0.08);
            }

            .overview-table thead .col-name {
                min-width: 210px;
                width: 210px;
                max-width: 210px;
            }

            .overview-table tbody .col-name {
                min-width: 210px;
                width: 210px;
                max-width: 210px;
            }

            .overview-table .col-name .name-cover {
                padding: 12px;
                min-width: 210px;
                width: 210px;
                max-width: 210px;
                background: var(--neu-surface-flat);
                background-image: linear-gradient(145deg, var(--neu-surface-flat), var(--neu-surface-flat));
            }

            .overview-table thead .col-name {
                z-index: 6;
            }

            .overview-table tbody .col-name {
                z-index: 5;
            }

            .overview-table .month-header,
            .overview-table .month-cell,
            .overview-table .paid-total-value,
            .overview-table .unpaid-total-value,
            .overview-table .summary-label,
            .overview-table .col-room {
                position: relative;
                z-index: 1;
            }
        }

        @media (max-width: 480px) {
            .modal-payment-grid {
                grid-template-columns: 1fr;
            }
        }

        /* Profile & Dark Mode Styles */
        .sidebar {
            padding-bottom: 90px;
        }

        .sidebar-profile {
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            padding: 20px;
            background: rgba(255, 255, 255, 0.95);
            border-top: 1px solid #e1e5e9;
            transition: all 0.3s ease;
        }

        .sidebar.dark-mode-element .sidebar-profile {
            background: rgba(30, 30, 40, 0.95);
            border-top-color: #333;
        }

        .profile-info {
            display: flex;
            align-items: center;
            gap: 10px;
            cursor: pointer;
            padding: 10px;
            border-radius: 10px;
            transition: background 0.2s;
        }

        .profile-info:hover {
            background: rgba(0, 0, 0, 0.05);
        }

        .sidebar.dark-mode-element .profile-info:hover {
            background: rgba(255, 255, 255, 0.05);
        }

        .profile-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea, #764ba2);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 1.2rem;
        }

        .profile-details {
            flex-grow: 1;
            display: flex;
            flex-direction: column;
        }

        .profile-name {
            font-weight: 600;
            color: #333;
            font-size: 0.95rem;
        }

        .profile-role {
            font-size: 0.8rem;
            color: #666;
        }

        .profile-dropdown {
            position: absolute;
            bottom: 85px;
            left: 20px;
            right: 20px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.15);
            opacity: 0;
            visibility: hidden;
            transform: translateY(10px);
            transition: all 0.3s ease;
            display: flex;
            flex-direction: column;
            overflow: hidden;
            z-index: 1001;
        }

        .profile-dropdown.show {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }

        .profile-dropdown a {
            padding: 12px 20px;
            color: #444;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 500;
            transition: background 0.2s;
        }

        .profile-dropdown a:hover {
            background: #f8f9fa;
            color: #667eea;
        }

        .profile-dropdown a.logout-text {
            color: #dc3545;
            border-top: 1px solid #eee;
        }

        .profile-dropdown a.logout-text:hover {
            background: #fff5f5;
        }

        body.dark-mode {
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
        }

        .dark-mode .sidebar {
            background: rgba(25, 25, 35, 0.95);
            border-right: 1px solid #333;
        }

        .dark-mode .sidebar-profile {
            background: rgba(25, 25, 35, 0.95);
            border-top-color: #333;
        }

        .dark-mode .sidebar-header {
            border-bottom-color: #333;
        }

        .dark-mode .sidebar-header h2 {
            background: linear-gradient(135deg, #a8c0ff, #3f2b96);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .dark-mode .sidebar-header p {
            color: #aaa;
        }

        .dark-mode .sidebar-menu a {
            color: #aaa;
        }

        .dark-mode .sidebar-menu a:hover,
        .dark-mode .sidebar-menu a.active {
            background: linear-gradient(135deg, #3f2b96, #a8c0ff);
            color: white;
        }

        .dark-mode .main-content h1 {
            color: #fff;
        }

        .dark-mode .main-content p {
            color: #aaa;
        }

        .dark-mode .card,
        .dark-mode .header,
        .dark-mode .stats-grid>div,
        .dark-mode .filters,
        .dark-mode .table-container,
        .dark-mode .announcement-item {
            background: rgba(30, 30, 45, 0.95);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
            color: #fff;
            border: 1px solid #333;
        }

        .dark-mode .card-header {
            border-bottom-color: #333;
        }

        .dark-mode .card-title,
        .dark-mode .stat-number,
        .dark-mode th,
        .dark-mode td {
            color: #fff;
        }

        .dark-mode .stat-label,
        .dark-mode .announcement-date {
            color: #aaa;
        }

        .dark-mode .announcement-title {
            color: #fff;
        }

        .dark-mode .announcement-content-preview {
            color: #bbb;
        }

        .dark-mode .chart-container {
            background: rgba(30, 30, 45, 0.95) !important;
            border: 1px solid #333;
        }

        .dark-mode .overview-table th,
        .dark-mode .overview-table td {
            border-color: #444;
            color: var(--neu-text);
        }

        .dark-mode .paid-unpaid-row td,
        .dark-mode .overview-table th,
        .dark-mode .overview-table td {
            background: transparent;
        }

        .dark-mode .overview-table .col-name {
            background: var(--neu-surface-flat);
        }

        .dark-mode .overview-table .col-name .name-cover {
            background: var(--neu-surface-flat);
            background-image: linear-gradient(145deg, var(--neu-surface-flat), var(--neu-surface-flat));
        }

        .dark-mode .month-value {
            background: rgba(237, 243, 251, 0.08);
            color: var(--neu-text);
        }

        .dark-mode .month-value.is-empty {
            background: transparent;
        }

        .dark-mode .form-group label {
            color: #ddd;
        }

        .dark-mode .form-control {
            background: #2a2a35;
            border-color: #444;
            color: #fff;
        }

        .dark-mode table th,
        .dark-mode table td {
            border-bottom-color: #444;
        }

        .dark-mode table {
            border-color: #444;
        }

        .dark-mode table th {
            background: #2a2a35;
        }

        .dark-mode .clickable-row:hover {
            background: #3a3a45 !important;
        }

        .dark-mode .profile-name {
            color: #fff;
        }

        .dark-mode .profile-role {
            color: #aaa;
        }

        .dark-mode .profile-dropdown {
            background: #2a2a35;
            border: 1px solid #444;
        }

        .dark-mode .profile-dropdown a {
            color: #ddd;
        }

        .dark-mode .profile-dropdown a.logout-text {
            color: #ff6b6b;
            border-top-color: #444;
        }

        .dark-mode .profile-dropdown a:hover {
            background: #3a3a45;
            color: #a8c0ff;
        }

        .dark-mode .profile-dropdown a.logout-text:hover {
            background: rgba(220, 53, 69, 0.2);
        }

        .dark-mode .modal-content {
            background: #2a2a35;
            color: #fff;
            border-color: #444;
        }

        .dark-mode .modal-header {
            border-bottom-color: #444;
        }

        .dark-mode .modal-header h3 {
            color: #fff;
        }

        .dark-mode .close {
            color: #aaa;
        }

        .dark-mode .close:hover {
            color: #fff;
        }

        .dark-mode .nav-btn {
            background: #333;
            border-color: #555;
            color: #fff;
        }

        .dark-mode .history-nav {
            background: #2a2a35;
        }

        .dark-mode .history-year {
            color: #fff;
        }

        .dark-mode .history-item.paid {
            background: rgba(46, 125, 50, 0.2);
            border-color: #2e7d32;
            color: #81c784;
        }

        .dark-mode .history-item.unpaid {
            background: rgba(229, 62, 62, 0.2);
            border-color: #e53e3e;
            color: #fc8181;
        }

        .dark-mode .alert-success {
            background: rgba(40, 167, 69, 0.2);
            border-color: #2e7d32;
            color: #81c784;
        }

        .dark-mode .alert-danger {
            background: rgba(220, 53, 69, 0.2);
            border-color: #e53e3e;
            color: #fc8181;
        }
    </style>
    <link rel="stylesheet" href="../assets/css/neuromorphic-theme.css">
    <link rel="stylesheet" href="../assets/css/admin-sidebar.css">
    <script src="../assets/js/neuromorphic-theme.js" defer></script>
</head>

<body>
    <?php $current_admin_page = 'dashboard'; ?>
    <?php require __DIR__ . '/includes/admin-sidebar.php'; ?>

    <div class="main-content">
        <div class="header">
            <h1>Welcome back, <?php echo $_SESSION['admin_fullname']; ?>!</h1>
            <p>Here's what's happening with your boarders today.</p>
        </div>

        <?php if (isset($_SESSION['login_success'])): ?>
            <div
                style="background: #d4edda; color: #155724; padding: 15px; border-radius: 8px; margin-bottom: 20px; border-left: 4px solid #28a745;">
                <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($_SESSION['login_success']); ?>
                <?php if (isset($_SESSION['login_time'])): ?><small
                        style="display: block; margin-top: 5px; opacity: 0.8;">Logged in at
                        <?php echo htmlspecialchars($_SESSION['login_time']); ?></small><?php endif; ?>
            </div>
            <?php unset($_SESSION['login_success']);
            unset($_SESSION['login_time']); ?>
        <?php endif; ?>

        <div class="stats-grid">

        </div>

        <!-- Interactive Room Statistics (moved above Payment Overview) -->
        <div class="card" style="margin-bottom:30px;">
            <div class="card-header">
                <h3 class="card-title">Room Statistics</h3>
                <form method="GET" style="margin-left:auto; display:flex; gap:10px; align-items:center;">
                    <label for="year" style="color:#666; font-weight:500;">Year</label>
                    <select id="year" name="year" onchange="showRoomLoaderAndSubmit(this)"
                        style="padding:8px 12px; border:2px solid #e1e5e9; border-radius:8px;">
                        <?php foreach ($available_years as $yr): ?>
                            <option value="<?php echo htmlspecialchars($yr); ?>" <?php echo ($selected_year == $yr) ? 'selected' : ''; ?>><?php echo htmlspecialchars($yr); ?></option>
                        <?php endforeach; ?>
                    </select>

                    <label for="room" style="color:#666; font-weight:500;">Room</label>
                    <select id="room" name="room" onchange="showRoomLoaderAndSubmit(this)"
                        style="padding:8px 12px; border:2px solid #e1e5e9; border-radius:8px;">
                        <option value="all" <?php echo ($selected_room === '' || $selected_room === 'all') ? 'selected' : ''; ?>>
                            All Rooms</option>
                        <?php foreach ($rooms as $room): ?>
                            <option value="<?php echo htmlspecialchars($room); ?>" <?php echo ((string) $selected_room === (string) $room) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($room); ?></option>
                        <?php endforeach; ?>
                    </select>
                </form>
            </div>
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon users"><i class="fas fa-users"></i></div>
                    <div class="stat-number"><?php echo $room_total_boarders; ?></div>
                    <div class="stat-label">Boarders
                        (<?php echo $selected_room && $selected_room !== 'all' ? 'Room ' . htmlspecialchars($selected_room) : 'All'; ?>)
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon amount"><i class="fas fa-coins"></i></div>
                    <div class="stat-number">₱<?php echo number_format($room_total_amount, 2); ?></div>
                    <div class="stat-label">Total Collected (<?php echo $selected_year; ?>)</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon check"><i class="fas fa-check"></i></div>
                    <div class="stat-number"><?php echo $room_paid_cells; ?></div>
                    <div class="stat-label">Paid Months</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon times"><i class="fas fa-times"></i></div>
                    <div class="stat-number"><?php echo $room_unpaid_cells; ?></div>
                    <div class="stat-label">Unpaid Months</div>
                </div>
            </div>
            <div class="chart-container"
                style="background:#fff; border-radius:12px; padding:15px; position:relative; min-height:160px;">
                <div id="roomLoader"
                    style="display:none; position:absolute; inset:0; display:flex; align-items:center; justify-content:center; background:rgba(255,255,255,0.7); border-radius:12px;">
                    <svg width="38" height="38" viewBox="0 0 38 38" xmlns="http://www.w3.org/2000/svg" stroke="#667eea">
                        <g fill="none" fill-rule="evenodd">
                            <g transform="translate(1 1)" stroke-width="2">
                                <circle stroke-opacity=".3" cx="18" cy="18" r="18" />
                                <path d="M36 18c0-9.94-8.06-18-18-18">
                                    <animateTransform attributeName="transform" type="rotate" from="0 18 18"
                                        to="360 18 18" dur="1s" repeatCount="indefinite" />
                                </path>
                            </g>
                        </g>
                    </svg>
                </div>
                <canvas id="roomChart" height="110"></canvas>
            </div>
        </div>

        <!-- Payment Overview Table -->
        <div class="card" style="margin-bottom: 30px;">
            <div class="card-header">
                <h3 class="card-title">Payment Overview - <?php echo $selected_year; ?></h3>
                <a href="payments.php" class="btn btn-primary" style="margin-left:auto;"><i class="fas fa-edit"></i>
                    Manage Payments</a>
            </div>
            <div class="table-scroll">
                <table class="overview-table">
                    <thead>
                        <tr>
                            <th class="col-room" style="text-align: left;">
                                Room No.</th>
                            <th class="col-name" style="text-align: left;">
                                <span class="name-cover">Name</span></th>
                            <?php foreach ($month_names as $month): ?>
                                <th class="month-header">
                                    &nbsp;<?php echo $month; ?></th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($all_users as $user): ?>
                            <tr class="clickable-row" onclick='showUserPaymentModal(<?php echo json_encode($user); ?>)'>
                                <td class="col-room" style="font-weight: 500;">
                                    &nbsp;<?php echo htmlspecialchars($user['room_number']); ?></td>
                                <td class="col-name" style="font-weight: 500;">
                                    <span class="name-cover"><?php echo htmlspecialchars($user['fullname']); ?></span></td>
                                <?php foreach ($months as $month): ?>
                                    <td class="month-cell">
                                        <?php $amount = $user[$month] ?? 0;
                                        $is_paid = $amount > 0;
                                        if ($is_paid): ?>
                                            <div
                                                class="month-value">
                                                ₱<?php echo number_format($amount, 0); ?></div>
                                        <?php else: ?>
                                            <div class="month-value is-empty"></div>
                                        <?php endif; ?>
                                    </td>
                                <?php endforeach; ?>
                            </tr>
                        <?php endforeach; ?>
                        <tr class="paid-unpaid-row">
                            <td colspan="2" class="paid-total-label">Paid</td>
                            <?php foreach ($months as $m): ?>
                                <td class="paid-total-value" style="text-align:center;">
                                    &nbsp;<?php echo (int) $col_paid_counts[$m]; ?></td>
                            <?php endforeach; ?>
                        </tr>
                        <tr class="paid-unpaid-row">
                            <td colspan="2" class="unpaid-total-label">Unpaid</td>
                            <?php foreach ($months as $m): ?>
                                <td class="unpaid-total-value" style="text-align:center;">
                                    &nbsp;<?php echo (int) $col_unpaid_counts[$m]; ?></td>
                            <?php endforeach; ?>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="content-grid">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Recent Announcements</h3>
                    <a href="announcements.php" class="btn btn-primary" style="margin-left:auto;"><i
                            class="fas fa-plus"></i> New Announcement</a>
                </div>
                <?php if (empty($recent_announcements)): ?>
                    <p style="color: #666; text-align: center; padding: 20px;">No announcements yet.</p>
                <?php else: ?>
                    <?php foreach ($recent_announcements as $announcement): ?>
                        <a href="announcements.php" class="announcement-link">
                            <div class="announcement-item">
                                <div class="announcement-title"><?php echo htmlspecialchars($announcement['title']); ?></div>
                                <div class="announcement-content-preview">
                                    <?php echo htmlspecialchars(substr($announcement['content'], 0, 80)) . '...'; ?></div>
                                <div class="announcement-date">
                                    <?php echo date('M d, Y', strtotime($announcement['created_at'])); ?></div>
                            </div>
                        </a>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Quick Actions</h3>
                </div>
                <div style="display: flex; flex-direction: column; gap: 10px;">
                    <a href="users.php?action=add" class="btn btn-primary"><i class="fas fa-user-plus"></i> Add New
                        Boarder</a>
                    <a href="payments.php" class="btn btn-primary"><i class="fas fa-edit"></i> Manage Payments</a>
                    <a href="receipts.php" class="btn btn-primary"><i class="fas fa-check-circle"></i> Review
                        Receipts</a>
                </div>
            </div>
        </div>
    </div>

    <!-- User Payment Detail Modal -->
    <div id="paymentModal" class="modal">
        <div class="modal-content history-modal-content">
            <div class="modal-header">
                <h3 id="modalUserName">User Payments</h3>
                <span class="close" onclick="closePaymentModal()">&times;</span>
            </div>
            <div class="history-nav">
                <button class="nav-btn" onclick="changeYear(-1)"><i class="fas fa-chevron-left"></i> Back</button>
                <div class="history-year" id="currentYearDisplay"><?php echo $selected_year; ?></div>
                <button class="nav-btn" onclick="changeYear(1)">Next <i class="fas fa-chevron-right"></i></button>
            </div>
            <div id="modalPaymentContent" class="history-grid">
                <!-- Payment items will be injected here by JavaScript -->
            </div>
            <div style="margin-top: 25px; text-align: right;">
                <button class="btn btn-primary" onclick="closePaymentModal()">Close</button>
            </div>
        </div>
    </div>

    <script>
        function toggleSidebar() { document.getElementById('sidebar').classList.toggle('open'); }
        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', function (e) { const sidebar = document.getElementById('sidebar'); const toggle = document.querySelector('.mobile-menu-toggle'); if (window.innerWidth <= 768 && !sidebar.contains(e.target) && !toggle.contains(e.target)) { sidebar.classList.remove('open'); } });

        let roomChartInstance = null;

        function getThemeColorValue(name, fallback) {
            const value = getComputedStyle(document.body).getPropertyValue(name).trim();
            return value || fallback;
        }

        function renderRoomChart() {
            const ctx = document.getElementById('roomChart');
            const loader = document.getElementById('roomLoader');
            const isDarkMode = document.body.classList.contains('dark-mode');
            if (loader) {
                loader.style.display = 'flex';
                loader.style.background = isDarkMode
                    ? 'rgba(20, 28, 40, 0.7)'
                    : 'rgba(255, 255, 255, 0.7)';
            }
            if (!ctx || !window.Chart) {
                if (loader)
                    loader.style.display = 'none';
                return;
            }

            const labels = <?php echo json_encode($month_names); ?>;
            const paid = <?php echo json_encode(array_values(array_map('intval', array_values($room_paid_counts)))); ?>;
            const unpaid = <?php echo json_encode(array_values(array_map('intval', array_values($room_unpaid_counts)))); ?>;
            const tickColor = isDarkMode ? '#edf3fb' : getThemeColorValue('--neu-text', '#253142');
            const gridColor = isDarkMode
                ? 'rgba(255,255,255,0.1)'
                : 'rgba(37,49,66,0.12)';
            const legendColor = isDarkMode ? '#edf3fb' : getThemeColorValue('--neu-text', '#253142');

            if (roomChartInstance) {
                roomChartInstance.destroy();
            }

            roomChartInstance = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [
                        { label: 'Paid', data: paid, backgroundColor: 'rgba(79, 124, 255, 0.78)' },
                        { label: 'Unpaid', data: unpaid, backgroundColor: 'rgba(37, 49, 66, 0.38)' }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        x: { stacked: false, grid: { display: false }, ticks: { color: tickColor } },
                        y: { beginAtZero: true, ticks: { precision: 0, color: tickColor }, grid: { color: gridColor } }
                    },
                    plugins: { legend: { position: 'top', labels: { color: legendColor } } }
                }
            });

            setTimeout(function () { if (loader) loader.style.display = 'none'; }, 150);
        }

        function showRoomLoaderAndSubmit(sel) {
            var loader = document.getElementById('roomLoader');
            if (loader) loader.style.display = 'flex';
            sel.form.submit();
        }

        const monthNames = <?php echo json_encode($months); ?>;
        const displayMonthNames = <?php echo json_encode($month_names); ?>;
        let currentUserId = null;
        let currentModalYear = <?php echo $selected_year; ?>;

        function showUserPaymentModal(user) {
            currentUserId = user.id;
            currentModalYear = <?php echo $selected_year; ?>;
            document.getElementById('modalUserName').textContent = `Payment Overview: ${user.fullname}`;
            fetchAndShowPayments(currentUserId, currentModalYear);

            document.getElementById('paymentModal').style.display = 'block';
        }

        function changeYear(delta) {
            currentModalYear += delta;
            fetchAndShowPayments(currentUserId, currentModalYear);
        }

        async function fetchAndShowPayments(userId, year) {
            document.getElementById('currentYearDisplay').textContent = year;
            const contentEl = document.getElementById('modalPaymentContent');
            contentEl.style.opacity = '1';
            contentEl.innerHTML = '<div style="grid-column: 1/-1; text-align: center; padding: 20px;"><i class="fas fa-spinner fa-spin"></i> Loading...</div>';

            try {
                const response = await fetch(`dashboard.php?ajax=get_payments&user_id=${userId}&year=${year}`);
                const data = await response.json();

                contentEl.innerHTML = '';
                monthNames.forEach((month, index) => {
                    const amount = parseFloat(data[month]) || 0;
                    const isPaid = amount > 0;
                    const displayMonth = displayMonthNames[index];

                    const item = document.createElement('div');
                    item.className = `history-item ${isPaid ? 'paid' : 'unpaid'}`;
                    item.innerHTML = `
                        <div class="history-month">${displayMonth}</div>
                        <div class="history-amount">${isPaid ? '₱' + amount.toLocaleString() : 'Unpaid'}</div>
                    `;
                    contentEl.appendChild(item);
                });
            } catch (error) {
                console.error('Error fetching payments:', error);
                contentEl.innerHTML = '<div style="grid-column: 1/-1; color: red; text-align: center;">Error loading data.</div>';
            }
        }

        function closePaymentModal() {
            document.getElementById('paymentModal').style.display = 'none';
        }

        // Close modal when clicking outside
        window.onclick = function (event) {
            const modal = document.getElementById('paymentModal');
            if (event.target == modal) {
                closePaymentModal();
            }
        };

        function toggleProfileDropdown(e) {
            e.stopPropagation();
            document.getElementById('profileDropdown').classList.toggle('show');
        }

        function toggleDarkMode(e) {
            if (e) e.preventDefault();
            document.body.classList.toggle('dark-mode');
            const isDark = document.body.classList.contains('dark-mode');
            localStorage.setItem('darkMode', isDark ? 'enabled' : 'disabled');
            renderRoomChart();
        }

        document.addEventListener('DOMContentLoaded', () => {
            if (localStorage.getItem('darkMode') === 'enabled') {
                document.body.classList.add('dark-mode');
            }
            renderRoomChart();
        });

        window.addEventListener('click', function (e) {
            const dropdown = document.getElementById('profileDropdown');
            if (dropdown && dropdown.classList.contains('show')) {
                dropdown.classList.remove('show');
            }
        });

    </script>
</body>

</html>
