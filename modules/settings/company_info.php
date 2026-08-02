<?php
require_once __DIR__ . '/../../config/db.php';
require_login();

$msg = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $company_name = trim($_POST['company_name'] ?? 'Parway Industry');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $logo_path = $settings['logo'];

    // Handle Image File Upload
    if (isset($_FILES['logo_file']) && $_FILES['logo_file']['error'] === UPLOAD_ERR_OK) {
        $file_tmp = $_FILES['logo_file']['tmp_name'];
        $file_name = $_FILES['logo_file']['name'];
        $ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

        $allowed = ['jpg', 'jpeg', 'png', 'webp', 'svg'];
        if (in_array($ext, $allowed)) {
            $upload_dir = __DIR__ . '/../../assets/uploads/logo/';
            if (!file_exists($upload_dir)) {
                @mkdir($upload_dir, 0777, true);
            }
            $target_file = $upload_dir . 'logo.' . $ext;
            if (move_uploaded_file($file_tmp, $target_file)) {
                $logo_path = 'assets/uploads/logo/logo.' . $ext;
            }
        } else {
            $error = 'Invalid image format. Allowed: JPG, PNG, WEBP, SVG.';
        }
    }

    if (empty($error)) {
        $stmt = $pdo->prepare("UPDATE settings SET company_name = ?, phone = ?, address = ?, logo = ? WHERE id = 1");
        $stmt->execute([$company_name, $phone, $address, $logo_path]);

        $msg = 'Company profile and official logo updated successfully!';
        $settings['company_name'] = $company_name;
        $settings['phone'] = $phone;
        $settings['address'] = $address;
        $settings['logo'] = $logo_path;
    }
}

$page_title = 'Company Profile & Logo Settings';
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="row justify-content-center">
  <div class="col-md-6">
    <div class="card-custom">
      <div class="card-custom-header">
        <h6 class="card-custom-title"><i class="fa-solid fa-building text-primary"></i> Company Profile & Logo</h6>
      </div>
      <div class="card-custom-body">
        <?php if ($msg): ?>
          <div class="alert alert-success alert-dismissible fade show py-2">
            <i class="fa-solid fa-check-circle me-1"></i> <?= htmlspecialchars($msg) ?>
            <button type="button" class="btn-close py-2" data-bs-dismiss="alert"></button>
          </div>
        <?php endif; ?>

        <?php if ($error): ?>
          <div class="alert alert-danger py-2"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form action="" method="POST" enctype="multipart/form-data">
          <!-- Current Logo Preview -->
          <div class="mb-4 text-center">
            <label class="form-label d-block fw-bold mb-2">Current Official Logo</label>
            <?php if (!empty($settings['logo']) && file_exists(__DIR__ . '/../../' . $settings['logo'])): ?>
              <div class="p-3 bg-light border rounded d-inline-block">
                <img src="<?= base_url($settings['logo']) ?>" alt="Official Logo" style="max-height: 100px; width: auto; object-fit: contain;">
              </div>
            <?php else: ?>
              <div class="p-3 bg-light border rounded text-muted">No logo uploaded yet.</div>
            <?php endif; ?>
          </div>

          <div class="mb-3">
            <label class="form-label fw-medium">Upload / Change Logo Image</label>
            <input type="file" name="logo_file" class="form-control" accept="image/*">
            <small class="text-muted">Supports PNG, JPG, WEBP formats.</small>
          </div>

          <hr class="my-3">

          <div class="mb-3">
            <label class="form-label fw-medium">Company Name <span class="text-danger">*</span></label>
            <input type="text" name="company_name" class="form-control" value="<?= htmlspecialchars($settings['company_name']) ?>" required>
          </div>

          <div class="mb-3">
            <label class="form-label fw-medium">Phone Number</label>
            <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($settings['phone']) ?>">
          </div>

          <div class="mb-4">
            <label class="form-label fw-medium">Address (Appears on Invoices)</label>
            <textarea name="address" class="form-control" rows="3"><?= htmlspecialchars($settings['address']) ?></textarea>
          </div>

          <button type="submit" class="btn btn-primary-custom w-100 py-2.5 fw-bold">
            <i class="fa-solid fa-save me-1"></i> Save Profile & Logo
          </button>
        </form>
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
