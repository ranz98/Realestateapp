
// Direct DB connection - no auth needed




<?php
// Direct DB connection - no auth needed
$host = 'sql300.infinityfree.com';
$dbname = 'if0_39877814_realestatex';
$user = 'if0_39877814'; // Default XAMPP username
$pass = 'mAfb2vH9kHV';     // Default XAMPP password

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(Exception $e) {
    die("<div style='font-family:monospace;padding:2rem;color:red;'>DB Error: " . $e->getMessage() . "</div>");
}

// Create table if not exists
$pdo->exec("CREATE TABLE IF NOT EXISTS site_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT NOT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)");

$defaults = [
    'primary'             => '#4f46e5',
    'primary_hover'       => '#4338ca',
    'accent'              => '#0ea5e9',
    'bg_main'             => '#f8fafc',
    'bg_panel'            => '#ffffff',
    'bg_card'             => '#ffffff',
    'border_glass'        => '#e2e8f0',
    'text_primary'        => '#0f172a',
    'text_secondary'      => '#475569',
    'radius_lg'           => '16px',
    'radius_md'           => '8px',
    'radius_sm'           => '6px',
    'dark_bg_main'        => '#0f172a',
    'dark_bg_panel'       => '#1e293b',
    'dark_bg_card'        => '#1e293b',
    'dark_border_glass'   => '#334155',
    'dark_text_primary'   => '#f8fafc',
    'dark_text_secondary' => '#94a3b8',
    'font_heading'        => 'Outfit',
    'font_body'           => 'Inter',
    'shadow_intensity'    => 'soft',
    'card_hover_lift'     => 'yes',
    'custom_css'          => '',
    // Logo settings
    'logo_url'            => '',
    'logo_width'          => '140',
    'logo_height'         => '40',
    'logo_padding_top'    => '0',
    'logo_padding_bottom' => '0',
    'logo_border_radius'  => '0',
    'logo_position'       => 'left',
    'logo_show_text'      => 'yes',
    'logo_text'           => 'MyHomeMyLand.LK',
    'logo_text_size'      => '22',
    'logo_text_color'     => '',
    'logo_bg'             => '',
    'logo_fit'            => 'contain',
];

$saved = $pdo->query("SELECT setting_key, setting_value FROM site_settings")->fetchAll(PDO::FETCH_KEY_PAIR);
$settings = array_merge($defaults, $saved);
$msg = '';
$msg_type = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'save';

    if ($action === 'reset') {
        $pdo->exec("DELETE FROM site_settings");
        $settings = $defaults;
        $msg = 'Reset to defaults!';

    } elseif ($action === 'delete_logo') {
        // Delete old logo file
        $old = $saved['logo_url'] ?? '';
        if ($old && file_exists(ltrim($old, '/'))) @unlink(ltrim($old, '/'));
        $stmt = $pdo->prepare("INSERT INTO site_settings (setting_key, setting_value) VALUES ('logo_url','') ON DUPLICATE KEY UPDATE setting_value=''");
        $stmt->execute();
        $settings['logo_url'] = '';
        $msg = 'Logo removed.';

    } else {
        // Handle logo upload first
        if (!empty($_FILES['logo_file']['name']) && $_FILES['logo_file']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = __DIR__ . '/uploads/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

            $ext = strtolower(pathinfo($_FILES['logo_file']['name'], PATHINFO_EXTENSION));
            $allowed = ['png','jpg','jpeg','gif','webp','svg'];
            if (!in_array($ext, $allowed)) {
                $msg = 'Invalid file type. Use PNG, JPG, GIF, WEBP or SVG.';
                $msg_type = 'error';
            } else {
                // Delete old logo if exists
                $old = $saved['logo_url'] ?? '';
                if ($old && file_exists(__DIR__ . '/' . ltrim($old, '/'))) {
                    @unlink(__DIR__ . '/' . ltrim($old, '/'));
                }
                $filename = 'logo_' . time() . '.' . $ext;
                $dest = $uploadDir . $filename;
                if (move_uploaded_file($_FILES['logo_file']['tmp_name'], $dest)) {
                    $_POST['logo_url'] = 'uploads/' . $filename;
                } else {
                    $msg = 'Upload failed — check folder permissions on /uploads/';
                    $msg_type = 'error';
                }
            }
        }

        // Save all settings
        $fields = array_keys($defaults);
        $stmt = $pdo->prepare("INSERT INTO site_settings (setting_key, setting_value) VALUES (?,?) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)");
        foreach ($fields as $f) {
            // Checkboxes that are unchecked won't appear in POST
            if ($f === 'logo_show_text' || $f === 'card_hover_lift') {
                $val = isset($_POST[$f]) ? $_POST[$f] : 'no';
            } else {
                $val = $_POST[$f] ?? $defaults[$f];
            }
            $stmt->execute([$f, $val]);
            $settings[$f] = $val;
        }
        if (empty($msg)) $msg = 'Theme saved!';
    }
}

$logoUrl = $settings['logo_url'] ?? '';
$hasLogo = !empty($logoUrl);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Theme Studio</title>
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;700;800&family=Inter:wght@400;500;600&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
<style>
* { margin:0; padding:0; box-sizing:border-box; }
body { font-family:'Inter',sans-serif; background:#0d0d0f; color:#fafafa; height:100vh; display:flex; flex-direction:column; overflow:hidden; }

.topbar { display:flex; align-items:center; justify-content:space-between; padding:0 1.5rem; height:56px; background:#18181b; border-bottom:1px solid #27272a; flex-shrink:0; }
.topbar-title { font-family:'Outfit',sans-serif; font-size:1rem; font-weight:700; display:flex; align-items:center; gap:0.5rem; }
.topbar-title i { color:#6366f1; }
.topbar-actions { display:flex; gap:0.6rem; }
.btn { padding:0.45rem 1.1rem; border-radius:6px; font-size:0.83rem; font-weight:600; cursor:pointer; border:none; display:inline-flex; align-items:center; gap:0.4rem; transition:all .2s; }
.btn-save { background:#6366f1; color:#fff; }
.btn-save:hover { background:#4f52e8; }
.btn-reset { background:#18181b; color:#71717a; border:1px solid #27272a; }
.btn-reset:hover { color:#ef4444; border-color:#ef4444; }

.studio { display:flex; flex:1; overflow:hidden; }

.sidebar { width:310px; background:#18181b; border-right:1px solid #27272a; display:flex; flex-direction:column; overflow:hidden; flex-shrink:0; }
.tabs { display:flex; border-bottom:1px solid #27272a; overflow-x:auto; scrollbar-width:none; }
.tabs::-webkit-scrollbar { display:none; }
.tab { padding:0.6rem 0.85rem; font-size:0.77rem; font-weight:600; color:#71717a; cursor:pointer; border-bottom:2px solid transparent; white-space:nowrap; transition:all .2s; }
.tab.active { color:#6366f1; border-bottom-color:#6366f1; }
.tab:hover { color:#fafafa; }

.panels { flex:1; overflow-y:auto; padding:1rem; }
.panels::-webkit-scrollbar { width:3px; }
.panels::-webkit-scrollbar-thumb { background:#27272a; border-radius:4px; }

.panel { display:none; }
.panel.active { display:block; }

.stitle { font-size:0.68rem; font-weight:700; text-transform:uppercase; letter-spacing:1px; color:#52525b; margin-bottom:0.8rem; margin-top:1.2rem; padding-bottom:0.3rem; border-bottom:1px solid #27272a; }
.stitle:first-child { margin-top:0; }

.row { display:flex; align-items:center; justify-content:space-between; margin-bottom:0.65rem; gap:0.5rem; }
.lbl { font-size:0.8rem; color:#a1a1aa; flex:1; min-width:0; }
.lbl small { display:block; font-family:'JetBrains Mono',monospace; font-size:0.68rem; color:#52525b; margin-top:1px; }

/* Color picker */
.cpick { display:flex; align-items:center; gap:0.4rem; background:#09090b; border:1px solid #27272a; border-radius:6px; padding:0.25rem 0.5rem; cursor:pointer; flex-shrink:0; }
.cpick input[type=color] { width:22px; height:22px; border:none; background:none; cursor:pointer; padding:0; border-radius:3px; }
.cpick .hex { font-family:'JetBrains Mono',monospace; font-size:0.72rem; color:#e4e4e7; min-width:54px; }

/* Slider */
.srow { display:flex; align-items:center; gap:0.5rem; flex-shrink:0; }
.srow input[type=range] { width:75px; accent-color:#6366f1; cursor:pointer; background:none; border:none; padding:0; }
.bdg { font-family:'JetBrains Mono',monospace; font-size:0.7rem; background:#09090b; border:1px solid #27272a; border-radius:4px; padding:0.1rem 0.35rem; min-width:40px; text-align:center; color:#e4e4e7; }

/* Toggle */
.tog { position:relative; width:34px; height:18px; flex-shrink:0; }
.tog input { opacity:0; width:0; height:0; }
.tog-t { position:absolute; inset:0; background:#27272a; border-radius:18px; cursor:pointer; transition:.3s; }
.tog-t::before { content:''; position:absolute; width:12px; height:12px; left:3px; top:3px; background:#fff; border-radius:50%; transition:.3s; }
.tog input:checked + .tog-t { background:#6366f1; }
.tog input:checked + .tog-t::before { transform:translateX(16px); }

/* Selects */
.fsel { background:#09090b; border:1px solid #27272a; color:#e4e4e7; border-radius:6px; padding:0.3rem 0.5rem; font-size:0.78rem; width:120px; outline:none; flex-shrink:0; }
.fsel:focus { border-color:#6366f1; }

/* Number input */
.numinp { background:#09090b; border:1px solid #27272a; color:#e4e4e7; border-radius:6px; padding:0.28rem 0.5rem; font-size:0.78rem; font-family:'JetBrains Mono',monospace; width:68px; outline:none; flex-shrink:0; text-align:center; }
.numinp:focus { border-color:#6366f1; }

/* Text input */
.tinp { background:#09090b; border:1px solid #27272a; color:#e4e4e7; border-radius:6px; padding:0.28rem 0.6rem; font-size:0.78rem; width:100%; outline:none; }
.tinp:focus { border-color:#6366f1; }

/* Custom CSS editor */
.cssed { width:100%; min-height:130px; background:#09090b; border:1px solid #27272a; color:#86efac; font-family:'JetBrains Mono',monospace; font-size:0.75rem; padding:0.7rem; border-radius:8px; resize:vertical; line-height:1.6; outline:none; }
.cssed:focus { border-color:#6366f1; }

/* === LOGO UPLOAD DROP ZONE === */
.logo-drop { border:2px dashed #27272a; border-radius:10px; padding:1.5rem 1rem; text-align:center; cursor:pointer; transition:all .25s; background:#09090b; position:relative; }
.logo-drop:hover, .logo-drop.drag { border-color:#6366f1; background:rgba(99,102,241,.07); }
.logo-drop input[type=file] { position:absolute; inset:0; opacity:0; cursor:pointer; width:100%; height:100%; }
.logo-drop i { font-size:1.6rem; color:#3f3f46; margin-bottom:0.5rem; display:block; }
.logo-drop p { font-size:0.8rem; color:#52525b; }
.logo-drop p span { color:#6366f1; font-weight:600; }

/* Logo current preview */
.logo-preview-box { background:#09090b; border:1px solid #27272a; border-radius:8px; padding:0.8rem; margin-bottom:0.8rem; display:flex; align-items:center; justify-content:space-between; gap:0.5rem; }
.logo-preview-box img { max-width:120px; max-height:50px; object-fit:contain; border-radius:4px; }
.logo-preview-box .no-logo { font-size:0.78rem; color:#52525b; }
.logo-del { background:rgba(239,68,68,.15); border:1px solid rgba(239,68,68,.3); color:#ef4444; border-radius:5px; padding:0.25rem 0.6rem; font-size:0.75rem; font-weight:600; cursor:pointer; white-space:nowrap; flex-shrink:0; }
.logo-del:hover { background:rgba(239,68,68,.25); }

/* Preview panel */
.preview { flex:1; overflow-y:auto; background:#0d0d0f; display:flex; flex-direction:column; }
.prev-bar { display:flex; align-items:center; justify-content:space-between; padding:0.6rem 1.2rem; background:#18181b; border-bottom:1px solid #27272a; flex-shrink:0; }
.prev-bar h3 { font-size:0.85rem; font-weight:600; color:#a1a1aa; display:flex; align-items:center; gap:0.4rem; }
.mbtns { display:flex; gap:0.3rem; background:#09090b; border-radius:6px; padding:0.25rem; }
.mbtn { padding:0.3rem 0.8rem; border-radius:4px; font-size:0.75rem; font-weight:600; cursor:pointer; border:none; color:#71717a; background:transparent; transition:all .2s; }
.mbtn.active { background:#6366f1; color:#fff; }

.prev-body { flex:1; padding:1.2rem; }
.canvas { border-radius:10px; border:1px solid #27272a; overflow:hidden; }
.live { padding:1.2rem; }

/* Toast */
.toast { position:fixed; bottom:1.5rem; right:1.5rem; padding:0.7rem 1.2rem; border-radius:8px; font-size:0.85rem; font-weight:600; display:flex; align-items:center; gap:0.5rem; z-index:9999; transform:translateY(80px); opacity:0; transition:all .35s cubic-bezier(.4,0,.2,1); }
.toast.show { transform:translateY(0); opacity:1; }
.toast.success { background:#22c55e; color:#fff; }
.toast.error { background:#ef4444; color:#fff; }

@media (max-width:700px) {
    .sidebar { width:100%; height:55vh; border-right:none; border-bottom:1px solid #27272a; }
    .studio { flex-direction:column; }
    .preview { min-height:45vh; }
}
</style>
</head>
<body>
<form method="POST" id="tf" enctype="multipart/form-data">
<input type="hidden" name="action" id="faction" value="save">

<div class="topbar">
    <div class="topbar-title"><i class="fa-solid fa-palette"></i> Theme Studio — MyHomeMyLand.LK</div>
    <div class="topbar-actions">
        <button type="button" class="btn btn-reset" onclick="doReset()"><i class="fa-solid fa-rotate-left"></i> Reset</button>
        <button type="submit" class="btn btn-save"><i class="fa-solid fa-floppy-disk"></i> Save Theme</button>
    </div>
</div>

<div class="studio">
<!-- ===== SIDEBAR ===== -->
<aside class="sidebar">
    <div class="tabs">
        <div class="tab active" data-tab="colors">Colors</div>
        <div class="tab" data-tab="dark">Dark</div>
        <div class="tab" data-tab="type">Fonts</div>
        <div class="tab" data-tab="shape">Shape</div>
        <div class="tab" data-tab="logo">Logo</div>
        <div class="tab" data-tab="css">Custom CSS</div>
    </div>
    <div class="panels">

        <!-- COLORS -->
        <div class="panel active" id="panel-colors">
            <div class="stitle">Brand</div>
            <?php foreach([['primary','Primary','--primary'],['primary_hover','Primary Hover','--primary-hover'],['accent','Accent','--accent']] as [$n,$l,$v]): ?>
            <div class="row">
                <div class="lbl"><?=$l?><small><?=$v?></small></div>
                <label class="cpick"><input type="color" name="<?=$n?>" value="<?=htmlspecialchars($settings[$n])?>" class="lc"><span class="hex"><?=htmlspecialchars($settings[$n])?></span></label>
            </div>
            <?php endforeach; ?>
            <div class="stitle">Backgrounds</div>
            <?php foreach([['bg_main','Page BG','--bg-main'],['bg_panel','Panel BG','--bg-panel'],['bg_card','Card BG','--bg-card'],['border_glass','Border','--border-glass']] as [$n,$l,$v]): ?>
            <div class="row">
                <div class="lbl"><?=$l?><small><?=$v?></small></div>
                <label class="cpick"><input type="color" name="<?=$n?>" value="<?=htmlspecialchars($settings[$n])?>" class="lc"><span class="hex"><?=htmlspecialchars($settings[$n])?></span></label>
            </div>
            <?php endforeach; ?>
            <div class="stitle">Text</div>
            <?php foreach([['text_primary','Heading Text','--text-primary'],['text_secondary','Subtle Text','--text-secondary']] as [$n,$l,$v]): ?>
            <div class="row">
                <div class="lbl"><?=$l?><small><?=$v?></small></div>
                <label class="cpick"><input type="color" name="<?=$n?>" value="<?=htmlspecialchars($settings[$n])?>" class="lc"><span class="hex"><?=htmlspecialchars($settings[$n])?></span></label>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- DARK -->
        <div class="panel" id="panel-dark">
            <div class="stitle">Dark Backgrounds</div>
            <?php foreach([['dark_bg_main','Page BG','--bg-main'],['dark_bg_panel','Panel BG','--bg-panel'],['dark_bg_card','Card BG','--bg-card'],['dark_border_glass','Border','--border-glass']] as [$n,$l,$v]): ?>
            <div class="row">
                <div class="lbl"><?=$l?><small>dark <?=$v?></small></div>
                <label class="cpick"><input type="color" name="<?=$n?>" value="<?=htmlspecialchars($settings[$n])?>" class="lc"><span class="hex"><?=htmlspecialchars($settings[$n])?></span></label>
            </div>
            <?php endforeach; ?>
            <div class="stitle">Dark Text</div>
            <?php foreach([['dark_text_primary','Heading Text','--text-primary'],['dark_text_secondary','Subtle Text','--text-secondary']] as [$n,$l,$v]): ?>
            <div class="row">
                <div class="lbl"><?=$l?><small>dark <?=$v?></small></div>
                <label class="cpick"><input type="color" name="<?=$n?>" value="<?=htmlspecialchars($settings[$n])?>" class="lc"><span class="hex"><?=htmlspecialchars($settings[$n])?></span></label>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- FONTS -->
        <div class="panel" id="panel-type">
            <div class="stitle">Font Families</div>
            <div class="row">
                <div class="lbl">Heading<small>h1, h2, h3, .logo</small></div>
                <select name="font_heading" class="fsel" id="fh">
                    <?php foreach(['Outfit','Montserrat','Poppins','Playfair Display','DM Serif Display','Syne','Space Grotesk'] as $f): ?>
                    <option <?=$settings['font_heading']===$f?'selected':''?>><?=$f?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="row">
                <div class="lbl">Body<small>paragraphs, labels</small></div>
                <select name="font_body" class="fsel" id="fb">
                    <?php foreach(['Inter','DM Sans','Nunito','Manrope','Lato','Figtree','Plus Jakarta Sans'] as $f): ?>
                    <option <?=$settings['font_body']===$f?'selected':''?>><?=$f?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div style="margin-top:1rem;background:#09090b;border:1px solid #27272a;border-radius:8px;padding:1rem;">
                <div style="font-size:0.68rem;color:#52525b;margin-bottom:0.5rem;">PREVIEW</div>
                <div id="ph" style="font-size:1.2rem;font-weight:700;margin-bottom:0.3rem;">Find Your Dream Home</div>
                <div id="pb" style="font-size:0.82rem;color:#71717a;line-height:1.5;">Discover verified listings across Sri Lanka.</div>
            </div>
        </div>

        <!-- SHAPE -->
        <div class="panel" id="panel-shape">
            <div class="stitle">Border Radius</div>
            <?php foreach([['lg','Large','--radius-lg',32],['md','Medium','--radius-md',24],['sm','Small','--radius-sm',16]] as [$sz,$label,$var,$max]):
                $cur = (int)$settings['radius_'.$sz]; ?>
            <div class="row">
                <div class="lbl"><?=$label?><small><?=$var?></small></div>
                <div class="srow">
                    <input type="range" min="0" max="<?=$max?>" value="<?=$cur?>" oninput="setR('<?=$sz?>',this.value)">
                    <span class="bdg" id="rv-<?=$sz?>"><?=$cur?>px</span>
                    <input type="hidden" name="radius_<?=$sz?>" id="ri-<?=$sz?>" value="<?=htmlspecialchars($settings['radius_'.$sz])?>">
                </div>
            </div>
            <?php endforeach; ?>
            <div class="stitle">Effects</div>
            <div class="row">
                <div class="lbl">Card hover lift<small>translateY on hover</small></div>
                <label class="tog"><input type="checkbox" name="card_hover_lift" value="yes" <?=$settings['card_hover_lift']==='yes'?'checked':''?>><span class="tog-t"></span></label>
            </div>
            <div class="row">
                <div class="lbl">Shadow style<small>--shadow-card</small></div>
                <select name="shadow_intensity" class="fsel" style="width:90px;" onchange="updatePreview()">
                    <?php foreach(['none','soft','medium','hard'] as $s): ?>
                    <option <?=$settings['shadow_intensity']===$s?'selected':''?>><?=$s?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <!-- ===== LOGO TAB ===== -->
        <div class="panel" id="panel-logo">

            <div class="stitle">Current Logo</div>
            <div class="logo-preview-box">
                <?php if($hasLogo): ?>
                    <img src="<?=htmlspecialchars($logoUrl)?>" alt="Logo" id="logo-current-img">
                    <form method="POST" style="display:inline;" onsubmit="return confirm('Remove logo?');">
                        <input type="hidden" name="action" value="delete_logo">
                        <button type="submit" class="logo-del"><i class="fa-solid fa-trash"></i> Remove</button>
                    </form>
                <?php else: ?>
                    <span class="no-logo"><i class="fa-solid fa-image" style="margin-right:0.3rem;"></i> No logo uploaded yet</span>
                <?php endif; ?>
            </div>

            <!-- Drop zone -->
            <div class="stitle">Upload New Logo</div>
            <div class="logo-drop" id="logo-drop">
                <input type="file" name="logo_file" id="logo-file-input" accept="image/png,image/jpeg,image/gif,image/webp,image/svg+xml">
                <i class="fa-solid fa-cloud-arrow-up"></i>
                <p>Drag & drop or <span>browse</span></p>
                <p style="margin-top:0.3rem;font-size:0.72rem;">PNG, JPG, GIF, WEBP, SVG — max 5MB</p>
            </div>
            <!-- File chosen indicator -->
            <div id="file-chosen" style="display:none;margin-top:0.5rem;background:#09090b;border:1px solid #27272a;border-radius:6px;padding:0.5rem 0.8rem;font-size:0.78rem;color:#a1a1aa;display:none;align-items:center;gap:0.5rem;">
                <i class="fa-solid fa-image" style="color:#6366f1;"></i>
                <span id="file-chosen-name"></span>
                <img id="logo-instant-preview" style="max-height:32px;max-width:80px;object-fit:contain;margin-left:auto;border-radius:3px;" src="" alt="">
            </div>

            <!-- Size controls -->
            <div class="stitle" style="margin-top:1.2rem;">Size</div>
            <div class="row">
                <div class="lbl">Width (px)<small>0 = auto</small></div>
                <div class="srow">
                    <input type="range" min="0" max="400" value="<?=(int)$settings['logo_width']?>" oninput="setLogoNum('logo_width',this.value,'logo-w-bdg','px')">
                    <span class="bdg" id="logo-w-bdg"><?=(int)$settings['logo_width']?>px</span>
                    <input type="number" name="logo_width" id="logo-width-inp" value="<?=(int)$settings['logo_width']?>" min="0" max="600" class="numinp" oninput="syncLogoSlider(this,'logo_width','logo-w-bdg','px')">
                </div>
            </div>
            <div class="row">
                <div class="lbl">Height (px)<small>0 = auto</small></div>
                <div class="srow">
                    <input type="range" min="0" max="120" value="<?=(int)$settings['logo_height']?>" oninput="setLogoNum('logo_height',this.value,'logo-h-bdg','px')">
                    <span class="bdg" id="logo-h-bdg"><?=(int)$settings['logo_height']?>px</span>
                    <input type="number" name="logo_height" id="logo-height-inp" value="<?=(int)$settings['logo_height']?>" min="0" max="200" class="numinp" oninput="syncLogoSlider(this,'logo_height','logo-h-bdg','px')">
                </div>
            </div>
            <div class="row">
                <div class="lbl">Object Fit<small>how image fills box</small></div>
                <select name="logo_fit" id="logo-fit" class="fsel" style="width:90px;" onchange="updateLogoPreview()">
                    <?php foreach(['contain','cover','fill','scale-down'] as $f): ?>
                    <option <?=$settings['logo_fit']===$f?'selected':''?>><?=$f?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="row">
                <div class="lbl">Border Radius (px)<small>rounded corners</small></div>
                <div class="srow">
                    <input type="range" min="0" max="60" value="<?=(int)$settings['logo_border_radius']?>" oninput="setLogoNum('logo_border_radius',this.value,'logo-br-bdg','px')">
                    <span class="bdg" id="logo-br-bdg"><?=(int)$settings['logo_border_radius']?>px</span>
                    <input type="number" name="logo_border_radius" id="logo-border-radius-inp" value="<?=(int)$settings['logo_border_radius']?>" min="0" max="100" class="numinp" oninput="syncLogoSlider(this,'logo_border_radius','logo-br-bdg','px')">
                </div>
            </div>

            <!-- Spacing -->
            <div class="stitle">Spacing</div>
            <div class="row">
                <div class="lbl">Padding Top (px)</div>
                <div class="srow">
                    <input type="range" min="0" max="30" value="<?=(int)$settings['logo_padding_top']?>" oninput="setLogoNum('logo_padding_top',this.value,'logo-pt-bdg','px')">
                    <span class="bdg" id="logo-pt-bdg"><?=(int)$settings['logo_padding_top']?>px</span>
                    <input type="number" name="logo_padding_top" id="logo-padding-top-inp" value="<?=(int)$settings['logo_padding_top']?>" min="0" max="50" class="numinp" oninput="syncLogoSlider(this,'logo_padding_top','logo-pt-bdg','px')">
                </div>
            </div>
            <div class="row">
                <div class="lbl">Padding Bottom (px)</div>
                <div class="srow">
                    <input type="range" min="0" max="30" value="<?=(int)$settings['logo_padding_bottom']?>" oninput="setLogoNum('logo_padding_bottom',this.value,'logo-pb-bdg','px')">
                    <span class="bdg" id="logo-pb-bdg"><?=(int)$settings['logo_padding_bottom']?>px</span>
                    <input type="number" name="logo_padding_bottom" id="logo-padding-bottom-inp" value="<?=(int)$settings['logo_padding_bottom']?>" min="0" max="50" class="numinp" oninput="syncLogoSlider(this,'logo_padding_bottom','logo-pb-bdg','px')">
                </div>
            </div>

            <!-- Logo BG -->
            <div class="stitle">Appearance</div>
            <div class="row">
                <div class="lbl">Logo BG Color<small>empty = transparent</small></div>
                <label class="cpick">
                    <input type="color" name="logo_bg" id="logo-bg-inp" value="<?=htmlspecialchars($settings['logo_bg'] ?: '#ffffff')?>" class="lc" oninput="updateLogoPreview()">
                    <span class="hex" id="logo-bg-hex"><?=htmlspecialchars($settings['logo_bg'] ?: '#ffffff')?></span>
                </label>
            </div>
            <div class="row">
                <div class="lbl">Use logo BG color<small>off = transparent</small></div>
                <label class="tog">
                    <input type="checkbox" id="logo-bg-toggle" <?=!empty($settings['logo_bg'])?'checked':''?> onchange="updateLogoPreview()">
                    <span class="tog-t"></span>
                </label>
                <input type="hidden" name="logo_bg" id="logo-bg-val" value="<?=htmlspecialchars($settings['logo_bg'])?>">
            </div>

            <!-- Text beside logo -->
            <div class="stitle">Text Beside Logo</div>
            <div class="row">
                <div class="lbl">Show site name text</div>
                <label class="tog">
                    <input type="checkbox" name="logo_show_text" id="logo-show-text" value="yes" <?=$settings['logo_show_text']==='yes'?'checked':''?> onchange="updateLogoPreview()">
                    <span class="tog-t"></span>
                </label>
            </div>
            <div class="row" style="margin-top:0.5rem;">
                <div class="lbl">Site Name Text</div>
            </div>
            <input type="text" name="logo_text" id="logo-text-inp" value="<?=htmlspecialchars($settings['logo_text'])?>" class="tinp" placeholder="MyHomeMyLand.LK" oninput="updateLogoPreview()" style="margin-bottom:0.65rem;">
            <div class="row">
                <div class="lbl">Text Size (px)</div>
                <div class="srow">
                    <input type="range" min="12" max="40" value="<?=(int)$settings['logo_text_size']?>" oninput="setLogoNum('logo_text_size',this.value,'logo-ts-bdg','px')">
                    <span class="bdg" id="logo-ts-bdg"><?=(int)$settings['logo_text_size']?>px</span>
                    <input type="number" name="logo_text_size" id="logo-text-size-inp" value="<?=(int)$settings['logo_text_size']?>" min="10" max="60" class="numinp" oninput="syncLogoSlider(this,'logo_text_size','logo-ts-bdg','px')">
                </div>
            </div>
            <div class="row">
                <div class="lbl">Text Color<small>empty = uses --primary</small></div>
                <label class="cpick">
                    <input type="color" name="logo_text_color" id="logo-tc-inp" value="<?=htmlspecialchars($settings['logo_text_color'] ?: '#4f46e5')?>" class="lc" oninput="updateLogoPreview()">
                    <span class="hex"><?=htmlspecialchars($settings['logo_text_color'] ?: '#4f46e5')?></span>
                </label>
            </div>

            <!-- Position -->
            <div class="stitle">Position in Navbar</div>
            <div class="row">
                <div class="lbl">Logo alignment</div>
                <select name="logo_position" id="logo-pos" class="fsel" onchange="updateLogoPreview()">
                    <?php foreach(['left','center','right'] as $p): ?>
                    <option <?=$settings['logo_position']===$p?'selected':''?>><?=$p?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <p style="font-size:0.72rem;color:#52525b;margin-top:1rem;line-height:1.6;">
                <i class="fa-solid fa-circle-info" style="color:#6366f1;margin-right:0.3rem;"></i>
                After saving, add <code style="color:#a5f3fc;">get-theme.php</code> to your pages to apply the logo site-wide.
            </p>
        </div>

        <!-- CUSTOM CSS -->
        <div class="panel" id="panel-css">
            <div class="stitle">Custom CSS</div>
            <p style="font-size:0.78rem;color:#52525b;margin-bottom:0.8rem;line-height:1.5;">Injected into every page via <code style="color:#a5f3fc;">get-theme.php</code></p>
            <textarea name="custom_css" class="cssed" placeholder="/* e.g. */&#10;.property-card { border-radius: 20px; }"><?=htmlspecialchars($settings['custom_css'])?></textarea>
        </div>

    </div><!-- /panels -->
</aside>

<!-- ===== PREVIEW ===== -->
<div class="preview">
    <div class="prev-bar">
        <h3><i class="fa-solid fa-eye" style="color:#06b6d4;"></i> Live Preview</h3>
        <div class="mbtns">
            <button type="button" class="mbtn active" onclick="setMode('light',this)"><i class="fa-solid fa-sun"></i> Light</button>
            <button type="button" class="mbtn" onclick="setMode('dark',this)"><i class="fa-solid fa-moon"></i> Dark</button>
        </div>
    </div>
    <div class="prev-body">
        <div class="canvas" id="canvas">
            <div class="live" id="live">
                <!-- Navbar with logo -->
                <div id="prev-nav" style="display:flex;align-items:center;justify-content:space-between;padding:0.7rem 1rem;margin:-1.2rem -1.2rem 1.2rem;border-bottom:1px solid var(--pv-border);background:var(--pv-panel);">
                    <div id="prev-logo-wrap" style="display:flex;align-items:center;gap:0.5rem;">
                        <img id="prev-logo-img" src="" alt="Logo" style="display:none;object-fit:contain;">
                        <span id="prev-logo-text" style="font-family:var(--pv-fh);font-size:1rem;font-weight:800;color:var(--pv-tp);display:flex;align-items:center;gap:0.3rem;"><span style="color:var(--pv-primary);">⌂</span> <?=htmlspecialchars($settings['logo_text'])?></span>
                    </div>
                    <div style="display:flex;gap:0.7rem;align-items:center;">
                        <span style="font-size:0.8rem;color:var(--pv-ts);font-family:var(--pv-fb);">Explore</span>
                        <span style="background:linear-gradient(135deg,var(--pv-primary),var(--pv-ph));color:#fff;padding:0.35rem 0.9rem;border-radius:50px;font-size:0.78rem;font-weight:600;font-family:var(--pv-fb);">List Property</span>
                    </div>
                </div>
                <!-- Cards -->
                <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:0.8rem;margin-bottom:1rem;">
                    <?php foreach([['2BR Colombo 3','Rs. 85k','Apartment'],['Studio Kandy','Rs. 32k','Studio'],['Villa Galle','Rs. 150k','Villa']] as $c): ?>
                    <div style="background:var(--pv-card);border:1px solid var(--pv-border);border-radius:var(--pv-rm);overflow:hidden;box-shadow:var(--pv-sh);">
                        <div style="height:90px;background:linear-gradient(135deg,var(--pv-primary),var(--pv-accent));position:relative;">
                            <span style="position:absolute;top:6px;left:6px;background:rgba(0,0,0,.45);color:#fff;font-size:0.65rem;padding:0.1rem 0.4rem;border-radius:4px;font-family:var(--pv-fb);"><?=$c[2]?></span>
                            <span style="position:absolute;bottom:6px;left:6px;background:var(--pv-primary);color:#fff;font-size:0.72rem;font-weight:700;padding:0.2rem 0.45rem;border-radius:var(--pv-rs);font-family:var(--pv-fb);"><?=$c[1]?></span>
                        </div>
                        <div style="padding:0.6rem;">
                            <div style="font-size:0.8rem;font-weight:600;color:var(--pv-tp);font-family:var(--pv-fh);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?=$c[0]?></div>
                            <div style="font-size:0.7rem;color:var(--pv-ts);font-family:var(--pv-fb);border-top:1px solid var(--pv-border);margin-top:0.4rem;padding-top:0.3rem;">2 Bed · 2 Bath</div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <!-- Buttons -->
                <div style="display:flex;gap:0.6rem;flex-wrap:wrap;margin-bottom:1rem;">
                    <button type="button" style="background:linear-gradient(135deg,var(--pv-primary),var(--pv-ph));color:#fff;border:none;padding:0.5rem 1.2rem;border-radius:50px;font-weight:600;font-size:0.82rem;font-family:var(--pv-fb);cursor:pointer;">Search</button>
                    <button type="button" style="background:var(--pv-card);border:1px solid var(--pv-border);color:var(--pv-tp);padding:0.5rem 1.2rem;border-radius:50px;font-weight:600;font-size:0.82rem;font-family:var(--pv-fb);cursor:pointer;">View Map</button>
                    <span style="background:rgba(79,70,229,.1);color:var(--pv-primary);padding:0.25rem 0.7rem;border-radius:50px;font-size:0.75rem;font-weight:600;font-family:var(--pv-fb);">✓ Approved</span>
                </div>
                <!-- Radius demo -->
                <div style="background:var(--pv-panel);border:1px solid var(--pv-border);border-radius:var(--pv-rm);padding:0.8rem 1rem;">
                    <div style="font-family:var(--pv-fh);font-size:0.85rem;font-weight:700;color:var(--pv-tp);margin-bottom:0.5rem;">Radius & Shadow</div>
                    <div style="display:flex;gap:0.6rem;flex-wrap:wrap;">
                        <div style="background:var(--pv-card);border:1px solid var(--pv-border);border-radius:var(--pv-rl);padding:0.4rem 0.8rem;font-size:0.75rem;color:var(--pv-ts);font-family:var(--pv-fb);box-shadow:var(--pv-sh);">radius-lg</div>
                        <div style="background:var(--pv-primary);border-radius:var(--pv-rs);padding:0.25rem 0.6rem;font-size:0.75rem;color:#fff;font-family:var(--pv-fb);">radius-sm</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</div><!-- /studio -->
</form>

<div class="toast" id="toast"><i class="fa-solid fa-check-circle"></i> <span id="tmsg"></span></div>

<script>
let mode = 'light';
// Current logo URL from server
let currentLogoUrl = <?=json_encode($logoUrl)?>;

const shadows = {
    none:'none',
    soft:'0 4px 6px -1px rgba(0,0,0,.05),0 2px 4px -1px rgba(0,0,0,.03)',
    medium:'0 10px 15px -3px rgba(0,0,0,.1),0 4px 6px -2px rgba(0,0,0,.05)',
    hard:'0 20px 25px -5px rgba(0,0,0,.2),0 10px 10px -5px rgba(0,0,0,.1)'
};

function vals() {
    const v = {};
    document.querySelectorAll('.lc').forEach(i => v[i.name] = i.value);
    ['lg','md','sm'].forEach(s => v['radius_'+s] = document.getElementById('ri-'+s).value);
    v.font_heading = document.getElementById('fh').value;
    v.font_body    = document.getElementById('fb').value;
    v.shadow_intensity = document.querySelector('[name=shadow_intensity]').value;
    return v;
}

function loadFont(f) {
    const id = 'gf-'+f.replace(/\s/g,'-');
    if (!document.getElementById(id)) {
        const l = document.createElement('link');
        l.id=id; l.rel='stylesheet';
        l.href=`https://fonts.googleapis.com/css2?family=${encodeURIComponent(f)}:wght@400;600;700;800&display=swap`;
        document.head.appendChild(l);
    }
}

function updatePreview() {
    const v = vals();
    const dark   = mode === 'dark';
    const bg     = dark ? v.dark_bg_main     : v.bg_main;
    const panel  = dark ? v.dark_bg_panel    : v.bg_panel;
    const card   = dark ? v.dark_bg_card     : v.bg_card;
    const border = dark ? v.dark_border_glass: v.border_glass;
    const tp     = dark ? v.dark_text_primary: v.text_primary;
    const ts     = dark ? v.dark_text_secondary: v.text_secondary;
    const sh     = shadows[v.shadow_intensity] || shadows.soft;

    loadFont(v.font_heading); loadFont(v.font_body);

    const live = document.getElementById('live');
    const s = (k,val) => live.style.setProperty(k,val);
    s('--pv-primary', v.primary);
    s('--pv-ph', v.primary_hover);
    s('--pv-accent', v.accent);
    s('--pv-panel', panel);
    s('--pv-card', card);
    s('--pv-border', border);
    s('--pv-tp', tp);
    s('--pv-ts', ts);
    s('--pv-rl', v.radius_lg);
    s('--pv-rm', v.radius_md);
    s('--pv-rs', v.radius_sm);
    s('--pv-fh', `'${v.font_heading}',sans-serif`);
    s('--pv-fb', `'${v.font_body}',sans-serif`);
    s('--pv-sh', sh);
    live.style.background = bg;
    const canvas = document.getElementById('canvas');
    canvas.style.background = bg;
    canvas.style.borderColor = border;

    document.getElementById('ph').style.fontFamily = `'${v.font_heading}',sans-serif`;
    document.getElementById('pb').style.fontFamily = `'${v.font_body}',sans-serif`;

    updateLogoPreview();
}

// ===== LOGO PREVIEW =====
function updateLogoPreview() {
    const img     = document.getElementById('prev-logo-img');
    const txt     = document.getElementById('prev-logo-text');
    const wrap    = document.getElementById('prev-logo-wrap');
    const nav     = document.getElementById('prev-nav');

    const w       = document.getElementById('logo-width-inp').value;
    const h       = document.getElementById('logo-height-inp').value;
    const br      = document.getElementById('logo-border-radius-inp').value;
    const pt      = document.getElementById('logo-padding-top-inp').value;
    const pb      = document.getElementById('logo-padding-bottom-inp').value;
    const fit     = document.getElementById('logo-fit').value;
    const bgToggle= document.getElementById('logo-bg-toggle').checked;
    const bgColor = document.getElementById('logo-bg-inp').value;
    const showTxt = document.getElementById('logo-show-text').checked;
    const logoTxt = document.getElementById('logo-text-inp').value;
    const txtSize = document.getElementById('logo-text-size-inp').value;
    const txtColor= document.getElementById('logo-tc-inp').value;
    const pos     = document.getElementById('logo-pos').value;

    // Update hidden BG field
    document.getElementById('logo-bg-val').value = bgToggle ? bgColor : '';

    // Determine logo src
    const instantSrc = document.getElementById('logo-instant-preview').src;
    const logoSrc = instantSrc && instantSrc !== window.location.href ? instantSrc : (currentLogoUrl || '');

    if (logoSrc) {
        img.src = logoSrc;
        img.style.display = 'block';
        img.style.width  = w > 0 ? w+'px' : 'auto';
        img.style.height = h > 0 ? h+'px' : 'auto';
        img.style.maxWidth = w > 0 ? w+'px' : '200px';
        img.style.maxHeight = h > 0 ? h+'px' : '60px';
        img.style.objectFit = fit;
        img.style.borderRadius = br+'px';
        img.style.paddingTop = pt+'px';
        img.style.paddingBottom = pb+'px';
        img.style.background = bgToggle ? bgColor : 'transparent';
    } else {
        img.style.display = 'none';
    }

    // Text
    txt.style.display = showTxt ? 'flex' : 'none';
    txt.childNodes.forEach(n => { if(n.nodeType===3) n.textContent = ' '+logoTxt; });
    const textSpan = txt.querySelector('span');
    if (textSpan) textSpan.style.color = 'var(--pv-primary)';
    txt.style.fontSize = txtSize+'px';
    txt.style.color = txtColor;

    // Position
    if (pos === 'center') {
        nav.style.justifyContent = 'space-between';
        wrap.style.position = 'absolute';
        wrap.style.left = '50%';
        wrap.style.transform = 'translateX(-50%)';
        nav.style.position = 'relative';
    } else if (pos === 'right') {
        nav.style.flexDirection = 'row-reverse';
        wrap.style.position = '';
        wrap.style.left = '';
        wrap.style.transform = '';
        nav.style.position = '';
    } else {
        nav.style.flexDirection = 'row';
        wrap.style.position = '';
        wrap.style.left = '';
        wrap.style.transform = '';
        nav.style.position = '';
    }
}

// ===== SLIDER SYNC HELPERS =====
function setR(sz, val) {
    const px = val+'px';
    document.getElementById('rv-'+sz).textContent = px;
    document.getElementById('ri-'+sz).value = px;
    updatePreview();
}

// Logo sliders: range → updates badge + number input
function setLogoNum(field, val, badgeId, suffix) {
    document.getElementById(badgeId).textContent = val+suffix;
    // Find the number input by its name
    const inp = document.querySelector(`input[name="${field}"]`);
    if (inp && inp.type === 'number') inp.value = val;
    updateLogoPreview();
}

// Logo number inputs: type → updates badge + range
function syncLogoSlider(inp, field, badgeId, suffix) {
    const val = inp.value;
    document.getElementById(badgeId).textContent = val+suffix;
    // Find associated range input in the same .srow
    const srow = inp.closest('.srow');
    if (srow) {
        const range = srow.querySelector('input[type=range]');
        if (range) range.value = val;
    }
    updateLogoPreview();
}

function setMode(m, btn) {
    mode = m;
    document.querySelectorAll('.mbtn').forEach(b=>b.classList.remove('active'));
    btn.classList.add('active');
    updatePreview();
}

function doReset() {
    if (!confirm('Reset all theme settings to defaults?')) return;
    document.getElementById('faction').value = 'reset';
    document.getElementById('tf').submit();
}

// Color inputs
document.querySelectorAll('.lc').forEach(inp => {
    inp.addEventListener('input', function() {
        this.closest('.cpick').querySelector('.hex').textContent = this.value;
        updatePreview();
    });
});

// Tabs
document.getElementById('fh').addEventListener('change', updatePreview);
document.getElementById('fb').addEventListener('change', updatePreview);
document.querySelectorAll('.tab').forEach(t => {
    t.addEventListener('click', function() {
        document.querySelectorAll('.tab').forEach(x=>x.classList.remove('active'));
        document.querySelectorAll('.panel').forEach(x=>x.classList.remove('active'));
        this.classList.add('active');
        document.getElementById('panel-'+this.dataset.tab).classList.add('active');
    });
});

// ===== FILE INPUT / DRAG DROP =====
const dropZone = document.getElementById('logo-drop');
const fileInput = document.getElementById('logo-file-input');
const fileChosen = document.getElementById('file-chosen');
const fileChosenName = document.getElementById('file-chosen-name');
const instantPreview = document.getElementById('logo-instant-preview');

function handleFile(file) {
    if (!file) return;
    fileChosenName.textContent = file.name;
    fileChosen.style.display = 'flex';
    const reader = new FileReader();
    reader.onload = e => {
        instantPreview.src = e.target.result;
        updateLogoPreview();
    };
    reader.readAsDataURL(file);
}

fileInput.addEventListener('change', () => handleFile(fileInput.files[0]));

dropZone.addEventListener('dragover', e => { e.preventDefault(); dropZone.classList.add('drag'); });
dropZone.addEventListener('dragleave', () => dropZone.classList.remove('drag'));
dropZone.addEventListener('drop', e => {
    e.preventDefault();
    dropZone.classList.remove('drag');
    const file = e.dataTransfer.files[0];
    if (file) {
        // Transfer to input
        const dt = new DataTransfer();
        dt.items.add(file);
        fileInput.files = dt.files;
        handleFile(file);
    }
});

// Init
updatePreview();
<?php if($msg): ?>
(()=>{
    const t=document.getElementById('toast');
    t.className='toast <?=$msg_type?> show';
    document.getElementById('tmsg').textContent='<?=addslashes($msg)?>';
    setTimeout(()=>t.classList.remove('show'),3500);
})();
<?php endif; ?>
</script>
</body>
</html>