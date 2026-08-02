<?php
require_once __DIR__ . '/../../config/db.php';
require_login();

// 1. Fetch Executive Financial Stats
$total_sales = (float)($pdo->query("SELECT SUM(grand_total) FROM invoices")->fetchColumn() ?? 0);
$total_invoice_count = (int)($pdo->query("SELECT COUNT(*) FROM invoices")->fetchColumn() ?? 0);

$stmt_cust_bal = $pdo->query("SELECT SUM(cl.balance) 
                              FROM customer_ledger cl
                              INNER JOIN (SELECT customer_id, MAX(id) as max_id FROM customer_ledger GROUP BY customer_id) latest ON cl.id = latest.max_id");
$total_customer_receivables = (float)($stmt_cust_bal->fetchColumn() ?? 0);

$stmt_work_bal = $pdo->query("SELECT SUM(wl.balance) 
                              FROM worker_ledger wl
                              INNER JOIN (SELECT worker_id, MAX(id) as max_id FROM worker_ledger GROUP BY worker_id) latest ON wl.id = latest.max_id");
$total_worker_payables = (float)($stmt_work_bal->fetchColumn() ?? 0);

$total_lots = (int)($pdo->query("SELECT COUNT(*) FROM lots")->fetchColumn() ?? 0);
$total_workers = (int)($pdo->query("SELECT COUNT(*) FROM workers")->fetchColumn() ?? 0);
$total_customers = (int)($pdo->query("SELECT COUNT(*) FROM customers")->fetchColumn() ?? 0);
$total_items = (int)($pdo->query("SELECT COUNT(*) FROM items")->fetchColumn() ?? 0);

// 2. Fetch Customers & Balances
$customers = $pdo->query("SELECT * FROM customers ORDER BY name ASC")->fetchAll();

// 3. Fetch Workers & Balances
$workers = $pdo->query("SELECT * FROM workers ORDER BY name ASC")->fetchAll();

// 4. Fetch Lots Breakdown
$lots = $pdo->query("SELECT l.*, COUNT(lw.id) as total_workers, SUM(lw.qty_done) as total_pcs_done, SUM(lw.total_amount) as total_wages 
                     FROM lots l 
                     LEFT JOIN lot_work lw ON l.id = lw.lot_id 
                     GROUP BY l.id 
                     ORDER BY l.date DESC, l.id DESC")->fetchAll();

// 5. Fetch Invoices Summary
$invoices = $pdo->query("SELECT i.*, c.name as customer_name FROM invoices i LEFT JOIN customers c ON i.customer_id = c.id ORDER BY i.date DESC, i.id DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Master System Backup Report - <?= htmlspecialchars($settings['company_name'] ?? 'Parway ERP') ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
  <style>
    @import url('https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700&display=swap');
    body {
      font-family: 'Outfit', sans-serif;
      background: #f8fafc;
      color: #0f172a;
      font-size: 13px;
    }
    .backup-container {
      max-width: 900px;
      margin: 20px auto;
      background: #fff;
      padding: 30px;
      border-radius: 10px;
      box-shadow: 0 10px 25px rgba(0,0,0,0.05);
    }
    .section-title {
      font-size: 14px;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      background: #1e3c72;
      color: #fff;
      padding: 6px 12px;
      border-radius: 4px;
      margin-top: 25px;
      margin-bottom: 12px;
    }
    .table-sm th {
      background: #f1f5f9;
      font-size: 11px;
      text-transform: uppercase;
    }
    .stat-card {
      background: #f8fafc;
      border: 1px solid #e2e8f0;
      border-radius: 8px;
      padding: 12px;
      text-align: center;
    }
    @media print {
      body { background: #fff !important; margin: 0; padding: 0; }
      .backup-container { box-shadow: none !important; max-width: 100% !important; margin: 0 !important; padding: 15px !important; }
      .no-print { display: none !important; }
      .section-title { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    }
  </style>
</head>
<body>

<div class="container no-print text-center my-3">
  <button onclick="window.print();" class="btn btn-primary btn-lg fw-bold px-4 shadow">
    <i class="fa-solid fa-file-pdf me-2"></i> Save as PDF / Print Full Backup Report
  </button>
  <a href="<?= base_url('modules/settings/backup.php') ?>" class="btn btn-outline-secondary btn-lg ms-2">
    <i class="fa-solid fa-arrow-left me-1"></i> Back to Backup Settings
  </a>
</div>

<div class="backup-container">
  <!-- Report Header -->
  <div class="d-flex justify-content-between align-items-center border-bottom pb-3 mb-3">
    <div>
      <?php if (!empty($settings['logo']) && file_exists(__DIR__ . '/../../' . $settings['logo'])): ?>
        <img src="<?= base_url($settings['logo']) ?>" style="max-height: 50px; margin-bottom: 5px;">
      <?php endif; ?>
      <h4 class="fw-bold m-0 text-primary"><?= htmlspecialchars($settings['company_name'] ?? 'PARWAY ERP') ?></h4>
      <small class="text-muted"><?= htmlspecialchars($settings['company_address'] ?? 'Sialkot, Punjab') ?> | Phone: <?= htmlspecialchars($settings['company_phone'] ?? '-') ?></small>
    </div>
    <div class="text-end">
      <span class="badge bg-danger fs-6 mb-1">MASTER SYSTEM BACKUP PDF</span>
      <div class="small fw-bold text-dark">Generated On: <?= date('d-M-Y h:i A') ?></div>
      <small class="text-muted">Generated By: <?= htmlspecialchars($_SESSION['username'] ?? 'Admin') ?></small>
    </div>
  </div>

  <!-- Financial KPI Executive Summary -->
  <div class="row g-2 mb-3">
    <div class="col-3">
      <div class="stat-card">
        <small class="text-muted uppercase fw-bold">Total Sales Invoices</small>
        <h5 class="fw-bold text-success m-0"><?= format_rs($total_sales) ?></h5>
        <small class="text-muted"><?= $total_invoice_count ?> Invoices</small>
      </div>
    </div>
    <div class="col-3">
      <div class="stat-card">
        <small class="text-muted uppercase fw-bold">Customer Receivables</small>
        <h5 class="fw-bold text-danger m-0"><?= format_rs($total_customer_receivables) ?></h5>
        <small class="text-muted"><?= $total_customers ?> Customers</small>
      </div>
    </div>
    <div class="col-3">
      <div class="stat-card">
        <small class="text-muted uppercase fw-bold">Worker Payables</small>
        <h5 class="fw-bold text-info m-0"><?= format_rs($total_worker_payables) ?></h5>
        <small class="text-muted"><?= $total_workers ?> Workers</small>
      </div>
    </div>
    <div class="col-3">
      <div class="stat-card">
        <small class="text-muted uppercase fw-bold">Lots Managed</small>
        <h5 class="fw-bold text-primary m-0"><?= $total_lots ?> Lots</h5>
        <small class="text-muted"><?= $total_items ?> Items in Catalog</small>
      </div>
    </div>
  </div>

  <!-- 1. Customer Directory & Balances -->
  <div class="section-title"><i class="fa-solid fa-users me-2"></i>1. Customers & Outstanding Balances Summary</div>
  <table class="table table-bordered table-sm mb-3">
    <thead>
      <tr>
        <th>#</th>
        <th>Customer Name</th>
        <th>Phone</th>
        <th>Address</th>
        <th class="text-end">Current Receivable Balance</th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($customers)): ?>
        <tr><td colspan="5" class="text-center text-muted">No customer records.</td></tr>
      <?php else: ?>
        <?php foreach ($customers as $idx => $c): 
          $bal = recalculate_customer_balance($c['id'], $pdo);
        ?>
          <tr>
            <td><?= $idx + 1 ?></td>
            <td><strong><?= htmlspecialchars($c['name']) ?></strong></td>
            <td><?= htmlspecialchars($c['phone'] ?: '-') ?></td>
            <td><?= htmlspecialchars($c['address'] ?: 'Sialkot, Punjab') ?></td>
            <td class="text-end fw-bold <?= $bal > 0 ? 'text-danger' : 'text-success' ?>"><?= format_rs($bal) ?></td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
  </table>

  <!-- 2. Workers & Wages Payables -->
  <div class="section-title"><i class="fa-solid fa-user-gear me-2"></i>2. Workers & Net Payable Wages Summary</div>
  <table class="table table-bordered table-sm mb-3">
    <thead>
      <tr>
        <th>#</th>
        <th>Worker Name</th>
        <th>Phone / CNIC</th>
        <th>Default Rate / Pc</th>
        <th class="text-end">Current Net Payable Balance</th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($workers)): ?>
        <tr><td colspan="5" class="text-center text-muted">No worker records.</td></tr>
      <?php else: ?>
        <?php foreach ($workers as $idx => $w): 
          $wbal = recalculate_worker_balance($w['id'], $pdo);
        ?>
          <tr>
            <td><?= $idx + 1 ?></td>
            <td><strong><?= htmlspecialchars($w['name']) ?></strong></td>
            <td><?= htmlspecialchars($w['phone'] ?: '-') ?> <?= $w['cnic'] ? '(' . $w['cnic'] . ')' : '' ?></td>
            <td><?= format_rs($w['default_rate']) ?></td>
            <td class="text-end fw-bold <?= $wbal > 0 ? 'text-info' : 'text-success' ?>"><?= format_rs($wbal) ?></td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
  </table>

  <!-- 3. Lots & Production Sheets Directory -->
  <div class="section-title"><i class="fa-solid fa-layer-group me-2"></i>3. Production Working Sheets (Lots) Directory</div>
  <table class="table table-bordered table-sm mb-3">
    <thead>
      <tr>
        <th>Lot No</th>
        <th>Date</th>
        <th>Description</th>
        <th class="text-center">Workers Count</th>
        <th class="text-center">Total Pcs Done</th>
        <th class="text-end">Total Lot Wages</th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($lots)): ?>
        <tr><td colspan="6" class="text-center text-muted">No production lots.</td></tr>
      <?php else: ?>
        <?php foreach ($lots as $l): ?>
          <tr>
            <td><strong class="text-primary">#<?= htmlspecialchars($l['lot_no']) ?></strong></td>
            <td><?= date('d-M-Y', strtotime($l['date'])) ?></td>
            <td><?= htmlspecialchars($l['description'] ?: '-') ?></td>
            <td class="text-center"><?= (int)$l['total_workers'] ?> Workers</td>
            <td class="text-center fw-bold"><?= number_format((int)$l['total_pcs_done']) ?> Pcs</td>
            <td class="text-end fw-bold text-success"><?= format_rs($l['total_wages']) ?></td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
  </table>

  <!-- 4. Sales Invoices Summary -->
  <div class="section-title"><i class="fa-solid fa-receipt me-2"></i>4. Sales Invoices Directory Summary</div>
  <table class="table table-bordered table-sm mb-3">
    <thead>
      <tr>
        <th>Invoice No</th>
        <th>Date</th>
        <th>Customer Name</th>
        <th class="text-end">Grand Total</th>
        <th class="text-end text-success">Paid Amount</th>
        <th class="text-end">Remaining Balance</th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($invoices)): ?>
        <tr><td colspan="6" class="text-center text-muted">No sales invoices.</td></tr>
      <?php else: ?>
        <?php foreach ($invoices as $inv): ?>
          <tr>
            <td><strong class="text-primary">#<?= htmlspecialchars($inv['invoice_no']) ?></strong></td>
            <td><?= date('d-M-Y', strtotime($inv['date'])) ?></td>
            <td><?= htmlspecialchars($inv['customer_name'] ?: 'N/A') ?></td>
            <td class="text-end fw-bold"><?= format_rs($inv['grand_total']) ?></td>
            <td class="text-end text-success"><?= format_rs($inv['paid_amount']) ?></td>
            <td class="text-end fw-bold <?= $inv['balance'] > 0 ? 'text-danger' : 'text-success' ?>"><?= format_rs($inv['balance']) ?></td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
  </table>

  <!-- Footer Signature & Verification Block -->
  <div class="d-flex justify-content-between align-items-end mt-5 pt-4 border-top">
    <div>
      <small class="text-muted">Parway ERP System Confidential Data Backup Report</small>
    </div>
    <div class="text-center" style="width: 200px;">
      <div class="border-top border-dark pt-1 fw-bold">Authorized Signature</div>
    </div>
  </div>
</div>

<script>
  window.onload = function() {
    // Auto prompt print/save PDF dialog on page load
    setTimeout(function() {
      window.print();
    }, 500);
  };
</script>

</body>
</html>
