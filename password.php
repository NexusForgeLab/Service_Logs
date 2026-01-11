<?php
require_once __DIR__ . '/app/layout.php';
$user = require_admin(); // Restricted to Admin
$pdo  = db();

$err=''; $ok='';

if($_SERVER['REQUEST_METHOD']==='POST'){
  csrf_check();
  $current = $_POST['current_password'] ?? '';
  $new1 = $_POST['new_password'] ?? '';
  $new2 = $_POST['new_password2'] ?? '';

  if($new1 !== $new2) $err='New passwords do not match.';
  elseif(strlen($new1) < 6) $err='New password must be at least 6 characters.';
  else {
    $st=$pdo->prepare("SELECT pass_hash FROM users WHERE id=?");
    $st->execute([$user['id']]);
    $hash=$st->fetchColumn();

    if(!$hash || !password_verify($current, $hash)){
      $err='Current password is wrong.';
    } else {
      $nh = password_hash($new1, PASSWORD_DEFAULT);
      $pdo->prepare("UPDATE users SET pass_hash=? WHERE id=?")->execute([$nh, $user['id']]);
      $ok='Password updated.';
    }
  }
}

render_header('Change Password', $user);
?>
<div class="card">
  <h1>Change Password</h1>
  <div class="muted">Change the admin password.</div>
  <div class="no-print" style="margin-top:10px;display:flex;gap:10px;flex-wrap:wrap">
    <a class="btn" href="/users.php">User Management</a>
  </div>
</div>

<?php if($err): ?><div class="card" style="color:var(--pop-red); border-color:var(--pop-red);"><?php echo h($err); ?></div><?php endif; ?>
<?php if($ok): ?><div class="card" style="color:var(--pop-green); border-color:var(--pop-green);"><?php echo h($ok); ?></div><?php endif; ?>

<div class="card">
  <form method="post" class="grid">
    <input type="hidden" name="csrf" value="<?php echo h(csrf_token()); ?>"/>
    <div class="col-4">
      <div class="muted">Current Password</div>
      <input type="password" name="current_password" required />
    </div>
    <div class="col-4">
      <div class="muted">New Password</div>
      <input type="password" name="new_password" required />
    </div>
    <div class="col-4">
      <div class="muted">Repeat New Password</div>
      <input type="password" name="new_password2" required />
    </div>
    <div class="col-12">
      <button class="btn" type="submit">Update Password</button>
    </div>
  </form>
</div>

<?php render_footer(); ?>