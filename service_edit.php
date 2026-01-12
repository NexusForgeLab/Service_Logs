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

$err = '';
$msg = '';

// --- HANDLE IMAGE DELETION (Existing Images) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_img_id'])) {
    csrf_check();
    $delId = (int)$_POST['delete_img_id'];
    
    // Verify image belongs to this service
    $chk = $pdo->prepare("SELECT file_path FROM service_images WHERE id=? AND service_id=?");
    $chk->execute([$delId, $id]);
    $imgRow = $chk->fetch();
    
    if ($imgRow) {
        // 1. Delete file from disk
        $fullPath = __DIR__ . '/' . $imgRow['file_path'];
        if (file_exists($fullPath)) {
            unlink($fullPath);
        }
        // 2. Delete from DB
        $pdo->prepare("DELETE FROM service_images WHERE id=?")->execute([$delId]);
        
        // Redirect to prevent resubmission
        header("Location: /service_edit.php?id=" . $id);
        exit;
    }
}

// --- HANDLE SERVICE UPDATE ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['delete_img_id'])) {
  csrf_check();

  $provider_name = trim($_POST['provider_name'] ?? '');
  $name = trim($_POST['name'] ?? '');
  $date_from = trim($_POST['date_from'] ?? '');
  $date_to = trim($_POST['date_to'] ?? '');
  $time_from = trim($_POST['time_from'] ?? '');
  $time_to = trim($_POST['time_to'] ?? '');
  $company_name = trim($_POST['company_name'] ?? '');
  $company_place = trim($_POST['company_place'] ?? '');
  $company_contact = trim($_POST['company_contact'] ?? '');
  
  $contact_person = trim($_POST['contact_person'] ?? '');
  $machine_number = trim($_POST['machine_number'] ?? '');
  $issue_nature = trim($_POST['issue_nature'] ?? 'Observation');
  $issue_fixed = trim($_POST['issue_fixed'] ?? 'No');
  
  $issue_found = trim($_POST['issue_found'] ?? '');
  $solution = trim($_POST['solution'] ?? '');
  $expenses = (float)($_POST['expenses'] ?? 0);
  $cost = (float)($_POST['cost'] ?? 0);

  if ($provider_name==='' || $name===''||$company_name===''||$company_place===''||$company_contact===''||$issue_found===''||$solution==='') {
    $err = "Please fill all required fields.";
  } elseif (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date_from) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date_to)) {
    $err = "Invalid date format.";
  } else {
    // Update
    $st = $pdo->prepare("UPDATE services SET
      provider_name=?, name=?,
      date_from=?, date_to=?, time_from=?, time_to=?,
      company_name=?, company_place=?, company_contact=?, contact_person=?,
      machine_number=?,
      issue_nature=?, issue_fixed=?,
      issue_found=?, solution=?,
      expenses=?, cost=?
      WHERE id=?");
    $st->execute([
      $provider_name, $name,
      $date_from, $date_to, $time_from, $time_to,
      $company_name, $company_place, $company_contact, $contact_person,
      $machine_number,
      $issue_nature, $issue_fixed,
      $issue_found, $solution,
      $expenses, $cost,
      $id
    ]);

    // Handle New Images
    if (!empty($_FILES['images']['name'][0])) {
        $now = date('Y-m-d H:i:s');
        $uploadDir = __DIR__ . '/uploads/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
        
        // Normalize file array structure if needed, or loop standard way
        $count = count($_FILES['images']['name']);
        for($i=0; $i<$count; $i++) {
            if ($_FILES['images']['error'][$i] === UPLOAD_ERR_OK) {
                $filename = $_FILES['images']['name'][$i];
                $tmpName  = $_FILES['images']['tmp_name'][$i];
                $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
                
                if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                    $newFilename = $id . '_' . uniqid() . '.' . $ext;
                    if (move_uploaded_file($tmpName, $uploadDir . $newFilename)) {
                        $pdo->prepare("INSERT INTO service_images (service_id, file_path, created_at) VALUES (?, ?, ?)")
                            ->execute([$id, 'uploads/' . $newFilename, $now]);
                    }
                }
            }
        }
    }

    header("Location: /service_view.php?id=".$id); exit;
  }
}

// Fetch Existing Images for Display
$imgSt = $pdo->prepare("SELECT id, file_path FROM service_images WHERE service_id=?");
$imgSt->execute([$id]);
$existingImages = $imgSt->fetchAll();

render_header("Edit " . $r['service_no'], $user);
?>
<div class="card">
  <h1>Edit Service</h1>
  <div class="muted"><?php echo h($r['service_no']); ?> • <?php echo h(cat_label($r['category'])); ?></div>
  <?php if($err): ?><div class="card" style="border-color:var(--pop-red); color:var(--pop-red); user-select:text;"><?php echo h($err); ?></div><?php endif; ?>

  <form method="post" class="grid" enctype="multipart/form-data" id="mainForm">
    <input type="hidden" name="csrf" value="<?php echo h(csrf_token()); ?>"/>
    <div class="col-4"><div class="muted">Category</div><input value="<?php echo h(cat_label($r['category'])); ?>" readonly /></div>
    <div class="col-4"><div class="muted">Service No</div><input value="<?php echo h($r['service_no']); ?>" readonly /></div>
    <div class="col-4"><div class="muted">Service Provider</div><input name="provider_name" value="<?php echo h($r['provider_name']); ?>" required /></div>

    <div class="col-12"><div class="muted">Name / Title *</div><input name="name" value="<?php echo h($r['name']); ?>" required /></div>

    <div class="col-3"><div class="muted">Date From *</div><input type="date" name="date_from" value="<?php echo h($r['date_from']); ?>" required /></div>
    <div class="col-3"><div class="muted">Date To *</div><input type="date" name="date_to" value="<?php echo h($r['date_to']); ?>" required /></div>
    <div class="col-3"><div class="muted">Time From *</div><input type="time" name="time_from" value="<?php echo h($r['time_from']); ?>" required /></div>
    <div class="col-3"><div class="muted">Time To *</div><input type="time" name="time_to" value="<?php echo h($r['time_to']); ?>" required /></div>

    <div class="col-6"><div class="muted">Company Name *</div><input id="companyInput" name="company_name" value="<?php echo h($r['company_name']); ?>" required /></div>
    <div class="col-6"><div class="muted">Company Place *</div><input name="company_place" value="<?php echo h($r['company_place']); ?>" required /></div>

    <div class="col-6"><div class="muted">Contact Person</div><input name="contact_person" value="<?php echo h($r['contact_person']); ?>" /></div>
    <div class="col-6"><div class="muted">Contact Details *</div><input name="company_contact" value="<?php echo h($r['company_contact']); ?>" required /></div>

    <div class="col-12"><div class="muted">Machine Number</div><input name="machine_number" value="<?php echo h($r['machine_number']); ?>" placeholder="Manual Entry"/></div>

    <div class="col-3"><div class="muted">Issue Nature</div><select name="issue_nature"><?php foreach(['Observation','Maintenance','Minor','Major','Critical'] as $opt): ?><option value="<?php echo $opt; ?>" <?php echo ($r['issue_nature'] === $opt) ? 'selected' : ''; ?>><?php echo $opt; ?></option><?php endforeach; ?></select></div>
    <div class="col-3"><div class="muted">Fixed?</div><select name="issue_fixed"><option value="No" <?php echo ($r['issue_fixed'] === 'No') ? 'selected' : ''; ?>>No</option><option value="Yes" <?php echo ($r['issue_fixed'] === 'Yes') ? 'selected' : ''; ?>>Yes</option></select></div>
    <div class="col-3"><div class="muted">Expenses</div><input type="number" step="0.01" name="expenses" value="<?php echo h((string)$r['expenses']); ?>" /></div>
    <div class="col-3"><div class="muted">Total Cost</div><input type="number" step="0.01" name="cost" value="<?php echo h((string)$r['cost']); ?>" /></div>

    <div class="col-12"><div class="muted">Issue Found *</div><textarea name="issue_found" required><?php echo h($r['issue_found']); ?></textarea></div>
    <div class="col-12"><div class="muted">Solution *</div><textarea name="solution" required><?php echo h($r['solution']); ?></textarea></div>

    <div class="col-12">
        <div class="muted">Existing Images</div>
        <?php if(count($existingImages) > 0): ?>
            <div style="display:flex; gap:10px; flex-wrap:wrap; margin-top:5px;">
                <?php foreach($existingImages as $img): ?>
                    <div style="position:relative; border:1px solid #ddd; padding:4px; border-radius:4px;">
                        <a href="<?php echo h($img['file_path']); ?>" target="_blank">
                            <img src="<?php echo h($img['file_path']); ?>" style="height:80px; width:auto; display:block;">
                        </a>
                        <button form="delForm_<?php echo $img['id']; ?>" class="btn danger" style="position:absolute; top:-5px; right:-5px; width:24px; height:24px; padding:0; line-height:22px; font-size:14px; border-radius:50%; box-shadow:0 2px 4px rgba(0,0,0,0.2);" title="Delete Image">×</button>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="muted" style="font-style:italic;">No images attached.</div>
        <?php endif; ?>
    </div>

    <div class="col-12">
        <div class="muted">Add More Images</div>
        <div style="background:#f9f9f9; padding:10px; border:1px dashed #ccc; border-radius:6px;">
            <input type="file" id="imgInput" name="images[]" multiple accept="image/*" style="display:none;" onchange="handleFiles(this.files)">
            
            <button type="button" class="btn" onclick="document.getElementById('imgInput').click()" style="margin-bottom:10px;">
                + Select Images
            </button>

            <div id="previewArea" style="display:flex; gap:10px; flex-wrap:wrap;"></div>
            <div id="fileCount" class="muted" style="margin-top:5px;"></div>
        </div>
    </div>

    <div class="col-12 no-print" style="margin-top:20px;">
      <button class="btn" type="submit">Update Service</button>
      <a class="btn" href="/service_view.php?id=<?php echo (int)$id; ?>">Cancel</a>
    </div>
  </form>

  <?php foreach($existingImages as $img): ?>
      <form id="delForm_<?php echo $img['id']; ?>" method="post" onsubmit="return confirm('Delete this image permanently?');">
          <input type="hidden" name="csrf" value="<?php echo h(csrf_token()); ?>"/>
          <input type="hidden" name="delete_img_id" value="<?php echo $img['id']; ?>"/>
      </form>
  <?php endforeach; ?>
</div>

<script>
attachCompanySuggest('companyInput');

// --- JS FILE MANAGER FOR NEW UPLOADS ---
let dt = new DataTransfer();

function handleFiles(files) {
    for (let i = 0; i < files.length; i++) {
        dt.items.add(files[i]);
    }
    updateInputAndPreview();
}

function updateInputAndPreview() {
    // Sync DataTransfer to Input
    document.getElementById('imgInput').files = dt.files;
    
    // Render Previews
    const area = document.getElementById('previewArea');
    area.innerHTML = '';
    
    const files = dt.files;
    document.getElementById('fileCount').innerText = files.length === 0 ? '' : files.length + " file(s) selected to upload.";

    for (let i = 0; i < files.length; i++) {
        const file = files[i];
        const wrap = document.createElement('div');
        Object.assign(wrap.style, { position: 'relative', border: '1px solid #ddd', background:'#fff', padding:'2px', borderRadius:'4px' });

        // Thumbnail
        const img = document.createElement('img');
        img.file = file;
        img.style.height = '60px';
        img.style.width = 'auto';
        img.style.display = 'block';
        
        const reader = new FileReader();
        reader.onload = (e) => img.src = e.target.result;
        reader.readAsDataURL(file);

        // Remove Button
        const btn = document.createElement('button');
        btn.innerText = '×';
        Object.assign(btn.style, {
            position: 'absolute', top: '-6px', right: '-6px',
            width: '20px', height: '20px', lineHeight: '18px',
            background: '#ff5252', color: 'white', border: 'none',
            borderRadius: '50%', cursor: 'pointer', fontWeight: 'bold'
        });
        btn.type = 'button';
        btn.onclick = () => removeFile(i);

        wrap.appendChild(img);
        wrap.appendChild(btn);
        area.appendChild(wrap);
    }
}

function removeFile(index) {
    const newDt = new DataTransfer();
    for (let i = 0; i < dt.files.length; i++) {
        if (i !== index) newDt.items.add(dt.files[i]);
    }
    dt = newDt;
    updateInputAndPreview();
}
</script>
<?php render_footer(); ?>