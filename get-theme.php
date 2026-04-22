<?php
/**
 * get-theme.php
 * -------------------------------------------------------------------
 * Include in every page <head> AFTER style.css:
 *
 *     <link rel="stylesheet" href="style.css">
 *     <?php include 'get-theme.php'; ?>
 *
 * Then in your navbar, replace the logo <a> with:
 *
 *     <?php include 'logo-partial.php'; ?>
 * -------------------------------------------------------------------
 */

if (!isset($pdo)) {
    require_once __DIR__ . '/auth_check.php';
}

$theme = [];
try {
    $theme = $pdo->query("SELECT setting_key, setting_value FROM site_settings")->fetchAll(PDO::FETCH_KEY_PAIR);
} catch (Exception $e) { $theme = []; }

if (empty($theme)) return;

$shadowMap = [
    'none'   => 'none',
    'soft'   => '0 4px 6px -1px rgba(0,0,0,0.05), 0 2px 4px -1px rgba(0,0,0,0.03)',
    'medium' => '0 10px 15px -3px rgba(0,0,0,0.1), 0 4px 6px -2px rgba(0,0,0,0.05)',
    'hard'   => '0 20px 25px -5px rgba(0,0,0,0.2), 0 10px 10px -5px rgba(0,0,0,0.1)',
];
$shadow = $shadowMap[$theme['shadow_intensity'] ?? 'soft'] ?? $shadowMap['soft'];

function tv($theme, $key, $fallback = '') {
    return htmlspecialchars($theme[$key] ?? $fallback, ENT_QUOTES, 'UTF-8');
}

$fontHeading = $theme['font_heading'] ?? 'Outfit';
$fontBody    = $theme['font_body']    ?? 'Inter';
$fontsToLoad = array_unique([$fontHeading, $fontBody]);
$googleFontsUrl = 'https://fonts.googleapis.com/css2?family='
    . implode('&family=', array_map(fn($f) => urlencode($f).':wght@400;600;700;800', $fontsToLoad))
    . '&display=swap';

$hoverLift = ($theme['card_hover_lift'] ?? 'yes') === 'yes' ? 'transform: translateY(-3px);' : '';
$customCss = $theme['custom_css'] ?? '';

// Logo vars
$logoUrl    = $theme['logo_url']    ?? '';
$logoW      = (int)($theme['logo_width']    ?? 140);
$logoH      = (int)($theme['logo_height']   ?? 40);
$logoBr     = (int)($theme['logo_border_radius'] ?? 0);
$logoPt     = (int)($theme['logo_padding_top']    ?? 0);
$logoPb     = (int)($theme['logo_padding_bottom']  ?? 0);
$logoFit    = $theme['logo_fit']    ?? 'contain';
$logoBg     = $theme['logo_bg']     ?? '';
$logoShowTxt= ($theme['logo_show_text'] ?? 'yes') === 'yes';
$logoTxt    = $theme['logo_text']   ?? 'MyHomeMyLand.LK';
$logoTxtSz  = (int)($theme['logo_text_size']  ?? 22);
$logoTxtClr = $theme['logo_text_color'] ?? '';
$logoPos    = $theme['logo_position'] ?? 'left';
?>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="<?= $googleFontsUrl ?>" rel="stylesheet">
<style>
:root {
    <?php if (!empty($theme['primary'])): ?>--primary: <?= tv($theme,'primary') ?>;<?php endif; ?>
    <?php if (!empty($theme['primary_hover'])): ?>--primary-hover: <?= tv($theme,'primary_hover') ?>;<?php endif; ?>
    <?php if (!empty($theme['accent'])): ?>--accent: <?= tv($theme,'accent') ?>;<?php endif; ?>
    <?php if (!empty($theme['bg_main'])): ?>--bg-main: <?= tv($theme,'bg_main') ?>;<?php endif; ?>
    <?php if (!empty($theme['bg_panel'])): ?>--bg-panel: <?= tv($theme,'bg_panel') ?>;<?php endif; ?>
    <?php if (!empty($theme['bg_card'])): ?>--bg-card: <?= tv($theme,'bg_card') ?>;<?php endif; ?>
    <?php if (!empty($theme['border_glass'])): ?>--border-glass: <?= tv($theme,'border_glass') ?>;<?php endif; ?>
    <?php if (!empty($theme['text_primary'])): ?>--text-primary: <?= tv($theme,'text_primary') ?>;<?php endif; ?>
    <?php if (!empty($theme['text_secondary'])): ?>--text-secondary: <?= tv($theme,'text_secondary') ?>;<?php endif; ?>
    <?php if (!empty($theme['radius_lg'])): ?>--radius-lg: <?= tv($theme,'radius_lg') ?>;<?php endif; ?>
    <?php if (!empty($theme['radius_md'])): ?>--radius-md: <?= tv($theme,'radius_md') ?>;<?php endif; ?>
    <?php if (!empty($theme['radius_sm'])): ?>--radius-sm: <?= tv($theme,'radius_sm') ?>;<?php endif; ?>
    --shadow-card: <?= htmlspecialchars($shadow) ?>;
}
[data-theme="dark"] {
    <?php if (!empty($theme['dark_bg_main'])): ?>--bg-main: <?= tv($theme,'dark_bg_main') ?>;<?php endif; ?>
    <?php if (!empty($theme['dark_bg_panel'])): ?>--bg-panel: <?= tv($theme,'dark_bg_panel') ?>;<?php endif; ?>
    <?php if (!empty($theme['dark_bg_card'])): ?>--bg-card: <?= tv($theme,'dark_bg_card') ?>;<?php endif; ?>
    <?php if (!empty($theme['dark_border_glass'])): ?>--border-glass: <?= tv($theme,'dark_border_glass') ?>;<?php endif; ?>
    <?php if (!empty($theme['dark_text_primary'])): ?>--text-primary: <?= tv($theme,'dark_text_primary') ?>;<?php endif; ?>
    <?php if (!empty($theme['dark_text_secondary'])): ?>--text-secondary: <?= tv($theme,'dark_text_secondary') ?>;<?php endif; ?>
}
<?php if ($fontHeading !== 'Outfit'): ?>
h1,h2,h3,h4,.logo { font-family:'<?= htmlspecialchars($fontHeading) ?>',sans-serif; }
<?php endif; ?>
<?php if ($fontBody !== 'Inter'): ?>
body,p,span,a,label,input,select,textarea,button { font-family:'<?= htmlspecialchars($fontBody) ?>',sans-serif; }
<?php endif; ?>
<?php if ($hoverLift): ?>
.property-card:hover { <?= $hoverLift ?> }
<?php endif; ?>
<?php if (!empty($logoUrl)): ?>
/* Logo image styles */
.site-logo-img {
    width: <?= $logoW > 0 ? $logoW.'px' : 'auto' ?>;
    height: <?= $logoH > 0 ? $logoH.'px' : 'auto' ?>;
    max-width: <?= $logoW > 0 ? $logoW.'px' : '200px' ?>;
    object-fit: <?= htmlspecialchars($logoFit) ?>;
    border-radius: <?= $logoBr ?>px;
    padding-top: <?= $logoPt ?>px;
    padding-bottom: <?= $logoPb ?>px;
    <?php if (!empty($logoBg)): ?>background: <?= htmlspecialchars($logoBg) ?>;<?php endif; ?>
    display: block;
}
<?php endif; ?>
<?php if (!empty($logoTxtSz) && $logoShowTxt): ?>
.logo-text-override {
    font-size: <?= $logoTxtSz ?>px !important;
    <?php if (!empty($logoTxtClr)): ?>color: <?= htmlspecialchars($logoTxtClr) ?> !important;<?php endif; ?>
}
<?php endif; ?>
<?php if ($logoPos === 'center'): ?>
.navbar { position: relative; }
.logo { position: absolute; left: 50%; transform: translateX(-50%); }
<?php elseif ($logoPos === 'right'): ?>
.navbar { flex-direction: row-reverse; }
<?php endif; ?>
<?php if (!empty(trim($customCss))): ?>
/* Custom Admin CSS */
<?= strip_tags($customCss) ?>
<?php endif; ?>
</style>