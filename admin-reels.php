<?php
require_once 'auth_check.php';

if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: index.php");
    exit;
}

$msg = '';
$err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add_reel') {
        $title = trim($_POST['title'] ?? '');
        $caption = trim($_POST['caption'] ?? '');
        $video_url_text = trim($_POST['video_url'] ?? '');
        $poster_url = trim($_POST['poster_url'] ?? '') ?: null;
        $location = trim($_POST['location'] ?? '') ?: null;
        $price = $_POST['price'] !== '' ? (float)$_POST['price'] : null;
        $mode = ($_POST['listing_mode'] ?? 'Rent') === 'Buy' ? 'Buy' : 'Rent';
        $apartment_id = (isset($_POST['apartment_id']) && $_POST['apartment_id'] !== '') ? (int)$_POST['apartment_id'] : null;

        $final_video_url = $video_url_text;

        // Handle file upload (optional — overrides URL if both given)
        if (!empty($_FILES['video_file']['name']) && $_FILES['video_file']['error'] === UPLOAD_ERR_OK) {
            $tmp = $_FILES['video_file']['tmp_name'];
            $orig = basename($_FILES['video_file']['name']);
            $ext = strtolower(pathinfo($orig, PATHINFO_EXTENSION));
            $allowed = ['mp4', 'webm', 'mov', 'm4v'];
            if (!in_array($ext, $allowed, true)) {
                $err = 'Only mp4 / webm / mov files allowed.';
            } else {
                $newName = 'reel_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
                $destDir = __DIR__ . '/uploads/reels';
                if (!is_dir($destDir)) mkdir($destDir, 0775, true);
                $dest = $destDir . '/' . $newName;
                if (move_uploaded_file($tmp, $dest)) {
                    $final_video_url = 'uploads/reels/' . $newName;
                } else {
                    $err = 'Failed to move uploaded file.';
                }
            }
        }

        if (!$err) {
            if ($title === '' || $final_video_url === '') {
                $err = 'Title and video (URL or file) are required.';
            } else {
                $stmt = $pdo->prepare("INSERT INTO reels (title, caption, video_url, poster_url, location, price, listing_mode, apartment_id) VALUES (?,?,?,?,?,?,?,?)");
                $stmt->execute([$title, $caption, $final_video_url, $poster_url, $location, $price, $mode, $apartment_id]);
                $msg = 'Reel added.';
            }
        }
    }

    if ($action === 'delete_reel') {
        $id = (int)($_POST['id'] ?? 0);
        // delete physical file if it lives under uploads/
        $row = $pdo->prepare("SELECT video_url FROM reels WHERE id=?");
        $row->execute([$id]);
        $r = $row->fetch();
        if ($r && strpos($r['video_url'], 'uploads/reels/') === 0) {
            $f = __DIR__ . '/' . $r['video_url'];
            if (is_file($f)) @unlink($f);
        }
        $pdo->prepare("DELETE FROM reels WHERE id=?")->execute([$id]);
        $msg = 'Reel deleted.';
    }

    if ($action === 'toggle_active') {
        $id = (int)($_POST['id'] ?? 0);
        $pdo->prepare("UPDATE reels SET is_active = 1 - is_active WHERE id=?")->execute([$id]);
        $msg = 'Status toggled.';
    }

    if ($action === 'update_listing') {
        $id = (int)($_POST['id'] ?? 0);
        $ap = ($_POST['apartment_id'] !== '') ? (int)$_POST['apartment_id'] : null;
        $pdo->prepare("UPDATE reels SET apartment_id=? WHERE id=?")->execute([$ap, $id]);
        $msg = 'Linked listing updated.';
    }
}

$reels = $pdo->query("SELECT * FROM reels ORDER BY sort_order ASC, id DESC")->fetchAll();
$apartments_list = $pdo->query("SELECT id, title, address, price FROM apartments WHERE status='approved' ORDER BY id DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Admin · Reels</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" href="style.css?v=5.6">
<style>
.reels-admin { max-width: 980px; margin: 2rem auto; padding: 1rem; }
.reels-admin h1 { margin-bottom: 1rem; }
.reels-admin form.add-form { background: var(--bg-card,#fff); border:1px solid var(--border-glass,#e5e7eb); border-radius:14px; padding:1.2rem; margin-bottom:1.5rem; display:grid; gap:.7rem; }
.reels-admin form.add-form .row { display:grid; grid-template-columns: 1fr 1fr; gap:.7rem; }
.reels-admin label { font-size:.85rem; font-weight:600; display:block; margin-bottom:.25rem; }
.reels-admin input, .reels-admin textarea, .reels-admin select { width:100%; padding:.55rem .7rem; border:1px solid var(--border-glass,#d1d5db); border-radius:8px; font:inherit; background:var(--bg-input,#fff); color:inherit; }
.reels-admin .btn { background: var(--primary,#0ea5e9); color:#fff; border:none; padding:.6rem 1rem; border-radius:8px; cursor:pointer; font-weight:600; }
.reels-admin .btn-danger { background:#ef4444; }
.reels-admin .btn-secondary { background:#6b7280; }
.reels-admin .reel-list { display:grid; gap:.7rem; }
.reels-admin .reel-card { display:flex; gap:.8rem; align-items:center; background:var(--bg-card,#fff); border:1px solid var(--border-glass,#e5e7eb); border-radius:12px; padding:.7rem; }
.reels-admin .reel-card video, .reels-admin .reel-card img { width:90px; height:130px; object-fit:cover; border-radius:8px; background:#000; }
.reels-admin .reel-meta { flex:1; min-width:0; }
.reels-admin .reel-meta h3 { margin:0 0 .2rem; font-size:1rem; }
.reels-admin .reel-meta small { color:var(--text-secondary,#6b7280); word-break:break-all; }
.reels-admin .alert { padding:.7rem 1rem; border-radius:8px; margin-bottom:1rem; }
.reels-admin .alert.ok { background:#d1fae5; color:#065f46; }
.reels-admin .alert.err { background:#fee2e2; color:#991b1b; }
@media (max-width: 720px) { .reels-admin form.add-form .row { grid-template-columns: 1fr; } }
</style>
</head>
<body>
<div class="reels-admin">
  <p><a href="admin.php">← Back to Admin</a> · <a href="index.php">Home</a></p>
  <h1>🎬 Reels Manager</h1>

  <?php if ($msg): ?><div class="alert ok"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
  <?php if ($err): ?><div class="alert err"><?= htmlspecialchars($err) ?></div><?php endif; ?>

  <form class="add-form" method="post" enctype="multipart/form-data">
    <input type="hidden" name="action" value="add_reel">
    <h2 style="margin:0 0 .3rem;font-size:1.1rem;">Add a new Reel</h2>
    <div class="row">
      <div><label>Title *</label><input name="title" required></div>
      <div><label>Location</label><input name="location" placeholder="e.g. Colombo"></div>
    </div>
    <div><label>Caption</label><textarea name="caption" rows="2"></textarea></div>
    <div class="row">
      <div><label>Price (LKR)</label><input name="price" type="number" step="0.01"></div>
      <div><label>Mode</label>
        <select name="listing_mode"><option>Rent</option><option>Buy</option></select>
      </div>
    </div>
    <div><label>Video URL (mp4 / webm) — paste link OR upload below</label>
      <input name="video_url" placeholder="https://… .mp4 or uploads/reels/foo.mp4"></div>
    <div><label>Or upload a file (mp4 / webm / mov)</label>
      <input type="file" name="video_file" accept="video/mp4,video/webm,video/quicktime"></div>
    <div><label>Poster image URL (optional)</label>
      <input name="poster_url" placeholder="https://…"></div>
    <div><label>Link to Listing (optional) — picks up map &amp; card on the reel</label>
      <select name="apartment_id">
        <option value="">— No linked listing —</option>
        <?php foreach ($apartments_list as $ap): ?>
          <option value="<?= (int)$ap['id'] ?>">#<?= (int)$ap['id'] ?> · <?= htmlspecialchars($ap['title']) ?> — <?= htmlspecialchars($ap['address']) ?></option>
        <?php endforeach; ?>
      </select></div>
    <div><button class="btn" type="submit">Add Reel</button></div>
  </form>

  <h2>Existing Reels (<?= count($reels) ?>)</h2>
  <div class="reel-list">
    <?php foreach ($reels as $r): ?>
    <div class="reel-card">
      <?php if ($r['poster_url']): ?>
        <img src="<?= htmlspecialchars($r['poster_url']) ?>" alt="">
      <?php else: ?>
        <video src="<?= htmlspecialchars($r['video_url']) ?>" muted preload="metadata"></video>
      <?php endif; ?>
      <div class="reel-meta">
        <h3><?= htmlspecialchars($r['title']) ?>
          <?php if (!$r['is_active']): ?><span style="color:#ef4444;">(hidden)</span><?php endif; ?>
        </h3>
        <small><?= htmlspecialchars($r['video_url']) ?></small><br>
        <small><?= htmlspecialchars(($r['location'] ?? '') . ' · ' . ($r['listing_mode'] ?? '')) ?></small>
        <form method="post" style="margin-top:.4rem;display:flex;gap:.4rem;align-items:center;">
          <input type="hidden" name="action" value="update_listing">
          <input type="hidden" name="id" value="<?= $r['id'] ?>">
          <select name="apartment_id" style="flex:1;min-width:0;">
            <option value="">— No linked listing —</option>
            <?php foreach ($apartments_list as $ap): ?>
              <option value="<?= (int)$ap['id'] ?>" <?= ((int)($r['apartment_id'] ?? 0) === (int)$ap['id']) ? 'selected' : '' ?>>
                #<?= (int)$ap['id'] ?> · <?= htmlspecialchars($ap['title']) ?>
              </option>
            <?php endforeach; ?>
          </select>
          <button class="btn" type="submit">Link</button>
        </form>
      </div>
      <form method="post" style="display:inline">
        <input type="hidden" name="action" value="toggle_active">
        <input type="hidden" name="id" value="<?= $r['id'] ?>">
        <button class="btn btn-secondary" type="submit"><?= $r['is_active'] ? 'Hide' : 'Show' ?></button>
      </form>
      <form method="post" style="display:inline" onsubmit="return confirm('Delete this reel?');">
        <input type="hidden" name="action" value="delete_reel">
        <input type="hidden" name="id" value="<?= $r['id'] ?>">
        <button class="btn btn-danger" type="submit">Delete</button>
      </form>
    </div>
    <?php endforeach; ?>
    <?php if (!$reels): ?><p>No reels yet — add one above, or run <a href="run_reels_migration.php">run_reels_migration.php</a> to seed demo videos.</p><?php endif; ?>
  </div>
</div>
</body>
</html>
