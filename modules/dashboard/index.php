<?php
$page_title = 'Dashboard Overview';
require_once __DIR__ . '/../../includes/header.php';

$today = date('Y-m-d');

// Today's Sales
$stmt = $pdo->prepare("SELECT SUM(grand_total) as today_sales FROM invoices WHERE date = ?");
$stmt->execute([$today]);
$today_sales = (float)($stmt->fetch()['today_sales'] ?? 0);

// Today's Wages
$stmt = $pdo->prepare("SELECT SUM(amount) as today_wages FROM worker_ledger WHERE date = ?");
$stmt->execute([$today]);
$today_wages = (float)($stmt->fetch()['today_wages'] ?? 0);

// Customer Balance
$stmt = $pdo->query("SELECT SUM(cl.balance) as total_customer_receivable 
                     FROM customer_ledger cl
                     INNER JOIN (
                         SELECT customer_id, MAX(id) as max_id 
                         FROM customer_ledger 
                         GROUP BY customer_id
                     ) latest ON cl.id = latest.max_id");
$pending_customer_balance = (float)($stmt->fetch()['total_customer_receivable'] ?? 0);

// Worker Balance
$stmt = $pdo->query("SELECT SUM(wl.balance) as total_worker_payable 
                     FROM worker_ledger wl
                     INNER JOIN (
                         SELECT worker_id, MAX(id) as max_id 
                         FROM worker_ledger 
                         GROUP BY worker_id
                     ) latest ON wl.id = latest.max_id");
$pending_worker_balance = (float)($stmt->fetch()['total_worker_payable'] ?? 0);

// Chart Data (7 Days)
$days = [];
$sales_data = [];
$wages_data = [];

for ($i = 6; $i >= 0; $i--) {
    $d = date('Y-m-d', strtotime("-$i days"));
    $days[] = date('D, M j', strtotime($d));

    $st = $pdo->prepare("SELECT SUM(grand_total) as s FROM invoices WHERE date = ?");
    $st->execute([$d]);
    $sales_data[] = (float)($st->fetch()['s'] ?? 0);

    $st = $pdo->prepare("SELECT SUM(amount) as w FROM worker_ledger WHERE date = ?");
    $st->execute([$d]);
    $wages_data[] = (float)($st->fetch()['w'] ?? 0);
}

// 1. Lot Completion Progress & Status
$lots_progress = $pdo->query("SELECT l.id, l.lot_no, l.date, l.description,
                              COUNT(lw.id) as worker_count,
                              SUM(lw.qty_target) as total_target,
                              SUM(lw.qty_done) as total_done,
                              SUM(lw.total_amount) as total_wages
                               FROM lots l
                               LEFT JOIN lot_work lw ON l.id = lw.lot_id
                               GROUP BY l.id
                               ORDER BY l.date DESC, l.id DESC
                               LIMIT 5")->fetchAll();

// 2. Worker Production Leaderboard (Who did how much work)
$worker_leaderboard = $pdo->query("SELECT w.id, w.name, w.phone,
                                   COUNT(DISTINCT lw.lot_id) as total_lots,
                                   SUM(lw.qty_done) as total_pcs_done,
                                   SUM(lw.total_amount) as total_earned
                                    FROM workers w
                                    INNER JOIN lot_work lw ON w.id = lw.worker_id
                                    GROUP BY w.id
                                    ORDER BY total_pcs_done DESC
                                    LIMIT 5")->fetchAll();
?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<style>
  .kpi-card-link {
    transition: transform 0.2s ease, box-shadow 0.2s ease;
    cursor: pointer;
  }
  .kpi-card-link:hover {
    transform: translateY(-4px);
    box-shadow: 0 10px 20px rgba(0,0,0,0.08) !important;
  }
</style>

<!-- Action Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
  <div>
    <h4 class="fw-bold text-dark m-0">Manufacturing & Business Control Panel</h4>
    <p class="text-muted small m-0">Live production metrics and financial overview</p>
  </div>
  <div class="d-flex gap-2">
    <a href="<?= base_url('modules/settings/backup.php') ?>" class="btn btn-outline-danger btn-sm fw-bold">
      <i class="fa-solid fa-cloud-arrow-down me-1"></i> System Backup
    </a>
    <a href="<?= base_url('modules/invoice/create.php') ?>" class="btn btn-primary-custom btn-sm fw-bold">
      <i class="fa-solid fa-plus me-1"></i> New Invoice
    </a>
    <a href="<?= base_url('modules/working_sheet/lot_entry.php') ?>" class="btn btn-outline-dark btn-sm fw-bold">
      <i class="fa-solid fa-table-cells me-1"></i> Lot Entry
    </a>
  </div>
</div>

<!-- Interactive Clickable KPI Cards -->
<div class="row g-3 mb-4">
  <!-- 1. Today's Sales -->
  <div class="col-md-3">
    <a href="<?= base_url('modules/invoice/list.php') ?>" class="card-custom p-3 mb-0 d-block text-decoration-none kpi-card-link" style="border-left: 5px solid #28a745;" title="Click to view sales invoices">
      <div class="d-flex justify-content-between align-items-center">
        <small class="text-muted uppercase fw-bold">Today's Total Sales</small>
        <i class="fa-solid fa-chevron-right text-success small"></i>
      </div>
      <h3 class="fw-bold text-success mt-1 mb-1"><?= format_rs($today_sales) ?></h3>
      <small class="text-muted fs-7"><i class="fa-solid fa-arrow-up-right-from-square me-1"></i> View Sales Invoices &rarr;</small>
    </a>
  </div>

  <!-- 2. Today's Worker Wages -->
  <div class="col-md-3">
    <a href="<?= base_url('modules/reports/daily.php') ?>" class="card-custom p-3 mb-0 d-block text-decoration-none kpi-card-link" style="border-left: 5px solid #fd7e14;" title="Click to view today's wages report">
      <div class="d-flex justify-content-between align-items-center">
        <small class="text-muted uppercase fw-bold">Today's Worker Wages</small>
        <i class="fa-solid fa-chevron-right text-warning small"></i>
      </div>
      <h3 class="fw-bold text-warning mt-1 mb-1"><?= format_rs($today_wages) ?></h3>
      <small class="text-muted fs-7"><i class="fa-solid fa-arrow-up-right-from-square me-1"></i> View Daily Wages Report &rarr;</small>
    </a>
  </div>

  <!-- 3. Customer Receivables -->
  <div class="col-md-3">
    <a href="<?= base_url('modules/customers/list.php') ?>" class="card-custom p-3 mb-0 d-block text-decoration-none kpi-card-link" style="border-left: 5px solid #dc3545;" title="Click to view customer balances">
      <div class="d-flex justify-content-between align-items-center">
        <small class="text-muted uppercase fw-bold">Customer Receivables</small>
        <i class="fa-solid fa-chevron-right text-danger small"></i>
      </div>
      <h3 class="fw-bold text-danger mt-1 mb-1"><?= format_rs($pending_customer_balance) ?></h3>
      <small class="text-muted fs-7"><i class="fa-solid fa-arrow-up-right-from-square me-1"></i> View Customer Balances &rarr;</small>
    </a>
  </div>

  <!-- 4. Worker Payables -->
  <div class="col-md-3">
    <a href="<?= base_url('modules/workers/list.php') ?>" class="card-custom p-3 mb-0 d-block text-decoration-none kpi-card-link" style="border-left: 5px solid #17a2b8;" title="Click to view worker payables">
      <div class="d-flex justify-content-between align-items-center">
        <small class="text-muted uppercase fw-bold">Worker Payables</small>
        <i class="fa-solid fa-chevron-right text-info small"></i>
      </div>
      <h3 class="fw-bold text-info mt-1 mb-1"><?= format_rs($pending_worker_balance) ?></h3>
      <small class="text-muted fs-7"><i class="fa-solid fa-arrow-up-right-from-square me-1"></i> View Worker Payables &rarr;</small>
    </a>
  </div>
</div>

<!-- Chart & Performance Graph -->
<div class="card-custom mb-4">
  <div class="card-custom-header">
    <h6 class="card-custom-title"><i class="fa-solid fa-chart-line text-primary me-2"></i> 7-Day Performance Trend (Sales vs Wages)</h6>
  </div>
  <div class="card-custom-body">
    <canvas id="performanceChart" style="max-height: 280px;"></canvas>
  </div>
</div>

<!-- Manufacturing & Production Widgets (Lot Completion & Worker Work Leaderboard) -->
<div class="row g-4 mb-4">
  
  <!-- Left Column: Lot Completion Progress Bar -->
  <div class="col-lg-7">
    <div class="card-custom h-100 mb-0">
      <div class="card-custom-header d-flex justify-content-between align-items-center">
        <h6 class="card-custom-title m-0">
          <i class="fa-solid fa-bars-progress text-success me-2"></i> Lot Completion Status (% Done)
        </h6>
        <a href="<?= base_url('modules/working_sheet/lot_list.php') ?>" class="small text-primary text-decoration-none fw-bold">
          View All Lots <i class="fa-solid fa-arrow-right ms-1"></i>
        </a>
      </div>
      <div class="card-custom-body p-0">
        <div class="table-responsive">
          <table class="table table-custom align-middle mb-0">
            <thead>
              <tr>
                <th>Lot No</th>
                <th>Target vs Done</th>
                <th style="width: 35%;">Progress (%)</th>
                <th class="text-end">Total Wage</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($lots_progress)): ?>
                <tr><td colspan="4" class="text-center text-muted py-4">No active lot sheets recorded yet.</td></tr>
              <?php else: ?>
                <?php foreach ($lots_progress as $lot): 
                  $target = (int)$lot['total_target'];
                  $done = (int)$lot['total_done'];
                  $pct = $target > 0 ? min(100, round(($done / $target) * 100)) : ($done > 0 ? 100 : 0);
                  
                  $bar_class = $pct >= 100 ? 'bg-success' : ($pct >= 50 ? 'bg-primary' : 'bg-warning');
                ?>
                  <tr>
                    <td>
                      <strong class="text-primary">#<?= htmlspecialchars($lot['lot_no']) ?></strong>
                      <div class="text-muted small"><?= date('d-M', strtotime($lot['date'])) ?> | <?= htmlspecialchars($lot['description'] ?: 'Production') ?></div>
                    </td>
                    <td>
                      <div class="fw-bold text-dark"><?= number_format($done) ?> / <?= $target > 0 ? number_format($target) : 'N/A' ?> Pcs</div>
                      <small class="text-muted"><?= (int)$lot['worker_count'] ?> Workers Worked</small>
                    </td>
                    <td>
                      <div class="d-flex align-items-center gap-2">
                        <div class="progress flex-grow-1" style="height: 10px;">
                          <div class="progress-bar <?= $bar_class ?> progress-bar-striped progress-bar-animated" role="progressbar" style="width: <?= $pct ?>%;"></div>
                        </div>
                        <span class="fw-bold small <?= $pct >= 100 ? 'text-success' : 'text-dark' ?>"><?= $pct ?>%</span>
                      </div>
                    </td>
                    <td class="text-end fw-bold text-success">
                      <?= format_rs($lot['total_wages']) ?>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

  <!-- Right Column: Worker Work & Production Leaderboard -->
  <div class="col-lg-5">
    <div class="card-custom h-100 mb-0">
      <div class="card-custom-header d-flex justify-content-between align-items-center">
        <h6 class="card-custom-title m-0">
          <i class="fa-solid fa-trophy text-warning me-2"></i> Worker Production Leaderboard
        </h6>
        <a href="<?= base_url('modules/workers/list.php') ?>" class="small text-primary text-decoration-none fw-bold">
          All Workers <i class="fa-solid fa-arrow-right ms-1"></i>
        </a>
      </div>
      <div class="card-custom-body p-0">
        <div class="table-responsive">
          <table class="table table-custom align-middle mb-0">
            <thead>
              <tr>
                <th>Rank & Worker</th>
                <th class="text-center">Total Pcs</th>
                <th class="text-end">Total Earned</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($worker_leaderboard)): ?>
                <tr><td colspan="3" class="text-center text-muted py-4">No worker production recorded yet.</td></tr>
              <?php else: ?>
                <?php foreach ($worker_leaderboard as $idx => $w): 
                  $rank_badge = $idx === 0 ? 'bg-warning text-dark' : ($idx === 1 ? 'bg-secondary text-white' : ($idx === 2 ? 'bg-danger text-white' : 'bg-light text-dark border'));
                ?>
                  <tr>
                    <td>
                      <div class="d-flex align-items-center gap-2">
                        <span class="badge rounded-circle p-2 <?= $rank_badge ?>" style="width:24px; height:24px; display:inline-flex; align-items:center; justify-content:center;">
                          <?= $idx + 1 ?>
                        </span>
                        <div>
                          <a href="<?= base_url('modules/workers/ledger.php?id=' . $w['id']) ?>" class="fw-bold text-dark text-decoration-none hover-primary d-block">
                            <?= htmlspecialchars($w['name']) ?>
                          </a>
                          <small class="text-muted"><?= (int)$w['total_lots'] ?> Lots Worked</small>
                        </div>
                      </div>
                    </td>
                    <td class="text-center fw-bold text-dark">
                      <?= number_format((int)$w['total_pcs_done']) ?> Pcs
                    </td>
                    <td class="text-end fw-bold text-success">
                      <?= format_rs($w['total_earned']) ?>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

</div>

<script>
$(document).ready(function() {
  const ctx = document.getElementById('performanceChart').getContext('2d');
  new Chart(ctx, {
    type: 'line',
    data: {
      labels: <?= json_encode($days) ?>,
      datasets: [
        {
          label: 'Sales (Grand Total)',
          data: <?= json_encode($sales_data) ?>,
          borderColor: '#28a745',
          backgroundColor: 'rgba(40, 167, 69, 0.1)',
          fill: true,
          tension: 0.3
        },
        {
          label: 'Worker Wages Earned',
          data: <?= json_encode($wages_data) ?>,
          borderColor: '#fd7e14',
          backgroundColor: 'rgba(253, 126, 20, 0.1)',
          fill: true,
          tension: 0.3
        }
      ]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: { position: 'top' }
      },
      scales: {
        y: { beginAtZero: true }
      }
    }
  });
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
