<?php
require_once __DIR__ . '/../../config/db.php';
require_login();

// Handle Delete Lot
if (isset($_GET['delete'])) {
    $del_id = (int)$_GET['delete'];
    $pdo->prepare("DELETE FROM lots WHERE id = ?")->execute([$del_id]);
    header("Location: " . base_url('modules/working_sheet/lot_list.php?msg=deleted'));
    exit();
}

$page_title = 'All Lots / Working Sheets';
require_once __DIR__ . '/../../includes/header.php';

$lots = $pdo->query("SELECT l.*, 
                    COUNT(lw.id) as total_workers, 
                    SUM(lw.qty_done) as total_pcs_done, 
                    SUM(lw.total_amount) as total_wages 
                     FROM lots l 
                     LEFT JOIN lot_work lw ON l.id = lw.lot_id 
                     GROUP BY l.id 
                     ORDER BY l.date DESC, l.id DESC")->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <h5 class="fw-bold m-0"><i class="fa-solid fa-layer-group text-primary me-2"></i>Lot Working Sheets Directory</h5>
  <a href="<?= base_url('modules/working_sheet/lot_entry.php') ?>" class="btn btn-primary-custom btn-sm">
    <i class="fa-solid fa-plus me-1"></i> New Lot Entry
  </a>
</div>

<?php if (isset($_GET['msg']) && $_GET['msg'] === 'saved'): ?>
  <div class="alert alert-success alert-dismissible fade show py-2">
    <i class="fa-solid fa-check-circle me-1"></i> Lot entry saved successfully and worker ledgers credited!
    <button type="button" class="btn-close py-2" data-bs-dismiss="alert"></button>
  </div>
<?php endif; ?>

<?php if (isset($_GET['msg']) && $_GET['msg'] === 'updated'): ?>
  <div class="alert alert-success alert-dismissible fade show py-2">
    <i class="fa-solid fa-check-double me-1"></i> Lot working sheet updated successfully and worker ledgers recalculated!
    <button type="button" class="btn-close py-2" data-bs-dismiss="alert"></button>
  </div>
<?php endif; ?>

<?php if (isset($_GET['msg']) && $_GET['msg'] === 'deleted'): ?>
  <div class="alert alert-warning alert-dismissible fade show py-2">
    <i class="fa-solid fa-trash me-1"></i> Lot entry deleted.
    <button type="button" class="btn-close py-2" data-bs-dismiss="alert"></button>
  </div>
<?php endif; ?>

<div class="card-custom">
  <div class="card-custom-body p-0">
    <div class="table-responsive">
      <table class="table table-custom mb-0">
        <thead>
          <tr>
            <th>Lot No</th>
            <th>Date</th>
            <th>Description</th>
            <th class="text-center">Workers Count</th>
            <th class="text-center">Total Pcs Done</th>
            <th class="text-end">Total Lot Wages</th>
            <th class="text-center">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($lots)): ?>
            <tr><td colspan="7" class="text-center text-muted py-4">No lot working sheets registered yet.</td></tr>
          <?php else: ?>
            <?php foreach ($lots as $lot): ?>
              <tr>
                <td><strong class="text-primary">#<?= htmlspecialchars($lot['lot_no']) ?></strong></td>
                <td><?= date('d-M-Y', strtotime($lot['date'])) ?></td>
                <td><?= htmlspecialchars($lot['description'] ?: '-') ?></td>
                <td class="text-center"><span class="badge bg-secondary"><?= (int)$lot['total_workers'] ?> Workers</span></td>
                <td class="text-center fw-bold text-dark"><?= number_format((int)$lot['total_pcs_done']) ?> Pcs</td>
                <td class="text-end fw-bold text-success"><?= format_rs($lot['total_wages']) ?></td>
                <td class="text-center">
                  <button type="button" class="btn btn-sm btn-outline-primary py-1 px-2 view-lot-btn" data-id="<?= $lot['id'] ?>" title="View Lot Breakdown">
                    <i class="fa-solid fa-eye me-1"></i> View
                  </button>

                  <a href="<?= base_url('modules/working_sheet/lot_edit.php?id=' . $lot['id']) ?>" class="btn btn-sm btn-outline-secondary py-1 px-2" title="Edit Lot & Add Pieces">
                    <i class="fa-solid fa-pen me-1"></i> Edit
                  </a>

                  <a href="<?= base_url('modules/working_sheet/export_excel.php?lot_id=' . $lot['id']) ?>" class="btn btn-sm btn-outline-success py-1 px-2" title="Export to Excel">
                    <i class="fa-solid fa-file-excel me-1"></i> Excel
                  </a>

                  <a href="<?= base_url('modules/working_sheet/lot_list.php?delete=' . $lot['id']) ?>" class="btn btn-sm btn-outline-danger py-1 px-2" onclick="return confirm('Delete this lot and its worker entries?');" title="Delete Lot">
                    <i class="fa-solid fa-trash"></i>
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

<!-- Modal for Viewing Lot Details -->
<div class="modal fade" id="viewLotModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header bg-light">
        <h6 class="modal-header-title fw-bold m-0 text-dark">
          <i class="fa-solid fa-layer-group text-primary me-2"></i> Lot Working Sheet Details
        </h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body p-3" id="viewLotModalBody">
        <div class="text-center py-4">
          <div class="spinner-border text-primary" role="status"></div>
          <p class="text-muted small mt-2">Loading Lot Working Sheet...</p>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
$(document).ready(function() {
  $('.view-lot-btn').on('click', function() {
    const lotId = $(this).data('id');
    $('#viewLotModalBody').html(`
      <div class="text-center py-4">
        <div class="spinner-border text-primary" role="status"></div>
        <p class="text-muted small mt-2">Loading Lot Working Sheet...</p>
      </div>
    `);

    const modal = new bootstrap.Modal(document.getElementById('viewLotModal'));
    modal.show();

    $.get('<?= base_url("modules/working_sheet/ajax_lot_details.php") ?>', { id: lotId }, function(html) {
      $('#viewLotModalBody').html(html);
    }).fail(function() {
      $('#viewLotModalBody').html('<div class="alert alert-danger">Failed to load lot details.</div>');
    });
  });
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
