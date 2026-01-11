<?php
declare(strict_types=1);
require_once __DIR__ . '/../app/auth.php';
$user = require_login();
$pdo = db();

$q = trim($_GET['q'] ?? '');
$qLike = '%'.$q.'%';

// Query Logic:
// 1. Get companies from 'company_machines' (Priority = 100)
// 2. Get companies from 'services' history (Priority = 1)
// 3. Combine, Group by Name, and Order by Score.
$sql = "
SELECT company_name FROM (
    SELECT company_name, 100 as score FROM company_machines
    UNION ALL
    SELECT company_name, 1 as score FROM services
)
WHERE company_name LIKE ?
GROUP BY company_name
ORDER BY SUM(score) DESC, company_name ASC
LIMIT 15
";

$st = $pdo->prepare($sql);
$st->execute([$qLike]);
$rows = $st->fetchAll(PDO::FETCH_COLUMN);

header('Content-Type: application/json');
echo json_encode($rows);