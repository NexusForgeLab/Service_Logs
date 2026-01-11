<?php
require_once __DIR__ . '/app/layout.php';
$user = require_admin(); // Restricted to Admin
$pdo  = db();

$err = '';
$ok  = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  csrf_check();
  $action = $_POST['action'] ?? 'create';

  // --- CREATE USER ---
  if ($action === 'create') {
      $username = strtolower(trim($_POST['username'] ?? ''));
      $display  = trim($_POST['display_name'] ?? '');
      $pass     = $_POST['password'] ?? '';

      if ($username === '' || $display === '' || $pass === '') {
        $err = 'Fill all fields.';
      } elseif (!preg_match('/^[a-z0-9_.-]{3,30}$/', $username)) {
        $err = 'Username must be 3-30 chars: a-z 0-9 _ . -';
      } elseif (strlen($pass) < 6) {
        $err = 'Password must be at least 6 characters.';
      } else {
        try {
          $hash = password_hash($pass, PASSWORD_DEFAULT);
          $st = $pdo->prepare("INSERT INTO users(username, pass_hash, display_name) VALUES(?,?,?)");
          $st->execute([$username, $hash, $display]);
          $ok = 'User created.';
        } catch (Exception $e) {
          $err = 'Could not create user (username may already exist).';
        }
      }
  }
  // --- DELETE USER ---
  elseif ($action === 'delete') { // Added Delete Logic
      $del_id = (int)($_POST['user_id'] ?? 0);
      // Prevent deleting self (admin)
      $check = $pdo->prepare("SELECT username FROM users WHERE id=?");
      $check->execute([$del_id]);
      $u_name = $check->fetchColumn();

      if ($u_name === 'admin') {
          $err = 'Cannot delete the main admin user.';
      } elseif ($u_name) {
          $pdo->prepare("DELETE FROM users WHERE id=?")->execute([$del_id]);
          $ok = 'User deleted.';
      }
  }
  // --- RESET PASSWORD ---
  elseif ($action === 'reset_pass') { // Added Reset Logic
      $uid  = (int)($_POST['user_id'] ?? 0);
      $pass = $_POST['new_pass'] ?? '';
      
      if (strlen($pass) < 6) {
          $err = 'Password must be at least 6 characters.';
      } else {
          $hash = password_hash($pass, PASSWORD_DEFAULT);
          $pdo->prepare("UPDATE users SET pass_hash=? WHERE id=?")->execute([$hash, $uid]);
          $ok = 'Password reset successfully.';
      }
  }
}

$rows = $pdo->query("SELECT id, username, display_name, created_at FROM users ORDER BY id DESC")->fetchAll();

render_header('User Management', $user);
?>
<div class="card">
  <h1>User Management</h1>
  <div class="muted">Admin Panel: Add, delete, or manage users.</div>
  <div class="no-print" style="margin-top:10px;display:flex;gap:10px;flex-wrap:wrap">
    <a class="btn" href="/password.php">Change My Password</a>
  </div>
</div>

<?php if($err): ?><div class="card" style="color:var(--pop-red); border-color:var(--pop-red);"><?php echo h($err); ?></div><?php endif; ?>
<?php if($ok): ?><div class="card" style="color:var(--pop-green); border-color:var(--pop-green);"><?php echo h($ok); ?></div><?php endif; ?>

<div class="card">
  <h2>Add User</h2>
  <form method="post" class="grid">
    <input type="hidden" name="csrf" value="<?php echo h(csrf_token()); ?>"/>
    <input type="hidden" name="action" value="create"/>
    <div class="col-4">
      <div class="muted">Username *</div>
      <input name="username" placeholder="e.g. tejas" required />
    </div>
    <div class="col-4">
      <div class="muted">Display Name *</div>
      <input name="display_name" placeholder="e.g. Tejas Holla" required />
    </div>
    <div class="col-4">
      <div class="muted">Password *</div>
      <input type="password" name="password" required />
    </div>
    <div class="col-12">
      <button class="btn" type="submit">Create User</button>
    </div>
  </form>
</div>

<div class="card">
  <h2>Users</h2>
  <div class="table-responsive">
    <table>
      <thead><tr><th>ID</th><th>Username</th><th>Display Name</th><th>Actions</th></tr></thead>
      <tbody>
      <?php foreach($rows as $r): ?>
        <tr>
          <td><?php echo (int)$r['id']; ?></td>
          <td><span class="pill"><?php echo h($r['username']); ?></span></td>
          <td><?php echo h($r['display_name']); ?></td>
          <td>
            <?php if($r['username'] !== 'admin'): ?>
              <div style="display:flex; gap:6px;">
                 <form method="post" style="display:inline; display:flex; gap:4px; align-items:center;" onsubmit="return confirm('Reset password for <?php echo h($r['username']); ?>?')">
                    <input type="hidden" name="csrf" value="<?php echo h(csrf_token()); ?>"/>
                    <input type="hidden" name="action" value="reset_pass"/>
                    <input type="hidden" name="user_id" value="<?php echo $r['id']; ?>"/>
                    <input name="new_pass" placeholder="New Pass" style="width:100px; padding:6px; font-size:12px; margin:0;" required/>
                    <button class="btn" style="padding:6px 10px; font-size:11px;">Reset</button>
                 </form>

                 <form method="post" style="display:inline;" onsubmit="return confirm('Delete user <?php echo h($r['username']); ?>? This cannot be undone.')">
                    <input type="hidden" name="csrf" value="<?php echo h(csrf_token()); ?>"/>
                    <input type="hidden" name="action" value="delete"/>
                    <input type="hidden" name="user_id" value="<?php echo $r['id']; ?>"/>
                    <button class="btn danger" style="padding:6px 10px; font-size:11px;">Del</button>
                 </form>
              </div>
            <?php else: ?>
              <span class="muted">Main Admin</span>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php render_footer(); ?>