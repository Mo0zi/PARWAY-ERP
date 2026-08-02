<?php
require_once __DIR__ . '/../../config/db.php';
require_login();

$worker_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($worker_id <= 0) {
    header("Location: " . base_url('modules/workers/list.php'));
    exit();
}

$stmt = $pdo->prepare("SELECT * FROM workers WHERE id = ?");
$stmt->execute([$worker_id]);
$worker = $stmt->fetch();

if (!$worker) {
    die("Worker not found.");
}

// Handle Add Payment / Entry to Worker Ledger
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_entry'])) {
    $date = trim($_POST['date'] ?? date('Y-m-d'));
    $type = trim($_POST['type'] ?? 'debit');
    $category = trim($_POST['category'] ?? 'salary');
    $description = trim($_POST['description'] ?? '');
    $amount = (float)($_POST['amount'] ?? 0);

    if ($amount > 0 && !empty($description)) {
        $stmt = $pdo->prepare("INSERT INTO worker_ledger (worker_id, date, type, category, description, amount) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$worker_id, $date, $type, $category, $description, $amount]);
        $entry_id = $pdo->lastInsertId();

        recalculate_worker_balance($worker_id, $pdo);

        // Auto-redirect to Salary Slip Voucher view for printing/WhatsApp
        header("Location: " . base_url('modules/workers/salary_slip.php?id=' . $entry_id));
        exit();
    }
}

// Recalculate Worker Running Balance
$current_balance = recalculate_worker_balance($worker_id, $pdo);

// Fetch Ledger Entries
$stmt = $pdo->prepare("SELECT * FROM worker_ledger WHERE worker_id = ? ORDER BY date DESC, id DESC");
$stmt->execute([$worker_id]);
$entries = $stmt->fetchAll();

$page_title = $worker['name'] . ' - Wage Ledger Account';
require_once __DIR__ . '/../../includes/header.php';
?>

<!-- Action Header -->
<div class="d-flex justify-content-between align-items-center mb-3 no-print">
  <div>
    <h5 class="fw-bold m-0"><i class="fa-solid fa-user-gear text-primary me-2"></i><?= htmlspecialchars($worker['name']) ?> - Wage & Salary Ledger</h5>
    <small class="text-muted"><i class="fa-solid fa-phone me-1"></i><?= htmlspecialchars($worker['phone'] ?: 'N/A') ?> | CNIC: <?= htmlspecialchars($worker['cnic'] ?: 'N/A') ?></small>
  </div>
  <div class="d-flex gap-2">
    <a href="<?= base_url('modules/workers/salary_slip.php?worker_id=' . $worker_id) ?>" target="_blank" class="btn btn-outline-primary btn-sm fw-bold">
      <i class="fa-solid fa-file-invoice me-1"></i> Print Overall Slip
    </a>
    <button onclick="window.print()" class="btn btn-outline-dark btn-sm">
      <i class="fa-solid fa-print me-1"></i> Print Statement
    </button>
    <a href="<?= base_url('modules/workers/list.php') ?>" class="btn btn-outline-secondary btn-sm">
      <i class="fa-solid fa-arrow-left me-1"></i> Back
    </a>
  </div>
</div>

<!-- Net Balance Summary Card -->
<div class="card-custom p-3 mb-4 no-print text-center" style="border-left: 5px solid var(--primary-color);">
  <small class="text-muted uppercase fw-bold">Current Net Payable Balance to Worker</small>
  <h3 class="fw-bold mt-1 mb-0 <?= $current_balance > 0 ? 'text-info' : ($current_balance < 0 ? 'text-danger' : 'text-dark') ?>">
    <?= format_rs($current_balance) ?>
  </h3>
  <small class="text-muted">
    <?= $current_balance > 0 ? '(Company Dena Hai - Worker Earned Wages)' : ($current_balance < 0 ? '(Worker se Lena Hai - Advance Cash Given)' : 'Nill Balance (Fully Paid)') ?>
  </small>
</div>

<!-- Add Transaction Form (Payment / Advance Given) -->
<div class="card-custom mb-4 no-print">
  <div class="card-custom-header">
    <h6 class="card-custom-title"><i class="fa-solid fa-money-bill-wave text-success"></i> Post Worker Payment / Advance & Generate Salary Slip</h6>
  </div>
  <div class="card-custom-body">
    <form action="" method="POST" class="row g-3">
      <input type="hidden" name="add_entry" value="1">
      
      <div class="col-md-2">
        <label class="form-label small fw-bold">Date</label>
        <input type="date" name="date" class="form-control form-control-sm" value="<?= date('Y-m-d') ?>" required>
      </div>

      <div class="col-md-2">
        <label class="form-label small fw-bold">Type</label>
        <select name="type" class="form-select form-select-sm" required>
          <option value="debit" selected>Debit (Cash Paid / Payment)</option>
          <option value="credit">Credit (Work Bonus / Adjustment)</option>
        </select>
      </div>

      <div class="col-md-2">
        <label class="form-label small fw-bold">Category</label>
        <select name="category" class="form-select form-select-sm" required>
          <option value="salary">Salary Payment</option>
          <option value="advance">Advance Payment</option>
          <option value="extra">Extra / Bonus</option>
        </select>
      </div>

      <div class="col-md-4">
        <label class="form-label small fw-bold">Description / Details</label>
        <input type="text" name="description" class="form-control form-control-sm" placeholder="e.g. Weekly salary cash payment" required>
      </div>

      <div class="col-md-2">
        <label class="form-label small fw-bold">Amount (<?= htmlspecialchars($settings['currency_symbol']) ?>) <span class="text-danger">*</span></label>
        <input type="number" step="0.01" min="0" name="amount" class="form-control form-control-sm text-end fw-bold" placeholder="0.00" required>
      </div>

      <div class="col-12 text-end">
        <button type="submit" class="btn btn-success btn-sm px-4 fw-bold shadow-sm">
          <i class="fa-solid fa-file-invoice me-1"></i> Save Entry & Print Salary Slip
        </button>
      </div>
    </form>
  </div>
</div>

<!-- Ledger Table -->
<div class="card-custom">
  <div class="card-custom-body p-0">
    <div class="table-responsive">
      <table class="table table-custom mb-0">
        <thead>
          <tr>
            <th>Date</th>
            <th>Category</th>
            <th>Description</th>
            <th class="text-end text-danger">Debit (Cash Diya)</th>
            <th class="text-end text-success">Credit (Kam Diya / Lot Work)</th>
            <th class="text-end">Balance</th>
            <th class="text-center no-print">Action</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($entries)): ?>
            <tr><td colspan="7" class="text-center text-muted py-4">No ledger records found for this worker.</td></tr>
          <?php else: ?>
            <?php foreach ($entries as $e): ?>
              <tr>
                <td><?= date('d-M-Y', strtotime($e['date'])) ?></td>
                <td>
                  <span class="badge bg-light text-dark border text-capitalize"><?= htmlspecialchars($e['category']) ?></span>
                </td>
                <td><?= htmlspecialchars($e['description']) ?></td>
                <td class="text-end text-danger fw-medium">
                  <?= $e['type'] === 'debit' ? format_rs($e['amount']) : '-' ?>
                </td>
                <td class="text-end text-success fw-medium">
                  <?= $e['type'] === 'credit' ? format_rs($e['amount']) : '-' ?>
                </td>
                <td class="text-end fw-bold <?= $e['balance'] > 0 ? 'text-info' : ($e['balance'] < 0 ? 'text-danger' : 'text-dark') ?>">
                  <?= format_rs($e['balance']) ?>
                </td>
                <td class="text-center no-print">
                  <?php if ($e['type'] === 'debit'): ?>
                    <a href="<?= base_url('modules/workers/salary_slip.php?id=' . $e['id']) ?>" target="_blank" class="btn btn-sm btn-outline-primary py-1 px-2" title="Print Salary Slip Voucher">
                      <i class="fa-solid fa-receipt me-1"></i> Slip
                    </a>
                  <?php else: ?>
                    <span class="text-muted small">-</span>
                  <?php endif; ?>
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
