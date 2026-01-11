<?php
require_once __DIR__ . '/../app/auth.php';
$user = current_user();
if(!$user) { http_response_code(401); exit; }

$pdo = db();
header('Content-Type: application/json');

// --- GET: Fetch Machines for Company ---
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $comp = trim($_GET['company'] ?? '');
    if (!$comp) { echo json_encode([]); exit; }

    $st = $pdo->prepare("SELECT machine_number FROM company_machines WHERE company_name LIKE ? ORDER BY machine_number ASC");
    $st->execute([$comp]); // Exact match preference or LIKE? Prompt implies specific company.
    echo json_encode($st->fetchAll(PDO::FETCH_COLUMN));
    exit;
}

// --- POST: Request Admin to Add ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $comp = trim($_POST['company'] ?? '');
    $mach = trim($_POST['machine'] ?? '');

    if ($comp && $mach) {
        $msg = "User '{$user['display_name']}' requests to add Machine '$mach' for Company '$comp'.";
        $st = $pdo->prepare("INSERT INTO notifications (type, message) VALUES ('machine_request', ?)");
        $st->execute([$msg]);
        echo json_encode(['status' => 'ok']);
    } else {
        http_response_code(400);
        echo json_encode(['error' => 'Missing data']);
    }
    exit;
}
