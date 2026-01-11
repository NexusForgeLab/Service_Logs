<?php
require_once __DIR__ . '/app/layout.php';
$user = require_login();
$pdo  = db();

$cat = strtoupper(trim($_GET['cat'] ?? ''));
if ($cat !== '' && !in_array($cat, ['MECH','ELEC','APP'], true)) $cat = '';

$company = trim($_GET['company'] ?? '');
$provider = trim($_GET['provider'] ?? '');
$date = trim($_GET['date'] ?? '');

$where=[]; $params=[];
if($cat!==''){ $where[]="category=?"; $params[]=$cat; }
if($company!==''){ $where[]="company_name LIKE ?"; $params[]="%$company%"; }
if($provider!==''){ $where[]="provider_name LIKE ?"; $params[]="%$provider%"; }
if($date!=='' && preg_match('/^\d{4}-\d{2}-\d{2}$/',$date)){
  $where[]="(date_from <= ? AND date_to >= ?)";
  $params[]=$date; $params[]=$date;
}

$sql="SELECT * FROM services";
if(count($where)) $sql.=" WHERE ".implode(" AND ",$where);
$sql.=" ORDER BY created_at DESC LIMIT 500";

$st=$pdo->prepare($sql); $st->execute($params);
$rows=$st->fetchAll();

render_header("Search",$user);
?>
<div class="card">
  <h1>Search Services</h1>
  <div class="muted">Search by company, provider, date, and category. Blank shows latest 500.</div>

  <form method="get" class="grid no-print">
    <div class="col-3"><div class="muted">Category</div>
      <select name="cat">
        <option value="" <?php echo $cat===''?'selected':''; ?>>All</option>
        <option value="MECH" <?php echo $cat==='MECH'?'selected':''; ?>>Mechanical</option>
        <option value="ELEC" <?php echo $cat==='ELEC'?'selected':''; ?>>Electrical</option>
        <option value="APP"  <?php echo $cat==='APP'?'selected':''; ?>>Application</option>
      </select>
    </div>
    <div class="col-3"><div class="muted">Company</div><input name="company" value="<?php echo h($company); ?>"/></div>
    <div class="col-3"><div class="muted">Provider</div><input name="provider" value="<?php echo h($provider); ?>"/></div>
    <div class="col-3"><div class="muted">Date</div><input type="date" name="date" value="<?php echo h($date); ?>"/></div>
    <div class="col-12">
      <button class="btn" type="submit">Search</button>
      <a class="btn" href="/search.php">Reset</a>
    </div>
  </form>
</div>

<div class="card">
  <h2>Results (<?php echo count($rows); ?>)</h2>
  <?php if(!count($rows)): ?>
    <div class="muted">No results found.</div>
  <?php else: ?>
    <table>
      <thead><tr>
        <th>Category</th><th>Service No</th><th>Company</th><th>Provider</th><th>Date</th><th>Cost</th><th class="no-print">Action</th>
      </tr></thead>
      <tbody>
      <?php foreach($rows as $r): ?>
        <tr>
          <td><?php echo h(cat_label($r['category'])); ?></td>
          <td><span class="pill"><?php echo h($r['service_no']); ?></span></td>
          <td><?php echo h($r['company_name']); ?><div class="muted"><?php echo h($r['company_place']); ?></div></td>
          <td><?php echo h($r['provider_name']); ?></td>
          <td><?php echo h($r['date_from']); ?> → <?php echo h($r['date_to']); ?></td>
          <td><?php echo h(number_format((float)$r['cost'],2)); ?></td>
          <td class="no-print"><a class="btn" href="/service_view.php?id=<?php echo (int)$r['id']; ?>">View / Print</a></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
</div>
<?php render_footer(); ?>
