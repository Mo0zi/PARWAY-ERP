<?php
require_once __DIR__ . '/../config/db.php';
require_login();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($page_title ?? 'Dashboard') ?> - <?= htmlspecialchars($settings['company_name']) ?></title>
  
  <!-- FontAwesome Icons -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  
  <!-- Bootstrap 5 CSS -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
  
  <!-- Google Fonts: Inter -->
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

  <!-- jQuery -->
  <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

  <!-- Custom Design System CSS -->
  <link rel="stylesheet" href="<?= base_url('assets/css/style.css') ?>">

  <!-- Dynamic System Customizer Variables -->
  <style>
    :root {
      --primary-color: <?= $settings['theme_primary_color'] ?? '#1e3c72' ?>;
      --secondary-color: <?= $settings['theme_secondary_color'] ?? '#2a5298' ?>;
      --sidebar-bg: <?= $settings['sidebar_bg_color'] ?? '#1e293b' ?>;
    }

    .app-sidebar {
      background: var(--sidebar-bg) !important;
    }
  </style>
</head>
<body>

<div class="app-wrapper">
  <!-- Sidebar Component -->
  <?php require_once __DIR__ . '/sidebar.php'; ?>

  <!-- Main Content Container -->
  <div class="main-content">
    <!-- Top Header Bar -->
    <header class="top-navbar no-print">
      <div class="d-flex align-items-center gap-3">
        <button id="sidebarToggle" class="btn btn-sm btn-light border d-md-none">
          <i class="fa-solid fa-bars"></i>
        </button>
        <h5 class="fw-bold m-0 text-dark"><?= htmlspecialchars($page_title ?? 'Dashboard Overview') ?></h5>
      </div>

      <div class="d-flex align-items-center gap-3">
        <span class="badge bg-light text-dark border px-3 py-2">
          <i class="fa-solid fa-calendar-day me-1 text-primary"></i> <?= date('d M, Y (l)') ?>
        </span>

        <div class="dropdown">
          <button class="btn btn-light btn-sm border dropdown-toggle fw-bold" type="button" data-bs-toggle="dropdown">
            <i class="fa-solid fa-user-circle text-primary me-1"></i> <?= htmlspecialchars($_SESSION['username'] ?? 'Admin') ?>
          </button>
          <ul class="dropdown-menu dropdown-menu-end shadow-sm">
            <li><a class="dropdown-item small" href="<?= base_url('modules/settings/company_info.php') ?>"><i class="fa-solid fa-building me-2"></i>Company Profile</a></li>
            <li><a class="dropdown-item small" href="<?= base_url('modules/settings/theme.php') ?>"><i class="fa-solid fa-palette me-2"></i>Theme & Customizer</a></li>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item small text-danger" href="<?= base_url('logout.php') ?>"><i class="fa-solid fa-right-from-bracket me-2"></i>Sign Out</a></li>
          </ul>
        </div>
      </div>
    </header>

    <!-- Page Body Area -->
    <main class="page-container p-4">
