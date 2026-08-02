<?php
require_once __DIR__ . '/../../config/db.php';
require_login();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$customer = [
    'name' => '',
    'phone' => '',
    'address' => '',
    'opening_balance' => 0.00
];

if ($id > 0) {
    $stmt = $pdo->prepare("SELECT * FROM customers WHERE id = ?");
    $stmt->execute([$id]);
    $fetched = $stmt->fetch();
    if ($fetched) {
        $customer = $fetched;
    }
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $opening_balance = (float)($_POST['opening_balance'] ?? 0);

    if (!empty($name)) {
        if ($id > 0) {
            $stmt = $pdo->prepare("UPDATE customers SET name = ?, phone = ?, address = ?, opening_balance = ? WHERE id = ?");
            $stmt->execute([$name, $phone, $address, $opening_balance, $id]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO customers (name, phone, address, opening_balance) VALUES (?, ?, ?, ?)");
            $stmt->execute([$name, $phone, $address, $opening_balance]);
            $id = $pdo->lastInsertId();
        }

        recalculate_customer_balance($id, $pdo);
        header("Location: " . base_url('modules/customers/list.php'));
        exit();
    } else {
        $error = 'Customer name is required.';
    }
}

$page_title = 'Add / Edit Customer';
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="row justify-content-center">
  <div class="col-md-6">
    <div class="card-custom">
      <div class="card-custom-header">
        <h6 class="card-custom-title">
          <i class="fa-solid fa-user-pen text-primary"></i> <?= $id > 0 ? 'Edit Customer' : 'Add New Customer' ?>
        </h6>
        <a href="<?= base_url('modules/customers/list.php') ?>" class="btn btn-sm btn-outline-secondary">
          <i class="fa-solid fa-arrow-left me-1"></i> Back
        </a>
      </div>
      <div class="card-custom-body">
        <?php if ($error): ?>
          <div class="alert alert-danger py-2"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form action="" method="POST">
          <div class="mb-3">
            <label class="form-label fw-medium">Customer Name <span class="text-danger">*</span></label>
            <input type="text" name="name" class="form-control" placeholder="e.g. Al-Madina Traders" required value="<?= htmlspecialchars($customer['name']) ?>">
          </div>

          <div class="mb-3">
            <label class="form-label fw-medium">Phone Number</label>
            <input type="text" name="phone" class="form-control" placeholder="e.g. 0300 1234567" value="<?= htmlspecialchars($customer['phone']) ?>">
          </div>

          <div class="mb-3">
            <label class="form-label fw-medium">Address</label>
            <textarea name="address" class="form-control" rows="2" placeholder="Full shop/business address"><?= htmlspecialchars($customer['address']) ?></textarea>
          </div>

          <div class="mb-4">
            <label class="form-label fw-medium">Opening Balance (Rs.)</label>
            <input type="number" step="0.01" name="opening_balance" class="form-control" placeholder="0.00" value="<?= htmlspecialchars($customer['opening_balance']) ?>">
            <small class="text-muted">Pehle se kitna lena hai (+ve) ya dena hai (-ve)</small>
          </div>

          <button type="submit" class="btn btn-primary-custom w-100 py-2 fw-bold">
            <i class="fa-solid fa-save me-1"></i> Save Customer
          </button>
        </form>
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
