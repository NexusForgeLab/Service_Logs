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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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

    // Images
    if (!empty($_FILES['images']['name'][0])) {
        $now = date('Y-m-d H:i:s'); // Local Time
        $uploadDir = __DIR__ . '/uploads/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
        foreach ($_FILES['images']['name'] as $key => $filename) {
            if ($_FILES['images']['error'][$key] === UPLOAD_ERR_OK) {
                $tmpName = $_FILES['images']['tmp_name'][$key];
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

render_header("Edit " . $r['service_no'], $user);
?>
<div class="card">
  <h1>Edit Service</h1>
  <div class="muted"><?php echo h($r['service_no']); ?> • <?php echo h(cat_label($r['category'])); ?></div>
  <?php if($err): ?><div class="card" style="border-color:var(--pop-red); color:var(--pop-red);"><?php echo h($err); ?></div><?php endif; ?>

  <form method="post" class="grid" enctype="multipart/form-data">
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

    <div class="col-12"><div class="muted">Add More Images</div><input type="file" name="images[]" multiple accept="image/*" style="padding:10px; background:#f9f9f9;"/></div>

    <div class="col-12 no-print" style="margin-top:20px;">
      <button class="btn" type="submit">Update Service</button>
      <a class="btn" href="/service_view.php?id=<?php echo (int)$id; ?>">Cancel</a>
    </div>
  </form>
</div>
<script>attachCompanySuggest('companyInput');</script>
<?php render_footer(); ?>