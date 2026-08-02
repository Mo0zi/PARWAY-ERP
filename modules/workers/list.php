<?php
require_once __DIR__ . '/../../config/db.php';
require_login();

// Handle Delete Worker
if (isset($_GET['delete'])) {
    $del_id = (int)$_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM workers WHERE id = ?");
    $stmt->execute([$del_id]);
    header("Location: " . base_url('modules/workers/list.php?msg=deleted'));
    exit();
}

$page_title = 'Workers & Wages Master';
require_once __DIR__ . '/../../includes/header.php';

// Fetch all workers
$workers = $pdo->query("SELECT * FROM workers ORDER BY name ASC")->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <div>
    <h5 class="fw-bold m-0"><i class="fa-solid fa-user-gear text-primary me-2"></i>Workers & Wages Master</h5>
    <small class="text-muted">Manage worker profiles, piece rates, and pending payable balances</small>
  </div>
  <a href="<?= base_url('modules/workers/add_edit.php') ?>" class="btn btn-primary-custom btn-sm">
    <i class="fa-solid fa-user-plus me-1"></i> Add New Worker
  </a>
</div>

<?php if (isset($_GET['msg']) && $_GET['msg'] === 'deleted'): ?>
  <div class="alert alert-success alert-dismissible fade show py-2">
    Worker deleted successfully!
    <button type="button" class="btn-close py-2" data-bs-dismiss="alert"></button>
  </div>
<?php endif; ?>

<div class="card-custom">
  <div class="card-custom-body p-0">
    <div class="table-responsive">
      <table class="table table-custom align-middle mb-0">
        <thead>
          <tr>
            <th>#</th>
            <th>Worker Name</th>
            <th>Phone</th>
            <th>CNIC</th>
            <th class="text-end">Default Rate / Pc</th>
            <th class="text-end">Current Net Payable Balance</th>
            <th class="text-center">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($workers)): ?>
            <tr><td colspan="7" class="text-center text-muted py-4">No workers registered yet.</td></tr>
          <?php else: ?>
            <?php foreach ($workers as $index => $w): 
              $balance = recalculate_worker_balance($w['id'], $pdo);
            ?>
              <tr>
                <td><?= $index + 1 ?></td>
                <td>
                  <strong class="text-dark d-block"><?= htmlspecialchars($w['name']) ?></strong>
                  <small class="text-muted">Joined: <?= date('d-M-Y', strtotime($w['joining_date'])) ?></small>
                </td>
                <td><?= htmlspecialchars($w['phone'] ?: '-') ?></td>
                <td><?= htmlspecialchars($w['cnic'] ?: '-') ?></td>
                <td class="text-end fw-bold"><?= format_rs($w['default_rate']) ?></td>
                <td class="text-end fw-bold fs-6 <?= $balance > 0 ? 'text-info' : ($balance < 0 ? 'text-danger' : 'text-dark') ?>">
                  <?= format_rs($balance) ?>
                  <?php if ($balance > 0): ?>
                    <span class="badge bg-info text-dark ms-1">Payable</span>
                  <?php elseif ($balance < 0): ?>
                    <span class="badge bg-warning text-dark ms-1">Advance</span>
                  <?php endif; ?>
                </td>
                <td class="text-center">
                  <a href="<?= base_url('modules/workers/ledger.php?id=' . $w['id']) ?>" class="btn btn-sm btn-outline-primary py-1 px-2" title="View Ledger & Pay Salary">
                    <i class="fa-solid fa-book-journal-whills me-1"></i> Ledger & Pay
                  </a>
                  <a href="<?= base_url('modules/workers/add_edit.php?id=' . $w['id']) ?>" class="btn btn-sm btn-outline-secondary py-1 px-2" title="Edit Profile">
                    <i class="fa-solid fa-pen"></i>
                  </a>
                  <a href="<?= base_url('modules/workers/list.php?delete=' . $w['id']) ?>" class="btn btn-sm btn-outline-danger py-1 px-2" onclick="return confirm('Delete this worker?');" title="Delete Worker">
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
