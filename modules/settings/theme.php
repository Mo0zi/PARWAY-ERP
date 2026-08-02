<?php
$page_title = 'System Customizer & Theme Settings';
require_once __DIR__ . '/../../includes/header.php';

$success_msg = '';

// Handle Form Submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_customizer'])) {
    $primary_color = trim($_POST['theme_primary_color'] ?? '#1e3c72');
    $secondary_color = trim($_POST['theme_secondary_color'] ?? '#2a5298');
    $sidebar_bg_color = trim($_POST['sidebar_bg_color'] ?? '#1e293b');
    $company_name = trim($_POST['company_name'] ?? 'Parway Industry');
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $currency_symbol = trim($_POST['currency_symbol'] ?? 'Rs.');
    $invoice_title = trim($_POST['invoice_title'] ?? 'SALES INVOICE');
    $invoice_footer_note = trim($_POST['invoice_footer_note'] ?? '');
    $default_work_types = trim($_POST['default_work_types'] ?? '');

    $stmt = $pdo->prepare("UPDATE settings SET 
                           theme_primary_color = ?, 
                           theme_secondary_color = ?, 
                           sidebar_bg_color = ?, 
                           company_name = ?, 
                           phone = ?, 
                           email = ?, 
                           address = ?, 
                           currency_symbol = ?, 
                           invoice_title = ?, 
                           invoice_footer_note = ?, 
                           default_work_types = ? 
                           WHERE id = 1");
    $stmt->execute([
        $primary_color,
        $secondary_color,
        $sidebar_bg_color,
        $company_name,
        $phone,
        $email,
        $address,
        $currency_symbol,
        $invoice_title,
        $invoice_footer_note,
        $default_work_types
    ]);

    // Refresh Settings Cache
    $stmt_fetch = $pdo->query("SELECT * FROM settings WHERE id = 1 LIMIT 1");
    $settings = array_merge($settings, array_filter($stmt_fetch->fetch(), function($v){ return $v !== null && $v !== ''; }));

    $success_msg = 'System settings & customizer updated successfully!';
}
?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <div>
    <h5 class="fw-bold m-0"><i class="fa-solid fa-palette text-primary me-2"></i>Ultimate System Customizer</h5>
    <small class="text-muted">Customize system colors, currency, company profile, and invoice titles</small>
  </div>
</div>

<?php if (!empty($success_msg)): ?>
  <div class="alert alert-success alert-dismissible fade show py-2 fw-medium mb-3">
    <i class="fa-solid fa-circle-check me-2"></i> <?= htmlspecialchars($success_msg) ?>
    <button type="button" class="btn-close py-2" data-bs-dismiss="alert"></button>
  </div>
<?php endif; ?>

<form action="" method="POST">
  <input type="hidden" name="save_customizer" value="1">

  <div class="row g-4">
    <!-- Left Column: Theme & Colors -->
    <div class="col-lg-6">
      <div class="card-custom h-100 mb-0">
        <div class="card-custom-header bg-light">
          <h6 class="card-custom-title fw-bold m-0"><i class="fa-solid fa-brush text-primary me-2"></i> Theme & Palette Customizer</h6>
        </div>
        <div class="card-custom-body">
          
          <!-- Primary Brand Color -->
          <div class="mb-3">
            <label class="form-label fw-bold small">Primary Brand Color</label>
            <div class="input-group">
              <input type="color" id="primaryColorPicker" class="form-control form-control-color" value="<?= htmlspecialchars($settings['theme_primary_color'] ?? '#1e3c72') ?>" title="Choose Primary Color">
              <input type="text" name="theme_primary_color" id="primaryColorText" class="form-control font-monospace" value="<?= htmlspecialchars($settings['theme_primary_color'] ?? '#1e3c72') ?>" required>
            </div>
            <small class="text-muted">Controls buttons, active sidebar items, links, and invoice headers.</small>
          </div>

          <!-- Secondary Accent Color -->
          <div class="mb-3">
            <label class="form-label fw-bold small">Secondary Accent Color</label>
            <div class="input-group">
              <input type="color" id="secondaryColorPicker" class="form-control form-control-color" value="<?= htmlspecialchars($settings['theme_secondary_color'] ?? '#2a5298') ?>" title="Choose Secondary Color">
              <input type="text" name="theme_secondary_color" id="secondaryColorText" class="form-control font-monospace" value="<?= htmlspecialchars($settings['theme_secondary_color'] ?? '#2a5298') ?>" required>
            </div>
            <small class="text-muted">Controls gradients, header accents, and hover effects.</small>
          </div>

          <!-- Sidebar Background Color -->
          <div class="mb-4">
            <label class="form-label fw-bold small">Sidebar Background Color</label>
            <div class="input-group">
              <input type="color" id="sidebarBgPicker" class="form-control form-control-color" value="<?= htmlspecialchars($settings['sidebar_bg_color'] ?? '#1e293b') ?>" title="Choose Sidebar Color">
              <input type="text" name="sidebar_bg_color" id="sidebarBgText" class="form-control font-monospace" value="<?= htmlspecialchars($settings['sidebar_bg_color'] ?? '#1e293b') ?>" required>
            </div>
            <small class="text-muted">Controls left navigation drawer background color.</small>
          </div>

          <!-- Quick Palette Presets -->
          <div class="p-3 bg-light rounded border">
            <label class="form-label fw-bold small d-block mb-2"><i class="fa-solid fa-wand-magic-sparkles me-1 text-warning"></i> Quick Color Palettes</label>
            <div class="d-flex flex-wrap gap-2">
              <button type="button" class="btn btn-sm btn-outline-dark palette-btn" data-primary="#adb71f" data-secondary="#047857" data-sidebar="#111827">
                <span class="d-inline-block rounded-circle me-1" style="width:12px; height:12px; background:#adb71f;"></span> Lime & Forest
              </button>
              <button type="button" class="btn btn-sm btn-outline-dark palette-btn" data-primary="#1e3c72" data-secondary="#2a5298" data-sidebar="#1e293b">
                <span class="d-inline-block rounded-circle me-1" style="width:12px; height:12px; background:#1e3c72;"></span> Classic Navy
              </button>
              <button type="button" class="btn btn-sm btn-outline-dark palette-btn" data-primary="#4f46e5" data-secondary="#4338ca" data-sidebar="#0f172a">
                <span class="d-inline-block rounded-circle me-1" style="width:12px; height:12px; background:#4f46e5;"></span> Royal Indigo
              </button>
              <button type="button" class="btn btn-sm btn-outline-dark palette-btn" data-primary="#78350f" data-secondary="#d97706" data-sidebar="#1c1917">
                <span class="d-inline-block rounded-circle me-1" style="width:12px; height:12px; background:#78350f;"></span> Warm Amber
              </button>
            </div>
          </div>

        </div>
      </div>
    </div>

    <!-- Right Column: Business & Invoice Details -->
    <div class="col-lg-6">
      <div class="card-custom h-100 mb-0">
        <div class="card-custom-header bg-light">
          <h6 class="card-custom-title fw-bold m-0"><i class="fa-solid fa-building text-primary me-2"></i> Company & Invoice Details</h6>
        </div>
        <div class="card-custom-body">
          
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label small fw-bold">Company Name <span class="text-danger">*</span></label>
              <input type="text" name="company_name" class="form-control form-control-sm" value="<?= htmlspecialchars($settings['company_name']) ?>" required>
            </div>

            <div class="col-md-6">
              <label class="form-label small fw-bold">Currency Symbol</label>
              <input type="text" name="currency_symbol" class="form-control form-control-sm fw-bold text-success" value="<?= htmlspecialchars($settings['currency_symbol'] ?? 'Rs.') ?>" placeholder="e.g. Rs., $, AED" required>
            </div>

            <div class="col-md-6">
              <label class="form-label small fw-bold">Phone Number</label>
              <input type="text" name="phone" class="form-control form-control-sm" value="<?= htmlspecialchars($settings['phone']) ?>">
            </div>

            <div class="col-md-6">
              <label class="form-label small fw-bold">Email Address</label>
              <input type="email" name="email" class="form-control form-control-sm" value="<?= htmlspecialchars($settings['email'] ?? '') ?>">
            </div>

            <div class="col-12">
              <label class="form-label small fw-bold">Company Address</label>
              <input type="text" name="address" class="form-control form-control-sm" value="<?= htmlspecialchars($settings['address']) ?>">
            </div>

            <div class="col-12">
              <label class="form-label small fw-bold">Invoice Title Header</label>
              <input type="text" name="invoice_title" class="form-control form-control-sm fw-bold" value="<?= htmlspecialchars($settings['invoice_title'] ?? 'SALES INVOICE') ?>" placeholder="e.g. SALES INVOICE or TAX INVOICE">
            </div>

            <div class="col-12">
              <label class="form-label small fw-bold">Invoice Footer Terms & Note</label>
              <textarea name="invoice_footer_note" class="form-control form-control-sm" rows="2" placeholder="e.g. Goods once sold are non-refundable. Thank you for your business!"><?= htmlspecialchars($settings['invoice_footer_note'] ?? '') ?></textarea>
            </div>

            <div class="col-12">
              <label class="form-label small fw-bold">Default Manufacturing Work Types (Comma Separated)</label>
              <input type="text" name="default_work_types" class="form-control form-control-sm" value="<?= htmlspecialchars($settings['default_work_types'] ?? 'Stitching, Cutting, Overlock, Packing, Pressing, Embroidery, Thread Cutting') ?>">
              <small class="text-muted">Defines options available in Working Sheet (Lot Entry)</small>
            </div>
          </div>

        </div>
      </div>
    </div>

    <!-- Save Button Bar -->
    <div class="col-12 text-end">
      <button type="submit" class="btn btn-primary-custom px-5 py-2.5 fw-bold text-uppercase fs-6">
        <i class="fa-solid fa-floppy-disk me-2"></i> Save All Customizations
      </button>
    </div>
  </div>
</form>

<script>
$(document).ready(function() {
  // Sync Color Pickers with Text Inputs
  $('#primaryColorPicker').on('input change', function() {
    $('#primaryColorText').val($(this).val());
  });
  $('#primaryColorText').on('input change', function() {
    $('#primaryColorPicker').val($(this).val());
  });

  $('#secondaryColorPicker').on('input change', function() {
    $('#secondaryColorText').val($(this).val());
  });
  $('#secondaryColorText').on('input change', function() {
    $('#secondaryColorPicker').val($(this).val());
  });

  $('#sidebarBgPicker').on('input change', function() {
    $('#sidebarBgText').val($(this).val());
  });
  $('#sidebarBgText').on('input change', function() {
    $('#sidebarBgPicker').val($(this).val());
  });

  // Palette Preset Click
  $('.palette-btn').on('click', function() {
    const primary = $(this).data('primary');
    const secondary = $(this).data('secondary');
    const sidebar = $(this).data('sidebar');

    $('#primaryColorPicker, #primaryColorText').val(primary);
    $('#secondaryColorPicker, #secondaryColorText').val(secondary);
    $('#sidebarBgPicker, #sidebarBgText').val(sidebar);
  });
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
