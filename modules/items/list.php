<?php
require_once __DIR__ . '/../../config/db.php';
require_login();

// Handle Item Add
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_item'])) {
    $item_name = trim($_POST['item_name'] ?? '');
    $default_price = (float)($_POST['default_price'] ?? 0);
    $unit = trim($_POST['unit'] ?? 'Pcs');

    if (!empty($item_name)) {
        $stmt = $pdo->prepare("INSERT INTO items (item_name, default_price, unit) VALUES (?, ?, ?)");
        $stmt->execute([$item_name, $default_price, $unit]);
        header("Location: " . base_url('modules/items/list.php?msg=added'));
        exit();
    }
}

// Handle Item Delete
if (isset($_GET['delete'])) {
    $del_id = (int)$_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM items WHERE id = ?");
    $stmt->execute([$del_id]);
    header("Location: " . base_url('modules/items/list.php?msg=deleted'));
    exit();
}

$page_title = 'Item Master Catalog';
require_once __DIR__ . '/../../includes/header.php';

$items = $pdo->query("SELECT * FROM items ORDER BY item_name ASC")->fetchAll();
?>

<?php if (isset($_GET['msg']) && $_GET['msg'] === 'added'): ?>
  <div class="alert alert-success alert-dismissible fade show py-2 mb-3">
    <i class="fa-solid fa-check-circle me-1"></i> New item added to catalog successfully!
    <button type="button" class="btn-close py-2" data-bs-dismiss="alert"></button>
  </div>
<?php endif; ?>

<?php if (isset($_GET['msg']) && $_GET['msg'] === 'deleted'): ?>
  <div class="alert alert-warning alert-dismissible fade show py-2 mb-3">
    <i class="fa-solid fa-trash me-1"></i> Item deleted from catalog.
    <button type="button" class="btn-close py-2" data-bs-dismiss="alert"></button>
  </div>
<?php endif; ?>

<div class="row g-3">
  <!-- Add New Item Form -->
  <div class="col-md-4">
    <div class="card-custom">
      <div class="card-custom-header">
        <h6 class="card-custom-title"><i class="fa-solid fa-square-plus text-primary"></i> Add New Item</h6>
      </div>
      <div class="card-custom-body">
        <form action="" method="POST">
          <input type="hidden" name="add_item" value="1">
          <div class="mb-3">
            <label class="form-label fw-medium">Item Name <span class="text-danger">*</span></label>
            <input type="text" name="item_name" class="form-control" placeholder="e.g. Denim Shirt" required>
          </div>
          <div class="mb-3">
            <label class="form-label fw-medium">Default Unit Price (<?= htmlspecialchars($settings['currency_symbol']) ?>)</label>
            <input type="number" step="0.01" min="0" name="default_price" class="form-control" placeholder="0.00">
          </div>
          <div class="mb-3">
            <label class="form-label fw-medium">Unit</label>
            <input type="text" name="unit" class="form-control" value="Pcs" placeholder="Pcs, Dozen, Meters">
          </div>
          <button type="submit" class="btn btn-primary-custom w-100 fw-bold">
            <i class="fa-solid fa-plus me-1"></i> Add Item
          </button>
        </form>
      </div>
    </div>
  </div>

  <!-- Items Table -->
  <div class="col-md-8">
    <div class="card-custom">
      <div class="card-custom-header">
        <h6 class="card-custom-title"><i class="fa-solid fa-boxes-stacked text-secondary"></i> Existing Item Master</h6>
      </div>
      <div class="card-custom-body p-0">
        <div class="table-responsive">
          <table class="table table-custom mb-0">
            <thead>
              <tr>
                <th>#</th>
                <th>Item Name</th>
                <th>Unit</th>
                <th class="text-end">Default Price</th>
                <th class="text-center">Action</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($items)): ?>
                <tr><td colspan="5" class="text-center text-muted py-3">No items added yet.</td></tr>
              <?php else: ?>
                <?php foreach ($items as $idx => $it): ?>
                  <tr>
                    <td><?= $idx + 1 ?></td>
                    <td><strong><?= htmlspecialchars($it['item_name']) ?></strong></td>
                    <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($it['unit']) ?></span></td>
                    <td class="text-end fw-bold"><?= format_rs($it['default_price']) ?></td>
                    <td class="text-center">
                      <a href="<?= base_url('modules/items/list.php?delete=' . $it['id']) ?>" class="btn btn-sm btn-outline-danger py-1 px-2" onclick="return confirm('Delete this item?');">
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
  </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
