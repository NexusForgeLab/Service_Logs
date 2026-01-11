<?php
declare(strict_types=1);
require_once __DIR__ . '/db.php';

function current_user(): ?array { return $_SESSION['user'] ?? null; }

function require_login(): array {
  $u = current_user();
  if (!$u) { header('Location: /login.php'); exit; }
  return $u;
}

// Added Admin checks
function is_admin(): bool {
  $u = current_user();
  // Fixed: explicitly check $u['username']
  return ($u && $u['username'] === 'admin');
}

function require_admin(): array {
  $u = require_login();
  if ($u['username'] !== 'admin') {
    http_response_code(403);
    echo "<h1>Access Denied</h1><p>Only 'admin' can access this page.</p><a href='/'>Go Home</a>";
    exit;
  }
  return $u;
}

function csrf_token(): string {
  if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(16));
  return $_SESSION['csrf'];
}

function csrf_check(): void {
  $t = $_POST['csrf'] ?? '';
  if (!$t || !hash_equals($_SESSION['csrf'] ?? '', $t)) {
    http_response_code(400);
    echo "Bad CSRF token";
    exit;
  }
}

function h(string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }

function cat_label(string $c): string {
  return match($c){
    'MECH' => 'Mechanical',
    'ELEC' => 'Electrical',
    'APP'  => 'Application',
    default => $c
  };
}