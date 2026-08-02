<?php
require_once __DIR__ . '/../../config/db.php';
require_login();

// Handle Database SQL Export
if (isset($_GET['download_sql'])) {
    $tables = ['settings', 'users', 'customers', 'workers', 'items', 'lots', 'lot_work', 'invoices', 'invoice_items', 'customer_ledger', 'worker_ledger', 'personal_ledger'];
    $sql_backup = "-- Parway ERP Full Database Backup\n";
    $sql_backup .= "-- Generated on: " . date('Y-m-d H:i:s') . "\n\n";

    foreach ($tables as $table) {
        $stmt = $pdo->query("SELECT * FROM $table");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (!empty($rows)) {
            $sql_backup .= "-- Table: $table\n";
            foreach ($rows as $row) {
                $cols = array_keys($row);
                $vals = array_map(function($v) use ($pdo) {
                    return $v === null ? 'NULL' : $pdo->quote($v);
                }, array_values($row));

                $sql_backup .= "INSERT INTO `$table` (`" . implode("`, `", $cols) . "`) VALUES (" . implode(", ", $vals) . ");\n";
            }
            $sql_backup .= "\n";
        }
    }

    header('Content-Type: application/sql');
    header('Content-Disposition: attachment; filename="parway_erp_backup_' . date('Y-m-d_H-i') . '.sql"');
    echo $sql_backup;
    exit();
}

$page_title = 'System Data & PDF Backup';
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <div>
    <h5 class="fw-bold m-0"><i class="fa-solid fa-cloud-arrow-down text-primary me-2"></i>System Backup & PDF Export</h5>
    <small class="text-muted">Export complete factory project data, customers, wages, lots, and ledgers in PDF or SQL format</small>
  </div>
</div>

<div class="row g-4">
  <!-- PDF Backup Export Card -->
  <div class="col-md-6">
    <div class="card-custom h-100 mb-0">
      <div class="card-custom-header bg-light">
        <h6 class="card-custom-title text-danger">
          <i class="fa-solid fa-file-pdf fs-5 me-1"></i> Full Project PDF Backup & Report
        </h6>
      </div>
      <div class="card-custom-body text-center py-4">
        <div class="mb-3">
          <i class="fa-solid fa-file-invoice text-danger display-4"></i>
        </div>
        <h5 class="fw-bold text-dark mb-2">Download Complete Data PDF</h5>
        <p class="text-muted small px-3 mb-4">
          Generates an A4 Master PDF Report containing <strong>Executive Summary, Customer Balances, Worker Wages, Production Lots, Invoices, and Ledgers</strong>. You can save it anywhere on your computer or phone!
        </p>
        <a href="<?= base_url('modules/settings/export_pdf_backup.php') ?>" target="_blank" class="btn btn-danger btn-lg px-4 fw-bold shadow-sm">
          <i class="fa-solid fa-file-pdf me-2"></i> Download Full PDF Backup Report
        </a>
      </div>
    </div>
  </div>

  <!-- Database SQL Backup Export Card -->
  <div class="col-md-6">
    <div class="card-custom h-100 mb-0">
      <div class="card-custom-header bg-light">
        <h6 class="card-custom-title text-primary">
          <i class="fa-solid fa-database fs-5 me-1"></i> MySQL Database Backup (.SQL File)
        </h6>
      </div>
      <div class="card-custom-body text-center py-4">
        <div class="mb-3">
          <i class="fa-solid fa-database text-primary display-4"></i>
        </div>
        <h5 class="fw-bold text-dark mb-2">Download Full SQL Backup</h5>
        <p class="text-muted small px-3 mb-4">
          Downloads a raw `.sql` database backup file containing all tables, customer ledgers, worker piece logs, lots, and invoices.
        </p>
        <a href="<?= base_url('modules/settings/backup.php?download_sql=1') ?>" class="btn btn-primary-custom btn-lg px-4 fw-bold shadow-sm">
          <i class="fa-solid fa-download me-2"></i> Download SQL Database Backup
        </a>
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
