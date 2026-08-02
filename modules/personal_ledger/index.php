<?php
$page_title = 'Personal Ledger Account';
require_once __DIR__ . '/../../includes/header.php';

// Handle New Transaction Entry
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_entry'])) {
    $date = trim($_POST['date'] ?? date('Y-m-d'));
    $type = trim($_POST['type'] ?? 'debit');
    $category = trim($_POST['category'] ?? 'General Expense');
    $description = trim($_POST['description'] ?? '');
    $amount = (float)($_POST['amount'] ?? 0);

    if ($amount > 0 && !empty($description)) {
        $stmt = $pdo->prepare("INSERT INTO personal_ledger (date, type, category, description, amount) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$date, $type, $category, $description, $amount]);

        recalculate_personal_balance($pdo);
        header("Location: " . base_url('modules/personal_ledger/index.php?msg=added'));
        exit();
    }
}

// Handle Delete Entry
if (isset($_GET['delete'])) {
    $del_id = (int)$_GET['delete'];
    $pdo->prepare("DELETE FROM personal_ledger WHERE id = ?")->execute([$del_id]);
    recalculate_personal_balance($pdo);
    header("Location: " . base_url('modules/personal_ledger/index.php?msg=deleted'));
    exit();
}

$current_balance = recalculate_personal_balance($pdo);

// Fetch Entries
$entries = $pdo->query("SELECT * FROM personal_ledger ORDER BY date DESC, id DESC")->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <h5 class="fw-bold m-0"><i class="fa-solid fa-wallet text-primary me-2"></i>Personal Ledger (Private Account)</h5>
</div>

<!-- Net Personal Cash Balance Widget -->
<div class="card-custom p-3 mb-4 text-center" style="border-left: 5px solid var(--primary-color);">
  <small class="text-muted uppercase fw-bold">Personal Net Balance / Savings</small>
  <h3 class="fw-bold mt-1 mb-0 <?= $current_balance >= 0 ? 'text-success' : 'text-danger' ?>">
    <?= format_rs($current_balance) ?>
  </h3>
</div>

<!-- Post Transaction Form -->
<div class="card-custom mb-4">
  <div class="card-custom-header">
    <h6 class="card-custom-title"><i class="fa-solid fa-plus-circle text-primary"></i> Post Personal Expense / Income</h6>
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
          <option value="debit" selected>Debit (Expense / Kharcha)</option>
          <option value="credit">Credit (Income / Personal Addition)</option>
        </select>
      </div>

      <div class="col-md-3">
        <label class="form-label small fw-bold">Category</label>
        <select name="category" class="form-select form-select-sm" required>
          <option value="Household Expense">Household Expense</option>
          <option value="Utility Bills">Utility Bills</option>
          <option value="Personal Loan / Borrowing">Personal Loan / Borrowing</option>
          <option value="Vehicle / Fuel">Vehicle / Fuel</option>
          <option value="Other Personal">Other Personal</option>
        </select>
      </div>

      <div class="col-md-3">
        <label class="form-label small fw-bold">Description / Remarks</label>
        <input type="text" name="description" class="form-control form-control-sm" placeholder="e.g. Electricity bill payment" required>
      </div>

      <div class="col-md-2">
        <label class="form-label small fw-bold">Amount (Rs.)</label>
        <input type="number" step="0.01" name="amount" class="form-control form-control-sm" placeholder="0.00" required>
      </div>

      <div class="col-12 text-end">
        <button type="submit" class="btn btn-primary-custom btn-sm px-4 fw-bold">
          <i class="fa-solid fa-check me-1"></i> Save Entry
        </button>
      </div>
    </form>
  </div>
</div>

<!-- Personal Ledger Table -->
<div class="card-custom">
  <div class="card-custom-body p-0">
    <div class="table-responsive">
      <table class="table table-custom mb-0">
        <thead>
          <tr>
            <th>Date</th>
            <th>Category</th>
            <th>Description</th>
            <th class="text-end text-danger">Expense (Debit)</th>
            <th class="text-end text-success">Income (Credit)</th>
            <th class="text-end">Balance (Rs.)</th>
            <th class="text-center">Action</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($entries)): ?>
            <tr><td colspan="7" class="text-center text-muted py-4">No personal transactions logged yet.</td></tr>
          <?php else: ?>
            <?php foreach ($entries as $e): ?>
              <tr>
                <td><?= date('d-M-Y', strtotime($e['date'])) ?></td>
                <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($e['category']) ?></span></td>
                <td><?= htmlspecialchars($e['description']) ?></td>
                <td class="text-end text-danger fw-medium">
                  <?= $e['type'] === 'debit' ? format_rs($e['amount']) : '-' ?>
                </td>
                <td class="text-end text-success fw-medium">
                  <?= $e['type'] === 'credit' ? format_rs($e['amount']) : '-' ?>
                </td>
                <td class="text-end fw-bold <?= $e['balance'] >= 0 ? 'text-success' : 'text-danger' ?>">
                  <?= format_rs($e['balance']) ?>
                </td>
                <td class="text-center">
                  <a href="<?= base_url('modules/personal_ledger/index.php?delete=' . $e['id']) ?>" class="btn btn-sm btn-outline-danger py-1 px-2" onclick="return confirm('Delete entry?');">
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

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
