<?php
require_once __DIR__ . '/../../config/db.php';
require_login();

// Handle Delete Customer
if (isset($_GET['delete'])) {
    $del_id = (int)$_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM customers WHERE id = ?");
    $stmt->execute([$del_id]);
    header("Location: " . base_url('modules/customers/list.php?msg=deleted'));
    exit();
}

$page_title = 'Customer Directory';
require_once __DIR__ . '/../../includes/header.php';

// Fetch all customers
$customers = $pdo->query("SELECT * FROM customers ORDER BY name ASC")->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <h5 class="fw-bold m-0"><i class="fa-solid fa-users text-primary me-2"></i>Customers Master</h5>
  <a href="<?= base_url('modules/customers/add_edit.php') ?>" class="btn btn-primary-custom btn-sm">
    <i class="fa-solid fa-user-plus me-1"></i> Add New Customer
  </a>
</div>

<?php if (isset($_GET['msg']) && $_GET['msg'] === 'deleted'): ?>
  <div class="alert alert-success alert-dismissible fade show py-2">
    Customer deleted successfully!
    <button type="button" class="btn-close py-2" data-bs-dismiss="alert"></button>
  </div>
<?php endif; ?>

<div class="card-custom">
  <div class="card-custom-body p-0">
    <div class="table-responsive">
      <table class="table table-custom mb-0">
        <thead>
          <tr>
            <th>#</th>
            <th>Customer Name</th>
            <th>Phone</th>
            <th>Address</th>
            <th class="text-end">Opening Balance</th>
            <th class="text-end">Current Balance</th>
            <th class="text-center">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($customers)): ?>
            <tr><td colspan="7" class="text-center text-muted py-4">No customers registered yet.</td></tr>
          <?php else: ?>
            <?php foreach ($customers as $index => $c): 
              $balance = recalculate_customer_balance($c['id'], $pdo);
            ?>
              <tr>
                <td><?= $index + 1 ?></td>
                <td><strong><?= htmlspecialchars($c['name']) ?></strong></td>
                <td><?= htmlspecialchars($c['phone'] ?: '-') ?></td>
                <td><?= htmlspecialchars($c['address'] ?: '-') ?></td>
                <td class="text-end"><?= format_rs($c['opening_balance']) ?></td>
                <td class="text-end fw-bold <?= $balance > 0 ? 'text-danger' : ($balance < 0 ? 'text-success' : 'text-dark') ?>">
                  <?= format_rs($balance) ?>
                </td>
                <td class="text-center">
                  <a href="<?= base_url('modules/customers/ledger.php?id=' . $c['id']) ?>" class="btn btn-sm btn-outline-primary py-1 px-2" title="View Ledger">
                    <i class="fa-solid fa-book-journal-whills me-1"></i> Ledger
                  </a>
                  <a href="<?= base_url('modules/customers/add_edit.php?id=' . $c['id']) ?>" class="btn btn-sm btn-outline-secondary py-1 px-2" title="Edit">
                    <i class="fa-solid fa-pen"></i>
                  </a>
                  <a href="<?= base_url('modules/customers/list.php?delete=' . $c['id']) ?>" class="btn btn-sm btn-outline-danger py-1 px-2" onclick="return confirm('Are you sure you want to delete this customer?');" title="Delete">
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
