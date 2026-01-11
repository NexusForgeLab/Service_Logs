<?php
require_once __DIR__ . '/app/layout.php';
$user = require_admin();
$pdo  = db();

$msg = '';

// Handle Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $comp = trim($_POST['company_name'] ?? '');
        $mach = trim($_POST['machine_number'] ?? '');
        if ($comp && $mach) {
            try {
                $st = $pdo->prepare("INSERT INTO company_machines (company_name, machine_number) VALUES (?, ?)");
                $st->execute([$comp, $mach]);
                $msg = "Machine added.";
            } catch (Exception $e) { $msg = "Error: May already exist."; }
        }
    } elseif ($action === 'delete') {
        $id = (int)$_POST['id'];
        $pdo->prepare("DELETE FROM company_machines WHERE id=?")->execute([$id]);
        $msg = "Machine deleted.";
    } elseif ($action === 'clear_notif') {
        $pdo->exec("DELETE FROM notifications");
        $msg = "Notifications cleared.";
    }
}

// Fetch Data
$machines = $pdo->query("SELECT * FROM company_machines ORDER BY company_name, machine_number")->fetchAll();
$notifs = $pdo->query("SELECT * FROM notifications ORDER BY id DESC LIMIT 20")->fetchAll();

render_header("Manage Machines", $user);
?>
<div class="card">
    <h1>Manage Machines</h1>
    <div class="muted">Add predefined machine numbers for companies.</div>
    <?php if($msg): ?><div style="color:green; font-weight:bold; margin-top:10px;"><?php echo h($msg); ?></div><?php endif; ?>
</div>

<div class="grid">
    <div class="col-12">
        <div class="card" style="border-color:#ff9800;">
            <div style="display:flex; justify-content:space-between;">
                <h3>🔔 Notifications</h3>
                <?php if($notifs): ?>
                <form method="post" style="margin:0;">
                    <input type="hidden" name="csrf" value="<?php echo h(csrf_token()); ?>">
                    <input type="hidden" name="action" value="clear_notif">
                    <button class="btn danger" style="padding:4px 8px; font-size:11px;">Clear All</button>
                </form>
                <?php endif; ?>
            </div>
            <?php if(!$notifs): ?><div class="muted">No new notifications.</div><?php endif; ?>
            <ul style="margin:0; padding-left:20px;">
                <?php foreach($notifs as $n): ?>
                    <li style="margin-bottom:5px;"><?php echo h($n['message']); ?> <span class="muted" style="font-size:10px;">(<?php echo h($n['created_at']); ?>)</span></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>

    <div class="col-4">
        <div class="card">
            <h3>Add Machine</h3>
            <form method="post">
                <input type="hidden" name="csrf" value="<?php echo h(csrf_token()); ?>">
                <input type="hidden" name="action" value="add">
                
                <div class="muted">Company Name</div>
                <input name="company_name" required placeholder="e.g. Sahara">
                
                <div class="muted">Machine Number</div>
                <input name="machine_number" required placeholder="e.g. M-101">
                
                <button class="btn" style="width:100%;">Add Machine</button>
            </form>
        </div>
    </div>

    <div class="col-8">
        <div class="card">
            <h3>Registered Machines</h3>
            <div class="table-responsive" style="max-height:400px; overflow-y:auto;">
                <table>
                    <thead><tr><th>Company</th><th>Machine No</th><th>Action</th></tr></thead>
                    <tbody>
                        <?php foreach($machines as $m): ?>
                        <tr>
                            <td><?php echo h($m['company_name']); ?></td>
                            <td><b><?php echo h($m['machine_number']); ?></b></td>
                            <td>
                                <form method="post" style="margin:0;" onsubmit="return confirm('Delete?');">
                                    <input type="hidden" name="csrf" value="<?php echo h(csrf_token()); ?>">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?php echo $m['id']; ?>">
                                    <button class="btn danger" style="padding:2px 6px; font-size:10px;">X</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php render_footer(); ?>
