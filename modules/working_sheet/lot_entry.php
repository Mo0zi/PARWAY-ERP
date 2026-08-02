<?php
require_once __DIR__ . '/../../config/db.php';
require_login();

// Handle Save Lot Entry
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_lot'])) {
    $lot_no = trim($_POST['lot_no'] ?? '');
    $date = trim($_POST['date'] ?? date('Y-m-d'));
    $description = trim($_POST['description'] ?? '');

    $worker_ids = $_POST['worker_id'] ?? [];
    $work_types = $_POST['work_type'] ?? [];
    $rates = $_POST['rate_per_piece'] ?? [];
    $targets = $_POST['qty_target'] ?? [];
    $dones = $_POST['qty_done'] ?? [];

    if (!empty($lot_no) && !empty($worker_ids)) {
        // Insert Lot
        $stmt = $pdo->prepare("INSERT INTO lots (lot_no, date, description) VALUES (?, ?, ?)");
        $stmt->execute([$lot_no, $date, $description]);
        $lot_id = $pdo->lastInsertId();

        // Insert Lot Work & Credit Worker Ledgers
        $stmt_work = $pdo->prepare("INSERT INTO lot_work (lot_id, worker_id, work_type, rate_per_piece, qty_done, qty_target, total_amount, date) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt_wledger = $pdo->prepare("INSERT INTO worker_ledger (worker_id, date, type, category, description, amount) VALUES (?, ?, 'credit', 'lot_payment', ?, ?)");

        for ($i = 0; $i < count($worker_ids); $i++) {
            $w_id = (int)$worker_ids[$i];
            $type = trim($work_types[$i] ?? 'Stitching');
            $rate = (float)($rates[$i] ?? 0);
            $target = (int)($targets[$i] ?? 0);
            $done = (int)($dones[$i] ?? 0);

            if ($w_id > 0 && $done > 0) {
                $total_amount = $done * $rate;
                $stmt_work->execute([$lot_id, $w_id, $type, $rate, $done, $target, $total_amount, $date]);

                // Credit Worker Ledger
                $work_desc = "Lot #$lot_no Work ($type) - $done pcs @ " . format_rs($rate) . "/pc";
                $stmt_wledger->execute([$w_id, $date, $work_desc, $total_amount]);

                // Recalculate Worker Balance
                recalculate_worker_balance($w_id, $pdo);
            }
        }

        header("Location: " . base_url('modules/working_sheet/lot_list.php?msg=saved'));
        exit();
    }
}

$page_title = 'Lot Working Sheet Entry';
require_once __DIR__ . '/../../includes/header.php';

// Auto-generate Lot No
$stmt = $pdo->query("SELECT MAX(id) as max_id FROM lots");
$next_lot_id = ((int)($stmt->fetch()['max_id'] ?? 0)) + 101;
$auto_lot_no = 'LOT-' . $next_lot_id;

// Fetch Workers
$workers = $pdo->query("SELECT id, name, default_rate FROM workers ORDER BY name ASC")->fetchAll();

// Dynamic Work Types from Customizer Settings
$raw_types = $settings['default_work_types'] ?? 'Stitching, Cutting, Overlock, Packing, Pressing, Embroidery, Thread Cutting';
$work_types_list = array_map('trim', explode(',', $raw_types));
?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <h5 class="fw-bold m-0"><i class="fa-solid fa-table-cells text-primary me-2"></i>New Lot Working Sheet (Excel-Like)</h5>
  <a href="<?= base_url('modules/working_sheet/lot_list.php') ?>" class="btn btn-outline-secondary btn-sm">
    <i class="fa-solid fa-list me-1"></i> View All Lots
  </a>
</div>

<form action="" method="POST" id="lotForm">
  <input type="hidden" name="save_lot" value="1">
  
  <!-- Lot Meta Info Header -->
  <div class="card-custom mb-3">
    <div class="card-custom-body">
      <div class="row g-3">
        <div class="col-md-3">
          <label class="form-label small fw-bold">Lot / Production Number <span class="text-danger">*</span></label>
          <input type="text" name="lot_no" class="form-control form-control-sm fw-bold text-primary" value="<?= $auto_lot_no ?>" required>
        </div>
        <div class="col-md-3">
          <label class="form-label small fw-bold">Date <span class="text-danger">*</span></label>
          <input type="date" name="date" class="form-control form-control-sm" value="<?= date('Y-m-d') ?>" required>
        </div>
        <div class="col-md-6">
          <label class="form-label small fw-bold">Description / Article Reference</label>
          <input type="text" name="description" class="form-control form-control-sm" placeholder="e.g. Denim Trouser Lot #402 - 500 Pcs Order">
        </div>
      </div>
    </div>
  </div>

  <!-- Excel-Like Worker Entry Table -->
  <div class="card-custom mb-3">
    <div class="card-custom-header d-flex justify-content-between align-items-center">
      <h6 class="card-custom-title"><i class="fa-solid fa-users-gear text-success me-1"></i> Worker Production Entries</h6>
      <button type="button" class="btn btn-sm btn-outline-primary" id="addRowBtn">
        <i class="fa-solid fa-plus me-1"></i> Add Row
      </button>
    </div>
    <div class="card-custom-body p-0">
      <div class="table-responsive">
        <table class="table table-bordered table-custom mb-0" id="lotWorkTable">
          <thead class="table-light">
            <tr>
              <th style="width: 25%;">Worker Name <span class="text-danger">*</span></th>
              <th style="width: 20%;">Work Type</th>
              <th style="width: 15%;" class="text-end">Rate / Pc (<?= htmlspecialchars($settings['currency_symbol']) ?>)</th>
              <th style="width: 15%;" class="text-center">Target Qty</th>
              <th style="width: 15%;" class="text-center">Qty Done <span class="text-danger">*</span></th>
              <th style="width: 20%;" class="text-end">Total Wage (<?= htmlspecialchars($settings['currency_symbol']) ?>)</th>
              <th style="width: 5%;" class="text-center">#</th>
            </tr>
          </thead>
          <tbody>
            <tr class="lot-row">
              <td>
                <select name="worker_id[]" class="form-select form-select-sm worker-select" required>
                  <option value="">-- Select Worker --</option>
                  <?php foreach ($workers as $w): ?>
                    <option value="<?= $w['id'] ?>" data-rate="<?= $w['default_rate'] ?>"><?= htmlspecialchars($w['name']) ?></option>
                  <?php endforeach; ?>
                </select>
              </td>
              <td>
                <select name="work_type[]" class="form-select form-select-sm">
                  <?php foreach ($work_types_list as $wt): ?>
                    <option value="<?= htmlspecialchars($wt) ?>"><?= htmlspecialchars($wt) ?></option>
                  <?php endforeach; ?>
                </select>
              </td>
              <td>
                <input type="number" step="0.01" name="rate_per_piece[]" class="form-control form-control-sm text-end rate-input" placeholder="0.00" required>
              </td>
              <td>
                <input type="number" name="qty_target[]" class="form-control form-control-sm text-center target-input" placeholder="0">
              </td>
              <td>
                <input type="number" name="qty_done[]" class="form-control form-control-sm text-center done-input fw-bold" placeholder="0" required>
              </td>
              <td>
                <input type="text" class="form-control form-control-sm text-end fw-bold text-success total-wage-display" value="0.00" readonly>
              </td>
              <td class="text-center">
                <button type="button" class="btn btn-sm btn-outline-danger remove-row-btn"><i class="fa-solid fa-xmark"></i></button>
              </td>
            </tr>
          </tbody>
          <tfoot>
            <tr class="table-light fw-bold">
              <td colspan="4" class="text-end">LOT TOTALS:</td>
              <td class="text-center text-dark" id="grandTotalPcs">0 Pcs</td>
              <td class="text-end text-success fs-6" id="grandTotalWages"><?= htmlspecialchars($settings['currency_symbol']) ?> 0.00</td>
              <td></td>
            </tr>
          </tfoot>
        </table>
      </div>
    </div>
  </div>

  <div class="d-flex justify-content-end gap-2">
    <a href="<?= base_url('modules/working_sheet/lot_list.php') ?>" class="btn btn-secondary btn-sm">Cancel</a>
    <button type="submit" class="btn btn-success btn-sm fw-bold px-4">
      <i class="fa-solid fa-check-double me-1"></i> Save Lot & Credit Worker Ledgers
    </button>
  </div>
</form>

<script>
$(document).ready(function() {
  function calculateRow($row) {
    const rate = parseFloat($row.find('.rate-input').val()) || 0;
    const done = parseInt($row.find('.done-input').val()) || 0;
    const total = rate * done;
    $row.find('.total-wage-display').val(total.toFixed(2));
    calculateGrandTotals();
  }

  function calculateGrandTotals() {
    let totalPcs = 0;
    let totalWages = 0;

    $('#lotWorkTable tbody tr').each(function() {
      const done = parseInt($(this).find('.done-input').val()) || 0;
      const rate = parseFloat($(this).find('.rate-input').val()) || 0;
      totalPcs += done;
      totalWages += (done * rate);
    });

    $('#grandTotalPcs').text(totalPcs + ' Pcs');
    $('#grandTotalWages').text('<?= htmlspecialchars($settings['currency_symbol']) ?> ' + totalWages.toFixed(2));
  }

  $(document).on('change', '.worker-select', function() {
    const defaultRate = $(this).find(':selected').data('rate');
    const $row = $(this).closest('tr');
    if (defaultRate && parseFloat(defaultRate) > 0) {
      $row.find('.rate-input').val(parseFloat(defaultRate).toFixed(2));
    }
    calculateRow($row);
  });

  $(document).on('input change', '.rate-input, .done-input', function() {
    calculateRow($(this).closest('tr'));
  });

  $('#addRowBtn').on('click', function() {
    const newRowHtml = `
      <tr class="lot-row">
        <td>
          <select name="worker_id[]" class="form-select form-select-sm worker-select" required>
            <option value="">-- Select Worker --</option>
            <?php foreach ($workers as $w): ?>
              <option value="<?= $w['id'] ?>" data-rate="<?= $w['default_rate'] ?>"><?= htmlspecialchars($w['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </td>
        <td>
          <select name="work_type[]" class="form-select form-select-sm">
            <?php foreach ($work_types_list as $wt): ?>
              <option value="<?= htmlspecialchars($wt) ?>"><?= htmlspecialchars($wt) ?></option>
            <?php endforeach; ?>
          </select>
        </td>
        <td>
          <input type="number" step="0.01" name="rate_per_piece[]" class="form-control form-control-sm text-end rate-input" placeholder="0.00" required>
        </td>
        <td>
          <input type="number" name="qty_target[]" class="form-control form-control-sm text-center target-input" placeholder="0">
        </td>
        <td>
          <input type="number" name="qty_done[]" class="form-control form-control-sm text-center done-input fw-bold" placeholder="0" required>
        </td>
        <td>
          <input type="text" class="form-control form-control-sm text-end fw-bold text-success total-wage-display" value="0.00" readonly>
        </td>
        <td class="text-center">
          <button type="button" class="btn btn-sm btn-outline-danger remove-row-btn"><i class="fa-solid fa-xmark"></i></button>
        </td>
      </tr>
    `;
    $('#lotWorkTable tbody').append(newRowHtml);
  });

  $(document).on('click', '.remove-row-btn', function() {
    if ($('#lotWorkTable tbody tr').length > 1) {
      $(this).closest('tr').remove();
      calculateGrandTotals();
    } else {
      alert('At least one worker row is required.');
    }
  });
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
