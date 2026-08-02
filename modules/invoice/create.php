<?php
require_once __DIR__ . '/../../config/db.php';
require_login();

// Auto-generate Invoice Number
$stmt = $pdo->query("SELECT MAX(id) as max_id FROM invoices");
$next_id = ((int)($stmt->fetch()['max_id'] ?? 0)) + 1001;
$auto_invoice_no = 'INV-' . $next_id;

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $invoice_no = trim($_POST['invoice_no'] ?? $auto_invoice_no);
    $customer_id = (int)($_POST['customer_id'] ?? 0);
    $customer_name_input = trim($_POST['customer_name_input'] ?? '');
    $new_customer_phone = trim($_POST['new_customer_phone'] ?? '');
    $customer_address = trim($_POST['customer_address'] ?? 'Sialkot, Punjab');
    $date = trim($_POST['date'] ?? date('Y-m-d'));
    $discount = max(0, (float)($_POST['discount'] ?? 0));
    $paid_amount = max(0, (float)($_POST['paid_amount'] ?? 0));

    if (empty($customer_address)) {
        $customer_address = 'Sialkot, Punjab';
    }

    // Direct Add Customer if new name provided without selecting dropdown
    if ($customer_id <= 0 && !empty($customer_name_input)) {
        $stmt_cust = $pdo->prepare("INSERT INTO customers (name, phone, address, opening_balance) VALUES (?, ?, ?, 0.00)");
        $stmt_cust->execute([$customer_name_input, $new_customer_phone, $customer_address]);
        $customer_id = $pdo->lastInsertId();
    } else if ($customer_id > 0) {
        // Update existing customer address if provided
        $pdo->prepare("UPDATE customers SET address = ? WHERE id = ?")->execute([$customer_address, $customer_id]);
    }

    $item_names = $_POST['item_name'] ?? [];
    $item_ids = $_POST['item_id'] ?? [];
    $qtys = $_POST['qty'] ?? [];
    $prices = $_POST['price'] ?? [];

    if ($customer_id > 0 && !empty($item_names)) {
        $total_amount = 0.00;
        $valid_items = [];

        for ($i = 0; $i < count($item_names); $i++) {
            $name = trim($item_names[$i] ?? '');
            $qty = max(1, (int)($qtys[$i] ?? 1));
            $price = max(0, (float)($prices[$i] ?? 0));
            $item_id = !empty($item_ids[$i]) ? (int)$item_ids[$i] : null;

            if (!empty($name) && $qty > 0) {
                $row_total = $qty * $price;
                $total_amount += $row_total;
                $valid_items[] = [
                    'item_id' => $item_id,
                    'item_name' => $name,
                    'qty' => $qty,
                    'price' => $price,
                    'total' => $row_total
                ];
            }
        }

        if (!empty($valid_items)) {
            $grand_total = max(0, $total_amount - $discount);
            $balance = $grand_total - $paid_amount;

            // Insert Invoice
            $stmt = $pdo->prepare("INSERT INTO invoices (invoice_no, customer_id, date, total_amount, discount, grand_total, paid_amount, balance) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$invoice_no, $customer_id, $date, $total_amount, $discount, $grand_total, $paid_amount, $balance]);
            $invoice_id = $pdo->lastInsertId();

            // Insert Invoice Items
            $stmt_item = $pdo->prepare("INSERT INTO invoice_items (invoice_id, item_id, item_name, qty, price, total) VALUES (?, ?, ?, ?, ?, ?)");
            foreach ($valid_items as $vi) {
                $stmt_item->execute([$invoice_id, $vi['item_id'], $vi['item_name'], $vi['qty'], $vi['price'], $vi['total']]);
            }

            // Customer Ledger Entries
            $stmt_leg = $pdo->prepare("INSERT INTO customer_ledger (customer_id, date, type, description, amount, invoice_id) VALUES (?, ?, 'debit', ?, ?, ?)");
            $stmt_leg->execute([$customer_id, $date, "Sales Invoice #$invoice_no", $grand_total, $invoice_id]);

            if ($paid_amount > 0) {
                $stmt_leg2 = $pdo->prepare("INSERT INTO customer_ledger (customer_id, date, type, description, amount, invoice_id) VALUES (?, ?, 'credit', ?, ?, ?)");
                $stmt_leg2->execute([$customer_id, $date, "Payment Received for Invoice #$invoice_no", $paid_amount, $invoice_id]);
            }

            recalculate_customer_balance($customer_id, $pdo);

            // Auto-Save HTML Copy
            $folder = __DIR__ . '/../../invoices/saved_copies/' . date('Y-m-d');
            if (!file_exists($folder)) {
                @mkdir($folder, 0777, true);
            }
            $saved_copy_path = 'invoices/saved_copies/' . date('Y-m-d') . '/' . $invoice_no . '.html';

            ob_start();
            include __DIR__ . '/saved_copy_template.php';
            $html_content = ob_get_clean();
            @file_put_contents(__DIR__ . '/../../' . $saved_copy_path, $html_content);

            $pdo->prepare("UPDATE invoices SET saved_copy_path = ? WHERE id = ?")->execute([$saved_copy_path, $invoice_id]);

            header("Location: " . base_url('modules/invoice/print.php?id=' . $invoice_id));
            exit();
        } else {
            $error = 'Please add at least one valid item to the invoice.';
        }
    } else {
        $error = 'Please select or type a customer name and add items.';
    }
}

$page_title = 'Create New Sales Invoice';
require_once __DIR__ . '/../../includes/header.php';

// Fetch Customers
$customers = $pdo->query("SELECT id, name, phone, address FROM customers ORDER BY name ASC")->fetchAll();
?>

<div class="card-custom">
  <div class="card-custom-header d-flex justify-content-between align-items-center">
    <h5 class="fw-bold m-0"><i class="fa-solid fa-file-invoice-dollar text-primary me-2"></i>Create Sales Invoice</h5>
    <a href="<?= base_url('modules/invoice/list.php') ?>" class="btn btn-sm btn-outline-secondary">
      <i class="fa-solid fa-list me-1"></i> Invoice History
    </a>
  </div>

  <div class="card-custom-body">
    <?php if (!empty($error)): ?>
      <div class="alert alert-danger py-2 small mb-3 fw-medium">
        <i class="fa-solid fa-circle-exclamation me-1"></i> <?= htmlspecialchars($error) ?>
      </div>
    <?php endif; ?>

    <form action="" method="POST" id="invoiceForm">
      <!-- Top Invoice Meta Info -->
      <div class="row g-3 mb-4">
        <div class="col-md-2">
          <label class="form-label small fw-bold">Invoice No</label>
          <input type="text" name="invoice_no" class="form-control form-control-sm fw-bold text-primary" value="<?= $auto_invoice_no ?>" readonly>
        </div>

        <div class="col-md-2">
          <label class="form-label small fw-bold">Invoice Date</label>
          <input type="date" name="date" class="form-control form-control-sm" value="<?= date('Y-m-d') ?>" required>
        </div>

        <!-- Customer Auto-Suggest + Direct New Add -->
        <div class="col-md-3 position-relative">
          <label class="form-label small fw-bold">Customer Name <span class="text-danger">*</span></label>
          <input type="hidden" name="customer_id" id="customerIdInput" value="">
          <input type="text" name="customer_name_input" id="customerSearchInput" class="form-control form-control-sm fw-bold" placeholder="Type customer name (Direct Add or Search)..." autocomplete="off" required>
          <div id="customerSuggestions" class="dropdown-menu w-100 shadow-sm" style="display:none; max-height:200px; overflow-y:auto;"></div>
          <small class="text-muted fs-7"><i class="fa-solid fa-info-circle text-info me-1"></i>Purane customer ka naam search karein YA naye customer ka naam direct likhein.</small>
        </div>

        <div class="col-md-2">
          <label class="form-label small fw-bold">Phone Number</label>
          <input type="text" name="new_customer_phone" id="customerPhoneInput" class="form-control form-control-sm" placeholder="e.g. 0300 1234567">
        </div>

        <!-- Mandatory Address Field (Default: Sialkot, Punjab) -->
        <div class="col-md-3">
          <label class="form-label small fw-bold">Customer Address <span class="text-danger">*</span></label>
          <input type="text" name="customer_address" id="customerAddressInput" class="form-control form-control-sm fw-bold" value="Sialkot, Punjab" placeholder="e.g. Sialkot, Punjab" required>
        </div>
      </div>

      <!-- Invoice Items Table -->
      <div class="table-responsive mb-3">
        <table class="table table-bordered table-custom mb-0" id="invoiceItemsTable">
          <thead class="table-light">
            <tr>
              <th style="width: 45%;">Item Description</th>
              <th style="width: 15%;" class="text-end">Unit Price (<?= htmlspecialchars($settings['currency_symbol']) ?>)</th>
              <th style="width: 15%;" class="text-center">Quantity</th>
              <th style="width: 18%;" class="text-end">Total Amount (<?= htmlspecialchars($settings['currency_symbol']) ?>)</th>
              <th style="width: 7%;" class="text-center">Action</th>
            </tr>
          </thead>
          <tbody>
            <tr class="item-row">
              <td class="position-relative">
                <input type="hidden" name="item_id[]" class="item-id-hidden">
                <input type="text" name="item_name[]" class="form-control form-control-sm item-search-input" placeholder="Type or search item..." autocomplete="off" required>
                <div class="dropdown-menu w-100 item-suggestions shadow-sm" style="display:none; max-height:180px; overflow-y:auto;"></div>
              </td>
              <td>
                <input type="number" step="0.01" min="0" name="price[]" class="form-control form-control-sm text-end row-price" placeholder="0.00" required>
              </td>
              <td>
                <input type="number" min="1" name="qty[]" class="form-control form-control-sm text-center row-qty" value="1" required>
              </td>
              <td>
                <input type="text" class="form-control form-control-sm text-end fw-bold row-total" value="0.00" readonly>
              </td>
              <td class="text-center">
                <button type="button" class="btn btn-sm btn-outline-danger remove-row-btn"><i class="fa-solid fa-trash"></i></button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <div class="mb-4">
        <button type="button" class="btn btn-sm btn-outline-primary fw-bold" id="addItemRowBtn">
          <i class="fa-solid fa-plus me-1"></i> Add Another Item Row
        </button>
      </div>

      <!-- Invoice Calculation Summary -->
      <div class="row justify-content-end mb-4">
        <div class="col-md-5">
          <div class="card-custom p-3 bg-light border-0">
            <div class="d-flex justify-content-between align-items-center mb-2">
              <span class="fw-medium text-muted">Sub Total:</span>
              <strong class="fs-6 text-dark" id="subtotalDisplay"><?= htmlspecialchars($settings['currency_symbol']) ?> 0.00</strong>
            </div>

            <div class="d-flex justify-content-between align-items-center mb-2">
              <span class="fw-medium text-muted">Discount (<?= htmlspecialchars($settings['currency_symbol']) ?>):</span>
              <input type="number" step="0.01" min="0" name="discount" id="discountInput" class="form-control form-control-sm text-end font-monospace fw-bold text-danger" style="width: 140px;" value="0.00">
            </div>

            <div class="d-flex justify-content-between align-items-center mb-2 pt-2 border-top">
              <span class="fw-bold fs-5 text-dark">Grand Total:</span>
              <strong class="fs-5 text-primary" id="grandTotalDisplay"><?= htmlspecialchars($settings['currency_symbol']) ?> 0.00</strong>
            </div>

            <div class="d-flex justify-content-between align-items-center mb-2">
              <span class="fw-medium text-muted">Paid Amount (<?= htmlspecialchars($settings['currency_symbol']) ?>):</span>
              <input type="number" step="0.01" min="0" name="paid_amount" id="paidInput" class="form-control form-control-sm text-end font-monospace fw-bold text-success" style="width: 140px;" value="0.00">
            </div>

            <div class="d-flex justify-content-between align-items-center pt-2 border-top">
              <span class="fw-bold" id="balanceLabel">Remaining Balance:</span>
              <strong class="fs-5 text-danger fw-bold" id="balanceDisplay"><?= htmlspecialchars($settings['currency_symbol']) ?> 0.00</strong>
            </div>
          </div>
        </div>
      </div>

      <!-- Action Buttons -->
      <div class="d-flex justify-content-end gap-2">
        <button type="submit" class="btn btn-primary-custom px-4 py-2 fw-bold text-uppercase">
          <i class="fa-solid fa-print me-1"></i> Save & Print Invoice
        </button>
      </div>
    </form>
  </div>
</div>

<script>
$(document).ready(function() {
  const currencySymbol = '<?= htmlspecialchars($settings['currency_symbol']) ?>';

  function formatCurrency(num) {
    return currencySymbol + ' ' + parseFloat(num).toFixed(2);
  }

  function calculateInvoiceTotals() {
    let subtotal = 0;

    $('.row-total').each(function() {
      subtotal += parseFloat($(this).val()) || 0;
    });

    let discount = parseFloat($('#discountInput').val()) || 0;
    if (discount < 0) {
      discount = 0;
      $('#discountInput').val('0.00');
    }

    let grandTotal = Math.max(0, subtotal - discount);

    let paid = parseFloat($('#paidInput').val()) || 0;
    if (paid < 0) {
      paid = 0;
      $('#paidInput').val('0.00');
    }

    let balance = grandTotal - paid;

    $('#subtotalDisplay').text(formatCurrency(subtotal));
    $('#grandTotalDisplay').text(formatCurrency(grandTotal));

    const $balanceEl = $('#balanceDisplay');
    $balanceEl.text(formatCurrency(balance));

    // DYNAMIC COLOR INDICATOR FOR REMAINING BALANCE
    if (balance > 0.001) {
      // Pending Balance -> RED TEXT
      $balanceEl.removeClass('text-success text-dark').addClass('text-danger');
      $('#balanceLabel').text('Remaining Balance (Pending):');
    } else if (Math.abs(balance) <= 0.001) {
      // Zero Remaining Balance (Fully Paid) -> GREEN TEXT
      $balanceEl.removeClass('text-danger text-dark').addClass('text-success');
      $('#balanceLabel').text('Remaining Balance (Fully Paid):');
    } else {
      // Advance Paid -> GREEN TEXT
      $balanceEl.removeClass('text-danger text-dark').addClass('text-success');
      $('#balanceLabel').text('Advance Paid Balance:');
    }
  }

  // Row Price/Qty changes
  $(document).on('input change', '.row-price, .row-qty', function() {
    const $row = $(this).closest('tr');
    let price = parseFloat($row.find('.row-price').val()) || 0;
    let qty = parseInt($row.find('.row-qty').val()) || 0;
    if (price < 0) { price = 0; $row.find('.row-price').val('0.00'); }
    if (qty < 1) { qty = 1; $row.find('.row-qty').val(1); }
    
    $row.find('.row-total').val((price * qty).toFixed(2));
    calculateInvoiceTotals();
  });

  // Discount & Paid Amount input clamp
  $('#discountInput, #paidInput').on('input change', function() {
    let val = parseFloat($(this).val()) || 0;
    if (val < 0) {
      $(this).val('0.00');
    }
    calculateInvoiceTotals();
  });

  // Customer AJAX Search
  $('#customerSearchInput').on('keyup focus', function() {
    const q = $(this).val();
    if (q.length < 1) {
      $('#customerSuggestions').hide();
      return;
    }
    $.get('<?= base_url("modules/invoice/ajax_item_search.php") ?>', { action: 'search_customers', q: q }, function(data) {
      let html = '';
      if (data && data.length > 0) {
        data.forEach(function(c) {
          html += `<a href="#" class="dropdown-item cust-select-item" data-id="${c.id}" data-name="${c.name}" data-phone="${c.phone || ''}" data-address="${c.address || 'Sialkot, Punjab'}">
                    <strong>${c.name}</strong> ${c.phone ? '<small class="text-muted">(' + c.phone + ')</small>' : ''}
                   </a>`;
        });
      } else {
        html = `<div class="dropdown-item text-muted small"><i class="fa-solid fa-plus text-success me-1"></i> Naya Customer Add Ho Jaayega: "${q}"</div>`;
      }
      $('#customerSuggestions').html(html).show();
    });
  });

  $(document).on('click', '.cust-select-item', function(e) {
    e.preventDefault();
    $('#customerIdInput').val($(this).data('id'));
    $('#customerSearchInput').val($(this).data('name'));
    if ($(this).data('phone')) {
      $('#customerPhoneInput').val($(this).data('phone'));
    }
    if ($(this).data('address')) {
      $('#customerAddressInput').val($(this).data('address'));
    } else {
      $('#customerAddressInput').val('Sialkot, Punjab');
    }
    $('#customerSuggestions').hide();
  });

  $(document).on('click', function(e) {
    if (!$(e.target).closest('#customerSearchInput, #customerSuggestions').length) {
      $('#customerSuggestions').hide();
    }
  });

  // Item AJAX Search
  $(document).on('keyup focus', '.item-search-input', function() {
    const $input = $(this);
    const $menu = $input.siblings('.item-suggestions');
    const q = $input.val();

    if (q.length < 1) {
      $menu.hide();
      return;
    }

    $.get('<?= base_url("modules/invoice/ajax_item_search.php") ?>', { action: 'search_items', q: q }, function(data) {
      let html = '';
      if (data && data.length > 0) {
        data.forEach(function(it) {
          html += `<a href="#" class="dropdown-item item-select-option" data-id="${it.id}" data-name="${it.item_name}" data-price="${it.default_price}">
                    <strong>${it.item_name}</strong> - ${currencySymbol} ${parseFloat(it.default_price).toFixed(2)}
                   </a>`;
        });
      } else {
        html = `<div class="dropdown-item text-muted small">Type item name directly...</div>`;
      }
      $menu.html(html).show();
    });
  });

  $(document).on('click', '.item-select-option', function(e) {
    e.preventDefault();
    const $row = $(this).closest('tr');
    $row.find('.item-id-hidden').val($(this).data('id'));
    $row.find('.item-search-input').val($(this).data('name'));
    $row.find('.row-price').val(parseFloat($(this).data('price')).toFixed(2));
    $row.find('.item-suggestions').hide();
    calculateRowTotal($row);
  });

  function calculateRowTotal($row) {
    const price = parseFloat($row.find('.row-price').val()) || 0;
    const qty = parseInt($row.find('.row-qty').val()) || 0;
    $row.find('.row-total').val((price * qty).toFixed(2));
    calculateInvoiceTotals();
  }

  // Add Item Row
  $('#addItemRowBtn').on('click', function() {
    const newRow = `
      <tr class="item-row">
        <td class="position-relative">
          <input type="hidden" name="item_id[]" class="item-id-hidden">
          <input type="text" name="item_name[]" class="form-control form-control-sm item-search-input" placeholder="Type or search item..." autocomplete="off" required>
          <div class="dropdown-menu w-100 item-suggestions shadow-sm" style="display:none; max-height:180px; overflow-y:auto;"></div>
        </td>
        <td>
          <input type="number" step="0.01" min="0" name="price[]" class="form-control form-control-sm text-end row-price" placeholder="0.00" required>
        </td>
        <td>
          <input type="number" min="1" name="qty[]" class="form-control form-control-sm text-center row-qty" value="1" required>
        </td>
        <td>
          <input type="text" class="form-control form-control-sm text-end fw-bold row-total" value="0.00" readonly>
        </td>
        <td class="text-center">
          <button type="button" class="btn btn-sm btn-outline-danger remove-row-btn"><i class="fa-solid fa-trash"></i></button>
        </td>
      </tr>
    `;
    $('#invoiceItemsTable tbody').append(newRow);
  });

  // Remove Item Row
  $(document).on('click', '.remove-row-btn', function() {
    if ($('#invoiceItemsTable tbody tr').length > 1) {
      $(this).closest('tr').remove();
      calculateInvoiceTotals();
    } else {
      alert('At least one item row is required.');
    }
  });

  calculateInvoiceTotals();
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
