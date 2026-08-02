<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Invoice #<?= htmlspecialchars($invoice_no) ?></title>
  <style>
    body { font-family: 'Helvetica Neue', Arial, sans-serif; font-size: 14px; color: #333; margin: 30px; }
    .invoice-box { max-width: 800px; margin: auto; padding: 20px; border: 1px solid #eee; }
    .header { text-align: center; border-bottom: 2px solid #1e3c72; padding-bottom: 15px; margin-bottom: 20px; }
    .company-logo { max-height: 80px; margin-bottom: 8px; }
    .company-title { font-size: 24px; font-weight: bold; color: #1e3c72; }
    .meta-table, .items-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
    .items-table th { background: #f4f6f9; border: 1px solid #ddd; padding: 8px; text-align: left; }
    .items-table td { border: 1px solid #ddd; padding: 8px; }
    .text-end { text-align: right; }
    .fw-bold { font-weight: bold; }
  </style>
</head>
<body>
<div class="invoice-box">
  <div class="header">
    <?php if (!empty($settings['logo']) && file_exists(__DIR__ . '/../../' . $settings['logo'])): ?>
      <img src="<?= base_url($settings['logo']) ?>" class="company-logo" alt="Logo">
    <?php endif; ?>
    <div class="company-title"><?= htmlspecialchars($settings['company_name']) ?></div>
    <div><?= htmlspecialchars($settings['address']) ?> | Phone: <?= htmlspecialchars($settings['phone']) ?></div>
    <h3 style="margin-top:10px;">SALES INVOICE</h3>
  </div>

  <table class="meta-table">
    <tr>
      <td><strong>Invoice No:</strong> <?= htmlspecialchars($invoice_no) ?></td>
      <td class="text-end"><strong>Date:</strong> <?= date('d-M-Y', strtotime($date)) ?></td>
    </tr>
    <tr>
      <td colspan="2"><strong>Customer ID/Name:</strong> #<?= $customer_id ?></td>
    </tr>
  </table>

  <table class="items-table">
    <thead>
      <tr>
        <th>#</th>
        <th>Item Description</th>
        <th class="text-end">Price</th>
        <th class="text-end">Qty</th>
        <th class="text-end">Total</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($valid_items as $idx => $it): ?>
        <tr>
          <td><?= $idx + 1 ?></td>
          <td><?= htmlspecialchars($it['item_name']) ?></td>
          <td class="text-end">Rs. <?= number_format($it['price'], 2) ?></td>
          <td class="text-end"><?= $it['qty'] ?></td>
          <td class="text-end">Rs. <?= number_format($it['total'], 2) ?></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>

  <table style="width: 40%; margin-left: auto;" class="items-table">
    <tr><td>Sub Total:</td><td class="text-end">Rs. <?= number_format($total_amount, 2) ?></td></tr>
    <tr><td>Discount:</td><td class="text-end">Rs. <?= number_format($discount, 2) ?></td></tr>
    <tr class="fw-bold"><td>Grand Total:</td><td class="text-end">Rs. <?= number_format($grand_total, 2) ?></td></tr>
    <tr><td>Paid Amount:</td><td class="text-end">Rs. <?= number_format($paid_amount, 2) ?></td></tr>
    <tr class="fw-bold"><td>Balance:</td><td class="text-end">Rs. <?= number_format($balance, 2) ?></td></tr>
  </table>
</div>
</body>
</html>
