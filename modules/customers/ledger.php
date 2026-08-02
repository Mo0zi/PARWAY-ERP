<?php
require_once __DIR__ . '/../../config/db.php';
require_login();

$customer_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($customer_id <= 0) {
    header("Location: " . base_url('modules/customers/list.php'));
    exit();
}

// Fetch Customer
$stmt = $pdo->prepare("SELECT * FROM customers WHERE id = ?");
$stmt->execute([$customer_id]);
$customer = $stmt->fetch();

if (!$customer) {
    die("Customer not found.");
}

// Handle Add Manual Ledger Transaction (Payment received / Adjustment)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_entry'])) {
    $date = trim($_POST['date'] ?? date('Y-m-d'));
    $type = trim($_POST['type'] ?? 'credit');
    $description = trim($_POST['description'] ?? '');
    $amount = (float)($_POST['amount'] ?? 0);

    if ($amount > 0 && !empty($description)) {
        $stmt = $pdo->prepare("INSERT INTO customer_ledger (customer_id, date, type, description, amount) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$customer_id, $date, $type, $description, $amount]);

        recalculate_customer_balance($customer_id, $pdo);
        header("Location: " . base_url('modules/customers/ledger.php?id=' . $customer_id . '&msg=added'));
        exit();
    }
}

// Recalculate balance
$current_balance = recalculate_customer_balance($customer_id, $pdo);

// Fetch Ledger Entries
$stmt = $pdo->prepare("SELECT * FROM customer_ledger WHERE customer_id = ? ORDER BY date ASC, id ASC");
$stmt->execute([$customer_id]);
$entries = $stmt->fetchAll();

$page_title = 'Customer Ledger Account';
require_once __DIR__ . '/../../includes/header.php';
?>

<!-- Printable Header -->
<div class="print-header text-center mb-4 d-none">
  <h3 class="fw-bold m-0"><?= htmlspecialchars($settings['company_name']) ?></h3>
  <p class="m-0"><?= htmlspecialchars($settings['address']) ?> | Phone: <?= htmlspecialchars($settings['phone']) ?></p>
  <h5 class="mt-2 fw-bold text-decoration-underline">CUSTOMER STATEMENT OF ACCOUNT</h5>
</div>

<div class="d-flex justify-content-between align-items-center mb-3 no-print">
  <div>
    <h5 class="fw-bold m-0"><?= htmlspecialchars($customer['name']) ?> - Statement</h5>
    <small class="text-muted"><i class="fa-solid fa-phone me-1"></i><?= htmlspecialchars($customer['phone'] ?: 'N/A') ?> | <i class="fa-solid fa-location-dot me-1"></i><?= htmlspecialchars($customer['address'] ?: 'N/A') ?></small>
  </div>
  <div class="d-flex gap-2">
    <button onclick="window.print()" class="btn btn-outline-dark btn-sm">
      <i class="fa-solid fa-print me-1"></i> Print Statement
    </button>
    <a href="<?= base_url('modules/customers/list.php') ?>" class="btn btn-outline-secondary btn-sm">
      <i class="fa-solid fa-arrow-left me-1"></i> Back
    </a>
  </div>
</div>

<!-- Balance Summary Card -->
<div class="row g-3 mb-4 no-print">
  <div class="col-md-4">
    <div class="card-custom p-3 text-center mb-0">
      <small class="text-muted uppercase fw-bold">Opening Balance</small>
      <h4 class="fw-bold text-dark mt-1 mb-0"><?= format_rs($customer['opening_balance']) ?></h4>
    </div>
  </div>
  <div class="col-md-8">
    <div class="card-custom p-3 text-center mb-0" style="border-left: 5px solid var(--primary-color);">
      <small class="text-muted uppercase fw-bold">Current Net Receivable Balance</small>
      <h3 class="fw-bold mt-1 mb-0 <?= $current_balance > 0 ? 'text-danger' : ($current_balance < 0 ? 'text-success' : 'text-dark') ?>">
        <?= format_rs($current_balance) ?>
      </h3>
    </div>
  </div>
</div>

<!-- Entry Posting Form (Credit Payment or Debit Adjustment) -->
<div class="card-custom mb-4 no-print">
  <div class="card-custom-header">
    <h6 class="card-custom-title"><i class="fa-solid fa-plus-circle text-success me-1"></i> Record Payment / Transaction Entry</h6>
  </div>
  <div class="card-custom-body">
    <form action="" method="POST" class="row g-3">
      <input type="hidden" name="add_entry" value="1">
      <div class="col-md-2">
        <label class="form-label small fw-bold">Date</label>
        <input type="date" name="date" class="form-control form-control-sm" value="<?= date('Y-m-d') ?>" required>
      </div>
      <div class="col-md-3">
        <label class="form-label small fw-bold">Entry Type</label>
        <select name="type" class="form-select form-select-sm" required>
          <option value="credit" selected>Credit (Payment Received - Cash/Bank)</option>
          <option value="debit">Debit (Goods Supplied / Debit Adjustment)</option>
        </select>
      </div>
      <div class="col-md-4">
        <label class="form-label small fw-bold">Description / Remarks</label>
        <input type="text" name="description" class="form-control form-control-sm" placeholder="e.g. Cash payment received by Meezan Bank" required>
      </div>
      <div class="col-md-2">
        <label class="form-label small fw-bold">Amount (Rs.)</label>
        <input type="number" step="0.01" name="amount" class="form-control form-control-sm" placeholder="0.00" required>
      </div>
      <div class="col-md-1 d-flex align-items-end">
        <button type="submit" class="btn btn-success btn-sm w-100 fw-bold py-1.5"><i class="fa-solid fa-check"></i> Post</button>
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
            <th>Description</th>
            <th class="text-end text-danger">Debit (Maal Diya)</th>
            <th class="text-end text-success">Credit (Paisy Aaye)</th>
            <th class="text-end">Balance (Rs.)</th>
          </tr>
        </thead>
        <tbody>
          <!-- Opening Balance Row -->
          <tr class="table-light">
            <td>-</td>
            <td><strong>Opening Balance</strong></td>
            <td class="text-end">-</td>
            <td class="text-end">-</td>
            <td class="text-end fw-bold"><?= format_rs($customer['opening_balance']) ?></td>
          </tr>

          <?php if (empty($entries)): ?>
            <tr><td colspan="5" class="text-center text-muted py-3">No ledger entries recorded yet.</td></tr>
          <?php else: ?>
            <?php foreach ($entries as $e): ?>
              <tr>
                <td><?= date('d-M-Y', strtotime($e['date'])) ?></td>
                <td>
                  <?= htmlspecialchars($e['description']) ?>
                  <?php if ($e['invoice_id']): ?>
                    <a href="<?= base_url('modules/invoice/print.php?id=' . $e['invoice_id']) ?>" class="no-print badge bg-info text-dark ms-2 text-decoration-none" target="_blank">
                      View Invoice
                    </a>
                  <?php endif; ?>
                </td>
                <td class="text-end text-danger fw-medium">
                  <?= $e['type'] === 'debit' ? format_rs($e['amount']) : '-' ?>
                </td>
                <td class="text-end text-success fw-medium">
                  <?= $e['type'] === 'credit' ? format_rs($e['amount']) : '-' ?>
                </td>
                <td class="text-end fw-bold <?= $e['balance'] > 0 ? 'text-danger' : ($e['balance'] < 0 ? 'text-success' : '') ?>">
                  <?= format_rs($e['balance']) ?>
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
