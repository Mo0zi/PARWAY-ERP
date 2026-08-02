<?php
// Parway Industry ERP - Core Database Connection & Dynamic Settings Helper

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

class Database {
    private static $instance = null;
    private $pdo;

    private $host = '127.0.0.1';
    private $user = 'root';
    private $pass = '';
    private $name = 'parway_erp';

    private function __construct() {
        try {
            // Connect to MySQL
            $this->pdo = new PDO("mysql:host={$this->host};charset=utf8mb4", $this->user, $this->pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ]);

            // Ensure database exists
            $this->pdo->exec("CREATE DATABASE IF NOT EXISTS `{$this->name}` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            $this->pdo->exec("USE `{$this->name}`");

            // Auto-create required tables if not existing
            $sql_file = __DIR__ . '/../sql/parway_erp.sql';
            if (file_exists($sql_file)) {
                $check = $this->pdo->query("SHOW TABLES LIKE 'users'")->rowCount();
                if ($check === 0) {
                    $sql_content = file_get_contents($sql_file);
                    $this->pdo->exec($sql_content);
                }
            }

            // Ensure Login User is updated to parway2002@gmail.com & SHAHBAZ@000
            $target_username = 'parway2002@gmail.com';
            $target_password_hash = password_hash('SHAHBAZ@000', PASSWORD_DEFAULT);

            $user_check = $this->pdo->prepare("SELECT id FROM users WHERE username = ? OR username = 'admin'");
            $user_check->execute([$target_username]);
            $existing_user = $user_check->fetch();

            if ($existing_user) {
                $this->pdo->prepare("UPDATE users SET username = ?, password = ? WHERE id = ?")->execute([$target_username, $target_password_hash, $existing_user['id']]);
            } else {
                $this->pdo->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, 'admin')")->execute([$target_username, $target_password_hash]);
            }

            // Auto-Migration for Settings Customization Columns
            $setting_cols = $this->pdo->query("DESCRIBE settings")->fetchAll(PDO::FETCH_COLUMN);
            if (!in_array('sidebar_bg_color', $setting_cols)) {
                $this->pdo->exec("ALTER TABLE settings ADD COLUMN sidebar_bg_color VARCHAR(20) DEFAULT '#1e293b'");
            }
            if (!in_array('currency_symbol', $setting_cols)) {
                $this->pdo->exec("ALTER TABLE settings ADD COLUMN currency_symbol VARCHAR(10) DEFAULT 'Rs.'");
            }
            if (!in_array('invoice_title', $setting_cols)) {
                $this->pdo->exec("ALTER TABLE settings ADD COLUMN invoice_title VARCHAR(100) DEFAULT 'SALES INVOICE'");
            }
            if (!in_array('invoice_footer_note', $setting_cols)) {
                $this->pdo->exec("ALTER TABLE settings ADD COLUMN invoice_footer_note TEXT NULL");
            }
            if (!in_array('email', $setting_cols)) {
                $this->pdo->exec("ALTER TABLE settings ADD COLUMN email VARCHAR(100) DEFAULT ''");
            }
            if (!in_array('default_work_types', $setting_cols)) {
                $this->pdo->exec("ALTER TABLE settings ADD COLUMN default_work_types TEXT NULL");
            }
        } catch (PDOException $e) {
            die("Database Connection Error: " . $e->getMessage());
        }
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new Database();
        }
        return self::$instance->pdo;
    }
}

// Global PDO Access
$pdo = Database::getInstance();

// Default Settings Fallback
$settings = [
    'company_name' => 'Parway Industry',
    'theme_primary_color' => '#1e3c72',
    'theme_secondary_color' => '#2a5298',
    'sidebar_bg_color' => '#1e293b',
    'address' => 'Industrial Area, Faisalabad, Pakistan',
    'phone' => '+92 300 0000000',
    'email' => 'parway2002@gmail.com',
    'logo' => 'assets/uploads/logo/logo.png',
    'currency_symbol' => 'Rs.',
    'invoice_title' => 'SALES INVOICE',
    'invoice_footer_note' => 'Goods once sold are non-refundable. Thank you for your business!',
    'default_work_types' => 'Stitching, Cutting, Overlock, Packing, Pressing, Embroidery, Thread Cutting'
];

try {
    $stmt = $pdo->query("SELECT * FROM settings WHERE id = 1 LIMIT 1");
    $row = $stmt->fetch();
    if ($row) {
        $settings = array_merge($settings, array_filter($row, function($val) { return $val !== null && $val !== ''; }));
    }
} catch (Exception $e) {
    // fallback
}

// Global Base URL Helper
function base_url($path = '') {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    
    $doc_root = str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT'] ?? '');
    $app_dir = str_replace('\\', '/', dirname(__DIR__));
    $relative = str_replace($doc_root, '', $app_dir);
    
    return rtrim($protocol . "://" . $host . $relative, '/') . '/' . ltrim($path, '/');
}

// Auth Check Helper
function require_login() {
    if (!isset($_SESSION['user_id'])) {
        header("Location: " . base_url('index.php'));
        exit();
    }
}

// Dynamic Currency Formatter
function format_rs($amount) {
    global $settings;
    $symbol = !empty($settings['currency_symbol']) ? $settings['currency_symbol'] : 'Rs.';
    return $symbol . ' ' . number_format((float)$amount, 2);
}

// Recalculate Customer Ledger Running Balance
function recalculate_customer_balance($customer_id, $pdo = null) {
    if (!$pdo) $pdo = Database::getInstance();
    $stmt = $pdo->prepare("SELECT opening_balance FROM customers WHERE id = ?");
    $stmt->execute([$customer_id]);
    $customer = $stmt->fetch();
    $running_balance = (float)($customer['opening_balance'] ?? 0);

    $stmt_entries = $pdo->prepare("SELECT id, type, amount FROM customer_ledger WHERE customer_id = ? ORDER BY date ASC, id ASC");
    $stmt_entries->execute([$customer_id]);
    $entries = $stmt_entries->fetchAll();

    $update_stmt = $pdo->prepare("UPDATE customer_ledger SET balance = ? WHERE id = ?");

    foreach ($entries as $entry) {
        if ($entry['type'] === 'debit') {
            $running_balance += (float)$entry['amount'];
        } else {
            $running_balance -= (float)$entry['amount'];
        }
        $update_stmt->execute([$running_balance, $entry['id']]);
    }

    return $running_balance;
}

// Recalculate Worker Ledger Running Balance
function recalculate_worker_balance($worker_id, $pdo = null) {
    if (!$pdo) $pdo = Database::getInstance();
    $running_balance = 0.00;

    $stmt_entries = $pdo->prepare("SELECT id, type, amount FROM worker_ledger WHERE worker_id = ? ORDER BY date ASC, id ASC");
    $stmt_entries->execute([$worker_id]);
    $entries = $stmt_entries->fetchAll();

    $update_stmt = $pdo->prepare("UPDATE worker_ledger SET balance = ? WHERE id = ?");

    foreach ($entries as $entry) {
        if ($entry['type'] === 'debit') {
            $running_balance -= (float)$entry['amount'];
        } else {
            $running_balance += (float)$entry['amount'];
        }
        $update_stmt->execute([$running_balance, $entry['id']]);
    }

    return $running_balance;
}

// Recalculate Personal Ledger Running Balance
function recalculate_personal_balance($pdo = null) {
    if (!$pdo) $pdo = Database::getInstance();
    $running_balance = 0.00;
    $stmt_entries = $pdo->query("SELECT id, type, amount FROM personal_ledger ORDER BY date ASC, id ASC");
    $entries = $stmt_entries->fetchAll();
    
    $update_stmt = $pdo->prepare("UPDATE personal_ledger SET balance = ? WHERE id = ?");

    foreach ($entries as $entry) {
        if ($entry['type'] === 'credit') {
            $running_balance += (float)$entry['amount'];
        } else {
            $running_balance -= (float)$entry['amount'];
        }
        $update_stmt->execute([$running_balance, $entry['id']]);
    }

    return $running_balance;
}
