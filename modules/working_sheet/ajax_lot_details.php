<?php
require_once __DIR__ . '/../../config/db.php';
require_login();

$lot_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($lot_id <= 0) {
    echo '<div class="alert alert-danger">Invalid Lot ID.</div>';
    exit();
}

// Fetch Lot
$stmt = $pdo->prepare("SELECT * FROM lots WHERE id = ?");
$stmt->execute([$lot_id]);
$lot = $stmt->fetch();

if (!$lot) {
    echo '<div class="alert alert-danger">Lot not found.</div>';
    exit();
}

// Fetch Lot Work Entries
$stmt_work = $pdo->prepare("SELECT lw.*, w.name as worker_name, w.phone as worker_phone 
                            FROM lot_work lw 
                            INNER JOIN workers w ON lw.worker_id = w.id 
                            WHERE lw.lot_id = ? 
                            ORDER BY lw.id ASC");
$stmt_work->execute([$lot_id]);
$work_entries = $stmt_work->fetchAll();

$total_pcs = 0;
$total_wages = 0.00;
?>

<div class="p-2">
  <!-- Lot Header -->
  <div class="d-flex justify-content-between align-items-center pb-3 border-bottom mb-3">
    <div>
      <h5 class="fw-bold text-primary m-0">Lot #<?= htmlspecialchars($lot['lot_no']) ?></h5>
      <small class="text-muted"><i class="fa-solid fa-calendar-day me-1"></i> Date: <?= date('d-M-Y', strtotime($lot['date'])) ?></small>
    </div>
    <div class="text-end">
      <span class="badge bg-secondary fs-6"><?= count($work_entries) ?> Workers Assigned</span>
      <div class="text-muted small mt-1"><?= htmlspecialchars($lot['description'] ?: 'No Description') ?></div>
    </div>
  </div>

  <!-- Worker Production Table -->
  <div class="table-responsive mb-3">
    <table class="table table-bordered table-custom mb-0">
      <thead class="table-light">
        <tr>
          <th>#</th>
          <th>Worker Name</th>
          <th>Work Type</th>
          <th class="text-end">Rate / Pc</th>
          <th class="text-center">Target Qty</th>
          <th class="text-center">Qty Done</th>
          <th class="text-end">Total Wage</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($work_entries)): ?>
          <tr><td colspan="7" class="text-center text-muted py-3">No worker entries recorded for this lot.</td></tr>
        <?php else: ?>
          <?php foreach ($work_entries as $idx => $we): 
            $total_pcs += $we['qty_done'];
            $total_wages += $we['total_amount'];
          ?>
            <tr>
              <td><?= $idx + 1 ?></td>
              <td>
                <strong class="text-dark"><?= htmlspecialchars($we['worker_name']) ?></strong>
                <?php if ($we['worker_phone']): ?>
                  <small class="text-muted d-block"><?= htmlspecialchars($we['worker_phone']) ?></small>
                <?php endif; ?>
              </td>
              <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($we['work_type']) ?></span></td>
              <td class="text-end"><?= format_rs($we['rate_per_piece']) ?></td>
              <td class="text-center"><?= number_format($we['qty_target']) ?></td>
              <td class="text-center fw-bold text-dark"><?= number_format($we['qty_done']) ?> Pcs</td>
              <td class="text-end fw-bold text-success"><?= format_rs($we['total_amount']) ?></td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
      <tfoot class="table-light fw-bold">
        <tr>
          <td colspan="5" class="text-end">TOTAL LOT SUMMARY:</td>
          <td class="text-center text-dark"><?= number_format($total_pcs) ?> Pcs</td>
          <td class="text-end text-success fs-6"><?= format_rs($total_wages) ?></td>
        </tr>
      </tfoot>
    </table>
  </div>

  <!-- Action Bar -->
  <div class="d-flex justify-content-end gap-2">
    <a href="<?= base_url('modules/working_sheet/lot_edit.php?id=' . $lot['id']) ?>" class="btn btn-outline-secondary btn-sm fw-bold">
      <i class="fa-solid fa-pen me-1"></i> Edit Lot & Add Pieces
    </a>
    <a href="<?= base_url('modules/working_sheet/export_excel.php?lot_id=' . $lot['id']) ?>" class="btn btn-outline-success btn-sm fw-bold">
      <i class="fa-solid fa-file-excel me-1"></i> Download Excel
    </a>
    <a href="<?= base_url('modules/working_sheet/view_lot.php?id=' . $lot['id']) ?>" target="_blank" class="btn btn-primary-custom btn-sm fw-bold">
      <i class="fa-solid fa-print me-1"></i> Printable Lot Sheet
    </a>
  </div>
</div>
