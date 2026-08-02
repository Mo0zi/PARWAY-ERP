<?php
$page_title = 'Sales & Production Reports';
require_once __DIR__ . '/../../includes/header.php';

$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-d');

// 1. Total Sales Revenue in Period
$stmt = $pdo->prepare("SELECT SUM(grand_total) as total_sales, COUNT(*) as invoice_count FROM invoices WHERE date BETWEEN ? AND ?");
$stmt->execute([$start_date, $end_date]);
$sales_summary = $stmt->fetch();
$period_sales = (float)($sales_summary['total_sales'] ?? 0);
$invoice_count = (int)($sales_summary['invoice_count'] ?? 0);

// 2. Total Lot Production & Wages in Period
$stmt = $pdo->prepare("SELECT SUM(total_amount) as total_wages, SUM(qty_done) as total_pcs FROM lot_work WHERE date BETWEEN ? AND ?");
$stmt->execute([$start_date, $end_date]);
$prod_summary = $stmt->fetch();
$period_wages = (float)($prod_summary['total_wages'] ?? 0);
$period_pcs = (int)($prod_summary['total_pcs'] ?? 0);

// Invoices List in Period
$stmt = $pdo->prepare("SELECT i.*, c.name as customer_name FROM invoices i LEFT JOIN customers c ON i.customer_id = c.id WHERE i.date BETWEEN ? AND ? ORDER BY i.date DESC");
$stmt->execute([$start_date, $end_date]);
$invoices = $stmt->fetchAll();

// Lot Works List in Period
$stmt = $pdo->prepare("SELECT lw.*, l.lot_no, w.name as worker_name FROM lot_work lw INNER JOIN lots l ON lw.lot_id = l.id INNER JOIN workers w ON lw.worker_id = w.id WHERE lw.date BETWEEN ? AND ? ORDER BY lw.date DESC");
$stmt->execute([$start_date, $end_date]);
$lot_works = $stmt->fetchAll();
?>

<!-- Filter Form Bar -->
<div class="card-custom mb-4 no-print">
  <div class="card-custom-body py-3">
    <form action="" method="GET" class="row g-3 align-items-end">
      <div class="col-md-3">
        <label class="form-label small fw-bold">From Date</label>
        <input type="date" name="start_date" class="form-control form-control-sm" value="<?= htmlspecialchars($start_date) ?>" required>
      </div>

      <div class="col-md-3">
        <label class="form-label small fw-bold">To Date</label>
        <input type="date" name="end_date" class="form-control form-control-sm" value="<?= htmlspecialchars($end_date) ?>" required>
      </div>

      <div class="col-md-4 d-flex gap-2">
        <button type="submit" class="btn btn-primary-custom btn-sm fw-bold px-3">
          <i class="fa-solid fa-filter me-1"></i> Filter Report
        </button>
        <a href="<?= base_url('modules/reports/daily.php?start_date=' . date('Y-m-d') . '&end_date=' . date('Y-m-d')) ?>" class="btn btn-outline-secondary btn-sm">Today</a>
        <a href="<?= base_url('modules/reports/daily.php?start_date=' . date('Y-m-01') . '&end_date=' . date('Y-m-d')) ?>" class="btn btn-outline-secondary btn-sm">This Month</a>
      </div>

      <div class="col-md-2 text-end">
        <button onclick="window.print()" class="btn btn-outline-dark btn-sm">
          <i class="fa-solid fa-print me-1"></i> Print
        </button>
      </div>
    </form>
  </div>
</div>

<!-- Summary Widgets -->
<div class="row g-3 mb-4">
  <div class="col-md-4">
    <div class="widget-card">
      <div class="widget-icon" style="background: rgba(16, 185, 129, 0.1); color: #10b981;">
        <i class="fa-solid fa-sack-dollar"></i>
      </div>
      <div class="widget-info">
        <div class="widget-label">Total Sales Revenue</div>
        <div class="widget-value text-success"><?= format_rs($period_sales) ?></div>
        <small class="text-muted"><?= $invoice_count ?> Sales Invoices</small>
      </div>
    </div>
  </div>

  <div class="col-md-4">
    <div class="widget-card">
      <div class="widget-icon" style="background: rgba(239, 68, 68, 0.1); color: #ef4444;">
        <i class="fa-solid fa-hand-holding-hand"></i>
      </div>
      <div class="widget-info">
        <div class="widget-label">Total Wages Expense</div>
        <div class="widget-value text-danger"><?= format_rs($period_wages) ?></div>
        <small class="text-muted"><?= number_format($period_pcs) ?> Pieces Produced</small>
      </div>
    </div>
  </div>

  <div class="col-md-4">
    <div class="widget-card">
      <div class="widget-icon" style="background: rgba(59, 130, 246, 0.1); color: #3b82f6;">
        <i class="fa-solid fa-chart-line"></i>
      </div>
      <div class="widget-info">
        <div class="widget-label">Gross Production Margin</div>
        <div class="widget-value text-primary"><?= format_rs($period_sales - $period_wages) ?></div>
        <small class="text-muted">Sales Revenue minus Wages</small>
      </div>
    </div>
  </div>
</div>

<!-- Sales Invoices Breakdown -->
<div class="card-custom mb-4">
  <div class="card-custom-header">
    <h6 class="card-custom-title"><i class="fa-solid fa-receipt text-primary"></i> Invoices Summary (<?= date('d M Y', strtotime($start_date)) ?> to <?= date('d M Y', strtotime($end_date)) ?>)</h6>
  </div>
  <div class="card-custom-body p-0">
    <div class="table-responsive">
      <table class="table table-custom mb-0">
        <thead>
          <tr>
            <th>Date</th>
            <th>Invoice No</th>
            <th>Customer</th>
            <th class="text-end">Grand Total</th>
            <th class="text-end">Paid Amount</th>
            <th class="text-end">Balance</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($invoices)): ?>
            <tr><td colspan="6" class="text-center text-muted py-3">No sales in selected period.</td></tr>
          <?php else: ?>
            <?php foreach ($invoices as $inv): ?>
              <tr>
                <td><?= date('d-M-Y', strtotime($inv['date'])) ?></td>
                <td><strong>#<?= htmlspecialchars($inv['invoice_no']) ?></strong></td>
                <td><?= htmlspecialchars($inv['customer_name']) ?></td>
                <td class="text-end fw-bold"><?= format_rs($inv['grand_total']) ?></td>
                <td class="text-end text-success"><?= format_rs($inv['paid_amount']) ?></td>
                <td class="text-end text-danger"><?= format_rs($inv['balance']) ?></td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- Lot Production Breakdown -->
<div class="card-custom">
  <div class="card-custom-header">
    <h6 class="card-custom-title"><i class="fa-solid fa-shirt text-secondary"></i> Lot Production Summary</h6>
  </div>
  <div class="card-custom-body p-0">
    <div class="table-responsive">
      <table class="table table-custom mb-0">
        <thead>
          <tr>
            <th>Date</th>
            <th>Lot No</th>
            <th>Worker Name</th>
            <th>Work Type</th>
            <th class="text-center">Target Qty</th>
            <th class="text-center">Done Qty</th>
            <th class="text-end">Wages Amount</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($lot_works)): ?>
            <tr><td colspan="7" class="text-center text-muted py-3">No production records in selected period.</td></tr>
          <?php else: ?>
            <?php foreach ($lot_works as $lw): ?>
              <tr>
                <td><?= date('d-M-Y', strtotime($lw['date'])) ?></td>
                <td><strong>#<?= htmlspecialchars($lw['lot_no']) ?></strong></td>
                <td><?= htmlspecialchars($lw['worker_name']) ?></td>
                <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($lw['work_type']) ?></span></td>
                <td class="text-center"><?= $lw['qty_target'] ?></td>
                <td class="text-center fw-bold text-primary"><?= $lw['qty_done'] ?></td>
                <td class="text-end fw-bold text-success"><?= format_rs($lw['total_amount']) ?></td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
