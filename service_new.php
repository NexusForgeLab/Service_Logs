<?php
require_once __DIR__ . '/app/layout.php';
$user = require_login();
$pdo  = db();

$cat = strtoupper(trim($_GET['cat'] ?? ''));
if (!in_array($cat, ['MECH','ELEC','APP'], true)) { header('Location:/'); exit; }

$err='';

if($_SERVER['REQUEST_METHOD']==='POST'){
  csrf_check();
  
  $provider_name = trim($_POST['provider_name'] ?? $user['display_name']);
  $name = trim($_POST['name']??'');
  $date_from = trim($_POST['date_from']??'');
  $date_to = trim($_POST['date_to']??'');
  $time_from = trim($_POST['time_from']??'');
  $time_to = trim($_POST['time_to']??'');
  $company_name = trim($_POST['company_name']??'');
  $company_place = trim($_POST['company_place']??'');
  $company_contact = trim($_POST['company_contact']??'');
  
  $contact_person = trim($_POST['contact_person']??'');
  $machine_number = trim($_POST['machine_number']??'');
  $issue_nature   = trim($_POST['issue_nature']??'Observation');
  $issue_fixed    = trim($_POST['issue_fixed']??'No');

  $issue_found = trim($_POST['issue_found']??'');
  $solution = trim($_POST['solution']??'');
  $expenses = (float)($_POST['expenses']??0);
  $cost = (float)($_POST['cost']??0);

  if($provider_name===''||$name===''||$company_name===''||$company_place===''||$company_contact===''||$issue_found===''||$solution===''){
    $err='Please fill all required fields.';
  } elseif(!preg_match('/^\d{4}-\d{2}-\d{2}$/',$date_from) || !preg_match('/^\d{4}-\d{2}-\d{2}$/',$date_to)){
    $err='Invalid date.';
  } else {
    $pdo->beginTransaction();
    try {
        // Capture Current Local Time (IST)
        $now = date('Y-m-d H:i:s');

        // Generate Number
        $st = $pdo->prepare("SELECT last_number FROM category_counters WHERE category=?");
        $st->execute([$cat]);
        $last = (int)$st->fetchColumn();
        $next = $last + 1;
        $pdo->prepare("UPDATE category_counters SET last_number=? WHERE category=?")->execute([$next, $cat]);
        $service_no = $cat . "-" . str_pad((string)$next, 6, "0", STR_PAD_LEFT);

        // Insert Service
        $sql = "INSERT INTO services(category, service_no, provider_id, provider_name, name, date_from, date_to, time_from, time_to, company_name, company_place, company_contact, contact_person, machine_number, issue_nature, issue_fixed, issue_found, solution, expenses, cost, created_at)
                VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
        $params = [$cat, $service_no, $user['id'], $provider_name, $name, $date_from, $date_to, $time_from, $time_to, $company_name, $company_place, $company_contact, $contact_person, $machine_number, $issue_nature, $issue_fixed, $issue_found, $solution, $expenses, $cost, $now];
        
        $pdo->prepare($sql)->execute($params);
        $service_id = (int)$pdo->lastInsertId();

        // Images (Loop through re-indexed file array)
        if (!empty($_FILES['images']['name'][0])) {
            $uploadDir = __DIR__ . '/uploads/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
            
            $count = count($_FILES['images']['name']);
            for($i=0; $i<$count; $i++) {
                if ($_FILES['images']['error'][$i] === UPLOAD_ERR_OK) {
                    $filename = $_FILES['images']['name'][$i];
                    $tmpName  = $_FILES['images']['tmp_name'][$i];
                    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
                    
                    if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                        $newFilename = $service_id . '_' . uniqid() . '.' . $ext;
                        if (move_uploaded_file($tmpName, $uploadDir . $newFilename)) {
                            $pdo->prepare("INSERT INTO service_images (service_id, file_path, created_at) VALUES (?, ?, ?)")
                                ->execute([$service_id, 'uploads/' . $newFilename, $now]);
                        }
                    }
                }
            }
        }

        $pdo->commit();
        header("Location:/service_view.php?id=".$service_id); exit;

    } catch(Exception $e) {
        $pdo->rollBack();
        $err = 'Save failed: ' . $e->getMessage();
    }
  }
}

render_header("New ".cat_label($cat)." Service",$user);
?>
<div class="card">
  <h1><?php echo h(cat_label($cat)); ?> Service Entry</h1>
  <div class="muted">Service Number will be generated automatically upon saving.</div>
  <?php if($err): ?><div class="card" style="border-color:var(--pop-red); color:var(--pop-red); user-select:text;"><?php echo h($err); ?></div><?php endif; ?>

  <form method="post" class="grid" enctype="multipart/form-data">
    <input type="hidden" name="csrf" value="<?php echo h(csrf_token()); ?>"/>

    <div class="col-4"><div class="muted">Category</div><input value="<?php echo h(cat_label($cat)); ?>" readonly/></div>
    <div class="col-4"><div class="muted">Service No</div><input value="(Auto-Generated)" readonly style="color:#888; font-style:italic;"/></div>
    <div class="col-4"><div class="muted">Service Provider</div><input name="provider_name" value="<?php echo h($user['display_name']); ?>" required/></div>

    <div class="col-12"><div class="muted">Name / Title *</div><input name="name" required placeholder="Brief title of the work"/></div>

    <div class="col-3"><div class="muted">Date From *</div><input type="date" name="date_from" value="<?php echo h(date('Y-m-d')); ?>" required/></div>
    <div class="col-3"><div class="muted">Date To *</div><input type="date" name="date_to" value="<?php echo h(date('Y-m-d')); ?>" required/></div>
    <div class="col-3"><div class="muted">Time From *</div><input type="time" name="time_from" value="<?php echo h(date('H:i')); ?>" required/></div>
    <div class="col-3"><div class="muted">Time To *</div><input type="time" name="time_to" value="<?php echo h(date('H:i')); ?>" required/></div>

    <div class="col-6"><div class="muted">Company Name *</div><input id="companyInput" name="company_name" required onblur="fetchMachines()"/></div>
    <div class="col-6"><div class="muted">Place *</div><input name="company_place" required/></div>

    <div class="col-6"><div class="muted">Contact Person (Name)</div><input name="contact_person" placeholder="Mr. John Doe"/></div>
    <div class="col-6"><div class="muted">Contact Details (Phone/Email) *</div><input name="company_contact" placeholder="+91 9999..." required/></div>

    <div class="col-12">
        <div class="muted">Machine Number</div>
        <div style="display:flex; gap:10px; align-items:center;">
            <div style="flex-grow:1; position:relative;">
                <input id="machineInput" name="machine_number" placeholder="Enter or Select Machine No" />
                <select id="machineSelect" style="display:none; position:absolute; top:0; left:0; height:100%; border:2px solid var(--pop-cyan);" onchange="selectMachine(this.value)">
                    <option value="">-- Select Machine --</option>
                </select>
            </div>
            <button type="button" class="btn" style="font-size:11px; padding:10px;" onclick="reqAddMachine()">Request Add</button>
        </div>
        <small class="muted" id="machineHint">Type company name to fetch machines.</small>
    </div>

    <div class="col-3"><div class="muted">Issue Nature</div><select name="issue_nature"><option value="Observation">Observation</option><option value="Maintenance">Maintenance</option><option value="Minor">Minor</option><option value="Major">Major</option><option value="Critical">Critical</option></select></div>
    <div class="col-3"><div class="muted">Issue Fixed?</div><select name="issue_fixed"><option value="No">No</option><option value="Yes">Yes</option></select></div>
    <div class="col-3"><div class="muted">Expenses</div><input type="number" step="0.01" name="expenses" value="0"/></div>
    <div class="col-3"><div class="muted">Total Cost</div><input type="number" step="0.01" name="cost" value="0"/></div>

    <div class="col-12"><div class="muted">Issue Found *</div><textarea name="issue_found" required></textarea></div>
    <div class="col-12"><div class="muted">Solution / Action Taken *</div><textarea name="solution" required></textarea></div>
    
    <div class="col-12">
        <div class="muted">Upload Images (Max 20 images)</div>
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
      <button class="btn" type="submit" style="background:var(--pop-cyan); color:white;">Save Service Entry</button>
      <a class="btn" href="/category.php?cat=<?php echo h($cat); ?>">Cancel</a>
    </div>
  </form>
</div>

<script>
attachCompanySuggest('companyInput');

// --- MACHINES LOGIC ---
async function fetchMachines() {
    const comp = document.getElementById('companyInput').value;
    const hint = document.getElementById('machineHint');
    const input = document.getElementById('machineInput');
    const sel = document.getElementById('machineSelect');
    if(!comp) return;
    try {
        const r = await fetch(`/api/machines.php?company=${encodeURIComponent(comp)}`);
        const machines = await r.json();
        sel.style.display = 'none';
        if (machines.length === 0) {
            hint.textContent = "No registered machines found. You can enter manually.";
        } else if (machines.length === 1) {
            input.value = machines[0];
            hint.textContent = "✔ Auto-selected single registered machine.";
        } else {
            sel.innerHTML = '<option value="">-- Select Registered Machine --</option>';
            machines.forEach(m => { sel.innerHTML += `<option value="${m}">${m}</option>`; });
            sel.innerHTML += '<option value="__MANUAL__">[ Enter Manually ]</option>';
            sel.style.display = 'block';
            hint.textContent = "⚠ Multiple machines found. Please select one.";
        }
    } catch(e) { console.error(e); }
}

function selectMachine(val) {
    if(val === '__MANUAL__') {
        document.getElementById('machineSelect').style.display = 'none';
        document.getElementById('machineInput').focus();
    } else if (val) {
        document.getElementById('machineInput').value = val;
        document.getElementById('machineSelect').style.display = 'none';
    }
}

async function reqAddMachine() {
    const comp = document.getElementById('companyInput').value;
    const mach = document.getElementById('machineInput').value;
    if(!comp || !mach) { alert("Enter Company and Machine Number first."); return; }
    if(confirm(`Request Admin to add Machine '${mach}' for '${comp}'?`)) {
        const fd = new FormData();
        fd.append('company', comp);
        fd.append('machine', mach);
        await fetch('/api/machines.php', { method:'POST', body:fd });
        alert("Request sent to Admin.");
    }
}

// --- IMAGE MANAGER JS ---
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
    document.getElementById('fileCount').innerText = files.length === 0 ? '' : files.length + " file(s) selected.";

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