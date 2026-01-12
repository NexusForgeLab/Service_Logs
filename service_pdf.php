<?php
require_once __DIR__ . '/app/layout.php';
require_once __DIR__ . '/app/pdf.php';
$user = require_login();
$pdo  = db();

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) { header('Location:/'); exit; }

$st = $pdo->prepare("SELECT * FROM services WHERE id=?");
$st->execute([$id]);
$r = $st->fetch();
if (!$r) { header('Location:/'); exit; }

$pdf = new SimplePDF();

// --- HEADER ---
$pdf->addCenter("SERVICE INVOICE", 18, true);
$pdf->addSpace(5);
$pdf->addCenter("Sinar Sheetmetal Solutions Pvt Ltd", 12, true);
$pdf->addLine();

// --- META INFO ---
$pdf->addText("Invoice No: " . $r['service_no'], 12, true);
$pdf->addText("Date:       " . date('d-M-Y', strtotime($r['created_at'])), 10, false);
$pdf->addText("Category:   " . cat_label($r['category']), 10, false);
$pdf->addSpace(10);

// --- CLIENT INFO ---
$pdf->addText("BILLED TO:", 10, true);
$pdf->addText($r['company_name'], 12, false);
$pdf->addText("Location: " . $r['company_place'], 10, false);
if (!empty($r['contact_person'])) {
    $pdf->addText("Contact:  " . $r['contact_person'], 10, false);
}
$pdf->addText("Phone:    " . $r['company_contact'], 10, false);
$pdf->addSpace(15);

// --- MACHINE INFO ---
$pdf->addText("MACHINE DETAILS:", 10, true);
$pdf->addText("Machine No: " . ($r['machine_number'] ?: 'N/A'), 11, false);
$pdf->addText("Status:     " . ($r['machine_status'] ?? 'Out of Warranty'), 11, false);
$pdf->addSpace(5);

if(!empty($r['spares_used'])) {
    $pdf->addText("Spares Used:", 10, true);
    $pdf->addText("  " . $r['spares_used'], 10, false);
} else {
    $pdf->addText("Spares Used: None", 10, false);
}
$pdf->addSpace(15);

// --- ISSUE & SOLUTION ---
$pdf->addText("ISSUE FOUND:", 10, true);
// Basic word wrap simulation for the PDF class
foreach (preg_split("/\r?\n/", wordwrap($r['issue_found'], 75)) as $ln) {
    $pdf->addText("  " . $ln, 10, false);
}
$pdf->addSpace(10);

$pdf->addText("SOLUTION / ACTION TAKEN:", 10, true);
foreach (preg_split("/\r?\n/", wordwrap($r['solution'], 75)) as $ln) {
    $pdf->addText("  " . $ln, 10, false);
}
$pdf->addSpace(15);

$pdf->addLine();

// --- FOOTER / COSTS ---
$pdf->addText("Service/Expenses: " . number_format((float)$r['expenses'], 2), 11, false);
$pdf->addText("TOTAL AMOUNT:     " . number_format((float)$r['cost'], 2), 14, true);

$pdf->addSpace(30);
$pdf->addText("__________________________                __________________________", 10, false);
$pdf->addText("Customer Signature                        Authorized Signatory", 10, false);

// Output
$pdf->output($r['service_no'] . '.pdf');
?>