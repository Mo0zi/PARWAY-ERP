<?php
require_once __DIR__ . '/../../config/db.php';
require_login();

// Handle Update Invoice Discount ONLY
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_discount'])) {
    $invoice_id = (int)($_POST['invoice_id'] ?? 0);
    $new_discount = max(0, (float)($_POST['new_discount'] ?? 0));

    $stmt = $pdo->prepare("SELECT * FROM invoices WHERE id = ?");
    $stmt->execute([$invoice_id]);
    $inv = $stmt->fetch();

    if ($inv) {
        $total_amount = (float)$inv['total_amount'];
        $paid_amount = (float)$inv['paid_amount'];
        $new_grand_total = max(0, $total_amount - $new_discount);
        $new_balance = $new_grand_total - $paid_amount;

        // 1. Update Invoices table
        $stmt_u = $pdo->prepare("UPDATE invoices SET discount = ?, grand_total = ?, balance = ? WHERE id = ?");
        $stmt_u->execute([$new_discount, $new_grand_total, $new_balance, $invoice_id]);

        // 2. Update Customer Ledger Debit entry for this invoice
        $stmt_l = $pdo->prepare("UPDATE customer_ledger SET amount = ? WHERE invoice_id = ? AND type = 'debit'");
        $stmt_l->execute([$new_grand_total, $invoice_id]);

        // 3. Recalculate Customer Running Balance
        recalculate_customer_balance($inv['customer_id'], $pdo);

        // 4. Re-generate auto-saved HTML copy
        if (!empty($inv['saved_copy_path']) && file_exists(__DIR__ . '/../../' . $inv['saved_copy_path'])) {
            $invoice_no = $inv['invoice_no'];
            $customer_id = $inv['customer_id'];
            $date = $inv['date'];
            $discount = $new_discount;
            $grand_total = $new_grand_total;
            $balance = $new_balance;

            $stmt_items = $pdo->prepare("SELECT item_name, price, qty, total FROM invoice_items WHERE invoice_id = ?");
            $stmt_items->execute([$invoice_id]);
            $valid_items = $stmt_items->fetchAll();

            ob_start();
            include __DIR__ . '/saved_copy_template.php';
            $html_content = ob_get_clean();
            @file_put_contents(__DIR__ . '/../../' . $inv['saved_copy_path'], $html_content);
        }

        header("Location: " . base_url('modules/invoice/list.php?msg=discount_updated'));
        exit();
    }
}

// Handle Delete Invoice
if (isset($_GET['delete'])) {
    $del_id = (int)$_GET['delete'];
    $stmt = $pdo->prepare("SELECT customer_id FROM invoices WHERE id = ?");
    $stmt->execute([$del_id]);
    $inv = $stmt->fetch();

    if ($inv) {
        $pdo->prepare("DELETE FROM invoices WHERE id = ?")->execute([$del_id]);
        recalculate_customer_balance($inv['customer_id'], $pdo);
    }

    header("Location: " . base_url('modules/invoice/list.php?msg=deleted'));
    exit();
}

$page_title = 'Invoice History & Management';
require_once __DIR__ . '/../../includes/header.php';

$invoices = $pdo->query("SELECT i.*, c.name as customer_name, c.phone as customer_phone 
                        FROM invoices i 
                        LEFT JOIN customers c ON i.customer_id = c.id 
                        ORDER BY i.date DESC, i.id DESC")->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <h5 class="fw-bold m-0"><i class="fa-solid fa-receipt text-primary me-2"></i>Invoices Master</h5>
  <a href="<?= base_url('modules/invoice/create.php') ?>" class="btn btn-primary-custom btn-sm">
    <i class="fa-solid fa-plus me-1"></i> Create New Invoice
  </a>
</div>

<?php if (isset($_GET['msg']) && $_GET['msg'] === 'discount_updated'): ?>
  <div class="alert alert-success alert-dismissible fade show py-2">
    <i class="fa-solid fa-check-circle me-1"></i> Invoice discount updated successfully and customer ledger recalculated!
    <button type="button" class="btn-close py-2" data-bs-dismiss="alert"></button>
  </div>
<?php endif; ?>

<div class="card-custom">
  <div class="card-custom-body p-0">
    <div class="table-responsive">
      <table class="table table-custom mb-0">
        <thead>
          <tr>
            <th>Invoice No</th>
            <th>Date</th>
            <th>Customer Name</th>
            <th class="text-end">Discount</th>
            <th class="text-end">Grand Total</th>
            <th class="text-end text-success">Paid Amount</th>
            <th class="text-end">Remaining Balance</th>
            <th class="text-center">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($invoices)): ?>
            <tr><td colspan="8" class="text-center text-muted py-4">No invoices created yet.</td></tr>
          <?php else: ?>
            <?php foreach ($invoices as $inv): 
              $raw_phone = preg_replace('/[^0-9]/', '', $inv['customer_phone'] ?? '');
              if (strpos($raw_phone, '03') === 0 && strlen($raw_phone) === 11) {
                  $wa_phone = '92' . substr($raw_phone, 1);
              } else {
                  $wa_phone = $raw_phone;
              }
              $pdf_url = base_url('modules/invoice/print.php?id=' . $inv['id']);
              $wa_text = "📄 *SALES INVOICE #" . $inv['invoice_no'] . "*\nDate: " . date('d-M-Y', strtotime($inv['date'])) . "\nCustomer: " . $inv['customer_name'] . "\nGrand Total: " . format_rs($inv['grand_total']) . "\nPaid Amount: " . format_rs($inv['paid_amount']) . "\nRemaining Balance: " . format_rs($inv['balance']) . "\n\n📥 *View / Download PDF Invoice:*\n" . $pdf_url;
              $wa_url = "https://web.whatsapp.com/send?" . ($wa_phone ? "phone=" . $wa_phone . "&" : "") . "text=" . urlencode($wa_text);
              
              $is_pending = $inv['balance'] > 0.001;
            ?>
              <tr>
                <td><strong class="text-primary">#<?= htmlspecialchars($inv['invoice_no']) ?></strong></td>
                <td><?= date('d-M-Y', strtotime($inv['date'])) ?></td>
                <td><strong><?= htmlspecialchars($inv['customer_name'] ?: 'N/A') ?></strong></td>
                <td class="text-end text-warning fw-bold"><?= format_rs($inv['discount']) ?></td>
                <td class="text-end fw-bold"><?= format_rs($inv['grand_total']) ?></td>
                <td class="text-end text-success fw-medium"><?= format_rs($inv['paid_amount']) ?></td>
                <td class="text-end fw-bold <?= $is_pending ? 'text-danger' : 'text-success' ?>">
                  <?= format_rs($inv['balance']) ?>
                  <?php if (!$is_pending): ?>
                    <span class="badge bg-success ms-1">Paid</span>
                  <?php endif; ?>
                </td>
                <td class="text-center">
                  <a href="<?= $wa_url ?>" onclick="return openWhatsAppInvoice('<?= $wa_url ?>');" class="btn btn-sm btn-success py-1 px-2" title="Send WhatsApp PDF Link">
                    <i class="fa-brands fa-whatsapp"></i>
                  </a>

                  <button type="button" class="btn btn-sm btn-outline-warning py-1 px-2 edit-discount-btn" 
                          data-id="<?= $inv['id'] ?>" 
                          data-invoiceno="<?= htmlspecialchars($inv['invoice_no']) ?>" 
                          data-totalamount="<?= $inv['total_amount'] ?>" 
                          data-discount="<?= $inv['discount'] ?>" 
                          data-paid="<?= $inv['paid_amount'] ?>"
                          title="Edit Discount Only">
                    <i class="fa-solid fa-percent"></i> Edit Discount
                  </button>

                  <a href="<?= base_url('modules/invoice/print.php?id=' . $inv['id']) ?>" class="btn btn-sm btn-outline-primary py-1 px-2" title="View & Save as PDF">
                    <i class="fa-solid fa-file-pdf"></i> PDF
                  </a>

                  <a href="<?= base_url('modules/invoice/list.php?delete=' . $inv['id']) ?>" class="btn btn-sm btn-outline-danger py-1 px-2" onclick="return confirm('Deleting invoice will adjust customer balance. Continue?');" title="Delete">
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

<!-- Modal for Editing Discount ONLY -->
<div class="modal fade" id="editDiscountModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header bg-light">
        <h6 class="modal-header-title fw-bold m-0 text-dark">
          <i class="fa-solid fa-percent text-warning me-2"></i> Edit Invoice Discount (<span id="modalInvoiceNo"></span>)
        </h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form action="" method="POST">
        <div class="modal-body">
          <input type="hidden" name="update_discount" value="1">
          <input type="hidden" name="invoice_id" id="modalInvoiceId" value="">
          <input type="hidden" id="modalTotalAmount" value="0">
          <input type="hidden" id="modalPaidAmount" value="0">

          <div class="mb-3">
            <label class="form-label text-muted small uppercase fw-bold">Sub Total Amount</label>
            <input type="text" id="modalSubTotalDisplay" class="form-control fw-bold bg-light" readonly>
          </div>

          <div class="mb-3">
            <label class="form-label fw-bold">Discount Amount (<?= htmlspecialchars($settings['currency_symbol']) ?>) <span class="text-danger">*</span></label>
            <input type="number" step="0.01" min="0" name="new_discount" id="modalDiscountInput" class="form-control form-control-lg text-primary fw-bold" placeholder="0.00" required>
            <small class="text-muted">Enter positive discount amount</small>
          </div>

          <div class="p-3 bg-light rounded border">
            <div class="d-flex justify-content-between mb-1">
              <span class="text-muted small">New Grand Total:</span>
              <strong class="text-dark" id="modalNewGrandTotal">Rs. 0.00</strong>
            </div>
            <div class="d-flex justify-content-between">
              <span class="text-muted small" id="modalBalanceLabel">New Remaining Balance:</span>
              <strong class="fs-6 fw-bold text-danger" id="modalNewBalance">Rs. 0.00</strong>
            </div>
          </div>
        </div>

        <div class="modal-footer bg-light py-2">
          <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-sm btn-warning fw-bold px-4">
            <i class="fa-solid fa-check me-1"></i> Update Discount
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
$(document).ready(function() {
  const currencySymbol = '<?= htmlspecialchars($settings['currency_symbol']) ?>';

  function formatCurrency(num) {
    return currencySymbol + ' ' + parseFloat(num).toFixed(2);
  }

  $('.edit-discount-btn').on('click', function() {
    const id = $(this).data('id');
    const invoiceNo = $(this).data('invoiceno');
    const totalAmount = parseFloat($(this).data('totalamount')) || 0;
    const discount = parseFloat($(this).data('discount')) || 0;
    const paid = parseFloat($(this).data('paid')) || 0;

    $('#modalInvoiceId').val(id);
    $('#modalInvoiceNo').text('#' + invoiceNo);
    $('#modalTotalAmount').val(totalAmount);
    $('#modalPaidAmount').val(paid);
    $('#modalSubTotalDisplay').val(formatCurrency(totalAmount));
    $('#modalDiscountInput').val(discount);

    calculateModalTotals();

    const editModal = new bootstrap.Modal(document.getElementById('editDiscountModal'));
    editModal.show();
  });

  function calculateModalTotals() {
    const total = parseFloat($('#modalTotalAmount').val()) || 0;
    const paid = parseFloat($('#modalPaidAmount').val()) || 0;
    let disc = parseFloat($('#modalDiscountInput').val()) || 0;

    if (disc < 0) {
      disc = 0;
      $('#modalDiscountInput').val('0.00');
    }

    const grandTotal = Math.max(0, total - disc);
    const balance = grandTotal - paid;

    $('#modalNewGrandTotal').text(formatCurrency(grandTotal));

    const $balEl = $('#modalNewBalance');
    $balEl.text(formatCurrency(balance));

    if (balance > 0.001) {
      $balEl.removeClass('text-success text-dark').addClass('text-danger');
      $('#modalBalanceLabel').text('New Remaining Balance (Pending):');
    } else {
      $balEl.removeClass('text-danger text-dark').addClass('text-success');
      $('#modalBalanceLabel').text('New Remaining Balance (Paid):');
    }
  }

  $('#modalDiscountInput').on('input change', function() {
    calculateModalTotals();
  });
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
