<?php
$key = $_GET['key'] ?? '';
if (!$key) {
    header('Location: index.html');
    exit;
}

$properties = json_decode(file_get_contents('data/properties.json'), true);
$villa = $properties[$key] ?? null;

if (!$villa) {
    header('Location: index.html');
    exit;
}

// ── Normalisation unifiée des images ─────────────────────────────
$allImages = [];
if (!empty($villa['images']) && is_array($villa['images'])) {
    foreach ($villa['images'] as $imgUrl) {
        if (is_string($imgUrl) && trim($imgUrl) !== '' && !in_array(trim($imgUrl), $allImages)) {
            $allImages[] = trim($imgUrl);
        }
    }
}

if (empty($allImages) && !empty($villa['photos']) && is_array($villa['photos'])) {
    if (isset($villa['photos']['exterieur']) || isset($villa['photos']['interieur']) || isset($villa['photos']['chambres'])) {
        foreach (['exterieur', 'interieur', 'chambres'] as $cat) {
            if (!empty($villa['photos'][$cat]) && is_array($villa['photos'][$cat])) {
                foreach ($villa['photos'][$cat] as $imgUrl) {
                    if (is_string($imgUrl) && trim($imgUrl) !== '' && !in_array(trim($imgUrl), $allImages)) {
                        $allImages[] = trim($imgUrl);
                    }
                }
            }
        }
    } else {
        foreach ($villa['photos'] as $imgUrl) {
            if (is_string($imgUrl) && trim($imgUrl) !== '' && !in_array(trim($imgUrl), $allImages)) {
                $allImages[] = trim($imgUrl);
            }
        }
    }
}

if (empty($allImages)) {
    $allImages = ['https://images.unsplash.com/photo-1613977257363-707ba9348227?w=1200&q=80'];
}

function getImageUrl($url) {
    if (empty($url)) return '';
    if (strpos($url, 'http') === 0) return $url;
    return ltrim($url, '/');
}

$coverImage = getImageUrl($allImages[0]);
$carouselImages = array_slice($allImages, 0, 8);

// Villa base color mappings (site identities)
function isLightColor($hexcolor) {
    $hexcolor = str_replace('#', '', $hexcolor ?: '#0f1a17');
    if (strlen($hexcolor) == 3) {
        $hexcolor = str_repeat(substr($hexcolor,0,1), 2) . str_repeat(substr($hexcolor,1,1), 2) . str_repeat(substr($hexcolor,2,1), 2);
    }
    if (strlen($hexcolor) != 6) return false;
    $r = hexdec(substr($hexcolor,0,2));
    $g = hexdec(substr($hexcolor,2,2));
    $b = hexdec(substr($hexcolor,4,2));
    $yiq = (($r*299)+($g*587)+($b*114))/1000;
    return $yiq >= 128;
}

function adjustColorBrightness($hex, $steps) {
    $hex = str_replace('#', '', $hex ?: '#ffffff');
    if (strlen($hex) == 3) {
        $hex = str_repeat(substr($hex,0,1), 2) . str_repeat(substr($hex,1,1), 2) . str_repeat(substr($hex,2,1), 2);
    }
    if (strlen($hex) != 6) return $hex;
    $r = hexdec(substr($hex, 0, 2));
    $g = hexdec(substr($hex, 2, 2));
    $b = hexdec(substr($hex, 4, 2));

    $r = max(0, min(255, $r + $steps));
    $g = max(0, min(255, $g + $steps));
    $b = max(0, min(255, $b + $steps));

    return '#' . str_pad(dechex($r), 2, '0', STR_PAD_LEFT) . str_pad(dechex($g), 2, '0', STR_PAD_LEFT) . str_pad(dechex($b), 2, '0', STR_PAD_LEFT);
}

$villaBaseColors = [
    'guede' => '#3E2922',
    'casa' => '#021024',
    'palm' => '#0F2A1D',
    'torino' => '#122324',
    'vhouse' => '#021024',
    'villa-familiale' => '#3E2922',
    'terrain-ngaparou' => '#0F2A1D',
    'villa-ngaparou' => '#0F2A1D'
];

$defaultBase = $villaBaseColors[$key] ?? '#0f1a17';
$villaColor = $villa['color'] ?? $defaultBase;

// Automatically generate 3 harmonized overlapping colors
if (isLightColor($villaColor)) {
    // Light base color -> darken to preserve text contrast
    $villaPalette = [
        $villaColor,
        adjustColorBrightness($villaColor, -30),
        adjustColorBrightness($villaColor, -60)
    ];
} else {
    // Dark base color -> lighten incrementally
    $villaPalette = [
        $villaColor,
        adjustColorBrightness($villaColor, 35),
        adjustColorBrightness($villaColor, 70)
    ];
}

function getContrastColor($hexcolor) {
    $hexcolor = str_replace('#', '', $hexcolor ?: '#ffffff');
    if (strlen($hexcolor) == 3) {
        $hexcolor = str_repeat(substr($hexcolor,0,1), 2) . str_repeat(substr($hexcolor,1,1), 2) . str_repeat(substr($hexcolor,2,1), 2);
    }
    if (strlen($hexcolor) != 6) return '#ffffff';
    $r = hexdec(substr($hexcolor,0,2));
    $g = hexdec(substr($hexcolor,2,2));
    $b = hexdec(substr($hexcolor,4,2));
    $yiq = (($r*299)+($g*587)+($b*114))/1000;
    return ($yiq >= 140) ? '#000000' : '#ffffff';
}

$themeClass = isLightColor($villaColor) ? 'light-theme' : '';
$isVacances = ($villa['type'] ?? '') === 'vacances';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <!-- Google tag (gtag.js) -->
  <script async src="https://www.googletagmanager.com/gtag/js?id=G-RC9Q62DRJ9"></script>
  <script>
    window.dataLayer = window.dataLayer || [];
    function gtag(){dataLayer.push(arguments);}
    gtag('js', new Date());

    gtag('config', 'G-RC9Q62DRJ9');
  </script>
  <script src="cookies.js" defer></script>
  <script src="js/i18n.js?v=6"></script>
  <script>

    window.openReservationModal = function(slug, name, price) {
      var s = slug || <?= json_encode($key) ?>;
      var n = name || <?= json_encode($villa['name']) ?>;
      var p = price !== undefined && price !== null ? price : <?= (float)($villa['price'] ?? 0) ?>;

      var modal = document.getElementById('reservationModal');
      if (!modal) return;

      var nameEl = document.getElementById('resVillaName');
      var priceEl = document.getElementById('resVillaPrice');
      var slugEl = document.getElementById('resVillaSlug');
      var priceValEl = document.getElementById('resVillaPriceValue');
      
      if (nameEl) nameEl.textContent = n;
      if (priceEl) priceEl.textContent = p ? 'Prix : ' + Number(p).toLocaleString('fr-FR') + ' FCFA / nuit' : 'Prix sur demande';
      if (slugEl) slugEl.value = s;
      if (priceValEl) priceValEl.value = p;
      
      var today = new Date().toISOString().split('T')[0];
      var startEl = document.getElementById('resStartDate');
      var endEl = document.getElementById('resEndDate');
      if (startEl) startEl.min = today;
      if (endEl) endEl.min = today;

      try {
        var saved = JSON.parse(localStorage.getItem('baobab_client') || '{}');
        if (saved && saved.name) {
          var parts = saved.name.split(' ');
          var fnEl = document.getElementById('resFirstName');
          var lnEl = document.getElementById('resLastName');
          if (fnEl) fnEl.value = parts[0] || '';
          if (lnEl) lnEl.value = parts.slice(1).join(' ') || '';
        }
        var phEl = document.getElementById('resPhone');
        var emEl = document.getElementById('resEmail');
        var mkEl = document.getElementById('resOptMarketing');
        if (saved && saved.phone && phEl) phEl.value = saved.phone;
        if (saved && saved.email && emEl) emEl.value = saved.email;
        if (saved && saved.marketing !== undefined && mkEl) mkEl.checked = !!saved.marketing;
      } catch(e) {}
      
      modal.style.setProperty('display', 'flex', 'important');
      document.body.style.overflow = 'hidden';
    };

    window.closeReservationModal = function() {
      var modal = document.getElementById('reservationModal');
      if (modal) {
        modal.style.setProperty('display', 'none', 'important');
      }
      document.body.style.overflow = '';
    };
  </script>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($villa['name']) ?> — Baobab Horizon</title>
  
  <link rel="icon" href="favicon.ico" type="image/x-icon">
  <link rel="shortcut icon" href="favicon.ico" type="image/x-icon">
  <link rel="apple-touch-icon" href="favicon.png">

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,600;1,400;1,600&family=Lora:ital,wght@0,400;0,500;0,600;1,400;1,500&family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
  
  <?php if (!empty($coverImage)): ?>
    <link rel="preload" as="image" href="<?= htmlspecialchars($coverImage) ?>" fetchpriority="high">
  <?php endif; ?>

  <style>
    /* Dynamic text colors for custom villa backgrounds */
    .light-theme, .light-theme * { color: #000000 !important; text-shadow: none !important; border-color: rgba(0,0,0,0.25) !important; }
    .dark-theme, .dark-theme * { color: #ffffff !important; text-shadow: none !important; border-color: rgba(255,255,255,0.25) !important; }

    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    
    :root {
      --dynamic-text: <?= isLightColor($villaColor) ? '#000000' : '#ffffff' ?>;
      --dynamic-border: <?= isLightColor($villaColor) ? 'rgba(0,0,0,0.25)' : 'rgba(255,255,255,0.25)' ?>;
      --night: #0B1613;
      --night-surface: #122520;
      --night-card: #18302A;
      --sand-bg: #FAF6F0;
      --sand-card: #F3ECE2;
      --gold: var(--dynamic-text);
      --gold-light: var(--dynamic-text);
      --gold-dark: var(--dynamic-text);
      --gold-border: var(--dynamic-border);
      --text-light: var(--dynamic-text);
      --text-muted: var(--dynamic-text);
      --text-dark: var(--dynamic-text);
      --font-serif: 'Cormorant Garamond', 'Lora', Georgia, serif;
      --font-body: 'Poppins', sans-serif;
    }

    html { scroll-behavior: smooth; }
    body {
      background: var(--night);
      color: var(--text-light);
      font-family: var(--font-body);
      font-weight: 300;
      line-height: 1.6;
      overflow-x: hidden;
    }

    a { text-decoration: none; color: inherit; }

    /* ── NAV BAR ────────────────────────────────────────────── */
    nav {
      position: fixed;
      top: 0; left: 0; right: 0;
      z-index: 900;
      padding: 18px 50px;
      display: grid;
      grid-template-columns: auto 1fr auto;
      align-items: center;
      gap: 24px;
      background: rgba(11, 22, 19, 0.92);
      backdrop-filter: blur(12px);
      border-bottom: 1px solid var(--gold-border);
      transition: all 0.3s;
    }

    .nav-logo, .footer-logo {
      display: inline-flex !important;
      align-items: center !important;
      justify-content: center !important;
      width: 105px !important;
      height: 105px !important;
      min-width: 105px !important;
      min-height: 105px !important;
      aspect-ratio: 1/1 !important;
      border-radius: 50% !important;
      background: #ffffff !important;
      padding: 3px !important;
      border: none !important;
      box-shadow: 0 4px 18px rgba(0,0,0,0.35) !important;
      overflow: hidden !important;
    }
    .nav-logo img, .footer-logo img {
      width: 100% !important;
      height: 100% !important;
      max-width: 100% !important;
      max-height: 100% !important;
      object-fit: contain !important;
      border-radius: 50% !important;
      display: block !important;
      background: transparent !important;
      padding: 0 !important;
      border: none !important;
    }
    @media(max-width:1024px) {
      .nav-logo, .footer-logo {
        width: 82px !important;
        height: 82px !important;
        min-width: 82px !important;
        min-height: 82px !important;
        padding: 2px !important;
      }
    }

    .nav-links {
      display: flex;
      gap: 32px;
      list-style: none;
      justify-self: center;
    }
    .nav-links a {
      font-size: 0.72rem;
      letter-spacing: 0.22em;
      text-transform: uppercase;
      color: rgba(247, 245, 240, 0.8);
      transition: 0.3s;
      font-weight: 400;
    }
    .nav-links a:hover, .nav-links a.active {
      color: var(--gold-light);
    }

    .nav-actions {
      display: flex;
      align-items: center;
      gap: 14px;
      justify-self: end;
    }
    .nav-cta {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      background: #ffffff !important;
      color: #000000 !important;
      font-weight: 600;
      font-size: 0.65rem;
      letter-spacing: 0.2em;
      text-transform: uppercase;
      padding: 10px 22px;
      border-radius: 2px;
      border: 1px solid #ffffff !important;
      transition: 0.3s;
      box-shadow: 0 4px 14px rgba(0, 0, 0, 0.2);
    }
    .nav-cta:hover {
      background: #f2f2f2 !important;
      box-shadow: 0 4px 20px rgba(255, 255, 255, 0.35);
      color: #000000 !important;
    }

    .nav-account-btn {
      display: inline-flex !important;
      align-items: center !important;
      justify-content: center !important;
      gap: 6px !important;
      padding: 8px 14px !important;
      background: rgba(214, 175, 92, 0.12) !important;
      border: 1px solid rgba(214, 175, 92, 0.4) !important;
      color: #D6AF5C !important;
      font-family: 'Poppins', 'Lora', sans-serif !important;
      font-size: 0.72rem !important;
      font-weight: 600 !important;
      text-transform: uppercase !important;
      letter-spacing: 0.08em !important;
      border-radius: 4px !important;
      cursor: pointer !important;
      transition: all 0.25s ease !important;
      line-height: 1 !important;
      text-decoration: none !important;
      height: auto !important;
    }
    .nav-account-btn:hover {
      background: rgba(214, 175, 92, 0.25) !important;
      border-color: #D6AF5C !important;
      color: #FFFFFF !important;
      box-shadow: 0 4px 15px rgba(214, 175, 92, 0.25) !important;
    }
    .nav-account-btn.registered {
      background: #D6AF5C !important;
      color: #0F1A17 !important;
      border-color: #D6AF5C !important;
    }
    @media (max-width: 1024px) {
      .nav-account-btn {
        padding: 6px 10px !important;
        font-size: 0.65rem !important;
      }
    }
    .nav-menu-btn {
      display: none;
      background: transparent;
      border: 1px solid var(--gold-border);
      color: var(--gold-light);
      font-size: 0.65rem;
      letter-spacing: 0.18em;
      text-transform: uppercase;
      padding: 8px 14px;
      cursor: pointer;
    }

    /* ── HERO BANNER ────────────────────────────────────────── */
    .hero-guede {
      position: relative !important;
      height: 100vh !important;
      min-height: 650px !important;
      display: flex !important;
      align-items: flex-end !important;
      padding: 140px 60px 80px !important;
      background: var(--night) !important;
      box-sizing: border-box !important;
      overflow: hidden !important;
    }
    .hero-bg-image {
      position: absolute !important;
      top: 0 !important;
      left: 0 !important;
      width: 100% !important;
      height: 100% !important;
      z-index: 1 !important;
    }
    .hero-bg-image img {
      width: 100% !important;
      height: 100% !important;
      object-fit: cover !important;
      filter: none !important;
    }
    .hero-overlay {
      display: none !important;
    }

    .hero-container {
      position: relative;
      z-index: 3;
      max-width: 1100px;
      width: 100%;
    }

    .hero-eyebrow {
      display: inline-flex;
      align-items: center;
      gap: 10px;
      font-size: 0.62rem;
      letter-spacing: 0.35em;
      text-transform: uppercase;
      color: #ffffff !important;
      margin-bottom: 16px;
      font-weight: 500;
      text-shadow: 0 2px 14px rgba(0,0,0,0.85), 0 4px 28px rgba(0,0,0,0.95) !important;
    }
    .hero-eyebrow::before {
      content: '';
      display: block;
      width: 32px;
      height: 1px;
      background: #ffffff !important;
    }

    .hero-title-guede {
      font-family: var(--font-serif);
      font-size: clamp(3rem, 7vw, 5.5rem);
      font-weight: 300;
      line-height: 1.05;
      color: #ffffff !important;
      margin-bottom: 16px;
      letter-spacing: -0.01em;
      text-shadow: 0 2px 14px rgba(0,0,0,0.85), 0 4px 28px rgba(0,0,0,0.95) !important;
    }
    .hero-title-guede em {
      font-style: italic;
      color: #ffffff !important;
      font-weight: 400;
    }

    .hero-subtitle-guede {
      font-family: var(--font-serif);
      font-size: clamp(1.2rem, 2.5vw, 1.8rem);
      font-style: italic;
      color: rgba(247, 245, 240, 0.95) !important;
      margin-bottom: 28px;
      max-width: 780px;
      font-weight: 300;
      text-shadow: 0 2px 14px rgba(0,0,0,0.85), 0 4px 28px rgba(0,0,0,0.95) !important;
    }

    .hero-actions {
      display: flex;
      gap: 16px;
      align-items: center;
      flex-wrap: wrap;
    }

    /* ── SPECS BAR (RICH ILLUSTRATED) ──────────────────────── */
    .specs-guede {
      background: var(--night-surface);
      border-top: 1px solid var(--gold-border);
      border-bottom: 1px solid var(--gold-border);
      padding: 60px;
      position: relative;
      overflow: hidden;
    }
    .specs-guede::before {
      content: '';
      position: absolute;
      inset: 0;
      background-image:
        radial-gradient(circle at 10% 50%, rgba(197,160,89,0.06) 0%, transparent 50%),
        radial-gradient(circle at 90% 50%, rgba(197,160,89,0.06) 0%, transparent 50%);
      pointer-events: none;
    }
    .specs-grid-guede {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 0;
      max-width: 1200px;
      margin: 0 auto;
      position: relative;
      z-index: 1;
    }
    .spec-card-guede {
      display: flex;
      flex-direction: column;
      align-items: center;
      text-align: center;
      padding: 20px 24px 28px;
      position: relative;
      /* African geometric corner motif via CSS */
    }
    .spec-card-guede::before {
      content: '';
      position: absolute;
      top: 10px; left: 10px;
      width: 20px; height: 20px;
      border-top: 1.5px solid rgba(197,160,89,0.45);
      border-left: 1.5px solid rgba(197,160,89,0.45);
    }
    .spec-card-guede::after {
      content: '';
      position: absolute;
      bottom: 10px; right: 10px;
      width: 20px; height: 20px;
      border-bottom: 1.5px solid rgba(197,160,89,0.45);
      border-right: 1.5px solid rgba(197,160,89,0.45);
    }
    /* Vertical separator between cards */
    .spec-card-guede + .spec-card-guede {
      border-left: 1px solid rgba(197,160,89,0.15);
    }
    .spec-illus-guede {
      width: 90px;
      height: 72px;
      display: flex;
      align-items: center;
      justify-content: center;
      margin-bottom: 16px;
      position: relative;
    }
    /* Diamond motif behind illustration */
    .spec-illus-guede::before {
      content: '';
      position: absolute;
      width: 64px; height: 64px;
      border: 1px solid rgba(197,160,89,0.18);
      transform: rotate(45deg);
      border-radius: 4px;
    }
    .spec-val-guede {
      font-family: var(--font-serif);
      font-size: 3rem;
      font-weight: 300;
      color: var(--gold-light);
      line-height: 1;
      margin-bottom: 6px;
    }
    .spec-lbl-guede {
      font-size: 0.6rem;
      letter-spacing: 0.28em;
      text-transform: uppercase;
      color: var(--text-muted);
    }
    .spec-motif-line {
      width: 32px;
      height: 1px;
      background: linear-gradient(90deg, transparent, rgba(197,160,89,0.5), transparent);
      margin: 10px auto 0;
    }

    /* ── MOSAIC GALLERY SECTION: PINTEREST PORTRAIT SLIDER ── */
    .gallery-guede {
      padding: 100px 40px;
      position: relative;
    }
    .bha-portrait-slider-wrap {
      position: relative;
      width: 100%;
      max-width: 1100px;
      margin: 40px auto 0;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 30px;
      overflow: hidden;
      padding: 20px 0;
    }
    .bha-portrait-slider {
      width: 100%;
      max-width: 440px; /* Width of active central slide */
      height: 620px;
      position: relative;
      overflow: visible;
    }
    .bha-slider-track {
      display: flex;
      align-items: center;
      justify-content: center;
      height: 100%;
      width: 100%;
      position: relative;
      transform-style: preserve-3d;
      perspective: 1000px;
    }
    .bha-slider-slide {
      position: absolute;
      width: 100%;
      height: 100%;
      border-radius: 4px;
      overflow: hidden;
      box-shadow: 0 20px 45px rgba(0,0,0,0.4);
      border: 1px solid rgba(255, 255, 255, 0.08);
      cursor: pointer;
      transition: transform 0.6s cubic-bezier(0.25, 1, 0.5, 1), 
                  opacity 0.6s cubic-bezier(0.25, 1, 0.5, 1), 
                  filter 0.6s ease;
      transform-origin: center center;
      opacity: 0;
      pointer-events: none;
    }
    .bha-slider-slide img {
      width: 100%;
      height: 100%;
      object-fit: cover;
    }
    .bha-slide-number {
      position: absolute;
      bottom: 24px;
      left: 50%;
      transform: translateX(-50%);
      background: rgba(11, 22, 19, 0.75);
      color: #fff;
      font-family: var(--font-body);
      font-size: 0.65rem;
      letter-spacing: 0.2em;
      padding: 6px 14px;
      border-radius: 30px;
      backdrop-filter: blur(4px);
      border: 1px solid rgba(255,255,255,0.08);
      pointer-events: none;
    }
    
    .bha-slider-slide.active {
      transform: translate3d(0, 0, 0) scale(1);
      opacity: 1;
      pointer-events: auto;
      z-index: 5;
    }
    .bha-slider-slide.prev-slide {
      transform: translate3d(-110%, 0, -150px) scale(0.85);
      opacity: 0.45;
      pointer-events: auto;
      z-index: 3;
      filter: blur(1.5px);
    }
    .bha-slider-slide.next-slide {
      transform: translate3d(110%, 0, -150px) scale(0.85);
      opacity: 0.45;
      pointer-events: auto;
      z-index: 3;
      filter: blur(1.5px);
    }
    
    .bha-slider-btn {
      width: 54px;
      height: 54px;
      border-radius: 50%;
      background: rgba(255, 255, 255, 0.08);
      border: 1px solid rgba(255, 255, 255, 0.15);
      color: #fff;
      display: flex;
      align-items: center;
      justify-content: center;
      cursor: pointer;
      transition: all 0.3s ease;
      z-index: 10;
      flex-shrink: 0;
    }
    .bha-slider-btn:hover {
      background: #ffffff;
      color: #000000;
      box-shadow: 0 0 20px rgba(255,255,255,0.2);
    }
    .bha-slider-btn svg {
      width: 22px;
      height: 22px;
    }
    
    .bha-slider-progress-bar {
      width: 120px;
      height: 2px;
      background: rgba(255, 255, 255, 0.15);
      margin: 36px auto 0;
      border-radius: 2px;
      overflow: hidden;
    }
    .bha-slider-progress-fill {
      height: 100%;
      background: #D6AF5C;
      transition: width 0.4s cubic-bezier(0.25, 1, 0.5, 1);
    }

    @media (max-width: 768px) {
      .bha-portrait-slider-wrap {
        gap: 15px;
      }
      .bha-portrait-slider {
        max-width: 280px;
        height: 420px;
      }
      .bha-slider-slide.prev-slide {
        transform: translate3d(-105%, 0, -100px) scale(0.85);
        opacity: 0.3;
      }
      .bha-slider-slide.next-slide {
        transform: translate3d(105%, 0, -100px) scale(0.85);
        opacity: 0.3;
      }
      .bha-slider-btn {
        width: 44px;
        height: 44px;
      }
      .bha-slider-btn svg {
        width: 18px;
        height: 18px;
      }
    }

    /* ── AFRICAN MOTIF DECORATOR ─────────────────────────────── */
    .motif-divider {
      width: 100%;
      overflow: hidden;
      height: 48px;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 12px;
      opacity: 0.35;
    }
    .motif-divider svg { flex-shrink: 0; }

    .section-motif-bg {
      position: relative;
      overflow: hidden;
    }
    .section-motif-bg::before {
      content: '';
      position: absolute;
      inset: 0;
      background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='60' height='60'%3E%3Crect width='60' height='60' fill='none'/%3E%3Ccircle cx='30' cy='30' r='12' fill='none' stroke='%23D6AF5C' stroke-width='0.6' opacity='0.3'/%3E%3Ccircle cx='30' cy='30' r='6' fill='none' stroke='%23D6AF5C' stroke-width='0.6' opacity='0.2'/%3E%3Cpath d='M30 18 L42 30 L30 42 L18 30 Z' fill='none' stroke='%23D6AF5C' stroke-width='0.5' opacity='0.2'/%3E%3Ccircle cx='0' cy='0' r='4' fill='none' stroke='%23D6AF5C' stroke-width='0.5' opacity='0.15'/%3E%3Ccircle cx='60' cy='0' r='4' fill='none' stroke='%23D6AF5C' stroke-width='0.5' opacity='0.15'/%3E%3Ccircle cx='0' cy='60' r='4' fill='none' stroke='%23D6AF5C' stroke-width='0.5' opacity='0.15'/%3E%3Ccircle cx='60' cy='60' r='4' fill='none' stroke='%23D6AF5C' stroke-width='0.5' opacity='0.15'/%3E%3C/svg%3E");
      background-size: 60px 60px;
      pointer-events: none;
      z-index: 0;
    }
    .section-motif-bg > * { position: relative; z-index: 1; }

    /* ── INTRO SPLIT SECTION ─────────────────────────────────── */
    .intro-split {
      background: var(--sand-bg);
      padding: 90px 60px;
    }
    .intro-split-grid {
      max-width: 1200px;
      margin: 0 auto;
      display: grid;
      grid-template-columns: 260px 1fr;
      gap: 70px;
      align-items: center;
    }
    .intro-big-letter {
      font-family: var(--font-serif);
      font-size: 18rem;
      line-height: 0.8;
      color: rgba(197,160,89,0.12);
      font-weight: 600;
      user-select: none;
      letter-spacing: -0.05em;
      position: relative;
      opacity: 0;
      transform: translateX(-100px);
      transition: opacity 1.2s cubic-bezier(0.25, 1, 0.5, 1), transform 1.2s cubic-bezier(0.25, 1, 0.5, 1);
    }
    .intro-big-letter.revealed {
      opacity: 1;
      transform: translateX(0);
    }
    .intro-split-text {
      opacity: 0;
      transform: translateX(100px);
      transition: opacity 1.2s cubic-bezier(0.25, 1, 0.5, 1), transform 1.2s cubic-bezier(0.25, 1, 0.5, 1);
    }
    .intro-split-text.revealed {
      opacity: 1;
      transform: translateX(0);
    }
    .intro-big-letter::after {
      content: '';
      position: absolute;
      bottom: 20px;
      right: -20px;
      width: 80px;
      height: 80px;
      border-right: 2px solid rgba(197,160,89,0.3);
      border-bottom: 2px solid rgba(197,160,89,0.3);
    }
    .intro-split-text h2 {
      font-family: var(--font-serif);
      font-size: clamp(1.8rem, 3.5vw, 2.8rem);
      color: #1a2e28;
      font-weight: 400;
      line-height: 1.2;
      margin-bottom: 28px;
    }
    .intro-split-text h2 em {
      font-style: italic;
      color: #5a7a70;
    }
    .intro-split-text p {
      font-size: 0.9rem;
      line-height: 1.9;
      color: #3b4d47;
      margin-bottom: 20px;
    }
    .intro-stats-row {
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      border-top: 1px solid rgba(197,160,89,0.25);
      margin-top: 40px;
      padding-top: 36px;
      gap: 0;
    }
    .intro-stat {
      text-align: center;
      padding: 0 20px;
      border-right: 1px solid rgba(197,160,89,0.2);
    }
    .intro-stat:last-child { border-right: none; }
    .intro-stat-val {
      font-family: var(--font-serif);
      font-size: 2.5rem;
      color: #1a2e28;
      font-weight: 300;
      line-height: 1;
      display: block;
    }
    .intro-stat-lbl {
      font-size: 0.6rem;
      text-transform: uppercase;
      letter-spacing: 0.2em;
      color: #7a9890;
      margin-top: 6px;
      display: block;
    }

    /* ── EXPERIENCES SECTION (#0B1613) ──────────────────────── */
    .experiences-guede {
      background: var(--night-surface);
      padding: 100px 60px;
      border-top: 1px solid var(--gold-border);
      border-bottom: 1px solid var(--gold-border);
    }
    .experiences-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
      gap: 28px;
      max-width: 1240px;
      margin: 50px auto 0;
    }
    .exp-card {
      background: #0B1613 !important;
      border: 1px solid rgba(214, 175, 92, 0.25) !important;
      padding: 40px 30px;
      text-align: center;
      transition: 0.3s;
      border-radius: 4px;
    }
    .exp-card, .exp-card * {
      color: #ffffff !important;
    }
    .exp-card:hover {
      border-color: #D6AF5C !important;
      transform: translateY(-5px);
      box-shadow: 0 12px 35px rgba(0,0,0,0.5) !important;
    }
    .exp-icon {
      width: 52px;
      height: 52px;
      margin: 0 auto 20px;
      color: #D6AF5C !important;
    }
    .exp-icon svg {
      stroke: #D6AF5C !important;
    }
    .exp-title {
      font-family: var(--font-serif);
      font-size: 1.45rem;
      color: #ffffff !important;
      margin-bottom: 12px;
      font-weight: 400;
    }
    .exp-desc {
      font-size: 0.8rem;
      color: rgba(248, 244, 236, 0.8) !important;
      line-height: 1.7;
    }

    /* ── HERITAGE STORYTELLING SECTION ──────────────────────── */
    .story-guede {
      background: var(--sand-bg);
      color: var(--text-dark);
      padding: 100px 60px;
    }
    .story-container {
      max-width: 1200px;
      margin: 0 auto;
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 60px;
      align-items: center;
    }
    .story-content h2 {
      font-family: var(--font-serif);
      font-size: clamp(2.2rem, 4vw, 3.2rem);
      font-weight: 400;
      color: var(--text-dark);
      margin-bottom: 24px;
      line-height: 1.15;
    }
    .story-content p {
      font-size: 0.88rem;
      line-height: 1.85;
      color: #3b4d47;
      margin-bottom: 24px;
    }
    .story-tags {
      display: flex;
      flex-wrap: wrap;
      gap: 10px;
      margin-top: 20px;
    }
    .story-tag-pill {
      font-size: 0.65rem;
      letter-spacing: 0.18em;
      text-transform: uppercase;
      border: 1px solid rgba(197, 160, 89, 0.4);
      color: var(--gold-dark);
      padding: 8px 18px;
      border-radius: 2px;
      background: rgba(197, 160, 89, 0.08);
    }
    @keyframes bhaKenBurns {
      0% { transform: scale(1) translate(0, 0); }
      50% { transform: scale(1.08) translate(-1%, -1%); }
      100% { transform: scale(1) translate(0, 0); }
    }
    .story-image {
      position: relative;
      aspect-ratio: 4/3;
      overflow: hidden;
      box-shadow: 0 20px 40px rgba(0,0,0,0.15);
      border: 1px solid var(--gold-border);
      transition: transform 0.6s cubic-bezier(0.165, 0.84, 0.44, 1), box-shadow 0.6s cubic-bezier(0.165, 0.84, 0.44, 1), border-color 0.6s ease !important;
    }
    .story-image:hover {
      transform: translateY(-8px) scale(1.02) !important;
      box-shadow: 0 30px 60px rgba(214, 175, 92, 0.22) !important;
      border-color: #D6AF5C !important;
    }
    .story-image img {
      width: 100%;
      height: 100%;
      object-fit: cover;
      animation: bhaKenBurns 20s infinite ease-in-out;
      transition: transform 0.8s cubic-bezier(0.165, 0.84, 0.44, 1) !important;
    }
    .story-image:hover img {
      transform: scale(1.15) !important;
      animation-play-state: paused;
    }

    /* ── HERITAGE TIMELINE ───────────────────────────────────── */
    .heritage-full {
      background: var(--sand-bg);
      padding: 0 60px 100px;
    }
    .heritage-timeline {
      max-width: 1200px;
      margin: 0 auto;
    }
    .heritage-lead {
      max-width: 720px;
      margin: 0 auto 60px;
      text-align: center;
    }
    .heritage-lead p {
      font-size: 0.92rem;
      line-height: 1.9;
      color: #3b4d47;
    }
    .timeline-row {
      display: grid;
      grid-template-columns: 1fr 60px 1fr;
      gap: 0;
      margin-bottom: 0;
    }
    .timeline-cell-left {
      text-align: right;
      padding: 30px 40px 30px 0;
      border-right: 2px solid rgba(197,160,89,0.3);
    }
    .timeline-cell-right {
      text-align: left;
      padding: 30px 0 30px 40px;
      border-left: none;
    }
    .timeline-center {
      display: flex;
      align-items: center;
      justify-content: center;
      position: relative;
    }
    .timeline-dot {
      width: 18px;
      height: 18px;
      background: rgba(197,160,89,0.8);
      border-radius: 50%;
      border: 3px solid var(--sand-bg);
      box-shadow: 0 0 0 2px rgba(197,160,89,0.5);
      z-index: 2;
      flex-shrink: 0;
    }
    .timeline-label {
      font-size: 0.6rem;
      letter-spacing: 0.28em;
      text-transform: uppercase;
      color: rgba(197,160,89,0.8);
      margin-bottom: 8px;
    }
    .timeline-title {
      font-family: var(--font-serif);
      font-size: 1.35rem;
      color: #1a2e28;
      margin-bottom: 10px;
      font-weight: 500;
    }
    .timeline-desc {
      font-size: 0.82rem;
      color: #5a7a70;
      line-height: 1.75;
    }
    .timeline-cell-left .timeline-label,
    .timeline-cell-left .timeline-title,
    .timeline-cell-left .timeline-desc { text-align: right; }

    /* ── ART DE RECEVOIR ─────────────────────────────────────── */
    .art-recevoir {
      background: var(--night);
      padding: 100px 60px;
      border-top: 1px solid rgba(197,160,89,0.15);
      position: relative;
      overflow: hidden;
    }
    .art-recevoir::after {
      content: '';
      position: absolute;
      bottom: -80px;
      right: -80px;
      width: 400px;
      height: 400px;
      border-radius: 50%;
      border: 60px solid rgba(197,160,89,0.04);
      pointer-events: none;
    }
    .art-recevoir-grid {
      max-width: 1200px;
      margin: 0 auto;
      display: grid;
      grid-template-columns: 1fr 2fr;
      gap: 80px;
      align-items: center;
    }
    .art-recevoir-intro h2 {
      font-family: var(--font-serif);
      font-size: clamp(2rem, 4vw, 3rem);
      color: #fff;
      font-weight: 300;
      line-height: 1.15;
      margin-bottom: 24px;
    }
    .art-recevoir-intro h2 em {
      display: block;
      font-style: italic;
      color: rgba(197,160,89,0.85);
    }
    .art-recevoir-intro p {
      font-size: 0.85rem;
      color: rgba(248,244,236,0.65);
      line-height: 1.85;
    }
    .art-pillars {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 32px;
    }
    .pillar-card {
      border-top: 2px solid rgba(197,160,89,0.3);
      padding-top: 28px;
    }
    .pillar-name {
      font-size: 0.6rem;
      text-transform: uppercase;
      letter-spacing: 0.28em;
      color: rgba(197,160,89,0.75);
      margin-bottom: 12px;
    }
    .pillar-title {
      font-family: var(--font-serif);
      font-size: 1.4rem;
      color: #fff;
      font-weight: 400;
      margin-bottom: 14px;
    }
    .pillar-desc {
      font-size: 0.78rem;
      color: rgba(248,244,236,0.6);
      line-height: 1.8;
    }

    /* ── CLOSING CTA SECTION ─────────────────────────────────── */
    .closing-cta {
      background: #0B1613 !important;
      padding: 100px 60px;
      text-align: center;
      position: relative;
      overflow: hidden;
    }
    .closing-cta::before {
      content: '';
      position: absolute;
      inset: 0;
      background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='80' height='80'%3E%3Crect width='80' height='80' fill='none'/%3E%3Cpath d='M40 10 L70 40 L40 70 L10 40 Z' fill='none' stroke='%23C5A059' stroke-width='0.5' opacity='0.18'/%3E%3Ccircle cx='40' cy='40' r='20' fill='none' stroke='%23C5A059' stroke-width='0.4' opacity='0.12'/%3E%3C/svg%3E");
      background-size: 80px 80px;
      pointer-events: none;
      opacity: 0.25;
    }
    .closing-cta > * { position: relative; z-index: 1; }
    .closing-cta-eyebrow {
      font-size: 0.6rem;
      letter-spacing: 0.3em;
      text-transform: uppercase;
      color: #D6AF5C !important;
      margin-bottom: 20px;
      display: block;
    }
    .closing-cta h2 {
      font-family: var(--font-serif);
      font-size: clamp(2.5rem, 5vw, 4.5rem);
      color: #ffffff !important;
      font-weight: 300;
      line-height: 1.1;
      margin-bottom: 12px;
    }
    .closing-cta h2 em {
      font-style: italic;
      color: #D6AF5C !important;
      display: block;
    }
    .closing-cta p {
      font-size: 0.9rem;
      color: rgba(255, 255, 255, 0.75) !important;
      margin: 20px auto 40px;
      max-width: 500px;
      line-height: 1.8;
    }
    .closing-cta .btn-closing {
      display: inline-flex;
      align-items: center;
      gap: 12px;
      background: #1a2e28;
      color: #fff;
      font-size: 0.7rem;
      letter-spacing: 0.22em;
      text-transform: uppercase;
      padding: 18px 48px;
      font-family: var(--font-body);
      font-weight: 500;
      border: none;
      cursor: pointer;
      transition: 0.3s;
      text-decoration: none;
    }
    .closing-cta .btn-closing:hover {
      background: rgba(197,160,89,0.85);
      color: #1a2e28;
    }
    .closing-leaf {
      position: absolute;
      bottom: -20px;
      right: 40px;
      opacity: 0.07;
      pointer-events: none;
    }

    /* ── PRICING & CTA BANNER ───────────────────────────────── */
    .booking-banner {
      padding: 90px 60px;
      max-width: 1200px;
      margin: 0 auto;
    }
    .booking-card {
      background: var(--night-surface);
      border: 1px solid var(--gold-border);
      padding: 50px 60px;
      display: grid;
      grid-template-columns: 1fr auto;
      gap: 40px;
      align-items: center;
    }
    .price-amount {
      font-family: var(--font-serif);
      font-size: clamp(2.8rem, 5vw, 4rem);
      color: var(--gold-light);
      font-weight: 300;
      line-height: 1;
    }
    .price-unit {
      font-size: 0.68rem;
      letter-spacing: 0.22em;
      text-transform: uppercase;
      color: var(--text-muted);
      margin-top: 10px;
    }

    /* ── BUTTONS ────────────────────────────────────────────── */
    .btn-primary-guede {
      display: inline-flex;
      align-items: center;
      gap: 12px;
      background: linear-gradient(135deg, var(--gold) 0%, var(--gold-dark) 100%);
      color: #000;
      font-family: var(--font-body);
      font-weight: 500;
      font-size: 0.7rem;
      letter-spacing: 0.2em;
      text-transform: uppercase;
      padding: 16px 36px;
      border: none;
      cursor: pointer;
      transition: 0.3s;
    }
    .btn-primary-guede:hover {
      background: var(--gold-light);
      box-shadow: 0 0 20px rgba(197, 160, 89, 0.4);
    }
    .btn-secondary-guede {
      display: inline-flex;
      align-items: center;
      gap: 12px;
      background: transparent;
      border: 1px solid var(--gold);
      color: var(--gold-light);
      font-family: var(--font-body);
      font-size: 0.7rem;
      letter-spacing: 0.2em;
      text-transform: uppercase;
      padding: 16px 36px;
      transition: 0.3s;
      cursor: pointer;
    }
    .btn-secondary-guede:hover {
      background: rgba(197, 160, 89, 0.12);
      color: #fff;
    }

    /* ── SIMILAR PROPERTIES (ÉCRITURES EN BLANC) ─────────────── */
    .similar-guede {
      padding: 80px 60px 120px;
      max-width: 1280px;
      margin: 0 auto;
    }
    .similar-guede .section-tag-guede {
      color: #ffffff !important;
    }
    .similar-guede .section-title-guede {
      color: #ffffff !important;
    }
    .similar-grid-guede {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
      gap: 28px;
      margin-top: 40px;
    }
    .similar-card-guede {
      background: #0B1613 !important;
      border: 1px solid rgba(214, 175, 92, 0.25) !important;
      overflow: hidden;
      transition: 0.3s;
      border-radius: 4px;
    }
    .similar-card-guede, .similar-card-guede * {
      color: #ffffff !important;
    }
    .similar-card-guede:hover {
      border-color: #D6AF5C !important;
      transform: translateY(-4px);
    }
    .similar-card-img {
      aspect-ratio: 4/3;
      overflow: hidden;
    }
    .similar-card-img img {
      width: 100%;
      height: 100%;
      object-fit: cover;
      transition: transform 0.5s;
    }
    .similar-card-guede:hover .similar-card-img img {
      transform: scale(1.05);
    }
    .similar-card-body {
      padding: 24px;
    }
    .similar-card-name {
      font-family: var(--font-serif);
      font-size: 1.3rem;
      color: #ffffff !important;
      margin-bottom: 6px;
    }
    .similar-card-zone {
      font-size: 0.65rem;
      letter-spacing: 0.18em;
      text-transform: uppercase;
      color: rgba(255, 255, 255, 0.85) !important;
      margin-bottom: 14px;
    }
    .similar-card-price {
      font-family: var(--font-serif);
      font-size: 1.4rem;
      color: #ffffff !important;
    }
    .similar-card-price span {
      color: rgba(255, 255, 255, 0.75) !important;
    }
    .similar-card-guede .btn-secondary-guede {
      color: #ffffff !important;
      border-color: rgba(255, 255, 255, 0.35) !important;
      background: rgba(255, 255, 255, 0.05) !important;
    }
    .similar-card-guede .btn-secondary-guede:hover {
      background: #D6AF5C !important;
      color: #000000 !important;
    }

    /* ── LIGHTBOX MODAL ─────────────────────────────────────── */
    .modal-lightbox {
      position: fixed;
      inset: 0;
      z-index: 9900;
      background: rgba(7, 14, 12, 0.96);
      display: none;
      align-items: center;
      justify-content: center;
    }
    .modal-lightbox.open { display: flex; }
    .modal-lightbox img {
      max-width: 90vw;
      max-height: 88vh;
      object-fit: contain;
      box-shadow: 0 10px 40px rgba(0,0,0,0.8);
      border: 1px solid var(--gold-border);
    }
    .modal-lightbox-close {
      position: absolute;
      top: 24px; right: 28px;
      background: transparent;
      border: none;
      color: var(--text-light);
      font-size: 2.5rem;
      cursor: pointer;
      line-height: 1;
    }
    .modal-lightbox-nav {
      position: absolute;
      top: 50%;
      transform: translateY(-50%);
      width: calc(100% - 60px);
      left: 30px;
      display: flex;
      justify-content: space-between;
      pointer-events: none;
    }
    .modal-lightbox-nav button {
      width: 52px;
      height: 52px;
      background: rgba(18, 37, 32, 0.7);
      border: 1px solid var(--gold-border);
      color: var(--text-light);
      font-size: 1.4rem;
      cursor: pointer;
      pointer-events: auto;
      transition: 0.3s;
    }
    .modal-lightbox-nav button:hover {
      background: var(--gold);
      color: #000;
    }

    /* ── FOOTER STYLES ──────────────────────────────────────── */
    footer {
      width: 100% !important;
      max-width: 100% !important;
      box-sizing: border-box !important;
      padding: 80px 60px 40px !important;
      background: #0B1613 !important;
      border-top: 1px solid rgba(184, 147, 90, 0.12) !important;
      overflow-x: hidden !important;
      margin: 0 !important;
    }
    .footer-top {
      width: 100% !important;
      max-width: 100% !important;
      margin: 0 0 60px 0 !important;
      display: grid !important;
      grid-template-columns: 1.5fr 1fr 1fr 1fr !important;
      gap: 60px !important;
      box-sizing: border-box !important;
      align-items: start !important;
    }
    .footer-tagline {
      font-size: .78rem !important;
      line-height: 1.65 !important;
      color: rgba(248, 244, 236, 0.65) !important;
      margin-top: 18px !important;
      margin-bottom: 22px !important;
      max-width: 320px !important;
    }
    .footer-social {
      display: flex !important;
      flex-direction: row !important;
      align-items: center !important;
      gap: 12px !important;
      margin-top: 16px !important;
      padding: 0 !important;
    }
    .footer-social a {
      width: 38px !important;
      height: 38px !important;
      min-width: 38px !important;
      min-height: 38px !important;
      border: 1px solid rgba(214, 175, 92, 0.25) !important;
      border-radius: 4px !important;
      display: inline-flex !important;
      align-items: center !important;
      justify-content: center !important;
      color: rgba(248, 244, 236, 0.65) !important;
      text-decoration: none !important;
      transition: all 0.25s ease !important;
      background: transparent !important;
      cursor: pointer !important;
    }
    .footer-social a:hover {
      border-color: #D6AF5C !important;
      color: #D6AF5C !important;
      background: rgba(214, 175, 92, 0.12) !important;
    }
    .footer-social svg {
      width: 17px !important;
      height: 17px !important;
      min-width: 17px !important;
      min-height: 17px !important;
      fill: currentColor !important;
      display: block !important;
    }
    .footer-col-title {
      font-size: .64rem !important;
      letter-spacing: .24em !important;
      text-transform: uppercase !important;
      color: #D6AF5C !important;
      margin-bottom: 26px !important;
      font-weight: 500 !important;
    }
    .footer-links {
      list-style: none !important;
      display: flex !important;
      flex-direction: column !important;
      gap: 14px !important;
    }
    .footer-links a {
      font-size: .8rem !important;
      color: rgba(248, 244, 236, 0.7) !important;
      text-decoration: none !important;
      transition: color 0.2s ease !important;
    }
    .footer-links a:hover {
      color: #D6AF5C !important;
    }
    .footer-bottom {
      width: 100% !important;
      max-width: 100% !important;
      padding-top: 30px !important;
      border-top: 1px solid rgba(184, 147, 90, 0.1) !important;
      display: flex !important;
      justify-content: space-between !important;
      align-items: center !important;
      font-size: .72rem !important;
      color: rgba(248, 244, 236, 0.45) !important;
      box-sizing: border-box !important;
    }

    @media (max-width: 1024px) {
      nav {
        padding: 16px 24px !important;
        display: flex !important;
        justify-content: space-between !important;
        align-items: center !important;
      }
      .nav-links {
        display: none !important;
      }
      .nav-menu-btn {
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        padding: 10px 16px !important;
        border: 1px solid rgba(214, 175, 92, 0.4) !important;
        color: #D6AF5C !important;
        background: transparent !important;
        font-size: 0.72rem !important;
        letter-spacing: 0.16em !important;
        text-transform: uppercase !important;
        cursor: pointer !important;
        border-radius: 3px !important;
      }
      .nav-cta {
        display: none !important;
      }
      .hero-guede { padding: 120px 24px 60px; height: 100vh; min-height: 520px; }
      .specs-guede, .gallery-guede, .experiences-guede, .story-guede, .heritage-full, .art-recevoir, .closing-cta, .booking-banner, .similar-guede, .intro-split { padding-left: 24px; padding-right: 24px; }
      .story-container { grid-template-columns: 1fr; gap: 40px; }
      .intro-split-grid { grid-template-columns: 1fr; gap: 30px; }
      .intro-big-letter { font-size: 8rem; }
      .intro-stats-row { grid-template-columns: repeat(2, 1fr); }
      .intro-stat { border-right: none; border-bottom: 1px solid rgba(197,160,89,0.2); padding-bottom: 20px; margin-bottom: 20px; }
      .timeline-row { grid-template-columns: 1fr; gap: 0; }
      .timeline-cell-left { border-right: none; border-left: 2px solid rgba(197,160,89,0.3); text-align: left; padding: 20px 0 20px 28px; }
      .timeline-cell-left .timeline-label, .timeline-cell-left .timeline-title, .timeline-cell-left .timeline-desc { text-align: left; }
      .timeline-center { display: none; }
      .timeline-cell-right { border-left: 2px solid rgba(197,160,89,0.3); padding: 20px 0 20px 28px; }
      .art-recevoir-grid { grid-template-columns: 1fr; gap: 40px; }
      .art-pillars { grid-template-columns: 1fr; }
      .booking-card { grid-template-columns: 1fr; gap: 24px; }
      .gallery-tile.tile-large, .gallery-tile.tile-medium, .gallery-tile.tile-small, .gallery-tile.tile-wide { grid-column: span 12; aspect-ratio: 16/10; }
      footer { padding: 60px 32px 32px !important; }
      .footer-top { grid-template-columns: 1fr 1fr !important; gap: 36px !important; }
    }
    @media (max-width: 640px) {
      footer { padding: 50px 20px 30px !important; }
      .footer-top { grid-template-columns: 1fr !important; gap: 32px !important; }
      .footer-bottom { flex-direction: column !important; gap: 14px !important; text-align: left !important; align-items: flex-start !important; }
    }

    /* ── WHATSAPP FLOATING ANIMATED WIDGET ── */
    @keyframes bhaWaPulse {
      0% {
        box-shadow: 0 0 0 0 rgba(37, 211, 102, 0.75), 0 8px 24px rgba(0, 0, 0, 0.35);
        transform: scale(1);
      }
      50% {
        box-shadow: 0 0 0 16px rgba(37, 211, 102, 0), 0 12px 28px rgba(0, 0, 0, 0.45);
        transform: scale(1.05);
      }
      100% {
        box-shadow: 0 0 0 0 rgba(37, 211, 102, 0), 0 8px 24px rgba(0, 0, 0, 0.35);
        transform: scale(1);
      }
    }

    @keyframes bhaWaIconWiggle {
      0%, 100% { transform: rotate(0deg) scale(1); }
      15% { transform: rotate(-14deg) scale(1.15); }
      30% { transform: rotate(14deg) scale(1.15); }
      45% { transform: rotate(-8deg) scale(1.1); }
      60% { transform: rotate(8deg) scale(1.1); }
      75% { transform: rotate(0deg) scale(1); }
    }

    .bha-wa-widget {
      position: fixed !important;
      bottom: 24px !important;
      right: 24px !important;
      z-index: 999999 !important;
    }
    .bha-wa-btn {
      display: inline-flex;
      align-items: center;
      gap: 10px;
      padding: 13px 22px;
      background: #25D366;
      color: #ffffff !important;
      font-family: var(--font-body, 'Poppins', sans-serif);
      font-weight: 600;
      font-size: 0.84rem;
      letter-spacing: 0.02em;
      border-radius: 50px;
      box-shadow: 0 8px 24px rgba(0,0,0,0.35);
      cursor: pointer;
      border: none;
      text-decoration: none;
      animation: bhaWaPulse 2.4s infinite ease-in-out;
      transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    }
    .bha-wa-btn:hover {
      background: #20ba59;
      transform: scale(1.08) translateY(-2px);
      animation-play-state: paused;
      box-shadow: 0 12px 30px rgba(37, 211, 102, 0.45);
      color: #ffffff !important;
    }
    .bha-wa-btn svg, .bha-wa-btn .wa-icon {
      width: 22px;
      height: 22px;
      fill: currentColor;
      display: inline-block;
      animation: bhaWaIconWiggle 3.2s infinite ease-in-out;
    }
    .bha-wa-popover {
      position: absolute;
      bottom: 64px;
      right: 0;
      width: 300px;
      background: #162420;
      border: 1px solid #D6AF5C;
      border-radius: 8px;
      padding: 16px;
      box-shadow: 0 12px 35px rgba(0,0,0,0.7);
      display: none;
      box-sizing: border-box;
    }
    .bha-wa-popover.open {
      display: block;
    }
    .bha-agent-card {
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 10px 12px;
      background: #0b1412;
      border-radius: 4px;
      margin-top: 8px;
      border: 1px solid rgba(214, 175, 92, 0.2);
    }
    /* ── RESERVATION MODAL EXACT ACCUEIL STYLE & COLORS (LOCKED GLOBALLY) ── */
    #reservationModal.reservation-modal {
      position: fixed !important;
      inset: 0 !important;
      z-index: 999999 !important;
      background: rgba(10, 18, 15, 0.94) !important;
      backdrop-filter: blur(8px) !important;
      display: none;
      align-items: flex-start !important;
      justify-content: center !important;
      padding: 30px 16px 60px !important;
      overflow-y: auto !important;
      -webkit-overflow-scrolling: touch !important;
      box-sizing: border-box !important;
    }
    #reservationModal.open {
      display: flex !important;
    }
    #reservationModal .reservation-box {
      background: #11231E !important;
      border: 1px solid rgba(214, 175, 92, 0.25) !important;
      max-width: 580px !important;
      width: 100% !important;
      padding: 28px 24px !important;
      position: relative !important;
      box-sizing: border-box !important;
      border-radius: 4px !important;
      box-shadow: 0 20px 60px rgba(0,0,0,0.8) !important;
      margin: auto 0 !important;
    }
    #reservationModal .reservation-close {
      position: absolute !important;
      top: 12px !important;
      right: 12px !important;
      width: 38px !important;
      height: 38px !important;
      border: 1px solid rgba(214, 175, 92, 0.3) !important;
      background: transparent !important;
      color: #EDE3D2 !important;
      font-size: 1.4rem !important;
      line-height: 1 !important;
      cursor: pointer !important;
      display: flex !important;
      align-items: center !important;
      justify-content: center !important;
      transition: all 0.2s !important;
    }
    #reservationModal .reservation-close:hover {
      border-color: #D6AF5C !important;
      color: #D6AF5C !important;
    }
    #reservationModal .reservation-title {
      font-family: 'Poppins', sans-serif !important;
      font-size: 1.5rem !important;
      font-weight: 500 !important;
      line-height: 1.2 !important;
      color: #FFFFFF !important;
      margin-bottom: 6px !important;
      text-transform: none !important;
      letter-spacing: 0 !important;
    }
    #reservationModal .reservation-subtitle {
      color: #8A9B97 !important;
      font-family: 'Poppins', sans-serif !important;
      line-height: 1.5 !important;
      margin-bottom: 20px !important;
      font-size: 0.88rem !important;
      font-weight: 400 !important;
    }
    #reservationModal .reservation-villa-info {
      background: rgba(214, 175, 92, 0.08) !important;
      border: 1px solid rgba(214, 175, 92, 0.25) !important;
      padding: 16px !important;
      margin-bottom: 20px !important;
      border-radius: 3px !important;
    }
    #reservationModal .reservation-villa-name {
      font-size: 1.12rem !important;
      color: #D6AF5C !important;
      margin-bottom: 6px !important;
      font-family: 'Poppins', sans-serif !important;
      font-weight: 600 !important;
    }
    #reservationModal .reservation-villa-price {
      font-size: 0.95rem !important;
      color: #FFFFFF !important;
      font-family: 'Poppins', sans-serif !important;
      font-weight: 500 !important;
    }
    #reservationModal .reservation-field label {
      display: block !important;
      font-size: 0.72rem !important;
      letter-spacing: 0.12em !important;
      text-transform: uppercase !important;
      color: #D6AF5C !important;
      margin-bottom: 8px !important;
      font-family: 'Poppins', sans-serif !important;
      font-weight: 600 !important;
    }
    #reservationModal .reservation-field input,
    #reservationModal .reservation-field select {
      width: 100% !important;
      border: 1px solid rgba(214, 175, 92, 0.25) !important;
      background: #0B1814 !important;
      color: #FFFFFF !important;
      padding: 14px 16px !important;
      font-family: 'Poppins', sans-serif !important;
      font-size: 0.95rem !important;
      outline: none !important;
      box-sizing: border-box !important;
      border-radius: 3px !important;
      color-scheme: dark !important;
    }
    #reservationModal .reservation-field input:focus,
    #reservationModal .reservation-field select:focus {
      border-color: #D6AF5C !important;
      box-shadow: 0 0 0 1px rgba(214, 175, 92, 0.4) !important;
    }
    #reservationModal .reservation-field select option {
      background: #0B1814 !important;
      color: #FFFFFF !important;
      padding: 8px !important;
    }
    #reservationModal .btn-secondary {
      display: inline-flex !important;
      align-items: center !important;
      justify-content: center !important;
      gap: 8px !important;
      border: 1px solid rgba(214, 175, 92, 0.4) !important;
      color: #EDE3D2 !important;
      font-size: 0.72rem !important;
      letter-spacing: 0.15em !important;
      text-transform: uppercase !important;
      text-decoration: none !important;
      padding: 14px 24px !important;
      transition: 0.3s !important;
      cursor: pointer !important;
      background: transparent !important;
      font-family: 'Poppins', sans-serif !important;
      font-weight: 600 !important;
      flex: 1 !important;
      border-radius: 3px !important;
    }
    #reservationModal .btn-secondary:hover {
      border-color: #D6AF5C !important;
      color: #D6AF5C !important;
    }
    #reservationModal .btn-primary {
      display: inline-flex !important;
      align-items: center !important;
      justify-content: center !important;
      gap: 10px !important;
      background: #9C6F1C !important;
      color: #0F1A17 !important;
      font-size: 0.72rem !important;
      letter-spacing: 0.15em !important;
      text-transform: uppercase !important;
      padding: 14px 24px !important;
      transition: 0.3s !important;
      cursor: pointer !important;
      border: 1px solid #9C6F1C !important;
      font-family: 'Poppins', sans-serif !important;
      font-weight: 700 !important;
      flex: 1 !important;
      border-radius: 3px !important;
    }
    #reservationModal .btn-primary:hover {
      background: #D6AF5C !important;
      border-color: #D6AF5C !important;
    }

    /* ── VILLA CONTACT SECTION: OVERLAPPING CAPSULES ── */
    .villa-contact-cta {
      padding: 110px 40px;
      text-align: center;
      position: relative;
    }
    .villa-contact-container {
      max-width: 900px;
      margin: 0 auto;
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: 40px;
    }
    .villa-contact-text h2 {
      font-family: var(--font-serif);
      font-size: clamp(2.2rem, 4vw, 3.2rem);
      font-weight: 300;
      line-height: 1.15;
    }
    .villa-contact-capsules {
      display: flex;
      flex-direction: column;
      align-items: center;
      width: 100%;
      max-width: 480px;
      margin-top: 10px;
      position: relative;
    }
    .villa-contact-capsule {
      width: 100%;
      height: 74px;
      border-radius: 37px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-family: var(--font-body);
      font-size: 0.8rem;
      letter-spacing: 0.22em;
      text-transform: uppercase;
      font-weight: 600;
      text-decoration: none;
      box-shadow: 0 10px 30px rgba(0,0,0,0.22);
      transition: all 0.4s cubic-bezier(0.25, 0.8, 0.25, 1);
      margin-bottom: -20px; /* Vertically overlapping */
      position: relative;
      border: 1px solid rgba(255, 255, 255, 0.12);
      gap: 16px;
    }
    .villa-contact-capsule:nth-child(1) { z-index: 3; }
    .villa-contact-capsule:nth-child(2) { z-index: 2; }
    .villa-contact-capsule:nth-child(3) { z-index: 1; }

    .villa-contact-capsule svg.contact-icon {
      width: 20px;
      height: 20px;
      transition: transform 0.3s ease;
    }
    .villa-contact-capsule:hover {
      transform: translateY(-8px) scale(1.025);
      z-index: 10 !important;
      box-shadow: 0 18px 40px rgba(0,0,0,0.38);
    }
    .villa-contact-capsule:hover svg.contact-icon {
      transform: scale(1.2) rotate(5deg);
    }

    @media (max-width: 768px) {
      .villa-contact-cta {
        padding: 70px 20px;
      }
      .villa-contact-capsule {
        height: 68px;
        border-radius: 34px;
        font-size: 0.75rem;
        margin-bottom: -16px;
      }
    }

    /* ── MOBILE NAV RESPONSIVENESS OVERRIDES (<768px) ── */
    @media (max-width: 768px) {
      html body nav {
        display: flex !important;
        justify-content: space-between !important;
        align-items: center !important;
        padding: 10px 16px !important;
        gap: 12px !important;
      }
      html body nav .nav-links {
        display: none !important;
      }
      html body nav .nav-actions {
        display: flex !important;
        align-items: center !important;
        gap: 8px !important;
        margin: 0 !important;
        padding: 0 !important;
        justify-self: end !important;
      }
      html body nav .nav-actions .bha-lang-switcher {
        display: inline-flex !important;
        align-items: center !important;
        gap: 2px !important;
        font-size: 0.65rem !important;
        margin: 0 !important;
        padding: 0 !important;
      }
      html body nav .nav-actions .bha-lang-switcher span {
        padding: 2px 4px !important;
        font-size: 0.62rem !important;
      }
      html body nav .nav-actions .nav-cta,
      html body nav .nav-actions .nav-account-btn {
        display: none !important;
      }
      html body nav .nav-actions button.nav-menu-btn {
        display: inline-flex !important;
        font-size: 0 !important;
        width: 38px !important;
        height: 38px !important;
        min-width: 38px !important;
        min-height: 38px !important;
        border-radius: 50% !important;
        padding: 0 !important;
        border: 1px solid rgba(214, 175, 92, 0.4) !important;
        background: transparent linear-gradient(to bottom, #D6AF5C 2px, transparent 2px, transparent 6px, #D6AF5C 6px, #D6AF5C 8px, transparent 8px, transparent 12px, #D6AF5C 12px, #D6AF5C 14px) no-repeat center/18px 14px !important;
        align-items: center !important;
        justify-content: center !important;
        cursor: pointer !important;
        box-sizing: border-box !important;
      }
    }

    /* ── PC HEADER ADJUSTMENTS (min-width: 769px) ── */
    @media (min-width: 769px) {
      html body nav .nav-actions .nav-cta {
        display: none !important;
      }
      html body nav .nav-links {
        gap: 48px !important;
      }
      html body nav .nav-links a {
        font-size: 0.76rem !important;
        letter-spacing: 0.2em !important;
      }
      html body nav .nav-actions .bha-lang-switcher {
        margin-left: 0 !important;
        margin-right: 20px !important;
        gap: 6px !important;
      }
      html body nav .nav-actions .bha-lang-switcher .bha-lang-btn {
        padding: 4px 8px !important;
        font-size: 0.72rem !important;
        border-radius: 4px !important;
        transition: all 0.3s ease !important;
        border-bottom: none !important;
      }
      html body nav .nav-actions .bha-lang-switcher .bha-lang-btn.active {
        background: rgba(214, 175, 92, 0.15) !important;
        color: #D6AF5C !important;
        border: 1px solid rgba(214, 175, 92, 0.3) !important;
      }
    }
  </style>
</head>
<body>

  <!-- ── NAV BAR ─────────────────────────────────────────── -->
  <nav>
    <a href="index.html" class="nav-logo">
      <img src="LOGO.jpg" alt="Baobab Horizon">
    </a>
    <ul class="nav-links">
      <li><a href="index.html" data-i18n="nav.accueil">Accueil</a></li>
      <li><a href="ventes.html" data-i18n="nav.acheter">Acheter</a></li>
      <li><a href="vacances.html" data-i18n="nav.louer">Louer</a></li>
      <li><a href="location-voiture.html">Location de voiture</a></li>
      <li><a href="contact.html" data-i18n="nav.contact">Contact</a></li>
    </ul>
    <div class="nav-actions">
      <a href="https://wa.me/221780140942" class="nav-cta" target="_blank" data-i18n="nav.contact_us">Nous contacter</a>
      <button class="nav-menu-btn" type="button" onclick="window.bhaToggleMobileMenu(this, event)" aria-expanded="false" aria-label="Ouvrir le menu" data-i18n="nav.menu">Menu</button>
    </div>
  </nav>

  <!-- ── HERO SECTION ────────────────────────────────────── -->
  <header class="hero-guede" style="border-top:5px solid <?= htmlspecialchars($villaColor) ?>">
    <div class="hero-bg-image">
      <img src="<?= htmlspecialchars($coverImage) ?>" alt="<?= htmlspecialchars($villa['name']) ?>">
    </div>
    <div class="hero-overlay"></div>
    <div class="hero-container">
      <span class="hero-eyebrow"><?= htmlspecialchars($villa['zone'] ?? 'SÉNÉGAL') ?></span>
      <h1 class="hero-title-guede"><?= htmlspecialchars($villa['name']) ?></h1>

      <div class="hero-actions">
        <?php $btnBg = $themeClass == 'light-theme' ? '#000000' : '#ffffff'; ?>
        <button type="button" class="btn-primary-guede" onclick="window.openReservationModal()" style="background: <?= $btnBg ?> !important; color: <?= htmlspecialchars($villaColor) ?> !important; border-color: <?= $btnBg ?> !important;">Réserver mon séjour</button>
        <a href="#gallery" class="btn-secondary-guede">Explorer la galerie</a>
      </div>
    </div>
  </header>

  <!-- ── SPECS BAR (ILLUSTRATED) ───────────────────────── -->
  <section class="specs-guede <?= $themeClass ?>" style="background-color: <?= htmlspecialchars($villaColor) ?>;">
    <div class="specs-grid-guede">

      <?php if (!empty($villa['bedrooms'])): ?>
      <div class="spec-card-guede">
        <!-- Illustration : lit + lune + étoile -->
        <div class="spec-illus-guede" aria-hidden="true">
          <svg width="80" height="64" viewBox="0 0 80 64" fill="none" xmlns="http://www.w3.org/2000/svg">
            <!-- Lune décorative -->
            <path d="M62 12 A10 10 0 1 1 72 22 A6 6 0 0 0 62 12Z" fill="rgba(255,255,255,0.12)" stroke="rgba(255,255,255,0.5)" stroke-width="1"/>
            <!-- Étoiles -->
            <circle cx="18" cy="10" r="1.2" fill="rgba(255,255,255,0.45)"/>
            <circle cx="24" cy="6" r="0.8" fill="rgba(255,255,255,0.3)"/>
            <circle cx="12" cy="16" r="0.9" fill="rgba(255,255,255,0.25)"/>
            <!-- Tête de lit -->
            <rect x="10" y="28" width="60" height="8" rx="3" fill="rgba(255,255,255,0.08)" stroke="rgba(255,255,255,0.6)" stroke-width="1.2"/>
            <!-- Matelas -->
            <rect x="10" y="36" width="60" height="20" rx="2" fill="rgba(255,255,255,0.05)" stroke="rgba(255,255,255,0.4)" stroke-width="1"/>
            <!-- Oreiller gauche -->
            <rect x="14" y="38" width="22" height="10" rx="3" fill="rgba(255,255,255,0.15)" stroke="rgba(255,255,255,0.4)" stroke-width="0.8"/>
            <!-- Oreiller droit -->
            <rect x="44" y="38" width="22" height="10" rx="3" fill="rgba(255,255,255,0.15)" stroke="rgba(255,255,255,0.4)" stroke-width="0.8"/>
            <!-- Couverture -->
            <path d="M10 46 Q40 52 70 46 L70 56 Q40 58 10 56 Z" fill="rgba(255,255,255,0.1)" stroke="rgba(255,255,255,0.35)" stroke-width="0.8"/>
            <!-- Motif africain sur tête de lit -->
            <path d="M24 30 L26 32 L28 30" stroke="rgba(255,255,255,0.5)" stroke-width="0.8" fill="none"/>
            <path d="M52 30 L54 32 L56 30" stroke="rgba(255,255,255,0.5)" stroke-width="0.8" fill="none"/>
            <line x1="35" y1="29" x2="45" y2="29" stroke="rgba(255,255,255,0.3)" stroke-width="0.7"/>
          </svg>
        </div>
        <div class="spec-val-guede"><?= $villa['bedrooms'] ?></div>
        <div class="spec-lbl-guede">Chambres</div>
        <div class="spec-motif-line"></div>
      </div>
      <?php endif; ?>

      <?php if (!empty($villa['persons'])): ?>
      <div class="spec-card-guede">
        <!-- Illustration : silhouettes de famille -->
        <div class="spec-illus-guede" aria-hidden="true">
          <svg width="80" height="64" viewBox="0 0 80 64" fill="none" xmlns="http://www.w3.org/2000/svg">
            <!-- Arc de cercle décoratif -->
            <path d="M10 55 Q40 20 70 55" stroke="rgba(255,255,255,0.2)" stroke-width="1" fill="none"/>
            <!-- Personne 1 (grande) -->
            <circle cx="25" cy="20" r="7" fill="rgba(255,255,255,0.1)" stroke="rgba(255,255,255,0.6)" stroke-width="1.2"/>
            <path d="M14 44 Q14 32 25 32 Q36 32 36 44" fill="rgba(255,255,255,0.08)" stroke="rgba(255,255,255,0.5)" stroke-width="1.2"/>
            <!-- Personne 2 (grande) -->
            <circle cx="55" cy="20" r="7" fill="rgba(255,255,255,0.1)" stroke="rgba(255,255,255,0.6)" stroke-width="1.2"/>
            <path d="M44 44 Q44 32 55 32 Q66 32 66 44" fill="rgba(255,255,255,0.08)" stroke="rgba(255,255,255,0.5)" stroke-width="1.2"/>
            <!-- Personne 3 (enfant, centre) -->
            <circle cx="40" cy="28" r="5" fill="rgba(255,255,255,0.12)" stroke="rgba(255,255,255,0.7)" stroke-width="1"/>
            <path d="M32 50 Q32 41 40 41 Q48 41 48 50" fill="rgba(255,255,255,0.1)" stroke="rgba(255,255,255,0.55)" stroke-width="1"/>
            <!-- Points décoratifs -->
            <circle cx="40" cy="58" r="1.5" fill="rgba(255,255,255,0.35)"/>
            <circle cx="34" cy="58" r="1" fill="rgba(255,255,255,0.2)"/>
            <circle cx="46" cy="58" r="1" fill="rgba(255,255,255,0.2)"/>
          </svg>
        </div>
        <div class="spec-val-guede"><?= $villa['persons'] ?></div>
        <div class="spec-lbl-guede">Voyageurs</div>
        <div class="spec-motif-line"></div>
      </div>
      <?php endif; ?>

      <?php if (!empty($villa['bathrooms'])): ?>
        <div class="spec-card-guede">
          <div class="spec-icon-guede">
            <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M9 6 6.5 3.5a1.5 1.5 0 0 0-2.12 0 1.5 1.5 0 0 0 0 2.12L7 8M12 2v2M4 12v7a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-7M2 12h20"/></svg>
          </div>
          <div class="spec-val-guede"><?= $villa['bathrooms'] ?></div>
          <div class="spec-lbl-guede">Salles de bain</div>
        </div>
      <?php endif; ?>

      <?php if (!empty($villa['area'])): ?>
        <div class="spec-card-guede">
          <div class="spec-icon-guede">
            <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18M9 21V9"/></svg>
          </div>
          <div class="spec-val-guede"><?= $villa['area'] ?></div>
          <div class="spec-lbl-guede"><?= $villa['areaLabel'] ?? 'm² Habitable' ?></div>
        </div>
      <?php endif; ?>
    </div>
  </section>

  <!-- ── INTRO SPLIT SECTION ─────────────────────────────── -->
  <section class="intro-split">
    <div class="intro-split-grid">
      <div class="intro-big-letter" aria-hidden="true"><?= mb_substr(htmlspecialchars($villa['name']), 0, 1) ?></div>
      <div class="intro-split-text">
        <h2>Une propriété pensée pour<br><em>se retrouver</em></h2>
        <p><?= htmlspecialchars($villa['description'] ?? "Cette villa d'exception est un r\u00eave devenu r\u00e9alit\u00e9 \u2014 pens\u00e9e pour la famille, les amis et les h\u00f4tes de passage. Son architecture marie la chaleur du terroir s\u00e9n\u00e9galais et le confort contemporain.") ?></p>
        <p>Chaque espace a été conçu pour inviter à la contemplation, à la détente et aux instants précieux partagés entre proches dans un cadre de nature préservée.</p>
        <div class="intro-stats-row">
          <?php if (!empty($villa['bedrooms'])): ?>
          <div class="intro-stat">
            <span class="intro-stat-val"><?= $villa['bedrooms'] ?></span>
            <span class="intro-stat-lbl">Chambres</span>
          </div>
          <?php endif; ?>
          <?php if (!empty($villa['persons'])): ?>
          <div class="intro-stat">
            <span class="intro-stat-val"><?= $villa['persons'] ?></span>
            <span class="intro-stat-lbl">Voyageurs</span>
          </div>
          <?php endif; ?>
          <div class="intro-stat">
            <span class="intro-stat-val" style="display:flex;justify-content:center;align-items:center;height:2.5rem;">
              <svg width="52" height="32" viewBox="0 0 52 32" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                <!-- Soleil -->
                <circle cx="26" cy="8" r="5" fill="none" stroke="#C5A059" stroke-width="1.4"/>
                <line x1="26" y1="1" x2="26" y2="3" stroke="#C5A059" stroke-width="1.2" stroke-linecap="round"/>
                <line x1="26" y1="13" x2="26" y2="15" stroke="#C5A059" stroke-width="1.2" stroke-linecap="round"/>
                <line x1="19" y1="8" x2="17" y2="8" stroke="#C5A059" stroke-width="1.2" stroke-linecap="round"/>
                <line x1="33" y1="8" x2="35" y2="8" stroke="#C5A059" stroke-width="1.2" stroke-linecap="round"/>
                <line x1="21.3" y1="3.3" x2="19.9" y2="1.9" stroke="#C5A059" stroke-width="1.2" stroke-linecap="round"/>
                <line x1="30.7" y1="12.7" x2="32.1" y2="14.1" stroke="#C5A059" stroke-width="1.2" stroke-linecap="round"/>
                <line x1="30.7" y1="3.3" x2="32.1" y2="1.9" stroke="#C5A059" stroke-width="1.2" stroke-linecap="round"/>
                <line x1="21.3" y1="12.7" x2="19.9" y2="14.1" stroke="#C5A059" stroke-width="1.2" stroke-linecap="round"/>
                <!-- Vagues -->
                <path d="M2 22 C6 19 10 25 14 22 C18 19 22 25 26 22 C30 19 34 25 38 22 C42 19 46 25 50 22" stroke="#1a6080" stroke-width="1.6" stroke-linecap="round" fill="none"/>
                <path d="M2 27 C6 24 10 30 14 27 C18 24 22 30 26 27 C30 24 34 30 38 27 C42 24 46 30 50 27" stroke="#1a6080" stroke-width="1.4" stroke-linecap="round" fill="none" opacity="0.6"/>
              </svg>
            </span>
            <span class="intro-stat-lbl">Vue mer / jardin</span>
          </div>
          <div class="intro-stat">
            <span class="intro-stat-val">✦</span>
            <span class="intro-stat-lbl">Service sur mesure</span>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- ── UNIFIED GALLERY SECTION ──────────────────────────── -->
  <section class="gallery-guede <?= $themeClass ?>" id="gallery" style="background-color: <?= htmlspecialchars($villaColor) ?>;">
    <div class="section-header-guede">
      <span class="section-tag-guede">Atmosphère & Architecture</span>
      <h2 class="section-title-guede">Galerie Photos</h2>
      <div class="section-divider-guede"></div>
    </div>

    <!-- PINTEREST-STYLE PORTRAIT SLIDER -->
    <div class="bha-portrait-slider-wrap">
      <!-- Flèche gauche -->
      <button class="bha-slider-btn prev" onclick="bhaMoveSlider(-1)" aria-label="Image précédente">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><polyline points="15 18 9 12 15 6"/></svg>
      </button>

      <!-- Container des slides -->
      <div class="bha-portrait-slider">
        <div class="bha-slider-track" id="bhaSliderTrack">
          <?php foreach ($allImages as $idx => $imgUrl): ?>
            <div class="bha-slider-slide" onclick="bhaSelectSlide(<?= $idx ?>)">
              <img src="<?= htmlspecialchars(getImageUrl($imgUrl)) ?>" alt="Photo <?= $idx + 1 ?>">
              <span class="bha-slide-number"><?= sprintf('%02d', $idx + 1) ?> / <?= sprintf('%02d', count($allImages)) ?></span>
            </div>
          <?php endforeach; ?>
        </div>
      </div>

      <!-- Flèche droite -->
      <button class="bha-slider-btn next" onclick="bhaMoveSlider(1)" aria-label="Image suivante">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><polyline points="9 18 15 12 9 6"/></svg>
      </button>
    </div>

    <!-- Indicateur de progression (barre fine en bas) -->
    <div class="bha-slider-progress-bar">
      <div class="bha-slider-progress-fill" id="bhaSliderProgress" style="width: 0%;"></div>
    </div>

    <script>
      (function(){
        let currentIdx = 0;
        const slides = Array.from(document.querySelectorAll('.bha-slider-slide'));
        const total = slides.length;
        const progressFill = document.getElementById('bhaSliderProgress');
        
        window.bhaUpdateSlider = function() {
          slides.forEach((slide, idx) => {
            slide.className = 'bha-slider-slide'; // clear classes
            
            let diff = idx - currentIdx;
            
            // Handle wrap around
            if (diff < -1 && idx === 0 && currentIdx === total - 1) diff = 1;
            if (diff > 1 && idx === total - 1 && currentIdx === 0) diff = -1;
            
            if (idx === currentIdx) {
              slide.classList.add('active');
            } else if (idx === (currentIdx - 1 + total) % total) {
              slide.classList.add('prev-slide');
            } else if (idx === (currentIdx + 1) % total) {
              slide.classList.add('next-slide');
            }
          });
          
          // Update progress bar
          if (progressFill) {
            const pct = ((currentIdx + 1) / total) * 100;
            progressFill.style.width = pct + '%';
          }
        };
        
        window.bhaMoveSlider = function(dir) {
          currentIdx = (currentIdx + dir + total) % total;
          window.bhaUpdateSlider();
        };
        
        window.bhaSelectSlide = function(idx) {
          if (idx === currentIdx) {
            // Open lightbox if clicking the active slide
            if (typeof openLightbox === 'function') {
              openLightbox(idx);
            }
          } else {
            currentIdx = idx;
            window.bhaUpdateSlider();
          }
        };
        
        // Initialize slider
        if (total > 0) {
          window.bhaUpdateSlider();
        }
      })();
    </script>
  </section>

  <!-- ── EXPERIENCES SECTION ("Un lieu, plusieurs façons de le vivre") ── -->
  <section class="experiences-guede <?= $themeClass ?>" style="background-color: <?= htmlspecialchars($villaColor) ?>;">
    <div class="section-header-guede">
      <span class="section-tag-guede">Art de Vivre</span>
      <h2 class="section-title-guede">Un lieu, plusieurs façons de le vivre</h2>
      <div class="section-divider-guede"></div>
    </div>

    <div class="experiences-grid">
      <div class="exp-card" style="background: #0B1613 !important;">
        <div class="exp-icon">
          <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="m12 3-1.912 5.813a2 2 0 0 1-1.275 1.275L3 12l5.813 1.912a2 2 0 0 1 1.275 1.275L12 21l1.912-5.813a2 2 0 0 1 1.275-1.275L21 12l-5.813-1.912a2 2 0 0 1-1.275-1.275L12 3Z"/></svg>
        </div>
        <h3 class="exp-title">Célébrations intimistes</h3>
        <p class="exp-desc">Marquez les moments précieux dans un cadre d'exception, élégant et chaleureux.</p>
      </div>

      <div class="exp-card" style="background: #0B1613 !important;">
        <div class="exp-icon">
          <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><circle cx="19" cy="11" r="3"/></svg>
        </div>
        <h3 class="exp-title">Réunions & séminaires</h3>
        <p class="exp-desc">Des espaces calmes et inspirants pour penser, partager et avancer en toute sérénité.</p>
      </div>

      <div class="exp-card" style="background: #0B1613 !important;">
        <div class="exp-icon">
          <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
        </div>
        <h3 class="exp-title">Retraites paisibles</h3>
        <p class="exp-desc">Prenez du temps pour vous, respirez et recentrez-vous dans un confort absolu.</p>
      </div>

      <div class="exp-card" style="background: #0B1613 !important;">
        <div class="exp-icon">
          <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
        </div>
        <h3 class="exp-title">Service sur mesure</h3>
        <p class="exp-desc">Chef cuisinier, conciergerie et prestations personnalisées selon vos besoins.</p>
      </div>
    </div>
  </section>

  <!-- ── HERITAGE & AMENITIES SECTION ─────────────────────── -->
  <section class="story-guede" style="background: #FAF6F0;">
    <div class="story-container">
      <div class="story-content bha-reveal-left">
        <span style="font-size:0.6rem;letter-spacing:0.3em;text-transform:uppercase;color:rgba(197,160,89,0.8);margin-bottom:16px;display:block;">Héritage &amp; Culture</span>
        <h2 style="font-family:var(--font-serif);font-size:clamp(2rem,4vw,3rem);color:#1a2e28;font-weight:400;line-height:1.15;margin-bottom:24px;">Un héritage habité<br>au cœur du Sénégal</h2>
        <p>Inspiré des origines Toucouleur-Peul, cette propriété rend hommage à une culture de tradition plurielle, de légèreté et de transmission. L'Afrique — dans sa vaste splendeur, ses traditions fécondes, ses mille facettes géographiques — résonne à chaque pierre posée.</p>
        <p>Ici, deux âmes se retrouvent — Dispora SN et Baobab Horizon — dans une propriété forgée aux générations futures.</p>
        <?php if (!empty($villa['tags'])): ?>
          <div class="story-tags">
            <?php foreach ($villa['tags'] as $tag): ?>
              <span class="story-tag-pill"><?= htmlspecialchars($tag) ?></span>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
      <div class="story-image bha-reveal-right">
        <img src="<?= htmlspecialchars(getImageUrl($allImages[1] ?? $allImages[0])) ?>" alt="Atmosphère de la propriété">
      </div>
    </div>
  </section>

  <!-- ── HERITAGE TIMELINE ──────────────────────────────────── -->
  <section class="heritage-full">
    <div class="heritage-timeline">
      <div class="heritage-lead">
        <p>Du rêve à la réalité, de la terre à la pierre, de la tradition à l'hospitalité contemporaine — découvrez l'histoire de cette propriété unique sur la Petite Côte du Sénégal.</p>
      </div>

      <!-- Row 1: left content -->
      <div class="timeline-row">
        <div class="timeline-cell-left">
          <div class="timeline-label">L'Origine</div>
          <div class="timeline-title">Toucouleur &amp; Peul</div>
          <div class="timeline-desc">Inscrite dans les mémoires de la Petite Côte, à mi-chemin entre Mbour et Saly, cette terre porta d'abord les empreintes des pêcheurs Lébou et des marchands Peul. Chaque pierre ici raconte une continuité.</div>
        </div>
        <div class="timeline-center"><div class="timeline-dot"></div></div>
        <div class="timeline-cell-right"></div>
      </div>

      <!-- Row 2: right content -->
      <div class="timeline-row">
        <div class="timeline-cell-left"></div>
        <div class="timeline-center"><div class="timeline-dot"></div></div>
        <div class="timeline-cell-right">
          <div class="timeline-label">Île à Morphil · Fleuve Sénégal</div>
          <div class="timeline-title">La Terre d'Attache</div>
          <div class="timeline-desc">C'est à Morphil, entre les méandres du fleuve Sénégal, que naquit la vision. Un territoire d'eau douce, de baobabs centenaires et d'hospitalité légendaire. Ce territoire est la genèse spirituelle de cet espace.</div>
        </div>
      </div>

      <!-- Row 3: left content -->
      <div class="timeline-row">
        <div class="timeline-cell-left">
          <div class="timeline-label">La Transition</div>
          <div class="timeline-title">Diaspora &amp; Transmission</div>
          <div class="timeline-desc">Entre deux rives — l'Afrique profonde et le monde contemporain — cette villa est née d'un souhait : créer un pont entre les générations, un espace de retour aux sources et d'ancrage identitaire fort.</div>
        </div>
        <div class="timeline-center"><div class="timeline-dot"></div></div>
        <div class="timeline-cell-right"></div>
      </div>

      <!-- Row 4: right content -->
      <div class="timeline-row">
        <div class="timeline-cell-left"></div>
        <div class="timeline-center"><div class="timeline-dot"></div></div>
        <div class="timeline-cell-right">
          <div class="timeline-label">Baobab Horizon</div>
          <div class="timeline-title"><?= htmlspecialchars($villa['name']) ?></div>
          <div class="timeline-desc">Une propriété Baobab Horizon — née d'une vision commune, tournée vers la beauté, le partage et la transmission. Ici, plusieurs générations se donnent rendez-vous.</div>
        </div>
      </div>

    </div>
  </section>

  <!-- ── L'ART DE RECEVOIR ──────────────────────────────────── -->
  <section class="art-recevoir section-motif-bg">
    <div class="art-recevoir-grid">
      <div class="art-recevoir-intro">
        <span style="font-size:0.6rem;letter-spacing:0.3em;text-transform:uppercase;color:rgba(197,160,89,0.6);margin-bottom:16px;display:block;">Philosophie</span>
        <h2>L'art de recevoir,<br><em>naturellement</em></h2>
        <p>À Baobab Horizon, chaque séjour est une mise en scène de l'hospitalité africaine dans toute sa générosité. Vous n'êtes pas seulement accueillis — vous êtes attendus, reconnus et choyés.</p>
      </div>
      <div class="art-pillars">
        <div class="pillar-card">
          <div class="pillar-name">Accueil</div>
          <div class="pillar-title">Une attention à l'arrivée</div>
          <div class="pillar-desc">Dès votre premier pas, une atmosphère chaleureuse vous enveloppe. Cocktail de bienvenue, présentation des lieux et disponibilité permanente de notre équipe.</div>
        </div>
        <div class="pillar-card">
          <div class="pillar-name">Sérénité</div>
          <div class="pillar-title">Un espace sans bruit</div>
          <div class="pillar-desc">Piscine privée, jardins soignés et espaces de vie généreux. Chaque recoin a été pensé pour que vous puissiez réellement vous déposer et souffler.</div>
        </div>
        <div class="pillar-card">
          <div class="pillar-name">Attention</div>
          <div class="pillar-title">Un service sur-mesure</div>
          <div class="pillar-desc">Chef cuisinier, conciergerie, excursions ou simple disponibilité — nous adaptons chaque séjour à vos envies, sans jamais vous envahir.</div>
        </div>
      </div>
    </div>
  </section>

  <!-- ── BOOKING & PRICE BANNER ────────────────────────────── -->
  <section class="booking-banner">
    <div class="booking-card" style="background: var(--night-surface); border:1px solid rgba(214,175,92,0.25);">
      <div>
        <div style="font-size:0.6rem;letter-spacing:0.28em;text-transform:uppercase;color:rgba(214,175,92,0.7);margin-bottom:12px;">Tarification</div>
        <div class="price-amount" style="color:#fff !important;"><?= number_format($villa['price'] ?? 0, 0, ',', ' ') ?> FCFA</div>
        <div class="price-unit" style="color:rgba(248,244,236,0.5)"><?= htmlspecialchars($villa['priceUnit'] ?? 'par nuit') ?> <?= !empty($villa['priceNote']) ? '— ' . htmlspecialchars($villa['priceNote']) : '' ?></div>
        <div style="margin-top:18px;display:flex;gap:12px;flex-wrap:wrap;align-items:center;">
          <span style="font-size:0.72rem;color:rgba(248,244,236,0.5);display:flex;align-items:center;gap:6px;"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="rgba(197,160,89,0.7)" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22,4 12,14.01 9,11.01"/></svg>Cocktail de bienvenue offert</span>
          <span style="font-size:0.72rem;color:rgba(248,244,236,0.5);display:flex;align-items:center;gap:6px;"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="rgba(197,160,89,0.7)" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22,4 12,14.01 9,11.01"/></svg>Chef cuisinier disponible</span>
        </div>
      </div>
      <div style="display:flex;gap:16px;flex-wrap:wrap;align-items:center;">
        <button type="button" class="btn-primary-guede" onclick="window.openReservationModal()" style="background:#D6AF5C !important; color:#0F1A17 !important; border-color:#D6AF5C !important;">Réserver mon séjour →</button>
        <a href="https://wa.me/221780140942?text=<?= urlencode('Bonjour, je suis intéressé par ' . $villa['name']) ?>" class="btn-secondary-guede" target="_blank" style="color:#fff;border-color:rgba(255,255,255,0.3);">Demander sur WhatsApp</a>
      </div>
    </div>
  </section>

  <!-- ── CLOSING CTA SECTION ─────────────────────────────────── -->
  <section class="closing-cta">
    <span class="closing-cta-eyebrow">Petite Côte · Sénégal</span>
    <h2>Votre parenthèse<br><em>commence ici</em></h2>
    <p>Chaque séjour à <?= htmlspecialchars($villa['name']) ?> est une invitation à ralentir, à respirer et à créer des souvenirs qui durent. Vous n'aurez qu'une envie : revenir.</p>
    
    <div class="villa-contact-capsules" style="margin: 0 auto 30px;">
      <?php 
        $wColor = $villaPalette[0];
        $pColor = $villaPalette[1];
        $eColor = $villaPalette[2];
      ?>
      <!-- Bouton WhatsApp -->
      <a href="https://wa.me/221780140942?text=Bonjour,%20je%20souhaite%20des%20informations%20sur%20la%20villa%20<?= urlencode($villa['name']) ?>" 
         class="villa-contact-capsule" target="_blank" 
         style="background-color: <?= $wColor ?>; color: <?= getContrastColor($wColor) ?>; border-color: rgba(255,255,255,0.08);">
        <svg viewBox="0 0 24 24" class="contact-icon" style="fill: currentColor;"><path d="M12.031 6.172c-3.181 0-5.767 2.586-5.768 5.766-.001 1.298.38 2.27 1.019 3.287l-.582 2.128 2.182-.573c.978.58 1.911.928 3.145.929 3.178 0 5.767-2.587 5.768-5.766.001-3.187-2.575-5.77-5.764-5.771zm3.392 8.244c-.144.405-.837.774-1.17.824-.299.045-.677.063-1.092-.069-.252-.08-.575-.187-.988-.365-1.739-.751-2.874-2.502-2.961-2.617-.087-.116-.708-.94-.708-1.793s.448-1.273.607-1.446c.159-.173.346-.217.462-.217l.332.006c.106.005.249-.04.39.298.144.347.491 1.2.534 1.287.043.087.072.188.014.304-.058.116-.087.188-.173.289l-.26.304c-.087.086-.177.18-.076.354.101.174.449.741.964 1.201.662.591 1.221.774 1.394.86s.275.072.376-.044c.101-.116.433-.506.549-.68.116-.173.231-.145.39-.087s1.011.477 1.184.564.289.13.332.202c.043.072.043.419-.101.824z"/></svg>
        <span>WhatsApp</span>
      </a>

      <!-- Bouton Appel Normal -->
      <a href="tel:+221780140942" class="villa-contact-capsule" 
         style="background-color: <?= $pColor ?>; color: <?= getContrastColor($pColor) ?>; border-color: rgba(255,255,255,0.08);">
        <svg viewBox="0 0 24 24" class="contact-icon" style="fill: none; stroke: currentColor; stroke-width: 2;"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
        <span>Appeler</span>
      </a>

      <!-- Bouton Email -->
      <a href="mailto:contact@baobabhorizon.com?subject=Demande%20d'information%20-%20Villa%20<?= urlencode($villa['name']) ?>" 
         class="villa-contact-capsule" 
         style="background-color: <?= $eColor ?>; color: <?= getContrastColor($eColor) ?>; border-color: rgba(255,255,255,0.08);">
        <svg viewBox="0 0 24 24" class="contact-icon" style="fill: none; stroke: currentColor; stroke-width: 2;"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
        <span>E-mail</span>
      </a>
    </div>

    <!-- Decorative leaf SVG -->
    <svg class="closing-leaf" width="320" height="320" viewBox="0 0 100 100" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
      <path d="M50 10 C70 10 90 30 90 50 C90 70 70 90 50 90 C30 90 10 70 10 50 C10 30 30 10 50 10Z" stroke="rgba(255,255,255,0.08)" stroke-width="0.8" fill="none"/>
      <path d="M50 10 L50 90" stroke="rgba(255,255,255,0.08)" stroke-width="0.5"/>
      <path d="M50 25 C62 28 72 38 72 50" stroke="rgba(255,255,255,0.08)" stroke-width="0.4"/>
      <path d="M50 40 C58 42 65 48 65 55" stroke="rgba(255,255,255,0.08)" stroke-width="0.4"/>
      <path d="M50 25 C38 28 28 38 28 50" stroke="rgba(255,255,255,0.08)" stroke-width="0.4"/>
      <path d="M50 40 C42 42 35 48 35 55" stroke="rgba(255,255,255,0.08)" stroke-width="0.4"/>
    </svg>
  </section>

  <!-- ── SIMILAR PROPERTIES ───────────────────────────────── -->
  <?php
  $similarProperties = [];
  foreach ($properties as $k => $v) {
    if ($k !== $key && ($v['type'] ?? '') === ($villa['type'] ?? 'vacances')) {
      $similarProperties[] = ['key' => $k, 'data' => $v];
    }
  }
  $similarProperties = array_slice($similarProperties, 0, 3);
  ?>

  <?php if (!empty($similarProperties)): ?>
  <section class="similar-guede <?= $themeClass ?>" style="background-color: <?= htmlspecialchars($villaColor) ?>;">
    <div class="section-header-guede">
      <span class="section-tag-guede">Découvrir aussi</span>
      <h2 class="section-title-guede">Biens Similaires</h2>
      <div class="section-divider-guede"></div>
    </div>

    <div class="similar-grid-guede">
      <?php foreach ($similarProperties as $similar): 
        $sd = $similar['data'];
        $sImg = !empty($sd['images'][0]) ? getImageUrl($sd['images'][0]) : 'https://images.unsplash.com/photo-1613977257363-707ba9348227?w=600&q=80';
      ?>
        <div class="similar-card-guede">
          <div class="similar-card-img">
            <img src="<?= htmlspecialchars($sImg) ?>" alt="<?= htmlspecialchars($sd['name']) ?>" loading="lazy">
          </div>
          <div class="similar-card-body">
            <div class="similar-card-name"><?= htmlspecialchars($sd['name']) ?></div>
            <div class="similar-card-zone"><?= htmlspecialchars($sd['zone'] ?? '') ?></div>
            <div class="similar-card-price"><?= number_format($sd['price'] ?? 0, 0, ',', ' ') ?> FCFA <span style="font-size:.6rem;color:var(--text-muted)">/ nuit</span></div>
            <a href="detail?key=<?= htmlspecialchars($similar['key']) ?>" class="btn-secondary-guede" style="margin-top:16px;padding:10px 20px;display:inline-block;font-size:.6rem">Voir le bien →</a>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </section>
  <?php endif; ?>



  <!-- ── GLOBAL FOOTER ────────────────────────────────────── -->
  <footer>
    <div class="footer-top">
      <div>
        <div class="footer-logo">
          <img src="LOGO.jpg" alt="Baobab Horizon Logo">
        </div>
        <p class="footer-tagline">Votre partenaire immobilier d'exception sur la Petite Côte du Sénégal.</p>
        <div class="footer-social">
          <a href="https://www.tiktok.com/@baobab_horizon" target="_blank" rel="noopener" aria-label="TikTok">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M19.59 6.69a4.83 4.83 0 0 1-3.77-4.25V2h-3.45v13.67a2.89 2.89 0 0 1-5.2 1.74 2.89 2.89 0 0 1 2.31-4.64 2.93 2.93 0 0 1 .88.13V9.4a6.84 6.84 0 0 0-1-.05A6.33 6.33 0 0 0 5 20.1a6.34 6.34 0 0 0 10.86-4.43v-7a8.16 8.16 0 0 0 4.77 1.52v-3.4a4.85 4.85 0 0 1-1-.1z"/></svg>
          </a>
          <a href="https://www.instagram.com/baobab_horizon" target="_blank" rel="noopener" aria-label="Instagram">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 1 0 0 12.324 6.162 6.162 0 0 0 0-12.324zM12 16a4 4 0 1 1 0-8 4 4 0 0 1 0 8zm6.406-11.845a1.44 1.44 0 1 0 0 2.881 1.44 1.44 0 0 0 0-2.881z"/></svg>
          </a>
        </div>
      </div>
      <div>
        <p class="footer-col-title">Navigation</p>
        <ul class="footer-links">
          <li><a href="index.html">Accueil</a></li>
          <li><a href="ventes.html">Acheter</a></li>
          <li><a href="vacances.html">Louer</a></li>
          <li><a href="location-voiture.html">Location de voiture</a></li>
          <li><a href="contact.html">Contact</a></li>
        </ul>
      </div>
      <div>
        <p class="footer-col-title">Offres</p>
        <ul class="footer-links">
          <li><a href="vacances.html">Villas de prestige</a></li>
          <li><a href="vacances.html">Séjours vacances</a></li>
          <li><a href="ventes.html">Investissement</a></li>
          <li><a href="location-voiture.html">Flotte de véhicules</a></li>
        </ul>
      </div>
      <div>
        <p class="footer-col-title">Contact</p>
        <ul class="footer-links">
          <li><a href="tel:+221780140942">+221 78 014 09 42</a></li>
          <li><a href="https://wa.me/221780140942" target="_blank">WhatsApp direct</a></li>
          <li><a href="mailto:baobhorizon@gmail.com">baobhorizon@gmail.com</a></li>
        </ul>
      </div>
    </div>
    <div class="footer-bottom">
      <span>© 2026 Baobab Horizon · Tous droits réservés</span>
      <div style="display:flex; gap:20px;">
        <a href="mentions-legales.html" style="color:inherit; text-decoration:none;">Mentions légales</a>
        <a href="politique-de-confidentialite.html" style="color:inherit; text-decoration:none;">Confidentialité</a>
      </div>
    </div>
  </footer>

  <!-- ── LIGHTBOX MODAL ───────────────────────────────────── -->
  <div class="modal-lightbox" id="lightboxModal" onclick="if(event.target===this)closeLightbox()">
    <button class="modal-lightbox-close" onclick="closeLightbox()">&times;</button>
    <img id="lightboxImg" src="" alt="">
    <div class="modal-lightbox-nav">
      <button onclick="navLightbox(-1)">&#8592;</button>
      <button onclick="navLightbox(1)">&#8594;</button>
    </div>
  </div>

  <!-- ── RESERVATION FORM MODAL (MATCHING ACCUEIL STYLE & COLORS) ── -->
  <div class="reservation-modal" id="reservationModal" onclick="if(event.target===this)closeReservationModal()"
    style="position:fixed !important;inset:0 !important;z-index:999999 !important;background:rgba(10,18,15,0.94) !important;backdrop-filter:blur(8px) !important;display:none;align-items:center !important;justify-content:center !important;padding:16px !important;box-sizing:border-box !important">
    <div class="reservation-box"
      style="background:#11231E !important;border:1px solid rgba(214,175,92,0.25) !important;max-width:580px !important;width:100% !important;padding:28px 24px !important;position:relative !important;max-height:90vh !important;overflow-y:auto !important;-webkit-overflow-scrolling:touch !important;border-radius:4px !important;box-shadow:0 20px 60px rgba(0,0,0,0.8) !important;box-sizing:border-box !important">
      <button type="button" class="reservation-close" onclick="closeReservationModal()"
        style="position:absolute !important;top:12px !important;right:12px !important;width:38px !important;height:38px !important;border:1px solid rgba(214,175,92,0.3) !important;background:transparent !important;color:#EDE3D2 !important;font-size:1.4rem !important;line-height:1 !important;cursor:pointer !important;display:flex !important;align-items:center !important;justify-content:center !important;border-radius:3px !important">&times;</button>
      <h2 class="reservation-title"
        style="font-family:'Poppins',sans-serif !important;font-size:1.5rem !important;font-weight:500 !important;line-height:1.2 !important;color:#FFFFFF !important;margin:0 0 6px 0 !important">
        Demande de réservation</h2>
      <p class="reservation-subtitle"
        style="color:#8A9B97 !important;font-family:'Poppins',sans-serif !important;line-height:1.5 !important;margin:0 0 20px 0 !important;font-size:0.88rem !important;font-weight:400 !important">Complétez le formulaire pour
        envoyer votre demande de réservation.</p>

      <div class="reservation-villa-info"
        style="background:rgba(214,175,92,0.08) !important;border:1px solid rgba(214,175,92,0.25) !important;padding:16px !important;margin-bottom:20px !important;border-radius:3px !important">
        <div class="reservation-villa-name" id="resVillaName"
          style="font-size:1.12rem !important;color:#D6AF5C !important;margin-bottom:6px !important;font-family:'Poppins',sans-serif !important;font-weight:600 !important"></div>
        <div class="reservation-villa-price" id="resVillaPrice" style="font-size:0.95rem !important;color:#FFFFFF !important;font-family:'Poppins',sans-serif !important;font-weight:500 !important"></div>
      </div>

      <form id="reservationForm" class="reservation-form"
        style="display:grid !important;grid-template-columns:1fr 1fr !important;gap:14px !important;margin-bottom:20px !important">
        <input type="hidden" id="resVillaSlug">
        <input type="hidden" id="resVillaPriceValue">

        <div class="reservation-field" style="display:flex !important;flex-direction:column !important;gap:6px !important">
          <label for="resFirstName"
            style="display:block !important;font-size:0.72rem !important;letter-spacing:0.12em !important;text-transform:uppercase !important;color:#D6AF5C !important;font-family:'Poppins',sans-serif !important;font-weight:600 !important">Prénom</label>
          <input type="text" id="resFirstName" required
            style="width:100% !important;border:1px solid rgba(214,175,92,0.25) !important;background:#0B1814 !important;color:#FFFFFF !important;padding:14px 16px !important;font-family:'Poppins',sans-serif !important;font-size:0.95rem !important;outline:none !important;box-sizing:border-box !important;border-radius:3px !important">
        </div>
        <div class="reservation-field" style="display:flex !important;flex-direction:column !important;gap:6px !important">
          <label for="resLastName"
            style="display:block !important;font-size:0.72rem !important;letter-spacing:0.12em !important;text-transform:uppercase !important;color:#D6AF5C !important;font-family:'Poppins',sans-serif !important;font-weight:600 !important">Nom</label>
          <input type="text" id="resLastName" required
            style="width:100% !important;border:1px solid rgba(214,175,92,0.25) !important;background:#0B1814 !important;color:#FFFFFF !important;padding:14px 16px !important;font-family:'Poppins',sans-serif !important;font-size:0.95rem !important;outline:none !important;box-sizing:border-box !important;border-radius:3px !important">
        </div>
        <div class="reservation-field" style="display:flex !important;flex-direction:column !important;gap:6px !important">
          <label for="resPhone"
            style="display:block !important;font-size:0.72rem !important;letter-spacing:0.12em !important;text-transform:uppercase !important;color:#D6AF5C !important;font-family:'Poppins',sans-serif !important;font-weight:600 !important">Téléphone</label>
          <input type="tel" id="resPhone" required
            style="width:100% !important;border:1px solid rgba(214,175,92,0.25) !important;background:#0B1814 !important;color:#FFFFFF !important;padding:14px 16px !important;font-family:'Poppins',sans-serif !important;font-size:0.95rem !important;outline:none !important;box-sizing:border-box !important;border-radius:3px !important">
        </div>
        <div class="reservation-field" style="display:flex !important;flex-direction:column !important;gap:6px !important">
          <label for="resEmail"
            style="display:block !important;font-size:0.72rem !important;letter-spacing:0.12em !important;text-transform:uppercase !important;color:#D6AF5C !important;font-family:'Poppins',sans-serif !important;font-weight:600 !important">Email</label>
          <input type="email" id="resEmail" required
            style="width:100% !important;border:1px solid rgba(214,175,92,0.25) !important;background:#0B1814 !important;color:#FFFFFF !important;padding:14px 16px !important;font-family:'Poppins',sans-serif !important;font-size:0.95rem !important;outline:none !important;box-sizing:border-box !important;border-radius:3px !important">
        </div>
        <div class="reservation-field" style="display:flex !important;flex-direction:column !important;gap:6px !important">
          <label for="resStartDate"
            style="display:block !important;font-size:0.72rem !important;letter-spacing:0.12em !important;text-transform:uppercase !important;color:#D6AF5C !important;font-family:'Poppins',sans-serif !important;font-weight:600 !important">Date d'arrivée</label>
          <input type="date" id="resStartDate" required
            style="width:100% !important;border:1px solid rgba(214,175,92,0.25) !important;background:#0B1814 !important;color:#FFFFFF !important;padding:14px 16px !important;font-family:'Poppins',sans-serif !important;font-size:0.95rem !important;outline:none !important;box-sizing:border-box !important;border-radius:3px !important;color-scheme:dark">
        </div>
        <div class="reservation-field" style="display:flex !important;flex-direction:column !important;gap:6px !important">
          <label for="resEndDate"
            style="display:block !important;font-size:0.72rem !important;letter-spacing:0.12em !important;text-transform:uppercase !important;color:#D6AF5C !important;font-family:'Poppins',sans-serif !important;font-weight:600 !important">Date de départ</label>
          <input type="date" id="resEndDate" required
            style="width:100% !important;border:1px solid rgba(214,175,92,0.25) !important;background:#0B1814 !important;color:#FFFFFF !important;padding:14px 16px !important;font-family:'Poppins',sans-serif !important;font-size:0.95rem !important;outline:none !important;box-sizing:border-box !important;border-radius:3px !important;color-scheme:dark">
        </div>
        <div class="reservation-field" style="display:flex !important;flex-direction:column !important;gap:6px !important">
          <label for="resGuests"
            style="display:block !important;font-size:0.72rem !important;letter-spacing:0.12em !important;text-transform:uppercase !important;color:#D6AF5C !important;font-family:'Poppins',sans-serif !important;font-weight:600 !important">Nombre de personnes</label>
          <input type="number" id="resGuests" min="1" required
            style="width:100% !important;border:1px solid rgba(214,175,92,0.25) !important;background:#0B1814 !important;color:#FFFFFF !important;padding:14px 16px !important;font-family:'Poppins',sans-serif !important;font-size:0.95rem !important;outline:none !important;box-sizing:border-box !important;border-radius:3px !important">
        </div>
        <div class="reservation-field" style="display:flex !important;flex-direction:column !important;gap:6px !important">
          <label for="resChef"
            style="display:block !important;font-size:0.72rem !important;letter-spacing:0.12em !important;text-transform:uppercase !important;color:#D6AF5C !important;font-family:'Poppins',sans-serif !important;font-weight:600 !important">Option chef cuisinier</label>
          <select id="resChef"
            style="width:100% !important;border:1px solid rgba(214,175,92,0.25) !important;background:#0B1814 !important;color:#FFFFFF !important;padding:14px 16px !important;font-family:'Poppins',sans-serif !important;font-size:0.95rem !important;outline:none !important;box-sizing:border-box !important;border-radius:3px !important">
            <option value="Non">Non</option>
            <option value="Oui (Inclus / Offert 1ère réservation)">Oui (Inclus / Offert 1ère réservation)</option>
          </select>
        </div>

        <div id="resWelcomeBox" style="grid-column: 1/-1 !important; background:rgba(214,175,92,0.12) !important; border:1px solid rgba(214,175,92,0.3) !important; border-radius:4px !important; padding:14px !important; margin-top:4px !important;">
          <div style="font-weight:700 !important; color:#D6AF5C !important; font-size:0.85rem !important; margin-bottom:6px !important; display:flex !important; align-items:center !important; gap:8px !important; font-family:'Poppins',sans-serif !important;">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#D6AF5C" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0;"><rect x="3" y="8" width="18" height="4" rx="1"/><path d="M12 8v13"/><path d="M19 12v7a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2v-7"/><path d="M7.5 8a2.5 2.5 0 0 1 0-5A4.8 8 0 0 1 12 8a4.8 8 0 0 1 4.5-5 2.5 2.5 0 0 1 0 5"/></svg>
            Offre 1ère réservation : Cocktail & Chef cuisinier offerts + Remise
          </div>
          <label style="display:flex !important; align-items:center !important; gap:8px !important; font-size:0.78rem !important; color:#fff !important; cursor:pointer !important; margin-bottom:6px !important; font-family:'Poppins',sans-serif !important; text-transform:none !important; letter-spacing:normal !important;">
            <input type="checkbox" id="resCreateAccount" checked style="accent-color:#D6AF5C !important; width:16px !important; height:16px !important;">
            Activer l'offre de bienvenue (<strong>Cocktail & Chef offerts + 1% de remise</strong>)
          </label>
          <label style="display:flex !important; align-items:center !important; gap:8px !important; font-size:0.78rem !important; color:#D6AF5C !important; cursor:pointer !important; font-family:'Poppins',sans-serif !important; text-transform:none !important; letter-spacing:normal !important;">
            <input type="checkbox" id="resOptMarketing" checked style="accent-color:#D6AF5C !important; width:16px !important; height:16px !important;">
            Recevoir nos offres exclusives (<strong>+1% de remise supplémentaire, soit 2% au total</strong>)
          </label>
        </div>

        <!-- PROMO CODE SECTION (POUR LES OFFRES SPÉCIALES OU CLIENTS EXISTANTS) -->
        <div style="grid-column: 1/-1 !important; background:rgba(255,255,255,0.03) !important; border:1px solid rgba(214,175,92,0.2) !important; border-radius:4px !important; padding:12px 14px !important;">
          <div style="font-size:0.75rem !important; color:#D6AF5C !important; font-weight:600 !important; margin-bottom:8px !important; text-transform:uppercase !important; letter-spacing:0.1em !important; font-family:'Poppins',sans-serif !important; display:flex !important; align-items:center !important; gap:6px !important;">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#D6AF5C" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0;"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/><line x1="7" y1="7" x2="7.01" y2="7"/></svg>
            Code promotionnel (Optionnel)
          </div>
          <div style="display:flex !important; gap:8px !important;">
            <input type="text" id="resPromoCodeInput" placeholder="Ex: VIP5, ETE2026" style="flex:1 !important; border:1px solid rgba(214,175,92,0.25) !important; background:#0B1814 !important; color:#FFFFFF !important; padding:10px 14px !important; font-family:'Poppins',sans-serif !important; font-size:0.85rem !important; outline:none !important; border-radius:3px !important; text-transform:uppercase !important;">
            <button type="button" onclick="applyPromoCode()" style="padding:10px 16px !important; background:#D6AF5C !important; color:#0B1613 !important; font-weight:700 !important; font-size:0.75rem !important; border:none !important; border-radius:3px !important; cursor:pointer !important; text-transform:uppercase !important; letter-spacing:0.08em !important; font-family:'Poppins',sans-serif !important;">Appliquer</button>
          </div>
          <div id="resPromoMessage" style="font-size:0.75rem !important; margin-top:6px !important; display:none;"></div>
        </div>

        <div class="reservation-actions" style="display:flex !important;gap:10px !important;margin-top:20px !important;grid-column:1/-1 !important">
          <button type="button" class="btn-secondary" onclick="closeReservationModal()"
            style="display:inline-flex !important;align-items:center !important;justify-content:center !important;gap:8px !important;border:1px solid rgba(214,175,92,0.4) !important;color:#EDE3D2 !important;font-size:0.72rem !important;letter-spacing:.15em !important;text-transform:uppercase !important;text-decoration:none !important;padding:14px 24px !important;transition:.3s !important;cursor:pointer !important;background:transparent !important;font-family:'Poppins',sans-serif !important;font-weight:600 !important;flex:1 !important;border-radius:3px !important">Annuler</button>
          <button type="submit" class="btn-primary"
            style="display:inline-flex !important;align-items:center !important;justify-content:center !important;gap:10px !important;background:#9C6F1C !important;color:#0F1A17 !important;font-size:0.72rem !important;letter-spacing:.15em !important;text-transform:uppercase !important;padding:14px 24px !important;transition:.3s !important;cursor:pointer !important;border:1px solid #9C6F1C !important;font-family:'Poppins',sans-serif !important;font-weight:700 !important;flex:1 !important;border-radius:3px !important">Envoyer
            ma demande</button>
        </div>
      </form>
    </div>
  </div>

  <!-- ── JAVASCRIPT LOGIC ─────────────────────────────────── -->
  <script>
    // Lightbox Modal logic
    const images = <?= json_encode(array_map('getImageUrl', $allImages)) ?>;
    let currentImgIdx = 0;

    function openLightbox(idx) {
      currentImgIdx = idx;
      updateLightbox();
      var lm = document.getElementById('lightboxModal');
      if (lm) lm.classList.add('open');
      document.body.style.overflow = 'hidden';
    }

    function closeLightbox() {
      var lm = document.getElementById('lightboxModal');
      if (lm) lm.classList.remove('open');
      document.body.style.overflow = '';
    }

    function updateLightbox() {
      var li = document.getElementById('lightboxImg');
      if (li && images[currentImgIdx]) li.src = images[currentImgIdx];
    }

    function navLightbox(dir) {
      currentImgIdx = (currentImgIdx + dir + images.length) % images.length;
      updateLightbox();
    }

    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape') {
        if (typeof closeLightbox === 'function') closeLightbox();
        if (typeof closeReservationModal === 'function') closeReservationModal();
      }
      var lm = document.getElementById('lightboxModal');
      if (lm && lm.classList.contains('open')) {
        if (e.key === 'ArrowRight') navLightbox(1);
        if (e.key === 'ArrowLeft') navLightbox(-1);
      }
    });

    // Promotion & Client Verification State
    var appliedPromo = null; // { code, discount_percent, description }
    var clientStatus = { exists: false, welcome_offer_used: false };

    function checkClientOfferEligibility(phone, email) {
      if (!phone && !email) return;
      fetch('api/clients.php?action=check_client&phone=' + encodeURIComponent(phone || '') + '&email=' + encodeURIComponent(email || ''))
        .then(r => r.json())
        .then(res => {
          if (res && res.ok) {
            clientStatus = res;
            var wBox = document.getElementById('resWelcomeBox');
            if (wBox) {
              if (res.welcome_offer_used) {
                wBox.innerHTML = '<div style="font-size:0.8rem; color:#D6AF5C; font-family:\'Poppins\',sans-serif; display:flex; align-items:center; gap:8px;">'
                  + '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#D6AF5C" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>'
                  + '<strong>Client Privilège reconnu (' + (res.name || 'Membre') + ')</strong></div>'
                  + '<div style="color:#8A9B97; font-size:0.75rem; margin-top:3px; padding-left:24px;">Offre de bienvenue déjà utilisée sur votre 1ère réservation. Vous pouvez appliquer un code promotionnel ci-dessous pour ce séjour.</div>';
              }
            }
          }
        })
        .catch(e => {});
    }

    var phoneInput = document.getElementById('resPhone');
    if (phoneInput) {
      phoneInput.addEventListener('blur', function() {
        checkClientOfferEligibility(this.value.trim(), (document.getElementById('resEmail') || {}).value || '');
      });
    }

    window.applyPromoCode = function() {
      var codeIn = document.getElementById('resPromoCodeInput');
      var msgEl = document.getElementById('resPromoMessage');
      if (!codeIn || !msgEl) return;
      var val = codeIn.value.trim().toUpperCase();
      if (!val) {
        msgEl.style.display = 'block';
        msgEl.style.color = '#ff9999';
        msgEl.textContent = 'Veuillez saisir un code promo.';
        return;
      }
      fetch('api/clients.php?action=check_promo&code=' + encodeURIComponent(val))
        .then(r => r.json())
        .then(data => {
          msgEl.style.display = 'block';
          if (data && data.ok) {
            appliedPromo = data;
            msgEl.style.color = '#74E291';
            msgEl.textContent = 'Code ' + data.code + ' activé : -' + data.discount_percent + '% (' + data.description + ')';
          } else {
            appliedPromo = null;
            msgEl.style.color = '#ff9999';
            msgEl.textContent = data.error || 'Code promo non valide';
          }
        })
        .catch(err => {
          msgEl.style.display = 'block';
          msgEl.style.color = '#ff9999';
          msgEl.textContent = 'Erreur de vérification du code';
        });
    };

    var resForm = document.getElementById('reservationForm');
    if (resForm) {
      resForm.addEventListener('submit', function(e) {
        e.preventDefault();
        var villa = document.getElementById('resVillaSlug').value;
        var firstName = document.getElementById('resFirstName').value.trim();
        var lastName = document.getElementById('resLastName').value.trim();
        var phone = document.getElementById('resPhone').value.trim();
        var email = document.getElementById('resEmail').value.trim();
        var startDate = document.getElementById('resStartDate').value;
        var endDate = document.getElementById('resEndDate').value;
        var guests = document.getElementById('resGuests').value;
        var chef = document.getElementById('resChef').value;
        
        var createAccountEl = document.getElementById('resCreateAccount');
        var optMarketingEl = document.getElementById('resOptMarketing');
        var createAccount = createAccountEl ? createAccountEl.checked : false;
        var optMarketing = optMarketingEl ? optMarketingEl.checked : false;
        
        var isFirstBooking = !clientStatus.welcome_offer_used;
        var welcomeDiscount = isFirstBooking && createAccount ? (optMarketing ? 2 : 1) : 0;
        var promoDiscount = appliedPromo ? appliedPromo.discount_percent : 0;
        var totalDiscountPct = Math.max(welcomeDiscount, promoDiscount);

        if (createAccount && isFirstBooking) {
          var clientPayload = {
            name: firstName + ' ' + lastName,
            phone: phone,
            email: email,
            marketing: optMarketing
          };
          fetch('api/clients.php?action=register', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(clientPayload)
          })
          .then(r => r.json())
          .then(cData => {
            if (cData && cData.client) {
              localStorage.setItem('baobab_client', JSON.stringify(cData.client));
              if (typeof window.bhaUpdateAccountUI === 'function') window.bhaUpdateAccountUI();
            }
          })
          .catch(e => {});
        }

        var villaName = document.getElementById('resVillaName').textContent;
        var villaPriceVal = parseFloat(document.getElementById('resVillaPriceValue').value) || 0;
        var nights = Math.max(1, Math.round((new Date(endDate) - new Date(startDate)) / 86400000) || 1);
        var totalBrut = villaPriceVal * nights;
        var discountAmt = totalDiscountPct > 0 ? Math.round(totalBrut * (totalDiscountPct / 100)) : 0;
        var totalNet = totalBrut - discountAmt;

        var formData = new FormData();
        formData.append('action', 'create_request');
        formData.append('villa', villa);
        formData.append('start', startDate);
        formData.append('end', endDate);
        formData.append('guests', guests);
        formData.append('chef', chef);
        formData.append('contact_method', 'whatsapp');
        formData.append('first_name', firstName);
        formData.append('last_name', lastName);
        formData.append('phone', phone);
        formData.append('email', email);
        formData.append('promo_code', appliedPromo ? appliedPromo.code : (welcomeDiscount > 0 ? 'BIENVENUE' : ''));
        formData.append('discount_percent', totalDiscountPct);
        formData.append('discount_amount', discountAmt);

        fetch('api/reservations.php', { method: 'POST', body: formData, credentials: 'same-origin' })
          .then(r => r.json())
          .then(data => {
            if (data && data.success) {
              var discountTxt = '';
              if (totalDiscountPct > 0) {
                var reason = appliedPromo ? ('Code Promo : ' + appliedPromo.code) : 'Offre 1ère Réservation';
                discountTxt = '\n• Remise Appliquée (-' + totalDiscountPct + '% — ' + reason + ') :\n'
                  + '  - Tarif de base : ' + totalBrut.toLocaleString('fr-FR') + ' FCFA (' + villaPriceVal.toLocaleString('fr-FR') + ' FCFA / nuit × ' + nights + ' nuits)\n'
                  + '  - Déduction (-' + totalDiscountPct + '%) : -' + discountAmt.toLocaleString('fr-FR') + ' FCFA\n'
                  + '  - TOTAL APRÈS REMISE : ' + totalNet.toLocaleString('fr-FR') + ' FCFA\n';
              } else if (clientStatus.welcome_offer_used) {
                discountTxt = '\n• Client Privilège Baobab Horizon (Offre 1ère réservation déjà validée antérieurement)\n';
              }

              var message = 'Bonjour Baobab Horizon,\n\n'
                + 'Je souhaite réserver la villa : ' + villaName + '\n\n'
                + 'Dates : ' + startDate + ' au ' + endDate + ' (' + nights + ' nuits)\n'
                + 'Voyageurs : ' + guests + '\n'
                + 'Option chef : ' + chef + '\n'
                + discountTxt + '\n'
                + 'Coordonnées :\n'
                + 'Nom : ' + firstName + ' ' + lastName + '\n'
                + 'Téléphone : ' + phone + '\n'
                + 'Email : ' + email + '\n\n'
                + 'Référence : ' + data.request_id;

              window.open('https://wa.me/221780140942?text=' + encodeURIComponent(message), '_blank');
              alert('Demande enregistrée avec succès !' + (totalDiscountPct > 0 ? ' (Remise de -' + totalDiscountPct + '% appliquée)' : '') + ' (Réf: ' + data.request_id + ')');
              closeReservationModal();
            } else {
              alert('Erreur : ' + (data.error || 'Impossible d\'enregistrer'));
            }
          })
          .catch(err => alert('Erreur de connexion : ' + err.message));
      });
    }

    // Scroll animation for intro-split section (V from left, writings from right) - triggers on both directions
    document.addEventListener("DOMContentLoaded", function() {
      const splitSection = document.querySelector(".intro-split");
      const bigLetter = document.querySelector(".intro-big-letter");
      const splitText = document.querySelector(".intro-split-text");
      
      if (splitSection && bigLetter && splitText) {
        const observer = new IntersectionObserver((entries) => {
          entries.forEach(entry => {
            if (entry.isIntersecting) {
              bigLetter.classList.add("revealed");
              splitText.classList.add("revealed");
            } else {
              // Reset state to slide-in again when scrolling back
              bigLetter.classList.remove("revealed");
              splitText.classList.remove("revealed");
            }
          });
        }, { threshold: 0.15 });
        
        observer.observe(splitSection);
      }
    });
  </script>

  <!-- WHATSAPP FLOATING WIDGET MULTI-AGENT -->
  <div class="bha-wa-widget" style="position:fixed !important; bottom:24px !important; right:24px !important; z-index:999999 !important;">
    <div class="bha-wa-popover" id="bhaWaPopover">
      <div style="font-size:.82rem;font-weight:600;color:#D6AF5C;margin-bottom:8px;display:flex;align-items:center;gap:6px">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#D6AF5C" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
        Contactez votre agent immobilier
      </div>
      <div class="bha-agent-card">
        <div>
          <div style="font-size:.85rem;font-weight:600;color:#fff">Dani</div>
          <div style="font-size:.68rem;color:#8A9B97">Associé - Co-fondateur</div>
        </div>
        <a href="https://wa.me/221780140942?text=Bonjour%20Dani,%20je%20souhaite%20des%20informations" target="_blank" style="padding:4px 9px;background:#25D366;color:#fff;font-size:.7rem;border-radius:3px;font-weight:600;text-decoration:none">WhatsApp</a>
      </div>
      <div class="bha-agent-card">
        <div>
          <div style="font-size:.85rem;font-weight:600;color:#fff">Mactar</div>
          <div style="font-size:.68rem;color:#8A9B97">Associé - Co-fondateur</div>
        </div>
        <a href="https://wa.me/221773371813?text=Bonjour%20Mactar,%20je%20souhaite%20des%20informations" target="_blank" style="padding:4px 9px;background:#25D366;color:#fff;font-size:.7rem;border-radius:3px;font-weight:600;text-decoration:none">WhatsApp</a>
      </div>
    </div>
    <button type="button" class="bha-wa-btn" onclick="document.getElementById('bhaWaPopover').classList.toggle('open')">
      <svg viewBox="0 0 24 24" class="wa-icon"><path d="M12.031 6.172c-3.181 0-5.767 2.586-5.768 5.766-.001 1.298.38 2.27 1.019 3.287l-.582 2.128 2.182-.573c.978.58 1.911.928 3.145.929 3.178 0 5.767-2.587 5.768-5.766.001-3.187-2.575-5.77-5.764-5.771zm3.392 8.244c-.144.405-.837.774-1.17.824-.299.045-.677.063-1.092-.069-.252-.08-.575-.187-.988-.365-1.739-.751-2.874-2.502-2.961-2.617-.087-.116-.708-.94-.708-1.793s.448-1.273.607-1.446c.159-.173.346-.217.462-.217l.332.006c.106.005.249-.04.39.298.144.347.491 1.2.534 1.287.043.087.072.188.014.304-.058.116-.087.188-.173.289l-.26.304c-.087.086-.177.18-.076.354.101.174.449.741.964 1.201.662.591 1.221.774 1.394.86s.275.072.376-.044c.101-.116.433-.506.549-.68.116-.173.231-.145.39-.087s1.011.477 1.184.564.289.13.332.202c.043.072.043.419-.101.824z"/></svg>
      <span>Contactez votre agent</span>
    </button>
  </div>
  <script src="js/decorations.js?v=5" defer></script>
</body>
</html>
