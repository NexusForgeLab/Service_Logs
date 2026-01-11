<?php
require_once __DIR__ . '/app/layout.php';
$pdo = db();
$sql = file_get_contents(__DIR__ . '/sql/init.sql');

try {
  $pdo->exec($sql);

  $count = (int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
  if ($count === 0) {
    $hash = password_hash("admin123", PASSWORD_DEFAULT);
    $pdo->prepare("INSERT INTO users(username, pass_hash, display_name) VALUES(?,?,?)")
        ->execute(["admin", $hash, "Admin"]);
  }

  render_header('Installed');
  echo "<div class='card'>
    <h1>✅ Installed</h1>
    <div class='muted'>DB: <code>".h(SQLITE_PATH)."</code></div>
    <div class='muted' style='margin-top:10px'>Default login:</div>
    <div style='display:flex;gap:10px;flex-wrap:wrap;margin-top:6px'>
      <span class='pill'>admin</span>
      <span class='pill'>admin123</span>
    </div>
    <div class='muted' style='margin-top:12px'>Delete <code>install.php</code> after this.</div>
    <a class='btn' href='/login.php'>Go to Login</a>
  </div>";
  render_footer();
} catch (Exception $e) {
  render_header('Install Error');
  echo "<div class='card'><h1>Install Error</h1><div class='muted'>".h($e->getMessage())."</div></div>";
  render_footer();
}
