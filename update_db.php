<?php
require_once __DIR__ . '/app/layout.php';

$user = current_user();
if (!$user) { header('Location: /login.php'); exit; }

$pdo = db();

render_header("Database Update", $user);
?>
<div class="card">
  <h1>Database Schema Update (v2)</h1>
  <div class="muted">Adding Machine Number support...</div>
  <hr>
  <div style="font-family:monospace; line-height:1.6;">
    <?php
    // 1. Add machine_number to services
    try {
        $pdo->exec("ALTER TABLE services ADD COLUMN machine_number TEXT NOT NULL DEFAULT ''");
        echo "<div style='color:green'>✔ Added column 'machine_number' to services.</div>";
    } catch (Exception $e) {
        echo "<div style='color:#e65100'>⚠ Column 'machine_number' skipped (likely exists).</div>";
    }

    // 2. Create company_machines table
    try {
        $sql = "CREATE TABLE IF NOT EXISTS company_machines (
          id INTEGER PRIMARY KEY AUTOINCREMENT,
          company_name TEXT NOT NULL,
          machine_number TEXT NOT NULL,
          created_at TEXT NOT NULL DEFAULT (datetime('now')),
          UNIQUE(company_name, machine_number)
        )";
        $pdo->exec($sql);
        echo "<div style='color:green'>✔ Table 'company_machines' ready.</div>";
    } catch (Exception $e) {
        echo "<div style='color:red'>✘ Error: " . htmlspecialchars($e->getMessage()) . "</div>";
    }

    // 3. Create notifications table
    try {
        $sql = "CREATE TABLE IF NOT EXISTS notifications (
          id INTEGER PRIMARY KEY AUTOINCREMENT,
          type TEXT NOT NULL,
          message TEXT NOT NULL,
          is_read INTEGER NOT NULL DEFAULT 0,
          created_at TEXT NOT NULL DEFAULT (datetime('now'))
        )";
        $pdo->exec($sql);
        echo "<div style='color:green'>✔ Table 'notifications' ready.</div>";
    } catch (Exception $e) {
        echo "<div style='color:red'>✘ Error: " . htmlspecialchars($e->getMessage()) . "</div>";
    }
    ?>
  </div>
  <div style="margin-top:20px;">
    <a class="btn" href="/">Return Home</a>
  </div>
</div>
<?php render_footer(); ?>
