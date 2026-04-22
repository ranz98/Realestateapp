<?php
/**
 * logo-partial.php — include inside <nav class="navbar"> to replace hardcoded logo.
 *
 * Replace:
 *     <a href="index.php" class="logo" style="text-decoration: none;">
 *         <i class="fa-solid fa-house-chimney-window"></i> MyHomeMyLand.LK
 *     </a>
 * With:
 *     <?php include 'logo-partial.php'; ?>
 */

// Reuse $pdo that's already set up by auth_check.php — never reconnect
$_logo = [];
try {
    $rows = $pdo->query("SELECT setting_key, setting_value FROM site_settings")->fetchAll(PDO::FETCH_KEY_PAIR);
    $_logo = $rows;
} catch (Exception $e) {
    $_logo = [];
}

$_logoUrl     = $_logo['logo_url']        ?? '';
$_logoShowTxt = ($_logo['logo_show_text'] ?? 'yes') === 'yes';
$_logoTxt     = $_logo['logo_text']       ?? 'MyHomeMyLand.LK';
$_logoTxtSz   = (int)($_logo['logo_text_size'] ?? 22);
$_logoTxtClr  = $_logo['logo_text_color'] ?? '';
$_logoW       = (int)($_logo['logo_width']  ?? 0);
$_logoH       = (int)($_logo['logo_height'] ?? 0);
$_logoBr      = (int)($_logo['logo_border_radius'] ?? 0);
$_logoPt      = (int)($_logo['logo_padding_top']    ?? 0);
$_logoPb      = (int)($_logo['logo_padding_bottom']  ?? 0);
$_logoFit     = $_logo['logo_fit'] ?? 'contain';
$_logoBg      = $_logo['logo_bg']  ?? '';

$_hasLogo = !empty($_logoUrl);

// Build inline style for the image
$_imgStyle = '';
$_imgStyle .= $_logoW > 0 ? "width:{$_logoW}px;"         : 'width:auto;';
$_imgStyle .= $_logoH > 0 ? "height:{$_logoH}px;"        : 'height:auto;';
$_imgStyle .= $_logoW > 0 ? "max-width:{$_logoW}px;"     : 'max-width:200px;';
$_imgStyle .= "object-fit:{$_logoFit};";
$_imgStyle .= "border-radius:{$_logoBr}px;";
$_imgStyle .= "padding-top:{$_logoPt}px;";
$_imgStyle .= "padding-bottom:{$_logoPb}px;";
$_imgStyle .= "display:block;";
if (!empty($_logoBg)) $_imgStyle .= "background:{$_logoBg};";
?>
<a href="index.php" class="logo" style="text-decoration:none;display:flex;align-items:center;gap:0.5rem;">
    <?php if ($_hasLogo): ?>
        <img src="<?= htmlspecialchars($_logoUrl) ?>"
             alt="<?= htmlspecialchars($_logoTxt) ?>"
             style="<?= $_imgStyle ?>">
    <?php else: ?>
        <i class="fa-solid fa-house-chimney-window" style="color:var(--primary);font-size:1.8rem;"></i>
    <?php endif; ?>

    <?php if ($_logoShowTxt || !$_hasLogo): ?>
        <span style="
            font-family:'Outfit',sans-serif;
            font-size:<?= $_logoTxtSz ?>px;
            font-weight:800;
            letter-spacing:-0.5px;
            <?= !empty($_logoTxtClr) ? 'color:'.htmlspecialchars($_logoTxtClr).';' : 'color:var(--text-primary);' ?>
        "><?= htmlspecialchars($_logoTxt) ?></span>
    <?php endif; ?>
</a>