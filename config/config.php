<?php
// Application configuration
session_start();
// Use Philippine time for all server-side timestamps (force via ini and PHP)
@ini_set('date.timezone', 'Asia/Manila');
date_default_timezone_set('Asia/Manila');

// Define constants
// Auto-detect the base URL and pin it to the site root to avoid nested path issues
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'];

// Dynamic base path calculation
$project_root = str_replace('\\', '/', realpath(__DIR__ . '/..'));
$doc_root = str_replace('\\', '/', realpath($_SERVER['DOCUMENT_ROOT']));
$base_path = str_replace($doc_root, '', $project_root);
$base_path = '/' . ltrim(str_replace('\\', '/', $base_path), '/');
$base_path = rtrim($base_path, '/') . '/';

define('BASE_URL', $protocol . '://' . $host . $base_path);
define('UPLOAD_PATH', __DIR__ . '/../uploads/');
define('PROFILE_PIC_PATH', __DIR__ . '/../uploads/profiles/');
define('RECEIPT_PATH', __DIR__ . '/../uploads/receipts/');

// Gmail SMTP settings for receipt notifications.
// You can edit these values directly or override them with RB_SMTP_* environment variables.
$mail_config = [
    'enabled' => true,
    'host' => 'smtp.gmail.com',
    'port' => 587,
    'encryption' => 'tls',
    'username' => 'jaysonproponio@gmail.com',
    'password' => 'cbayhevlafxetlgy',
    'from_email' => 'jaysonproponio@gmail.com',
    'from_name' => 'Ruin Boarders',
    'admin_recipients' => 'jaysonproponio@gmail.com',
    'timeout' => 20,
];

define('SMTP_ENABLED', filter_var(getenv('RB_SMTP_ENABLED') ?: ($mail_config['enabled'] ? 'true' : 'false'), FILTER_VALIDATE_BOOLEAN));
define('SMTP_HOST', getenv('RB_SMTP_HOST') ?: $mail_config['host']);
define('SMTP_PORT', (int) (getenv('RB_SMTP_PORT') ?: $mail_config['port']));
define('SMTP_ENCRYPTION', strtolower(getenv('RB_SMTP_ENCRYPTION') ?: $mail_config['encryption']));
define('SMTP_USERNAME', trim((string) (getenv('RB_SMTP_USERNAME') ?: $mail_config['username'])));
define('SMTP_PASSWORD', (string) (getenv('RB_SMTP_PASSWORD') ?: $mail_config['password']));
define('SMTP_FROM_EMAIL', trim((string) (getenv('RB_SMTP_FROM_EMAIL') ?: $mail_config['from_email'])));
define('SMTP_FROM_NAME', trim((string) (getenv('RB_SMTP_FROM_NAME') ?: $mail_config['from_name'])));
define('SMTP_ADMIN_RECIPIENTS', trim((string) (getenv('RB_ADMIN_NOTIFICATION_EMAILS') ?: $mail_config['admin_recipients'])));
define('SMTP_TIMEOUT', (int) (getenv('RB_SMTP_TIMEOUT') ?: $mail_config['timeout']));

// Create upload directories if they don't exist
if (!file_exists(UPLOAD_PATH)) {
    mkdir(UPLOAD_PATH, 0777, true);
}
if (!file_exists(PROFILE_PIC_PATH)) {
    mkdir(PROFILE_PIC_PATH, 0777, true);
}
if (!file_exists(RECEIPT_PATH)) {
    mkdir(RECEIPT_PATH, 0777, true);
}

// Include database connection
require_once 'database.php';

// --- START NOTIFICATION POLLING ---
if (isset($_GET['ajax_check_badges'])) {
    header('Content-Type: application/json');
    $database = new Database();
    $db = $database->getConnection();
    
    // Schema updates (once)
    try {
        $tables = [
            'users' => ['seen_payments_at', 'seen_receipts_at', 'seen_announcements_at'],
            'admins' => ['seen_receipts_at']
        ];
        foreach ($tables as $table => $columns) {
            foreach ($columns as $column) {
                try {
                    $stmt = $db->query("SHOW COLUMNS FROM `$table` LIKE '$column'");
                    if ($stmt->rowCount() == 0) {
                        $db->exec("ALTER TABLE `$table` ADD COLUMN `$column` TIMESTAMP NULL DEFAULT NULL");
                    }
                } catch (Throwable $e) {}
            }
        }
        $stmt = $db->query("SHOW COLUMNS FROM `payment_receipts` LIKE 'updated_at'");
        if ($stmt->rowCount() == 0) {
            $db->exec("ALTER TABLE `payment_receipts` ADD COLUMN `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP");
        }
        $stmt = $db->query("SHOW COLUMNS FROM `announcements` LIKE 'updated_at'");
        if ($stmt->rowCount() == 0) {
            $db->exec("ALTER TABLE `announcements` ADD COLUMN `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP");
        }
    } catch (Throwable $e) {}

    // Handle marking a specific category as seen
    if (isset($_GET['mark_seen']) && isset($_GET['type'])) {
        $type = $_GET['type'];
        if (isset($_SESSION['user_id'])) {
            $col = "seen_{$type}_at";
            if (in_array($col, ['seen_payments_at', 'seen_receipts_at', 'seen_announcements_at'])) {
                try {
                    $stmt = $db->prepare("UPDATE users SET {$col} = CURRENT_TIMESTAMP WHERE id = :id");
                    $stmt->execute([':id' => $_SESSION['user_id']]);
                } catch (Throwable $e) {}
            }
        } elseif (isset($_SESSION['admin_id'])) {
            if ($type == 'receipts') {
                try {
                    $stmt = $db->prepare("UPDATE admins SET seen_receipts_at = CURRENT_TIMESTAMP WHERE id = :id");
                    $stmt->execute([':id' => $_SESSION['admin_id']]);
                } catch (Throwable $e) {}
            }
        }
    }

    $response = ['status' => 'success', 'badges' => []];

    // Check badges for User
    if (isset($_SESSION['user_id'])) {
        $user_id = $_SESSION['user_id'];
        
        $stmt = $db->prepare("SELECT seen_payments_at, seen_receipts_at, seen_announcements_at FROM users WHERE id = :id");
        $stmt->execute([':id' => $user_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Payments
        $stmt = $db->prepare("SELECT MAX(created_at) as last_update FROM payment_history WHERE user_id = :id");
        $stmt->execute([':id' => $user_id]);
        $last_payment = $stmt->fetch(PDO::FETCH_ASSOC)['last_update'];
        $response['badges']['payments'] = ($last_payment && (!$user['seen_payments_at'] || strtotime($last_payment) > strtotime($user['seen_payments_at'])));
        
        // Receipts
        $stmt = $db->prepare("SELECT MAX(updated_at) as last_update FROM payment_receipts WHERE user_id = :id AND status != 'pending'");
        $stmt->execute([':id' => $user_id]);
        $last_receipt = $stmt->fetch(PDO::FETCH_ASSOC)['last_update'];
        $response['badges']['receipts'] = ($last_receipt && (!$user['seen_receipts_at'] || strtotime($last_receipt) > strtotime($user['seen_receipts_at'])));

        // Announcements
        $stmt = $db->prepare("SELECT MAX(updated_at) as last_update FROM announcements");
        $stmt->execute();
        $last_announcement = $stmt->fetch(PDO::FETCH_ASSOC)['last_update'];
        $response['badges']['announcements'] = ($last_announcement && (!$user['seen_announcements_at'] || strtotime($last_announcement) > strtotime($user['seen_announcements_at'])));
    }
    // Check badges for Admin
    elseif (isset($_SESSION['admin_id'])) {
        $admin_id = $_SESSION['admin_id'];
        
        $stmt = $db->prepare("SELECT seen_receipts_at FROM admins WHERE id = :id");
        $stmt->execute([':id' => $admin_id]);
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Receipts
        $stmt = $db->prepare("SELECT MAX(updated_at) as last_update FROM payment_receipts WHERE status = 'pending'");
        $stmt->execute();
        $last_receipt = $stmt->fetch(PDO::FETCH_ASSOC)['last_update'];
        $response['badges']['receipts'] = ($last_receipt && (!$admin['seen_receipts_at'] || strtotime($last_receipt) > strtotime($admin['seen_receipts_at'])));
    }
    
    echo json_encode($response);
    exit();
}
// --- END NOTIFICATION POLLING ---

// Helper functions
// Basic admin action logging (creates table if missing)
function logAdminAction($db, $adminId, $action, $details = '') {
    try {
        $db->exec("CREATE TABLE IF NOT EXISTS admin_logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            admin_id INT NOT NULL,
            action VARCHAR(255) NOT NULL,
            details TEXT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $stmt = $db->prepare("INSERT INTO admin_logs (admin_id, action, details) VALUES (:admin_id, :action, :details)");
        $stmt->bindParam(':admin_id', $adminId);
        $stmt->bindParam(':action', $action);
        $stmt->bindParam(':details', $details);
        $stmt->execute();
    } catch (Throwable $e) {
        // Avoid hard failure on logging
        error_log('logAdminAction error: ' . $e->getMessage());
    }
}

function parseNotificationEmails($emails) {
    $valid_emails = [];

    foreach (preg_split('/\s*,\s*/', (string) $emails, -1, PREG_SPLIT_NO_EMPTY) as $email) {
        $email = trim($email);
        if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $valid_emails[strtolower($email)] = $email;
        }
    }

    return array_values($valid_emails);
}

function getAdminNotificationRecipients($db = null) {
    $recipients = [];

    foreach (parseNotificationEmails(SMTP_ADMIN_RECIPIENTS) as $email) {
        $recipients[strtolower($email)] = $email;
    }

    if ($db instanceof PDO) {
        try {
            $stmt = $db->query("SHOW COLUMNS FROM `admins` LIKE 'email'");
            if ($stmt && $stmt->rowCount() > 0) {
                $stmt = $db->query("SELECT email FROM admins WHERE email IS NOT NULL AND email != ''");
                foreach ((array) $stmt->fetchAll(PDO::FETCH_COLUMN) as $email) {
                    if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                        $recipients[strtolower($email)] = $email;
                    }
                }
            }
        } catch (Throwable $e) {
            error_log('getAdminNotificationRecipients error: ' . $e->getMessage());
        }
    }

    return array_values($recipients);
}

function smtpHeaderText($value) {
    $value = str_replace(["\r", "\n"], '', (string) $value);
    if ($value === '') {
        return '';
    }

    return preg_match('/[^\x20-\x7E]/', $value)
        ? mb_encode_mimeheader($value, 'UTF-8', 'B', "\r\n")
        : $value;
}

function smtpHeaderAddress($email, $name = '') {
    $email = str_replace(["\r", "\n"], '', trim((string) $email));
    $name = str_replace(["\r", "\n"], '', trim((string) $name));

    if ($email === '') {
        return '';
    }

    return $name === '' ? '<' . $email . '>' : smtpHeaderText($name) . ' <' . $email . '>';
}

function smtpAttachmentMimeType($path) {
    $mime_type = function_exists('mime_content_type') ? @mime_content_type($path) : false;
    return $mime_type ?: 'application/octet-stream';
}

function smtpReadResponse($socket) {
    $response = '';

    while (($line = fgets($socket, 515)) !== false) {
        $response .= $line;
        if (strlen($line) < 4 || $line[3] === ' ') {
            break;
        }
    }

    if ($response === '') {
        throw new RuntimeException('SMTP server returned an empty response.');
    }

    return $response;
}

function smtpCommand($socket, $command, $expected_codes) {
    if ($command !== null) {
        fwrite($socket, $command . "\r\n");
    }

    $response = smtpReadResponse($socket);
    $code = (int) substr($response, 0, 3);
    $expected_codes = (array) $expected_codes;

    if (!in_array($code, $expected_codes, true)) {
        throw new RuntimeException('SMTP command failed: ' . trim($response));
    }

    return $response;
}

function buildSmtpMimeMessage(array $to, $subject, $html_body, $text_body = '', array $attachments = [], $reply_to = null) {
    $from_email = SMTP_FROM_EMAIL !== '' ? SMTP_FROM_EMAIL : SMTP_USERNAME;
    $from_name = SMTP_FROM_NAME !== '' ? SMTP_FROM_NAME : 'Ruin Boarders';
    $host = parse_url(BASE_URL, PHP_URL_HOST) ?: 'localhost';
    $plain_text = trim((string) $text_body);

    if ($plain_text === '') {
        $plain_text = html_entity_decode(
            strip_tags(preg_replace(['/<\s*br\s*\/?>/i', '/<\/p>/i'], ["\n", "\n\n"], (string) $html_body)),
            ENT_QUOTES | ENT_HTML5,
            'UTF-8'
        );
        $plain_text = trim($plain_text);
    }

    if ($plain_text === '') {
        $plain_text = 'New message from Ruin Boarders.';
    }

    $alternative_boundary = 'alt_' . bin2hex(random_bytes(12));
    $mixed_boundary = 'mix_' . bin2hex(random_bytes(12));

    $headers = [
        'Date: ' . date(DATE_RFC2822),
        'Message-ID: <' . bin2hex(random_bytes(12)) . '@' . $host . '>',
        'From: ' . smtpHeaderAddress($from_email, $from_name),
        'To: ' . implode(', ', array_map(static function ($email) {
            return smtpHeaderAddress($email);
        }, $to)),
        'Subject: ' . smtpHeaderText($subject),
        'MIME-Version: 1.0',
    ];

    if (is_array($reply_to) && !empty($reply_to['email']) && filter_var($reply_to['email'], FILTER_VALIDATE_EMAIL)) {
        $headers[] = 'Reply-To: ' . smtpHeaderAddress($reply_to['email'], $reply_to['name'] ?? '');
    }

    $body = '';
    if (!empty($attachments)) {
        $headers[] = 'Content-Type: multipart/mixed; boundary="' . $mixed_boundary . '"';
        $body .= '--' . $mixed_boundary . "\r\n";
        $body .= 'Content-Type: multipart/alternative; boundary="' . $alternative_boundary . '"' . "\r\n\r\n";
    } else {
        $headers[] = 'Content-Type: multipart/alternative; boundary="' . $alternative_boundary . '"';
    }

    $body .= '--' . $alternative_boundary . "\r\n";
    $body .= "Content-Type: text/plain; charset=UTF-8\r\n";
    $body .= "Content-Transfer-Encoding: base64\r\n\r\n";
    $body .= chunk_split(base64_encode($plain_text)) . "\r\n";

    $body .= '--' . $alternative_boundary . "\r\n";
    $body .= "Content-Type: text/html; charset=UTF-8\r\n";
    $body .= "Content-Transfer-Encoding: base64\r\n\r\n";
    $body .= chunk_split(base64_encode((string) $html_body)) . "\r\n";
    $body .= '--' . $alternative_boundary . "--\r\n";

    foreach ($attachments as $attachment) {
        $path = $attachment['path'] ?? '';
        if ($path === '' || !is_file($path) || !is_readable($path)) {
            continue;
        }

        $filename = basename($attachment['name'] ?? $path);
        $mime_type = $attachment['type'] ?? smtpAttachmentMimeType($path);
        $file_data = file_get_contents($path);

        if ($file_data === false) {
            continue;
        }

        $safe_filename = addcslashes($filename, "\\\"");

        $body .= '--' . $mixed_boundary . "\r\n";
        $body .= 'Content-Type: ' . $mime_type . '; name="' . $safe_filename . '"' . "\r\n";
        $body .= 'Content-Disposition: attachment; filename="' . $safe_filename . '"' . "\r\n";
        $body .= "Content-Transfer-Encoding: base64\r\n\r\n";
        $body .= chunk_split(base64_encode($file_data)) . "\r\n";
    }

    if (!empty($attachments)) {
        $body .= '--' . $mixed_boundary . "--\r\n";
    }

    return implode("\r\n", $headers) . "\r\n\r\n" . $body;
}

function sendSmtpMail(array $to, $subject, $html_body, $text_body = '', array $attachments = [], $reply_to = null) {
    $recipients = array_values(array_filter(array_unique($to), static function ($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL);
    }));

    if (!SMTP_ENABLED || empty($recipients)) {
        return false;
    }

    $from_email = SMTP_FROM_EMAIL !== '' ? SMTP_FROM_EMAIL : SMTP_USERNAME;
    if ($from_email === '' || SMTP_HOST === '' || SMTP_PORT <= 0 || SMTP_USERNAME === '' || SMTP_PASSWORD === '') {
        error_log('sendSmtpMail skipped: SMTP is enabled but incomplete credentials were provided.');
        return false;
    }

    $socket = null;

    try {
        $transport_host = SMTP_HOST;
        if (SMTP_ENCRYPTION === 'ssl') {
            $transport_host = 'ssl://' . SMTP_HOST;
        }

        $socket = stream_socket_client(
            $transport_host . ':' . SMTP_PORT,
            $errno,
            $errstr,
            max(1, SMTP_TIMEOUT),
            STREAM_CLIENT_CONNECT
        );

        if (!$socket) {
            throw new RuntimeException('Unable to connect to SMTP server: ' . $errstr . ' (' . $errno . ')');
        }

        stream_set_timeout($socket, max(1, SMTP_TIMEOUT));

        smtpCommand($socket, null, 220);
        $ehlo_host = $_SERVER['SERVER_NAME'] ?? 'localhost';
        smtpCommand($socket, 'EHLO ' . $ehlo_host, 250);

        if (SMTP_ENCRYPTION === 'tls') {
            smtpCommand($socket, 'STARTTLS', 220);
            $crypto_enabled = stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
            if ($crypto_enabled !== true) {
                throw new RuntimeException('Unable to start TLS encryption for SMTP.');
            }
            smtpCommand($socket, 'EHLO ' . $ehlo_host, 250);
        }

        smtpCommand($socket, 'AUTH LOGIN', 334);
        smtpCommand($socket, base64_encode(SMTP_USERNAME), 334);
        smtpCommand($socket, base64_encode(SMTP_PASSWORD), 235);
        smtpCommand($socket, 'MAIL FROM:<' . $from_email . '>', 250);

        foreach ($recipients as $recipient) {
            smtpCommand($socket, 'RCPT TO:<' . $recipient . '>', [250, 251]);
        }

        smtpCommand($socket, 'DATA', 354);

        $message = buildSmtpMimeMessage($recipients, $subject, $html_body, $text_body, $attachments, $reply_to);
        $message = preg_replace('/(?m)^\./', '..', $message);
        fwrite($socket, $message . "\r\n.\r\n");
        smtpCommand($socket, null, 250);
        smtpCommand($socket, 'QUIT', 221);
        fclose($socket);

        return true;
    } catch (Throwable $e) {
        error_log('sendSmtpMail error: ' . $e->getMessage());
        if (is_resource($socket)) {
            @fwrite($socket, "QUIT\r\n");
            @fclose($socket);
        }
        return false;
    }
}

function notifyAdminsOfReceiptUpload($db, array $boarder, array $receipt) {
    $recipients = getAdminNotificationRecipients($db);
    if (empty($recipients)) {
        return false;
    }

    $boarder_name = trim((string) ($boarder['fullname'] ?? 'Boarder'));
    $boarder_email = trim((string) ($boarder['email'] ?? ''));
    $month_key = strtolower(trim((string) ($receipt['month'] ?? '')));
    $month_label = ucfirst($month_key);
    $year = trim((string) ($receipt['year'] ?? ''));
    $amount = number_format((float) ($receipt['amount'] ?? 0), 2);
    $room_number = trim((string) ($boarder['room_number'] ?? ''));
    $receipt_image = trim((string) ($receipt['receipt_image'] ?? ''));
    $uploaded_at = trim((string) ($receipt['created_at'] ?? date('Y-m-d H:i:s')));
    $receipt_url = rtrim(BASE_URL, '/') . '/uploads/receipts/' . rawurlencode($receipt_image);

    $subject = 'New receipt uploaded: ' . $boarder_name . ' - ' . $month_label . ' ' . $year;
    $safe_name = htmlspecialchars($boarder_name, ENT_QUOTES, 'UTF-8');
    $safe_month = htmlspecialchars($month_label, ENT_QUOTES, 'UTF-8');
    $safe_year = htmlspecialchars($year, ENT_QUOTES, 'UTF-8');
    $safe_room = htmlspecialchars($room_number !== '' ? $room_number : 'N/A', ENT_QUOTES, 'UTF-8');
    $safe_email = htmlspecialchars($boarder_email !== '' ? $boarder_email : 'N/A', ENT_QUOTES, 'UTF-8');
    $safe_uploaded_at = htmlspecialchars($uploaded_at, ENT_QUOTES, 'UTF-8');
    $safe_receipt_url = htmlspecialchars($receipt_url, ENT_QUOTES, 'UTF-8');

    $html_body = '
        <h2 style="margin-bottom: 16px;">New Payment Receipt Submitted</h2>
        <p>A boarder uploaded a new receipt that is waiting for admin review.</p>
        <table cellpadding="8" cellspacing="0" border="0" style="border-collapse: collapse; margin: 16px 0;">
            <tr><td><strong>Boarder</strong></td><td>' . $safe_name . '</td></tr>
            <tr><td><strong>Email</strong></td><td>' . $safe_email . '</td></tr>
            <tr><td><strong>Room</strong></td><td>' . $safe_room . '</td></tr>
            <tr><td><strong>Month</strong></td><td>' . $safe_month . ' ' . $safe_year . '</td></tr>
            <tr><td><strong>Amount</strong></td><td>P' . $amount . '</td></tr>
            <tr><td><strong>Uploaded At</strong></td><td>' . $safe_uploaded_at . '</td></tr>
        </table>
        <p>The uploaded receipt image is attached to this email.</p>
        <p>You can also open it here: <a href="' . $safe_receipt_url . '">' . $safe_receipt_url . '</a></p>
    ';

    $text_body = "New Payment Receipt Submitted\n"
        . "Boarder: " . $boarder_name . "\n"
        . "Email: " . ($boarder_email !== '' ? $boarder_email : 'N/A') . "\n"
        . "Room: " . ($room_number !== '' ? $room_number : 'N/A') . "\n"
        . "Month: " . $month_label . ' ' . $year . "\n"
        . "Amount: P" . $amount . "\n"
        . "Uploaded At: " . $uploaded_at . "\n"
        . "Receipt URL: " . $receipt_url . "\n";

    $attachments = [];
    if ($receipt_image !== '') {
        $attachment_path = RECEIPT_PATH . $receipt_image;
        if (is_file($attachment_path)) {
            $attachments[] = [
                'path' => $attachment_path,
                'name' => $receipt_image,
            ];
        }
    }

    $reply_to = null;
    if ($boarder_email !== '' && filter_var($boarder_email, FILTER_VALIDATE_EMAIL)) {
        $reply_to = [
            'email' => $boarder_email,
            'name' => $boarder_name,
        ];
    }

    return sendSmtpMail($recipients, $subject, $html_body, $text_body, $attachments, $reply_to);
}

function redirect($url) {
    header("Location: " . BASE_URL . $url);
    exit();
}

function isLoggedIn() {
    return isset($_SESSION['user_id']) || isset($_SESSION['admin_id']);
}

function isAdmin() {
    return isset($_SESSION['admin_id']);
}

function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function getRoomOccupancy($room_number, $db) {
    $query = "SELECT COUNT(*) as count FROM users WHERE room_number = :room_number AND status = 'active'";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':room_number', $room_number);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result['count'];
}

function isRoomFull($room_number, $db) {
    return getRoomOccupancy($room_number, $db) >= 4;
}

function updateRoomOccupancy($room_number, $db) {
    // Get current occupancy count
    $occupancy = getRoomOccupancy($room_number, $db);
    
    // Update or insert room record
    $query = "INSERT INTO rooms (room_number, current_occupancy) 
              VALUES (:room_number, :occupancy)
              ON DUPLICATE KEY UPDATE current_occupancy = :occupancy";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':room_number', $room_number);
    $stmt->bindParam(':occupancy', $occupancy);
    $stmt->execute();
    
    // Remove room if no occupants
    if ($occupancy == 0) {
        $query = "DELETE FROM rooms WHERE room_number = :room_number";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':room_number', $room_number);
        $stmt->execute();
    }
}

function addUserToRoom($user_id, $room_number, $db) {
    // Add user to room
    $query = "UPDATE users SET room_number = :room_number WHERE id = :user_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':room_number', $room_number);
    $stmt->bindParam(':user_id', $user_id);
    $result = $stmt->execute();
    
    if ($result) {
        // Update room occupancy
        updateRoomOccupancy($room_number, $db);
    }
    
    return $result;
}

function removeUserFromRoom($user_id, $db) {
    // Get user's current room before removal
    $query = "SELECT room_number, status FROM users WHERE id = :user_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        $old_room = $user['room_number'];
        $new_status = ($user['status'] == 'active') ? 'deactivated' : 'active';
        
        // Toggle user status instead of deleting
        $query = "UPDATE users SET status = :status WHERE id = :user_id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':status', $new_status);
        $stmt->bindParam(':user_id', $user_id);
        $result = $stmt->execute();
        
        if ($result && $old_room) {
            // Update room occupancy
            updateRoomOccupancy($old_room, $db);
        }
        
        return $result;
    }
    
    return false;
}

function toggleUserStatus($user_id, $db) {
    return removeUserFromRoom($user_id, $db);
}
?>
