<?php
require_once __DIR__ . '/../../config/db.php';
require_login();

$entry_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$worker_id = isset($_GET['worker_id']) ? (int)$_GET['worker_id'] : 0;

if ($entry_id > 0) {
    $stmt = $pdo->prepare("SELECT wl.*, w.name as worker_name, w.phone as worker_phone, w.cnic as worker_cnic, w.default_rate, w.joining_date 
                          FROM worker_ledger wl 
                          INNER JOIN workers w ON wl.worker_id = w.id 
                          WHERE wl.id = ?");
    $stmt->execute([$entry_id]);
    $entry = $stmt->fetch();

    if ($entry) {
        $worker_id = $entry['worker_id'];
    }
}

if ($worker_id <= 0) {
    header("Location: " . base_url('modules/workers/list.php'));
    exit();
}

$stmt_worker = $pdo->prepare("SELECT * FROM workers WHERE id = ?");
$stmt_worker->execute([$worker_id]);
$worker = $stmt_worker->fetch();

if (!$worker) {
    die("Worker not found.");
}

// Net Balance
$current_balance = recalculate_worker_balance($worker_id, $pdo);

// Fetch recent work entries (Last 5 Lots)
$stmt_recent_work = $pdo->prepare("SELECT lw.*, l.lot_no, l.date as lot_date, l.description as lot_desc 
                                   FROM lot_work lw 
                                   INNER JOIN lots l ON lw.lot_id = l.id 
                                   WHERE lw.worker_id = ? 
                                   ORDER BY l.date DESC, lw.id DESC 
                                   LIMIT 5");
$stmt_recent_work->execute([$worker_id]);
$recent_work = $stmt_recent_work->fetchAll();

// Total Earnings vs Total Payments
$total_earned = (float)($pdo->query("SELECT SUM(amount) FROM worker_ledger WHERE worker_id = $worker_id AND type = 'credit'")->fetchColumn() ?? 0);
$total_paid = (float)($pdo->query("SELECT SUM(amount) FROM worker_ledger WHERE worker_id = $worker_id AND type = 'debit'")->fetchColumn() ?? 0);

// WhatsApp message text
$raw_phone = preg_replace('/[^0-9]/', '', $worker['phone'] ?? '');
if (strpos($raw_phone, '03') === 0 && strlen($raw_phone) === 11) {
    $wa_phone = '92' . substr($raw_phone, 1);
} else {
    $wa_phone = $raw_phone;
}

$pay_amount = isset($entry) ? format_rs($entry['amount']) : 'N/A';
$pay_date = isset($entry) ? date('d-M-Y', strtotime($entry['date'])) : date('d-M-Y');
$pay_desc = isset($entry) ? $entry['description'] : 'Salary / Advance Payment';

$wa_text = "🧾 *WORKER SALARY PAYMENT SLIP*\nFactory: " . ($settings['company_name'] ?? 'PARWAY ERP') . "\nWorker Name: " . $worker['name'] . "\nDate: " . $pay_date . "\nPayment Category: " . (isset($entry) ? ucfirst($entry['category']) : 'Salary') . "\nAmount Paid Today: " . $pay_amount . "\nRemaining Balance: " . format_rs($current_balance) . "\n\nThank you!";
$wa_url = "https://web.whatsapp.com/send?" . ($wa_phone ? "phone=" . $wa_phone . "&" : "") . "text=" . urlencode($wa_text);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Worker Salary Slip - <?= htmlspecialchars($worker['name']) ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
  <style>
    @import url('https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700&display=swap');
    body {
      font-family: 'Outfit', sans-serif;
      background: #f1f5f9;
      color: #0f172a;
      padding: 20px;
    }
    .slip-card {
      max-width: 750px;
      margin: 0 auto;
      background: #fff;
      border-radius: 12px;
      padding: 30px;
      border: 1px solid #cbd5e1;
      box-shadow: 0 10px 25px rgba(0,0,0,0.08);
    }
    .slip-header {
      border-bottom: 2px solid #1e3c72;
      padding-bottom: 15px;
      margin-bottom: 20px;
    }
    .watermark {
      position: absolute;
      top: 40%;
      left: 50%;
      transform: translate(-50%, -50%) rotate(-25deg);
      font-size: 5rem;
      font-weight: 900;
      color: rgba(30, 60, 114, 0.04);
      pointer-events: none;
      user-select: none;
    }
    .table-sm th {
      background: #f8fafc;
      font-size: 11px;
      text-transform: uppercase;
    }
    @media print {
      body { background: #fff !important; padding: 0 !important; }
      .slip-card { border: none !important; box-shadow: none !important; max-width: 100% !important; padding: 10px !important; }
      .no-print { display: none !important; }
    }
  </style>
</head>
<body>

<div class="container no-print text-center mb-3">
  <button onclick="window.print();" class="btn btn-primary fw-bold px-4 shadow">
    <i class="fa-solid fa-print me-1"></i> Print Salary Slip / Save PDF
  </button>
  <a href="<?= $wa_url ?>" onclick="return openWhatsAppInvoice('<?= $wa_url ?>');" class="btn btn-success fw-bold ms-2 shadow">
    <i class="fa-brands fa-whatsapp me-1"></i> Send Slip on WhatsApp
  </a>
  <a href="<?= base_url('modules/workers/ledger.php?id=' . $worker_id) ?>" class="btn btn-outline-secondary ms-2">
    <i class="fa-solid fa-arrow-left me-1"></i> Back to Ledger
  </a>
</div>

<div class="slip-card position-relative">
  <div class="watermark">SALARY SLIP</div>

  <!-- Header -->
  <div class="slip-header d-flex justify-content-between align-items-center">
    <div class="d-flex align-items-center gap-3">
      <?php if (!empty($settings['logo']) && file_exists(__DIR__ . '/../../' . $settings['logo'])): ?>
        <img src="<?= base_url($settings['logo']) ?>" style="max-height: 50px;">
      <?php endif; ?>
      <div>
        <h4 class="fw-bold m-0 text-primary"><?= htmlspecialchars($settings['company_name'] ?? 'PARWAY ERP') ?></h4>
        <small class="text-muted"><?= htmlspecialchars($settings['company_address'] ?? 'Sialkot, Punjab') ?> | Phone: <?= htmlspecialchars($settings['company_phone'] ?? '-') ?></small>
      </div>
    </div>
    <div class="text-end">
      <span class="badge bg-primary fs-6 px-3 py-2">SALARY / KHARCHA SLIP</span>
      <div class="text-muted small mt-1">Voucher #: <strong>SLIP-<?= isset($entry) ? $entry['id'] : rand(100, 999) ?></strong></div>
      <div class="text-muted small">Date: <strong><?= $pay_date ?></strong></div>
    </div>
  </div>

  <!-- Worker Profile Details -->
  <div class="row g-3 mb-4 p-3 bg-light rounded border">
    <div class="col-md-6">
      <small class="text-muted uppercase fw-bold d-block">Worker Name</small>
      <h5 class="fw-bold text-dark m-0"><?= htmlspecialchars($worker['name']) ?></h5>
      <small class="text-muted">CNIC: <?= htmlspecialchars($worker['cnic'] ?: 'N/A') ?></small>
    </div>
    <div class="col-md-3">
      <small class="text-muted uppercase fw-bold d-block">Phone Number</small>
      <strong class="text-dark"><?= htmlspecialchars($worker['phone'] ?: 'N/A') ?></strong>
    </div>
    <div class="col-md-3 text-md-end">
      <small class="text-muted uppercase fw-bold d-block">Default Rate / Pc</small>
      <strong class="text-primary fs-6"><?= format_rs($worker['default_rate']) ?></strong>
    </div>
  </div>

  <!-- Current Payment Highlight -->
  <?php if (isset($entry)): ?>
    <div class="p-3 mb-4 bg-success bg-opacity-10 border border-success rounded text-center">
      <small class="text-success uppercase fw-bold d-block">Payment Received Today (Category: <?= ucfirst($entry['category']) ?>)</small>
      <h2 class="fw-bold text-success my-1"><?= format_rs($entry['amount']) ?></h2>
      <small class="text-muted">Details: <?= htmlspecialchars($entry['description']) ?></small>
    </div>
  <?php endif; ?>

  <!-- Summary Breakdown -->
  <div class="row g-3 mb-4 text-center">
    <div class="col-4">
      <div class="p-2 border rounded bg-white">
        <small class="text-muted uppercase fw-bold d-block">Total Work Earned</small>
        <strong class="text-dark fs-6"><?= format_rs($total_earned) ?></strong>
      </div>
    </div>
    <div class="col-4">
      <div class="p-2 border rounded bg-white">
        <small class="text-muted uppercase fw-bold d-block">Total Cash Paid</small>
        <strong class="text-danger fs-6"><?= format_rs($total_paid) ?></strong>
      </div>
    </div>
    <div class="col-4">
      <div class="p-2 border rounded bg-white">
        <small class="text-muted uppercase fw-bold d-block">Remaining Net Balance</small>
        <strong class="fs-6 <?= $current_balance > 0 ? 'text-success' : 'text-danger' ?>"><?= format_rs($current_balance) ?></strong>
      </div>
    </div>
  </div>

  <!-- Recent Production Work Breakdown -->
  <h6 class="fw-bold text-dark border-bottom pb-2 mb-3">
    <i class="fa-solid fa-layer-group text-primary me-1"></i> Recent Production Work Recorded (Last Lots)
  </h6>
  <div class="table-responsive mb-4">
    <table class="table table-bordered table-sm mb-0">
      <thead>
        <tr>
          <th>Lot No</th>
          <th>Work Date</th>
          <th>Work Type</th>
          <th class="text-center">Qty Done</th>
          <th class="text-end">Rate / Pc</th>
          <th class="text-end">Total Wage</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($recent_work)): ?>
          <tr><td colspan="6" class="text-center text-muted py-2">No recent production lot entries.</td></tr>
        <?php else: ?>
          <?php foreach ($recent_work as $rw): ?>
            <tr>
              <td><strong class="text-primary">#<?= htmlspecialchars($rw['lot_no']) ?></strong></td>
              <td><?= date('d-M-Y', strtotime($rw['lot_date'])) ?></td>
              <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($rw['work_type']) ?></span></td>
              <td class="text-center fw-bold"><?= number_format($rw['qty_done']) ?> Pcs</td>
              <td class="text-end"><?= format_rs($rw['rate_per_piece']) ?></td>
              <td class="text-end fw-bold text-success"><?= format_rs($rw['total_amount']) ?></td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <!-- Signature Block -->
  <div class="d-flex justify-content-between align-items-end mt-5 pt-4 border-top">
    <div class="text-center" style="width: 200px;">
      <div class="border-top border-dark pt-1 fw-bold">Worker Signature / Thumb</div>
    </div>
    <div class="text-center" style="width: 200px;">
      <div class="border-top border-dark pt-1 fw-bold">Factory Cashier / Manager</div>
    </div>
  </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script>
  let waTabRef = null;
  function openWhatsAppInvoice(waUrl) {
    if (waTabRef && !waTabRef.closed) {
      waTabRef.location.href = waUrl;
      waTabRef.focus();
    } else {
      waTabRef = window.open(waUrl, 'parway_whatsapp_tab');
      if (waTabRef) {
        waTabRef.focus();
      }
    }
    return false;
  }
</script>

</body>
</html>
