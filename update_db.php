<?php
require_once __DIR__ . '/app/layout.php';

$user = current_user();
if (!$user) { header('Location: /login.php'); exit; }

$pdo = db();

render_header("Database Update", $user);
?>
<div class="card">
  <h1>Database Schema Update (v3)</h1>
  <div class="muted">Adding Machine Status & Spares fields...</div>
  <hr>
  <div style="font-family:monospace; line-height:1.6;">
    <?php
    // 1. Add machine_status
    try {
        $pdo->exec("ALTER TABLE services ADD COLUMN machine_status TEXT NOT NULL DEFAULT 'Out of Warranty'");
        echo "<div style='color:green'>✔ Added column 'machine_status' to services.</div>";
    } catch (Exception $e) {
        echo "<div style='color:#e65100'>⚠ Column 'machine_status' skipped (likely exists).</div>";
    }

    // 2. Add spares_used
    try {
        $pdo->exec("ALTER TABLE services ADD COLUMN spares_used TEXT NOT NULL DEFAULT ''");
        echo "<div style='color:green'>✔ Added column 'spares_used' to services.</div>";
    } catch (Exception $e) {
        echo "<div style='color:#e65100'>⚠ Column 'spares_used' skipped (likely exists).</div>";
    }
    ?>
  </div>
  <div style="margin-top:20px;">
    <a class="btn" href="/">Return Home</a>
  </div>
</div>
<?php render_footer(); ?>