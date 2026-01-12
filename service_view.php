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

<div class="card no-print">
  <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px;">
    <div>
      <h1><?php echo h($r['service_no']); ?></h1>
      <div class="muted"><?php echo h(cat_label($r['category'])); ?> • Logged by <?php echo h($r['provider_name']); ?> • <?php echo h($r['created_at']); ?></div>
    </div>
    
    <div style="display:flex; gap:8px; flex-wrap:wrap; align-items:center;">
      <a class="btn" href="/service_edit.php?id=<?php echo (int)$r['id']; ?>">Edit</a>
      <a class="btn" href="/service_pdf.php?id=<?php echo (int)$r['id']; ?>">Export PDF</a>
      <button class="btn" style="background:var(--pop-cyan); color:white;" onclick="window.print()">🖨️ Print Bill</button>
      
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
</div>

<div class="grid no-print">
    <div class="col-12">
      <div class="card">
          <div class="muted">Title</div>
          <div style="font-size:1.2em; font-weight:bold;"><?php echo h($r['name']); ?></div>
      </div>
    </div>

    <div class="col-6">
        <div class="card">
          <div class="muted">Company Details</div>
          <div style="font-weight:bold;"><?php echo h($r['company_name']); ?></div>
          <div><?php echo h($r['company_place']); ?></div>
          
          <div class="muted" style="margin-top:10px; border-top:1px dashed #ddd; padding-top:5px;">Machine Info</div>
          <div style="display:flex; gap:10px; align-items:center;">
              <b><?php echo h($r['machine_number'] ?: 'N/A'); ?></b>
              <span class="pill"><?php echo h($r['machine_status'] ?? 'Out of Warranty'); ?></span>
          </div>
        </div>
    </div>

    <div class="col-6">
        <div class="card">
          <div class="muted">Contact Info</div>
          <div><b><?php echo h($r['contact_person'] ?: '-'); ?></b></div>
          <div class="muted"><?php echo h($r['company_contact']); ?></div>
          
          <div class="muted" style="margin-top:10px; border-top:1px dashed #ddd; padding-top:5px;">Timings</div>
          <div><?php echo h($r['date_from']); ?> to <?php echo h($r['date_to']); ?></div>
          <div class="muted"><?php echo h($r['time_from']); ?> - <?php echo h($r['time_to']); ?></div>
        </div>
    </div>

    <div class="col-3">
        <div class="card" style="text-align:center;">
            <div class="muted">Issue Nature</div>
            <span class="pill" style="background:#fff3e0; color:#e65100;"><?php echo h($r['issue_nature']); ?></span>
        </div>
    </div>
    <div class="col-3">
        <div class="card" style="text-align:center;">
            <div class="muted">Fixed?</div>
            <?php if($r['issue_fixed'] === 'Yes'): ?>
                <span class="pill" style="background:#e8f5e9; color:#2e7d32;">YES</span>
            <?php else: ?>
                <span class="pill" style="background:#ffebee; color:#c62828;">NO</span>
            <?php endif; ?>
        </div>
    </div>
    <div class="col-3">
        <div class="card" style="text-align:center;">
            <div class="muted">Expenses</div>
            <div><?php echo h(number_format((float)$r['expenses'],2)); ?></div>
        </div>
    </div>
    <div class="col-3">
        <div class="card" style="text-align:center;">
            <div class="muted">Total Cost</div>
            <div style="font-weight:bold; font-size:1.2em;"><?php echo h(number_format((float)$r['cost'],2)); ?></div>
        </div>
    </div>

    <div class="col-12">
        <div class="card">
            <div class="muted">Spares Used</div>
            <div style="background:#fffbe6; color:#5d4037; padding:10px; border-radius:6px; border:1px solid #efebe9;">
                <?php echo !empty($r['spares_used']) ? h($r['spares_used']) : '<span class="muted">No spares recorded.</span>'; ?>
            </div>
        </div>
    </div>

    <div class="col-12">
        <div class="card">
            <div class="muted">Issue Found</div>
            <div style="white-space:pre-wrap; background:#fafafa; padding:10px; border-radius:6px; border:1px solid #eee;"><?php echo h($r['issue_found']); ?></div>
        </div>
    </div>

    <div class="col-12">
        <div class="card">
            <div class="muted">Solution</div>
            <div style="white-space:pre-wrap; background:#fafafa; padding:10px; border-radius:6px; border:1px solid #eee;"><?php echo h($r['solution']); ?></div>
        </div>
    </div>
    
    <?php if(count($imgs) > 0): ?>
    <div class="col-12">
        <div class="card">
            <div class="muted">Attached Images</div>
            <div style="display:flex; gap:10px; flex-wrap:wrap; margin-top:10px;">
                <?php foreach($imgs as $img): ?>
                    <a href="<?php echo h($img); ?>" target="_blank">
                        <img src="<?php echo h($img); ?>" style="height:100px; width:auto; border-radius:6px; border:1px solid #ddd; padding:2px;">
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<div class="invoice-box">
    <div class="invoice-header">
        <div>
            <img src="/assets/Sinar_logo_transparent_bg.png" class="invoice-logo" alt="SINAR LOGO" style="max-height:80px;">
        </div>
        <div class="header-right">
            <b style="font-size:14px;">SINAR SHEETMETAL SOLUTIONS PVT LTD</b><br>
            Plot No.31,2nd Phase, Opp Paragon outlet, Peenya Industrial Area<br>
            Bangalore, Karnataka - 560058<br>
            Contact: +91 98801 89838<br>
            Email: service@sinarsolution.com
        </div>
    </div>

    <div class="invoice-title">Service Report / Bill</div>

    <div class="info-grid">
        <div class="info-col">
            <div class="info-label">Billed To (Client):</div>
            <div class="info-val"><?php echo h($r['company_name']); ?></div>
            <div><?php echo h($r['company_place']); ?></div>
            <div><?php echo h($r['company_contact']); ?></div>
            <div>Attn: <?php echo h($r['contact_person']); ?></div>
        </div>
        <div class="info-col">
            <div class="info-label">Service Details:</div>
            <div style="display:flex; justify-content:space-between; margin-bottom:4px;"><span>Report No:</span> <b><?php echo h($r['service_no']); ?></b></div>
            <div style="display:flex; justify-content:space-between; margin-bottom:4px;"><span>Date:</span> <?php echo h(date('d-M-Y', strtotime($r['date_from']))); ?></div>
            <div style="display:flex; justify-content:space-between; margin-bottom:4px;"><span>Category:</span> <?php echo h(cat_label($r['category'])); ?></div>
            <div style="display:flex; justify-content:space-between; margin-bottom:4px;"><span>Tech:</span> <?php echo h($r['provider_name']); ?></div>
        </div>
    </div>

    <table class="invoice-table">
        <thead>
            <tr>
                <th width="25%">Machine No</th>
                <th width="20%">Status</th>
                <th width="20%">Nature</th>
                <th width="35%">Spares Used</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="text-center"><b><?php echo h($r['machine_number'] ?: '-'); ?></b></td>
                <td class="text-center"><?php echo h($r['machine_status'] ?? 'Out of Warranty'); ?></td>
                <td class="text-center"><?php echo h($r['issue_nature']); ?></td>
                <td><?php echo h($r['spares_used'] ?: 'None'); ?></td>
            </tr>
        </tbody>
    </table>

    <div class="info-label">ISSUE OBSERVED:</div>
    <div class="desc-box"><?php echo h($r['issue_found']); ?></div>

    <div class="info-label">ACTION TAKEN / SOLUTION:</div>
    <div class="desc-box"><?php echo h($r['solution']); ?></div>

    <div style="display:flex; justify-content:flex-end; margin-top:20px;">
        <table class="invoice-table" style="width:50%;">
            <tr>
                <td style="border:none; border-bottom:1px solid #ddd;">Service Charges / Expenses</td>
                <td class="text-right" style="border:none; border-bottom:1px solid #ddd;"><?php echo number_format((float)$r['expenses'], 2); ?></td>
            </tr>
            <tr>
                <td style="font-weight:bold; background:#eee; -webkit-print-color-adjust: exact;">TOTAL AMOUNT</td>
                <td class="text-right" style="font-weight:bold; background:#eee; -webkit-print-color-adjust: exact;">
                    <?php echo number_format((float)$r['cost'], 2); ?>
                </td>
            </tr>
        </table>
    </div>

    <div class="invoice-footer">
        <div class="sign-box">
            Customer Signature<br>
            (Seal & Sign)
        </div>
        <div class="sign-box">
            For Sinar Sheetmetal Solutions<br>
            (Authorized Signatory)
        </div>
    </div>
</div>

<?php if(count($imgs) > 0): ?>
<div class="invoice-box" style="page-break-before: always;">
  <h3>Attached Images</h3>
  <div style="display:flex; gap:10px; flex-wrap:wrap;">
      <?php foreach($imgs as $img): ?>
        <img src="<?php echo h($img); ?>" style="max-width:300px; max-height:200px; border:1px solid #ddd;">
      <?php endforeach; ?>
  </div>
</div>
<?php endif; ?>

<?php render_footer(); ?>