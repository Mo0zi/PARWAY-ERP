<?php
$page_title = 'Wage & Production Speed Analyzer';
require_once __DIR__ . '/../../includes/header.php';

$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-d');

// Fetch worker performance aggregated
$sql = "SELECT w.id, w.name, w.phone, w.default_rate, 
               COALESCE(SUM(lw.qty_done), 0) as total_done, 
               COALESCE(SUM(lw.qty_target), 0) as total_target, 
               COALESCE(SUM(lw.total_amount), 0) as total_earnings 
        FROM workers w 
        LEFT JOIN lot_work lw ON w.id = lw.worker_id AND (lw.date BETWEEN ? AND ?) 
        GROUP BY w.id 
        ORDER BY total_earnings DESC, total_done DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute([$start_date, $end_date]);
$analyzed_workers = $stmt->fetchAll();

$top_worker_id = !empty($analyzed_workers) ? $analyzed_workers[0]['id'] : 0;
?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <h5 class="fw-bold m-0"><i class="fa-solid fa-calculator text-primary me-2"></i>Worker Productivity & Wage Analyzer</h5>
  <button onclick="window.print()" class="btn btn-outline-dark btn-sm no-print">
    <i class="fa-solid fa-print me-1"></i> Print Analysis
  </button>
</div>

<!-- Date Filter Form -->
<div class="card-custom mb-4 no-print">
  <div class="card-custom-body py-3">
    <form action="" method="GET" class="row g-3 align-items-end">
      <div class="col-md-3">
        <label class="form-label small fw-bold">Analysis From</label>
        <input type="date" name="start_date" class="form-control form-control-sm" value="<?= htmlspecialchars($start_date) ?>" required>
      </div>

      <div class="col-md-3">
        <label class="form-label small fw-bold">Analysis To</label>
        <input type="date" name="end_date" class="form-control form-control-sm" value="<?= htmlspecialchars($end_date) ?>" required>
      </div>

      <div class="col-md-3">
        <button type="submit" class="btn btn-primary-custom btn-sm fw-bold px-3">
          <i class="fa-solid fa-chart-line me-1"></i> Analyze
        </button>
      </div>
    </form>
  </div>
</div>

<!-- Performance Table -->
<div class="card-custom">
  <div class="card-custom-header">
    <h6 class="card-custom-title">
      <i class="fa-solid fa-trophy text-warning"></i> Worker Performance Ranking (<?= date('d M Y', strtotime($start_date)) ?> - <?= date('d M Y', strtotime($end_date)) ?>)
    </h6>
  </div>
  <div class="card-custom-body p-0">
    <div class="table-responsive">
      <table class="table table-custom mb-0">
        <thead>
          <tr>
            <th>Rank</th>
            <th>Worker Name</th>
            <th>Default Piece Rate</th>
            <th class="text-center">Total Target</th>
            <th class="text-center">Total Done (Pcs)</th>
            <th class="text-center">Efficiency Rate</th>
            <th class="text-end">Total Earnings</th>
            <th class="text-center no-print">Action</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($analyzed_workers)): ?>
            <tr><td colspan="8" class="text-center text-muted py-4">No worker records found.</td></tr>
          <?php else: ?>
            <?php foreach ($analyzed_workers as $idx => $w): 
              $eff = $w['total_target'] > 0 ? round(($w['total_done'] / $w['total_target']) * 100, 1) : 0;
            ?>
              <tr class="<?= $w['id'] === $top_worker_id && $w['total_done'] > 0 ? 'table-warning' : '' ?>">
                <td>
                  <?php if ($idx === 0 && $w['total_done'] > 0): ?>
                    <span class="badge bg-warning text-dark"><i class="fa-solid fa-crown me-1"></i> #1 Top Worker</span>
                  <?php else: ?>
                    <span class="fw-bold text-muted">#<?= $idx + 1 ?></span>
                  <?php endif; ?>
                </td>
                <td>
                  <strong><?= htmlspecialchars($w['name']) ?></strong>
                  <small class="d-block text-muted"><?= htmlspecialchars($w['phone'] ?: 'N/A') ?></small>
                </td>
                <td><?= format_rs($w['default_rate']) ?> / pc</td>
                <td class="text-center"><?= number_format($w['total_target']) ?></td>
                <td class="text-center fw-bold text-primary"><?= number_format($w['total_done']) ?> Pcs</td>
                <td class="text-center">
                  <div class="progress" style="height: 18px;">
                    <div class="progress-bar <?= $eff >= 100 ? 'bg-success' : ($eff >= 75 ? 'bg-info' : 'bg-warning') ?>" role="progressbar" style="width: <?= min(100, $eff) ?>%;">
                      <?= $eff ?>%
                    </div>
                  </div>
                </td>
                <td class="text-end fw-bold text-success fs-6"><?= format_rs($w['total_earnings']) ?></td>
                <td class="text-center no-print">
                  <a href="<?= base_url('modules/workers/ledger.php?id=' . $w['id']) ?>" class="btn btn-sm btn-outline-primary py-1 px-2">
                    <i class="fa-solid fa-book-journal-whills me-1"></i> Ledger
                  </a>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
