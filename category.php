<?php
require_once __DIR__ . '/app/layout.php';
$user = require_login();
$pdo  = db();

$cat = strtoupper(trim($_GET['cat'] ?? ''));
if (!in_array($cat, ['MECH','ELEC','APP'], true)) { header('Location:/'); exit; }

$st=$pdo->prepare("SELECT * FROM services WHERE category=? ORDER BY created_at DESC LIMIT 500");
$st->execute([$cat]);
$rows=$st->fetchAll();

render_header(cat_label($cat)." Services",$user);
?>
<div class="card">
  <h1><?php echo h(cat_label($cat)); ?> Services</h1>
  <div class="no-print" style="display:flex;gap:10px;flex-wrap:wrap;margin-top:10px">
    <a class="btn" href="/service_new.php?cat=<?php echo h($cat); ?>">+ New Entry</a>
    <a class="btn" href="/search.php?cat=<?php echo h($cat); ?>">Search</a>
  </div>
</div>

<div class="card">
  <?php if(!count($rows)): ?>
    <div class="muted">No entries yet.</div>
  <?php else: ?>
    <div class="table-responsive">
        <table>
          <thead><tr>
            <th>Service No</th><th>Company</th><th>Provider</th><th>Date</th><th>Cost</th><th class="no-print">Action</th>
          </tr></thead>
          <tbody>
          <?php foreach($rows as $r): ?>
            <tr>
              <td><span class="pill"><?php echo h($r['service_no']); ?></span></td>
              <td><?php echo h($r['company_name']); ?><div class="muted"><?php echo h($r['company_place']); ?></div></td>
              <td><?php echo h($r['provider_name']); ?></td>
              <td><?php echo h($r['date_from']); ?> → <?php echo h($r['date_to']); ?></td>
              <td><?php echo h(number_format((float)$r['cost'],2)); ?></td>
              <td class="no-print">
                <div style="display:flex; gap:6px; flex-wrap:wrap;">
                    <a class="btn" href="/service_view.php?id=<?php echo (int)$r['id']; ?>" style="white-space:nowrap;">View</a>
                    
                    <?php if(is_admin()): ?>
                        <form method="post" action="/service_delete.php" onsubmit="return confirm('Delete Service <?php echo h($r['service_no']); ?>? This cannot be undone.')" style="margin:0;">
                            <input type="hidden" name="csrf" value="<?php echo h(csrf_token()); ?>"/>
                            <input type="hidden" name="id" value="<?php echo (int)$r['id']; ?>"/>
                            <button class="btn danger" style="padding:8px 10px;" title="Delete Service">Del</button>
                        </form>
                    <?php endif; ?>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
    </div>
  <?php endif; ?>
</div>
<?php render_footer(); ?>