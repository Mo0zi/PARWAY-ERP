<?php
require_once __DIR__ . '/config/db.php';

if (isset($_SESSION['user_id'])) {
    header("Location: " . base_url('modules/dashboard/index.php'));
    exit();
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (!empty($username) && !empty($password)) {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? LIMIT 1");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            header("Location: " . base_url('modules/dashboard/index.php'));
            exit();
        } else {
            $error = 'Invalid email/username or password.';
        }
    } else {
        $error = 'Please fill in both fields.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Login - <?= htmlspecialchars($settings['company_name']) ?></title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= base_url('assets/css/style.css') ?>">
</head>
<body class="bg-light d-flex align-items-center justify-content-center min-vh-100 p-3">

<div class="card-custom p-4 shadow-lg border-0" style="max-width: 420px; width: 100%;">
  <div class="text-center mb-4">
    <?php if (!empty($settings['logo']) && file_exists(__DIR__ . '/' . $settings['logo'])): ?>
      <img src="<?= base_url($settings['logo']) ?>" alt="Logo" style="max-height: 85px; width: auto; object-fit: contain;" class="mb-3">
    <?php else: ?>
      <div class="brand-logo mx-auto mb-3" style="width:65px; height:65px; background:var(--primary-color); border-radius:50%; display:flex; align-items:center; justify-content:center;">
        <i class="fa-solid fa-shirt text-white fs-2"></i>
      </div>
    <?php endif; ?>
    <h4 class="fw-bold text-dark m-0"><?= htmlspecialchars($settings['company_name']) ?></h4>
    <p class="text-muted small">Clothing Manufacturing ERP Portal</p>
  </div>

  <?php if (!empty($error)): ?>
    <div class="alert alert-danger py-2 small fw-medium mb-3">
      <i class="fa-solid fa-triangle-exclamation me-1"></i> <?= htmlspecialchars($error) ?>
    </div>
  <?php endif; ?>

  <form action="" method="POST">
    <div class="mb-3">
      <label class="form-label small fw-bold">Email / Username</label>
      <div class="input-group">
        <span class="input-group-text bg-light"><i class="fa-solid fa-envelope text-muted"></i></span>
        <input type="text" name="username" class="form-control" placeholder="parway2002@gmail.com" value="parway2002@gmail.com" required autofocus>
      </div>
    </div>

    <div class="mb-4">
      <label class="form-label small fw-bold">Password</label>
      <div class="input-group">
        <span class="input-group-text bg-light"><i class="fa-solid fa-lock text-muted"></i></span>
        <input type="password" name="password" class="form-control" placeholder="Enter password" value="SHAHBAZ@000" required>
      </div>
    </div>

    <button type="submit" class="btn btn-primary-custom w-100 py-2.5 fw-bold text-uppercase fs-6">
      <i class="fa-solid fa-right-to-bracket me-2"></i> Sign In to Portal
    </button>
  </form>

  <div class="text-center mt-4">
    <small class="text-muted">Parway Industry ERP &copy; <?= date('Y') ?></small>
  </div>
</div>

</body>
</html>
