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
$pdf->addCenter("SERVICE REPORT", 20, true);
$pdf->addSpace(10);
$pdf->addCenter($r['service_no'], 30, true); // Big Service Number
$pdf->addSpace(5);
$pdf->addCenter("Category: " . cat_label($r['category']), 12, false);
$pdf->addSpace(15);
$pdf->addLine();

// --- PROVIDER INFO ---
$pdf->addText("SERVICE PROVIDER", 10, false);
$pdf->addText($r['provider_name'], 14, true);
$pdf->addText("Logged on: " . $r['created_at'], 10, false);
$pdf->addSpace(15);

// --- CLIENT INFO ---
$pdf->addText("CLIENT / COMPANY", 10, false);
$pdf->addText($r['company_name'], 14, true);
$pdf->addText("Location: " . $r['company_place'], 12, false);
if (!empty($r['contact_person'])) {
    $pdf->addText("Contact Person: " . $r['contact_person'], 12, false);
}
$pdf->addText("Contact Details: " . $r['company_contact'], 12, false);
$pdf->addSpace(15);
$pdf->addLine();

// --- JOB DETAILS ---
$pdf->addText("JOB DETAILS", 10, false);
$pdf->addText($r['name'], 12, true);
$pdf->addSpace(5);
$pdf->addText("Duration: " . $r['date_from'] . " (" . $r['time_from'] . ")  TO  " . $r['date_to'] . " (" . $r['time_to'] . ")", 11, false);

$pdf->addSpace(5);
$pdf->addText("Expenses: " . number_format((float)$r['expenses'], 2) . "   Total Cost: " . number_format((float)$r['cost'], 2), 12, true);
$pdf->addSpace(15);

// --- FINDINGS ---
$pdf->addText("ISSUE FOUND / NATURE", 10, false);
$pdf->addText("Nature: " . $r['issue_nature'] . "  |  Fixed: " . $r['issue_fixed'], 12, true);
foreach (preg_split("/\r?\n/", (string)$r['issue_found']) as $ln) $pdf->addText("  " . $ln, 11, false);
$pdf->addSpace(15);

// --- SOLUTION ---
$pdf->addText("SOLUTION / ACTION TAKEN", 10, false);
foreach (preg_split("/\r?\n/", (string)$r['solution']) as $ln) $pdf->addText("  " . $ln, 11, false);

// Output
$pdf->output($r['service_no'] . '.pdf');