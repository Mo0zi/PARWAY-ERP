<?php
$page_title = 'Print Lot Working Sheet';
require_once __DIR__ . '/../../includes/header.php';

$lot_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($lot_id <= 0) {
    die("Invalid Lot ID.");
}

$stmt = $pdo->prepare("SELECT * FROM lots WHERE id = ?");
$stmt->execute([$lot_id]);
$lot = $stmt->fetch();

if (!$lot) {
    die("Lot not found.");
}

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

<style>
  @media print {
    @page {
      size: A4 portrait;
      margin: 12mm;
    }
    body {
      background: #fff !important;
      font-size: 13px !important;
    }
    .card-custom {
      border: none !important;
      box-shadow: none !important;
      padding: 0 !important;
    }
  }
</style>

<!-- Action Bar (Hidden on print) -->
<div class="d-flex justify-content-between align-items-center mb-3 no-print">
  <a href="<?= base_url('modules/working_sheet/lot_list.php') ?>" class="btn btn-outline-secondary btn-sm">
    <i class="fa-solid fa-arrow-left me-1"></i> Back to Lots Directory
  </a>
  <div class="d-flex gap-2">
    <a href="<?= base_url('modules/working_sheet/export_excel.php?lot_id=' . $lot['id']) ?>" class="btn btn-outline-success btn-sm fw-bold">
      <i class="fa-solid fa-file-excel me-1"></i> Export Excel
    </a>
    <button onclick="window.print()" class="btn btn-primary-custom btn-sm fw-bold">
      <i class="fa-solid fa-print me-1"></i> Print / Save as PDF
    </button>
  </div>
</div>

<div class="card-custom p-4">
  <!-- Lot Sheet Header with Official Logo -->
  <div class="row pb-3 mb-4 border-bottom align-items-center">
    <div class="col-7 d-flex align-items-center gap-3">
      <?php if (!empty($settings['logo']) && file_exists(__DIR__ . '/../../' . $settings['logo'])): ?>
        <img src="<?= base_url($settings['logo']) ?>" alt="Logo" style="max-height: 85px; width: auto; object-fit: contain;">
      <?php endif; ?>
      <div>
        <h4 class="fw-bold text-primary m-0"><?= htmlspecialchars($settings['company_name']) ?></h4>
        <p class="text-muted small m-0"><?= htmlspecialchars($settings['address']) ?></p>
        <p class="text-muted small m-0"><i class="fa-solid fa-phone me-1"></i><?= htmlspecialchars($settings['phone']) ?></p>
      </div>
    </div>
    <div class="col-5 text-end">
      <h3 class="fw-bold text-dark mb-1">LOT WORKING SHEET</h3>
      <div class="fw-bold text-danger fs-5">#<?= htmlspecialchars($lot['lot_no']) ?></div>
      <div class="text-muted small">Date: <?= date('d-M-Y', strtotime($lot['date'])) ?></div>
    </div>
  </div>

  <!-- Description Banner -->
  <?php if (!empty($lot['description'])): ?>
    <div class="p-3 bg-light rounded mb-4 border border-start border-4 border-primary">
      <strong class="text-dark small uppercase d-block">Article & Production Reference:</strong>
      <div class="fw-bold text-primary fs-6"><?= htmlspecialchars($lot['description']) ?></div>
    </div>
  <?php endif; ?>

  <!-- Items / Worker Breakdown Table -->
  <div class="table-responsive mb-4">
    <table class="table table-bordered table-custom mb-0">
      <thead class="table-light">
        <tr>
          <th style="width: 5%;">#</th>
          <th>Worker Name</th>
          <th>Work Type</th>
          <th class="text-end" style="width: 18%;">Rate / Piece</th>
          <th class="text-center" style="width: 15%;">Target Qty</th>
          <th class="text-center" style="width: 15%;">Qty Done</th>
          <th class="text-end" style="width: 20%;">Total Wage</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($work_entries as $idx => $we): 
          $total_pcs += $we['qty_done'];
          $total_wages += $we['total_amount'];
        ?>
          <tr>
            <td><?= $idx + 1 ?></td>
            <td>
              <strong><?= htmlspecialchars($we['worker_name']) ?></strong>
              <?php if ($we['worker_phone']): ?>
                <small class="text-muted d-block"><?= htmlspecialchars($we['worker_phone']) ?></small>
              <?php endif; ?>
            </td>
            <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($we['work_type']) ?></span></td>
            <td class="text-end"><?= format_rs($we['rate_per_piece']) ?></td>
            <td class="text-center"><?= number_format($we['qty_target']) ?></td>
            <td class="text-center fw-bold"><?= number_format($we['qty_done']) ?> Pcs</td>
            <td class="text-end fw-bold text-success"><?= format_rs($we['total_amount']) ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
      <tfoot class="table-light fw-bold fs-6">
        <tr>
          <td colspan="5" class="text-end">TOTAL LOT SUMMARY:</td>
          <td class="text-center text-dark"><?= number_format($total_pcs) ?> Pcs</td>
          <td class="text-end text-success"><?= format_rs($total_wages) ?></td>
        </tr>
      </tfoot>
    </table>
  </div>

  <!-- Signatures -->
  <div class="row mt-5 pt-4 text-center">
    <div class="col-6">
      <div class="border-top pt-2 text-muted small mx-auto" style="width: 60%;">Production Incharge Signature</div>
    </div>
    <div class="col-6">
      <div class="border-top pt-2 text-muted small mx-auto" style="width: 60%;">Authorized Management Signature</div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
