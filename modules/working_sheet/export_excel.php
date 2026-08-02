<?php
require_once __DIR__ . '/../../config/db.php';
require_login();

$lot_id = isset($_GET['lot_id']) ? (int)$_GET['lot_id'] : 0;

$where = "";
$params = [];
if ($lot_id > 0) {
    $where = "WHERE lw.lot_id = ?";
    $params[] = $lot_id;
}

$sql = "SELECT l.lot_no, lw.date, w.name as worker_name, lw.work_type, lw.rate_per_piece, lw.qty_target, lw.qty_done, lw.total_amount 
        FROM lot_work lw 
        INNER JOIN lots l ON lw.lot_id = l.id 
        INNER JOIN workers w ON lw.worker_id = w.id 
        $where 
        ORDER BY lw.date DESC, l.id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

$filename = "Lot_Working_Sheet_" . date('Y-m-d_His') . ".csv";

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=' . $filename);

$output = fopen('php://output', 'w');

// Output UTF-8 BOM for Excel
fputs($output, "\xEF\xBB\xBF");

// Write CSV Headings
fputcsv($output, ['Lot No', 'Date', 'Worker Name', 'Work Type', 'Rate/Piece (Rs.)', 'Qty Target', 'Qty Done', 'Total Amount (Rs.)']);

foreach ($rows as $r) {
    fputcsv($output, [
        $r['lot_no'],
        $r['date'],
        $r['worker_name'],
        $r['work_type'],
        $r['rate_per_piece'],
        $r['qty_target'],
        $r['qty_done'],
        $r['total_amount']
    ]);
}

fclose($output);
exit();
