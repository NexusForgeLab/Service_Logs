<?php
require_once __DIR__ . '/app/layout.php';
$user = require_login();
$pdo  = db();

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) { header('Location:/'); exit; }

$st = $pdo->prepare("SELECT * FROM services WHERE id=?");
$st->execute([$id]);
$r = $st->fetch();
if (!$r) { header('Location:/'); exit; }

// Fetch attached images
$images = $pdo->prepare("SELECT file_path FROM service_images WHERE service_id=?");
$images->execute([$id]);
$imgs = $images->fetchAll(PDO::FETCH_COLUMN);

// Permission Check: Admin OR The Creator (Provider)
$canDelete = (is_admin() || $r['provider_id'] == $user['id']);

render_header("Service " . $r['service_no'], $user);
?>

<div class="card">
  
  <div class="print-only">
      <div class="print-title">Service Report</div>
      <div class="print-id"><?php echo h($r['service_no']); ?></div>
      <div class="print-meta">
          Category: <b><?php echo h(cat_label($r['category'])); ?></b><br>
          Service Provider: <b><?php echo h($r['provider_name']); ?></b><br>
          Machine No: <b><?php echo h($r['machine_number'] ?: 'N/A'); ?></b><br>
          Date: <?php echo h($r['created_at']); ?>
      </div>
  </div>

  <div class="no-print" style="display:flex;justify-content:space-between;align-items:center;gap:10px;flex-wrap:wrap">
    <div>
      <h1><?php echo h($r['service_no']); ?></h1>
      <div class="muted"><?php echo h(cat_label($r['category'])); ?> • Logged by <?php echo h($r['provider_name']); ?> • <?php echo h($r['created_at']); ?></div>
    </div>
    
    <div style="display:flex; gap:8px; flex-wrap:wrap; align-items:center;">
      <a class="btn" href="/service_edit.php?id=<?php echo (int)$r['id']; ?>">Edit</a>
      <a class="btn" href="/service_pdf.php?id=<?php echo (int)$r['id']; ?>">Export PDF</a>
      <button class="btn" onclick="window.print()">Print</button>
      
      <?php if($canDelete): ?>
        <form method="post" action="/service_delete.php" onsubmit="return confirm('Are you sure you want to delete this service? This action cannot be undone.')" style="margin:0;">
            <input type="hidden" name="csrf" value="<?php echo h(csrf_token()); ?>"/>
            <input type="hidden" name="id" value="<?php echo (int)$r['id']; ?>"/>
            <button class="btn danger">Delete</button>
        </form>
      <?php endif; ?>

      <a class="btn" href="/category.php?cat=<?php echo h($r['category']); ?>">Back</a>
    </div>
  </div>

  <hr class="no-print"/>

  <div class="grid">
    <div class="col-12">
      <div class="muted">Title</div>
      <div style="font-size:1.2em; font-weight:bold;"><?php echo h($r['name']); ?></div>
    </div>

    <div class="col-6">
      <div class="muted">Company Details</div>
      <div style="font-weight:bold;"><?php echo h($r['company_name']); ?></div>
      <div><?php echo h($r['company_place']); ?></div>
      <div class="muted" style="margin-top:5px;">Machine: <b><?php echo h($r['machine_number'] ?: 'N/A'); ?></b></div>
    </div>

    <div class="col-6">
      <div class="muted">Contact Info</div>
      <div><b><?php echo h($r['contact_person'] ?: '-'); ?></b></div>
      <div class="muted"><?php echo h($r['company_contact']); ?></div>
    </div>

    <div class="col-3"><div class="muted">Date From</div><div><?php echo h($r['date_from']); ?></div></div>
    <div class="col-3"><div class="muted">Date To</div><div><?php echo h($r['date_to']); ?></div></div>
    <div class="col-3"><div class="muted">Time From</div><div><?php echo h($r['time_from']); ?></div></div>
    <div class="col-3"><div class="muted">Time To</div><div><?php echo h($r['time_to']); ?></div></div>

    <div class="col-3">
        <div class="muted">Issue Nature</div>
        <div><span class="pill" style="background:#fff3e0; color:#e65100;"><?php echo h($r['issue_nature']); ?></span></div>
    </div>
    <div class="col-3">
        <div class="muted">Fixed?</div>
        <div>
            <?php if($r['issue_fixed'] === 'Yes'): ?>
                <span class="pill" style="background:#e8f5e9; color:#2e7d32;">YES</span>
            <?php else: ?>
                <span class="pill" style="background:#ffebee; color:#c62828;">NO</span>
            <?php endif; ?>
        </div>
    </div>
    <div class="col-3"><div class="muted">Expenses</div><div><?php echo h(number_format((float)$r['expenses'],2)); ?></div></div>
    <div class="col-3"><div class="muted">Total Cost</div><div><b><?php echo h(number_format((float)$r['cost'],2)); ?></b></div></div>

    <div class="col-12"><div class="muted">Issue Found</div><div style="white-space:pre-wrap; background:#fafafa; padding:10px; border-radius:6px; border:1px solid #eee;"><?php echo h($r['issue_found']); ?></div></div>
    <div class="col-12"><div class="muted">Solution</div><div style="white-space:pre-wrap; background:#fafafa; padding:10px; border-radius:6px; border:1px solid #eee;"><?php echo h($r['solution']); ?></div></div>
  </div>
  
  <?php if(count($imgs) > 0): ?>
  <hr>
  <h3>Attached Images</h3>
  <div style="display:flex; gap:10px; flex-wrap:wrap;">
      <?php foreach($imgs as $img): ?>
        <a href="<?php echo h($img); ?>" target="_blank">
            <img src="<?php echo h($img); ?>" style="height:100px; width:auto; border-radius:6px; border:1px solid #ddd; padding:2px;">
        </a>
      <?php endforeach; ?>
  </div>
  <?php endif; ?>

  <div class="no-print" style="margin-top:20px">
      <button class="btn" onclick="window.print()">Print</button>
  </div>
</div>
<?php render_footer(); ?>