<?php
$current_uri = $_SERVER['REQUEST_URI'] ?? '';

function is_active($page, $current_uri) {
    return (strpos($current_uri, $page) !== false) ? 'active' : '';
}
?>

<aside class="app-sidebar">
  <div class="sidebar-header d-flex align-items-center gap-2">
    <?php if (!empty($settings['logo']) && file_exists(__DIR__ . '/../' . $settings['logo'])): ?>
      <img src="<?= base_url($settings['logo']) ?>" alt="Logo" style="max-height: 40px; width: auto; object-fit: contain; background: #fff; padding: 2px; border-radius: 4px;">
    <?php else: ?>
      <i class="fa-solid fa-shirt brand-icon text-white fs-4"></i>
    <?php endif; ?>
    <div class="brand-text">
      <h6 class="m-0 fw-bold text-white"><?= htmlspecialchars($settings['company_name'] ?? 'PARWAY') ?></h6>
      <small class="text-white-50 uppercase fs-7">ERP SYSTEM</small>
    </div>
  </div>

  <div class="sidebar-menu">
    <div class="menu-label">Main Navigation</div>
    <ul class="list-unstyled m-0 p-0">
      <li>
        <a href="<?= base_url('modules/dashboard/index.php') ?>" class="<?= is_active('dashboard/index.php', $current_uri) ?>">
          <i class="fa-solid fa-chart-line"></i>
          <span>Dashboard</span>
        </a>
      </li>
    </ul>

    <div class="menu-label">Sales & Invoicing</div>
    <ul class="list-unstyled m-0 p-0">
      <li>
        <a href="<?= base_url('modules/invoice/create.php') ?>" class="<?= is_active('invoice/create.php', $current_uri) ?>">
          <i class="fa-solid fa-file-invoice-dollar"></i>
          <span>New Invoice</span>
        </a>
      </li>
      <li>
        <a href="<?= base_url('modules/invoice/list.php') ?>" class="<?= is_active('invoice/list.php', $current_uri) ?>">
          <i class="fa-solid fa-receipt"></i>
          <span>Invoice History</span>
        </a>
      </li>
      <li>
        <a href="<?= base_url('modules/customers/list.php') ?>" class="<?= is_active('customers/', $current_uri) ?>">
          <i class="fa-solid fa-users"></i>
          <span>Customers & Ledger</span>
        </a>
      </li>
      <li>
        <a href="<?= base_url('modules/items/list.php') ?>" class="<?= is_active('items/list.php', $current_uri) ?>">
          <i class="fa-solid fa-boxes-stacked"></i>
          <span>Item Master</span>
        </a>
      </li>
    </ul>

    <div class="menu-label">Manufacturing & Production</div>
    <ul class="list-unstyled m-0 p-0">
      <li>
        <a href="<?= base_url('modules/working_sheet/lot_entry.php') ?>" class="<?= is_active('lot_entry.php', $current_uri) ?>">
          <i class="fa-solid fa-table-cells"></i>
          <span>Working Sheet (Lot)</span>
        </a>
      </li>
      <li>
        <a href="<?= base_url('modules/working_sheet/lot_list.php') ?>" class="<?= is_active('lot_list.php', $current_uri) ?>">
          <i class="fa-solid fa-list-check"></i>
          <span>All Lots</span>
        </a>
      </li>
      <li>
        <a href="<?= base_url('modules/workers/list.php') ?>" class="<?= is_active('workers/', $current_uri) ?>">
          <i class="fa-solid fa-user-gear"></i>
          <span>Workers & Wages</span>
        </a>
      </li>
    </ul>

    <div class="menu-label">Finance & Analytics</div>
    <ul class="list-unstyled m-0 p-0">
      <li>
        <a href="<?= base_url('modules/personal_ledger/index.php') ?>" class="<?= is_active('personal_ledger/', $current_uri) ?>">
          <i class="fa-solid fa-wallet"></i>
          <span>Personal Ledger</span>
        </a>
      </li>
      <li>
        <a href="<?= base_url('modules/reports/daily.php') ?>" class="<?= is_active('reports/daily.php', $current_uri) ?>">
          <i class="fa-solid fa-chart-pie"></i>
          <span>Sales & Production Reports</span>
        </a>
      </li>
      <li>
        <a href="<?= base_url('modules/reports/wage_analyzer.php') ?>" class="<?= is_active('reports/wage_analyzer.php', $current_uri) ?>">
          <i class="fa-solid fa-calculator"></i>
          <span>Wage Analyzer</span>
        </a>
      </li>
    </ul>

    <div class="menu-label">System Settings</div>
    <ul class="list-unstyled m-0 p-0">
      <li>
        <a href="<?= base_url('modules/settings/backup.php') ?>" class="<?= is_active('settings/backup.php', $current_uri) ?>">
          <i class="fa-solid fa-cloud-arrow-down text-warning"></i>
          <span>System Backup & PDF</span>
        </a>
      </li>
      <li>
        <a href="<?= base_url('modules/settings/theme.php') ?>" class="<?= is_active('settings/theme.php', $current_uri) ?>">
          <i class="fa-solid fa-palette"></i>
          <span>Theme Colors</span>
        </a>
      </li>
      <li>
        <a href="<?= base_url('modules/settings/company_info.php') ?>" class="<?= is_active('settings/company_info.php', $current_uri) ?>">
          <i class="fa-solid fa-building"></i>
          <span>Company Profile</span>
        </a>
      </li>
      <li>
        <a href="<?= base_url('logout.php') ?>" class="text-danger">
          <i class="fa-solid fa-right-from-bracket"></i>
          <span>Sign Out</span>
        </a>
      </li>
    </ul>
  </div>
</aside>
