<?php
$page_title = 'Print & PDF Invoice';
require_once __DIR__ . '/../../includes/header.php';

$invoice_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($invoice_id <= 0) {
    die("Invalid Invoice ID.");
}

$stmt = $pdo->prepare("SELECT i.*, c.name as customer_name, c.phone as customer_phone, c.address as customer_address 
                       FROM invoices i 
                       INNER JOIN customers c ON i.customer_id = c.id 
                       WHERE i.id = ?");
$stmt->execute([$invoice_id]);
$invoice = $stmt->fetch();

if (!$invoice) {
    die("Invoice not found.");
}

$stmt_items = $pdo->prepare("SELECT * FROM invoice_items WHERE invoice_id = ?");
$stmt_items->execute([$invoice_id]);
$items = $stmt_items->fetchAll();

// Format WhatsApp Phone & Message with Direct PDF Invoice Link
$raw_phone = preg_replace('/[^0-9]/', '', $invoice['customer_phone'] ?? '');
if (strpos($raw_phone, '03') === 0 && strlen($raw_phone) === 11) {
    $wa_phone = '92' . substr($raw_phone, 1);
} else {
    $wa_phone = $raw_phone;
}

$pdf_invoice_url = base_url('modules/invoice/print.php?id=' . $invoice_id);

$wa_msg = "📄 *SALES INVOICE #" . $invoice['invoice_no'] . "*\n";
$wa_msg .= "---------------------------------\n";
$wa_msg .= "Date: " . date('d-M-Y', strtotime($invoice['date'])) . "\n";
$wa_msg .= "Customer: " . $invoice['customer_name'] . "\n\n";

$wa_msg .= "*Invoice Summary:*\n";
$wa_msg .= "• Grand Total: Rs. " . number_format($invoice['grand_total'], 2) . "\n";
$wa_msg .= "• Paid Amount: Rs. " . number_format($invoice['paid_amount'], 2) . "\n";
$wa_msg .= "• Remaining Balance: Rs. " . number_format($invoice['balance'], 2) . "\n\n";

$wa_msg .= "📥 *Click to View / Download PDF Invoice:*\n";
$wa_msg .= $pdf_invoice_url . "\n";
$wa_msg .= "---------------------------------\n";
$wa_msg .= "Thank you for your business!";

// Direct WhatsApp Web link
$wa_link = "https://web.whatsapp.com/send?" . ($wa_phone ? "phone=" . $wa_phone . "&" : "") . "text=" . urlencode($wa_msg);
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
  <a href="<?= base_url('modules/invoice/list.php') ?>" class="btn btn-outline-secondary btn-sm">
    <i class="fa-solid fa-arrow-left me-1"></i> Back to Invoices
  </a>
  <div class="d-flex gap-2">
    <button type="button" class="btn btn-outline-success btn-sm fw-bold" onclick="copyWhatsAppText(this)" data-watext="<?= htmlspecialchars($wa_msg) ?>">
      <i class="fa-solid fa-copy me-1"></i> Copy PDF Link & Summary
    </button>

    <a href="<?= $wa_link ?>" onclick="return openWhatsAppInvoice('<?= $wa_link ?>');" class="btn btn-success btn-sm fw-bold">
      <i class="fa-brands fa-whatsapp me-1 fs-6"></i> Send PDF Link on WhatsApp
    </a>

    <button onclick="window.print()" class="btn btn-primary-custom btn-sm fw-bold">
      <i class="fa-solid fa-file-pdf me-1"></i> Save as PDF / Print
    </button>
  </div>
</div>

<div class="card-custom p-4" id="pdfInvoiceContainer">
  <!-- Invoice Header with Official Logo -->
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
      <h3 class="fw-bold text-dark mb-1">SALES INVOICE</h3>
      <div class="fw-bold text-danger fs-5">#<?= htmlspecialchars($invoice['invoice_no']) ?></div>
      <div class="text-muted small">Date: <?= date('d-M-Y', strtotime($invoice['date'])) ?></div>
    </div>
  </div>

  <!-- Customer & Billing Details -->
  <div class="row mb-4">
    <div class="col-6">
      <h6 class="fw-bold text-uppercase text-secondary small">Billed To:</h6>
      <h5 class="fw-bold text-dark mb-1"><?= htmlspecialchars($invoice['customer_name']) ?></h5>
      <p class="text-muted small mb-1"><i class="fa-solid fa-phone me-1"></i><?= htmlspecialchars($invoice['customer_phone'] ?: 'N/A') ?></p>
      <p class="text-muted small mb-0"><i class="fa-solid fa-location-dot me-1"></i><?= htmlspecialchars($invoice['customer_address'] ?: 'N/A') ?></p>
    </div>
  </div>

  <!-- Items Table -->
  <div class="table-responsive mb-4">
    <table class="table table-bordered table-custom mb-0">
      <thead class="table-light">
        <tr>
          <th style="width: 5%;">#</th>
          <th>Item Description</th>
          <th class="text-end" style="width: 18%;">Unit Price (Rs.)</th>
          <th class="text-center" style="width: 12%;">Qty</th>
          <th class="text-end" style="width: 20%;">Total (Rs.)</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($items as $idx => $it): ?>
          <tr>
            <td><?= $idx + 1 ?></td>
            <td><strong><?= htmlspecialchars($it['item_name']) ?></strong></td>
            <td class="text-end"><?= format_rs($it['price']) ?></td>
            <td class="text-center"><?= $it['qty'] ?></td>
            <td class="text-end fw-bold"><?= format_rs($it['total']) ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <!-- Summary Calculation -->
  <div class="row justify-content-end mb-4">
    <div class="col-md-5">
      <table class="table table-sm table-borderless">
        <tr>
          <td class="fw-medium text-secondary">Sub Total:</td>
          <td class="text-end fw-bold"><?= format_rs($invoice['total_amount']) ?></td>
        </tr>
        <?php if ($invoice['discount'] > 0): ?>
          <tr>
            <td class="fw-medium text-secondary">Discount:</td>
            <td class="text-end text-danger">- <?= format_rs($invoice['discount']) ?></td>
          </tr>
        <?php endif; ?>
        <tr class="border-top border-bottom">
          <td class="fw-bold fs-5 text-dark">Grand Total:</td>
          <td class="text-end fw-bold fs-5 text-primary"><?= format_rs($invoice['grand_total']) ?></td>
        </tr>
        <tr>
          <td class="fw-medium text-secondary">Paid Amount:</td>
          <td class="text-end text-success fw-bold"><?= format_rs($invoice['paid_amount']) ?></td>
        </tr>
        <tr>
          <td class="fw-bold text-secondary">Remaining Balance:</td>
          <td class="text-end fw-bold text-danger fs-6"><?= format_rs($invoice['balance']) ?></td>
        </tr>
      </table>
    </div>
  </div>

  <!-- Signature Footer -->
  <div class="row mt-5 pt-4 text-center">
    <div class="col-6">
      <div class="border-top pt-2 text-muted small mx-auto" style="width: 60%;">Customer Signature</div>
    </div>
    <div class="col-6">
      <div class="border-top pt-2 text-muted small mx-auto" style="width: 60%;">Authorized Signature</div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
