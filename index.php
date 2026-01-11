<?php
require_once __DIR__ . '/app/layout.php';
$user = current_user();
if (!$user) { header('Location: /login.php'); exit; }

render_header('Home', $user);
?>
<div class="card">
  <h1>New Service Entry</h1>
  <div class="muted">Select category. Each category has its own service number.</div>
  <div class="no-print" style="margin-top:12px;display:flex;gap:10px;flex-wrap:wrap">
    <a class="btn" href="/service_new.php?cat=MECH">Mechanical</a>
    <a class="btn" href="/service_new.php?cat=ELEC">Electrical</a>
    <a class="btn" href="/service_new.php?cat=APP">Application</a>
  </div>
</div>

<div class="card">
  <h2>Browse</h2>
  <div class="no-print" style="margin-top:12px;display:flex;gap:10px;flex-wrap:wrap">
    <a class="btn" href="/category.php?cat=MECH">Mechanical Services</a>
    <a class="btn" href="/category.php?cat=ELEC">Electrical Services</a>
    <a class="btn" href="/category.php?cat=APP">Application Services</a>
  </div>
</div>
<?php render_footer(); ?>
