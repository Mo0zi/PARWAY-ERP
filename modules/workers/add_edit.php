<?php
require_once __DIR__ . '/../../config/db.php';
require_login();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$worker = [
    'name' => '',
    'phone' => '',
    'cnic' => '',
    'joining_date' => date('Y-m-d'),
    'default_rate' => 0.00
];

if ($id > 0) {
    $stmt = $pdo->prepare("SELECT * FROM workers WHERE id = ?");
    $stmt->execute([$id]);
    $fetched = $stmt->fetch();
    if ($fetched) {
        $worker = $fetched;
    }
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $cnic = trim($_POST['cnic'] ?? '');
    $joining_date = trim($_POST['joining_date'] ?? date('Y-m-d'));
    $default_rate = (float)($_POST['default_rate'] ?? 0);

    if (!empty($name)) {
        if ($id > 0) {
            $stmt = $pdo->prepare("UPDATE workers SET name = ?, phone = ?, cnic = ?, joining_date = ?, default_rate = ? WHERE id = ?");
            $stmt->execute([$name, $phone, $cnic, $joining_date, $default_rate, $id]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO workers (name, phone, cnic, joining_date, default_rate) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$name, $phone, $cnic, $joining_date, $default_rate]);
        }

        header("Location: " . base_url('modules/workers/list.php'));
        exit();
    } else {
        $error = 'Worker name is required.';
    }
}

$page_title = 'Add / Edit Worker';
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="row justify-content-center">
  <div class="col-md-6">
    <div class="card-custom">
      <div class="card-custom-header">
        <h6 class="card-custom-title"><i class="fa-solid fa-user-pen text-primary"></i> <?= $id > 0 ? 'Edit Worker' : 'Add New Worker' ?></h6>
        <a href="<?= base_url('modules/workers/list.php') ?>" class="btn btn-sm btn-outline-secondary">
          <i class="fa-solid fa-arrow-left me-1"></i> Back
        </a>
      </div>
      <div class="card-custom-body">
        <?php if ($error): ?>
          <div class="alert alert-danger py-2"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form action="" method="POST">
          <div class="mb-3">
            <label class="form-label fw-medium">Worker Name <span class="text-danger">*</span></label>
            <input type="text" name="name" class="form-control" placeholder="e.g. Muhammad Aslam" required value="<?= htmlspecialchars($worker['name']) ?>">
          </div>

          <div class="mb-3">
            <label class="form-label fw-medium">Phone Number</label>
            <input type="text" name="phone" class="form-control" placeholder="e.g. 0312 9998877" value="<?= htmlspecialchars($worker['phone']) ?>">
          </div>

          <div class="mb-3">
            <label class="form-label fw-medium">CNIC Number</label>
            <input type="text" name="cnic" class="form-control" placeholder="e.g. 35202-1234567-1" value="<?= htmlspecialchars($worker['cnic']) ?>">
          </div>

          <div class="mb-3">
            <label class="form-label fw-medium">Joining Date</label>
            <input type="date" name="joining_date" class="form-control" value="<?= htmlspecialchars($worker['joining_date']) ?>">
          </div>

          <div class="mb-4">
            <label class="form-label fw-medium">Default Rate per Piece (<?= htmlspecialchars($settings['currency_symbol']) ?>)</label>
            <input type="number" step="0.01" name="default_rate" class="form-control" placeholder="0.00" value="<?= htmlspecialchars($worker['default_rate']) ?>">
          </div>

          <button type="submit" class="btn btn-primary-custom w-100 py-2 fw-bold">
            <i class="fa-solid fa-save me-1"></i> Save Worker Profile
          </button>
        </form>
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
