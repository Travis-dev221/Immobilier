<?php
session_start();

// ── CONFIG ────────────────────────────────────────────────
$adminConfig = require(__DIR__ . '/data/admin.secret.php');
define('ADMIN_PASSWORDS', $adminConfig['passwords'] ?? [$adminConfig['password'] ?? 'Baobab2026']);
define('DATA_DIR',  __DIR__ . '/data');
define('DATA_FILE', __DIR__ . '/data/properties.json');
define('IMAGES_DIR', __DIR__ . '/images');

// Créer les dossiers si nécessaire
if (!is_dir(DATA_DIR))   mkdir(DATA_DIR,   0755, true);
if (!is_dir(IMAGES_DIR)) mkdir(IMAGES_DIR, 0755, true);

// ── HELPERS ───────────────────────────────────────────────
function readData() {
    if (!file_exists(DATA_FILE)) {
        // Données par défaut si le fichier n'existe pas
        return [
            'guede'  => ['type'=>'vacances','name'=>'Villa Guédé Home','zone'=>'Nguerigne · Petite Côte','description'=>'Grande villa familiale avec pool house.','price'=>440000,'priceUnit'=>'FCFA · nuit','priceNote'=>'','bedrooms'=>7,'bathrooms'=>3,'persons'=>14,'area'=>'1 200','areaLabel'=>'m² hab.','tags'=>['Piscine','Pool house','3 suites','Clim.'],'images'=>['https://images.unsplash.com/photo-1613977257363-707ba9348227?w=1200&q=80','https://images.unsplash.com/photo-1512917774080-9991f1c4c750?w=1200&q=80','https://images.unsplash.com/photo-1600596542815-ffad4c1539a9?w=1200&q=80','https://images.unsplash.com/photo-1600585154340-be6161a56a0c?w=1200&q=80','https://images.unsplash.com/photo-1571896349842-33c89424de2d?w=1200&q=80','https://images.unsplash.com/photo-1618221195710-dd6b41faaea6?w=1200&q=80']],
            'casa'   => ['type'=>'vacances','name'=>'Villa Casa','zone'=>'Nguerigne · Petite Côte','description'=>'Villa conviviale avec piscine privée.','price'=>330000,'priceUnit'=>'FCFA · nuit','priceNote'=>'','bedrooms'=>6,'bathrooms'=>4,'persons'=>12,'area'=>'220','areaLabel'=>'m²','tags'=>['Piscine privée','Chef','Plages proches'],'images'=>['https://images.unsplash.com/photo-1570129477492-45c003edd2be?w=1200&q=80','https://images.unsplash.com/photo-1600047509807-ba8f99d2cdde?w=1200&q=80','https://images.unsplash.com/photo-1600607687939-ce8a6c25118c?w=1200&q=80','https://images.unsplash.com/photo-1505693416388-ac5ce068fe85?w=1200&q=80','https://images.unsplash.com/photo-1560448204-603b3fc33ddc?w=1200&q=80','https://images.unsplash.com/photo-1584622650111-993a426fbf0a?w=1200&q=80']],
            'palm'   => ['type'=>'vacances','name'=>'Villa Palm Évasion','zone'=>'Nguerigne · 50m plage','description'=>'Adresse complète proche de la mer.','price'=>400000,'priceUnit'=>'FCFA · nuit · hors électricité','priceNote'=>'','bedrooms'=>8,'bathrooms'=>5,'persons'=>16,'area'=>'50m','areaLabel'=>'Plage','tags'=>['Jacuzzi','Sauna','Sport','Cinéma'],'images'=>['https://images.unsplash.com/photo-1499793983690-e29da59ef1c2?w=1200&q=80','https://images.unsplash.com/photo-1566073771259-6a8506099945?w=1200&q=80','https://images.unsplash.com/photo-1540518614846-7eded433c457?w=1200&q=80','https://images.unsplash.com/photo-1602343168117-bb8ffe3e2e9f?w=1200&q=80','https://images.unsplash.com/photo-1615460549969-36fa19521a4f?w=1200&q=80','https://images.unsplash.com/photo-1534430480872-3498386e7856?w=1200&q=80']],
            'torino' => ['type'=>'vacances','name'=>'Villa Torino','zone'=>'Somone · 300m plage','description'=>'Villa entre plage et lagon.','price'=>340000,'priceUnit'=>'FCFA · nuit','priceNote'=>'','bedrooms'=>6,'bathrooms'=>3,'persons'=>12,'area'=>'500m','areaLabel'=>'Lagon','tags'=>['Piscine','Chef','Ménage'],'images'=>['https://images.unsplash.com/photo-1523217582562-09d0def993a6?w=1200&q=80','https://images.unsplash.com/photo-1506126613408-eca07ce68773?w=1200&q=80','https://images.unsplash.com/photo-1510798831971-661eb04b3739?w=1200&q=80','https://images.unsplash.com/photo-1576013551627-0cc20b96c2a7?w=1200&q=80','https://images.unsplash.com/photo-1600210491369-e753d80a41f3?w=1200&q=80','https://images.unsplash.com/photo-1558618666-fcd25c85cd64?w=1200&q=80']],
            'vhouse' => ['type'=>'vacances','name'=>'V-House','zone'=>'Nguérigne · Famille','description'=>'Adresse familiale avec personnel inclus.','price'=>340000,'priceUnit'=>'FCFA · nuit','priceNote'=>'','bedrooms'=>5,'bathrooms'=>3,'persons'=>10,'area'=>'Parc','areaLabel'=>'Inclus','tags'=>['Personnel','Chef privé','Verdure'],'images'=>['https://images.unsplash.com/photo-1585032226651-759b368d7246?w=1200&q=80','https://images.unsplash.com/photo-1600566752355-35792bedcfea?w=1200&q=80','https://images.unsplash.com/photo-1600566752734-a1b4de0c2ad5?w=1200&q=80','https://images.unsplash.com/photo-1555041469-a586c61ea9bc?w=1200&q=80','https://images.unsplash.com/photo-1598928506311-c55ded91a20c?w=1200&q=80','https://images.unsplash.com/photo-1583847268964-b28dc8f51f92?w=1200&q=80']],
        ];
    }
    $d = json_decode(file_get_contents(DATA_FILE), true);
    return is_array($d) ? $d : [];
}

function writeData($data) {
    // Créer data/ si besoin
    if (!is_dir(DATA_DIR)) mkdir(DATA_DIR, 0755, true);
    // Protéger data/ avec .htaccess
    $htaccess = DATA_DIR . '/.htaccess';
    if (!file_exists($htaccess)) {
        file_put_contents($htaccess, "Options -Indexes\n<Files \"*.php\">\nDeny from all\n</Files>\n");
    }
    $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    file_put_contents(DATA_FILE, $json);
}

function h($s) { return htmlspecialchars($s ?? '', ENT_QUOTES); }

// ── ACTIONS POST ──────────────────────────────────────────
$msg = '';
$msgType = 'ok';

// Login
if (isset($_POST['action']) && $_POST['action'] === 'login') {
    if (in_array(trim($_POST['password'] ?? ''), ADMIN_PASSWORDS)) {
        $_SESSION['admin'] = true;
    } else {
        $msg = 'Mot de passe incorrect.';
        $msgType = 'err';
    }
}

// Logout
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: admin.php');
    exit;
}

$logged = !empty($_SESSION['admin']);

if ($logged) {
    $data = readData();

    // Enregistrer un bien (nouveau ou modif)
    if (isset($_POST['action']) && $_POST['action'] === 'save') {
        $key = preg_replace('/[^a-z0-9_-]/', '', strtolower(trim($_POST['key'] ?? '')));
        $oldKey = trim($_POST['old_key'] ?? '');
        if ($key && !empty($_POST['name'])) {
            // Si changement de clé, supprimer l'ancienne
            if ($oldKey && $oldKey !== $key && isset($data[$oldKey])) {
                $images = $data[$oldKey]['images'] ?? [];
                unset($data[$oldKey]);
            } else {
                $images = $data[$key]['images'] ?? [];
            }
            $tags = array_filter(array_map('trim', explode(',', $_POST['tags'] ?? '')));
            $data[$key] = [
                'type'        => in_array($_POST['type'] ?? '', ['vacances','vente','terrain']) ? $_POST['type'] : 'vacances',
                'name'        => trim($_POST['name']),
                'zone'        => trim($_POST['zone'] ?? ''),
                'description' => trim($_POST['description'] ?? ''),
                'price'       => intval($_POST['price'] ?? 0),
                'priceUnit'   => trim($_POST['priceUnit'] ?? ''),
                'priceNote'   => trim($_POST['priceNote'] ?? ''),
                'bedrooms'    => intval($_POST['bedrooms'] ?? 0),
                'bathrooms'   => intval($_POST['bathrooms'] ?? 0),
                'persons'     => intval($_POST['persons'] ?? 0),
                'area'        => trim($_POST['area'] ?? ''),
                'areaLabel'   => trim($_POST['areaLabel'] ?? ''),
                'tags'        => array_values($tags),
                'images'      => $images,
            ];
            writeData($data);
            $msg = '✅ Bien "' . h($data[$key]['name']) . '" enregistré.';
        } else {
            $msg = 'Nom et clé requis.';
            $msgType = 'err';
        }
    }

    // Supprimer un bien
    if (isset($_POST['action']) && $_POST['action'] === 'delete') {
        $key = trim($_POST['key'] ?? '');
        if ($key && isset($data[$key])) {
            $name = $data[$key]['name'];
            unset($data[$key]);
            writeData($data);
            $msg = '🗑 Bien "' . h($name) . '" supprimé.';
        }
    }

    // Upload photo
    if (isset($_POST['action']) && $_POST['action'] === 'upload') {
        $key = preg_replace('/[^a-z0-9_-]/', '', strtolower(trim($_POST['key'] ?? '')));
        if ($key && isset($data[$key]) && isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['photo'];
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);
            $map = ['image/jpeg'=>'jpg','image/png'=>'jpg','image/webp'=>'jpg'];
            if (isset($map[$mime]) && $file['size'] <= 8*1024*1024) {
                $dir = IMAGES_DIR . '/' . $key;
                if (!is_dir($dir)) mkdir($dir, 0755, true);
                $name = date('Ymd-His') . '-' . bin2hex(random_bytes(3)) . '.jpg';
                $dest = $dir . '/' . $name;
                // Redimensionner 1200×800 crop centré
                $TW = 1200; $TH = 800;
                switch ($mime) {
                    case 'image/jpeg': $src = imagecreatefromjpeg($file['tmp_name']); break;
                    case 'image/png':  $src = imagecreatefrompng($file['tmp_name']); break;
                    case 'image/webp': $src = imagecreatefromwebp($file['tmp_name']); break;
                    default: $src = false;
                }
                if ($src) {
                    $ow = imagesx($src); $oh = imagesy($src);
                    $r = max($TW/$ow, $TH/$oh);
                    $sx = (int)round(($ow - $TW/$r)/2);
                    $sy = (int)round(($oh - $TH/$r)/2);
                    $sw = (int)round($TW/$r); $sh = (int)round($TH/$r);
                    $dst = imagecreatetruecolor($TW, $TH);
                    imagefill($dst, 0, 0, imagecolorallocate($dst, 255, 255, 255));
                    imagecopyresampled($dst, $src, 0, 0, $sx, $sy, $TW, $TH, $sw, $sh);
                    imagejpeg($dst, $dest, 85);
                    imagedestroy($src); imagedestroy($dst);
                } else {
                    move_uploaded_file($file['tmp_name'], $dest);
                }
                $category = $_POST['category'] ?? 'exterior';
                if (!isset($data[$key]['images']) || !is_array($data[$key]['images'])) {
                    $data[$key]['images'] = ['exterior'=>[], 'interior'=>[], 'bedrooms'=>[]];
                }
                if (is_array($data[$key]['images']) && !isset($data[$key]['images']['exterior'])) {
                    $data[$key]['images'] = ['exterior'=>$data[$key]['images'], 'interior'=>[], 'bedrooms'=>[]];
                }
                $data[$key]['images'][$category][] = 'images/' . $key . '/' . $name;
                writeData($data);
                $msg = '✅ Photo ajoutée dans la catégorie "' . ($category === 'exterior' ? 'Extérieur' : ($category === 'interior' ? 'Intérieur' : 'Chambres')) . '".';
            } else {
                $msg = 'Format invalide ou fichier trop lourd (max 8 Mo).';
                $msgType = 'err';
            }
        }
    }

    // Ajouter URL image
    if (isset($_POST['action']) && $_POST['action'] === 'add_url') {
        $key = preg_replace('/[^a-z0-9_-]/', '', strtolower(trim($_POST['key'] ?? '')));
        $url = trim($_POST['url'] ?? '');
        $category = $_POST['category'] ?? 'exterior';
        if ($key && isset($data[$key]) && $url) {
            if (!isset($data[$key]['images']) || !is_array($data[$key]['images'])) {
                $data[$key]['images'] = ['exterior'=>[], 'interior'=>[], 'bedrooms'=>[]];
            }
            if (is_array($data[$key]['images']) && !isset($data[$key]['images']['exterior'])) {
                $data[$key]['images'] = ['exterior'=>$data[$key]['images'], 'interior'=>[], 'bedrooms'=>[]];
            }
            $data[$key]['images'][$category][] = $url;
            writeData($data);
            $msg = '✅ URL ajoutée dans la catégorie "' . ($category === 'exterior' ? 'Extérieur' : ($category === 'interior' ? 'Intérieur' : 'Chambres')) . '".';
        }
    }

    // Supprimer photo
    if (isset($_POST['action']) && $_POST['action'] === 'del_photo') {
        $key = preg_replace('/[^a-z0-9_-]/', '', strtolower(trim($_POST['key'] ?? '')));
        $category = $_POST['category'] ?? 'exterior';
        $idx = intval($_POST['idx'] ?? -1);
        if ($key && isset($data[$key]['images'][$category][$idx])) {
            $url = $data[$key]['images'][$category][$idx];
            if (strpos($url, 'images/') === 0) {
                $path = __DIR__ . '/' . $url;
                if (file_exists($path)) @unlink($path);
            }
            array_splice($data[$key]['images'][$category], $idx, 1);
            writeData($data);
            $msg = '✅ Photo supprimée.';
        }
    }

    // Monter photo
    if (isset($_POST['action']) && $_POST['action'] === 'move_photo') {
        $key = preg_replace('/[^a-z0-9_-]/', '', strtolower(trim($_POST['key'] ?? '')));
        $category = $_POST['category'] ?? 'exterior';
        $idx = intval($_POST['idx'] ?? -1);
        $dir2 = $_POST['dir'] ?? '';
        if ($key && isset($data[$key]['images'][$category])) {
            $imgs = &$data[$key]['images'][$category];
            if ($dir2 === 'up' && $idx > 0) {
                [$imgs[$idx-1], $imgs[$idx]] = [$imgs[$idx], $imgs[$idx-1]];
                writeData($data);
            } elseif ($dir2 === 'dn' && $idx < count($imgs)-1) {
                [$imgs[$idx+1], $imgs[$idx]] = [$imgs[$idx], $imgs[$idx+1]];
                writeData($data);
            }
        }
    }

    // Reload data after writes
    $data = readData();
}

// Page en cours
$page = $_GET['p'] ?? 'list';
$editKey = $_GET['k'] ?? '';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Admin — Baobab Horizon</title>
<style>
*{box-sizing:border-box;margin:0;padding:0}
:root{--gold:#b8935a;--glight:#d4ab72;--night:#0f0d0a;--nmid:#1a1612;--nsoft:#2a2520;--cream:#faf7f2;--sand:#e8dcc8;--muted:#8a7d6a;--danger:#c45c5c;--ok:#5a9e6f}
body{font-family:'Jost',system-ui,sans-serif;background:var(--night);color:var(--sand);min-height:100vh}
a{color:var(--glight);text-decoration:none}
a:hover{color:var(--cream)}
.wrap{max-width:1000px;margin:0 auto;padding:28px 18px 80px}
/* Header */
.hdr{display:flex;align-items:center;justify-content:space-between;margin-bottom:28px;flex-wrap:wrap;gap:12px}
.logo{font-family:Georgia,serif;font-size:1.3rem;letter-spacing:.15em;text-transform:uppercase;color:var(--cream)}
.logo span{color:var(--gold)}
/* Nav */
.nav{display:flex;gap:4px;margin-bottom:24px;flex-wrap:wrap}
.nav a{font-size:.65rem;letter-spacing:.18em;text-transform:uppercase;padding:8px 16px;border:1px solid rgba(184,147,90,.25);color:var(--muted)}
.nav a:hover{border-color:var(--gold);color:var(--gold)}
.nav a.on{border-color:var(--gold);background:rgba(184,147,90,.1);color:var(--gold)}
/* Card */
.card{background:var(--nmid);border:1px solid rgba(184,147,90,.18);padding:24px;margin-bottom:18px}
.card-title{font-family:Georgia,serif;font-size:1.4rem;font-weight:400;color:var(--cream);margin-bottom:16px}
/* Msg */
.msg{padding:10px 14px;font-size:.84rem;margin-bottom:16px;border-radius:1px}
.msg.ok{background:rgba(90,158,111,.12);border:1px solid rgba(90,158,111,.35);color:#b8e0c4}
.msg.err{background:rgba(196,92,92,.12);border:1px solid rgba(196,92,92,.35);color:#f0b0b0}
/* Forms */
.fg{display:flex;flex-direction:column;gap:6px;margin-bottom:14px}
label.lbl{font-size:.65rem;letter-spacing:.14em;text-transform:uppercase;color:var(--muted)}
input[type=text],input[type=number],input[type=password],input[type=url],select,textarea{
  width:100%;padding:10px 12px;background:var(--nsoft);border:1px solid rgba(184,147,90,.22);
  color:var(--cream);font:inherit;font-size:.88rem}
input:focus,select:focus,textarea:focus{outline:none;border-color:var(--gold)}
textarea{min-height:90px;resize:vertical}
.grid2{display:grid;grid-template-columns:1fr 1fr;gap:14px}
.grid3{display:grid;grid-template-columns:1fr 1fr 1fr;gap:14px}
/* Buttons */
.btn{display:inline-flex;align-items:center;gap:6px;padding:10px 18px;border:1px solid var(--gold);background:var(--gold);color:var(--night);font:inherit;font-size:.72rem;letter-spacing:.08em;text-transform:uppercase;cursor:pointer}
.btn:hover{background:var(--glight)}
.btn-g{background:transparent;color:var(--sand);border-color:rgba(184,147,90,.4)}
.btn-g:hover{background:rgba(184,147,90,.1);color:var(--cream)}
.btn-d{background:transparent;border-color:var(--danger);color:var(--danger)}
.btn-d:hover{background:rgba(196,92,92,.1)}
.btn-sm{padding:6px 10px;font-size:.65rem}
/* Table biens */
.bien-list{display:flex;flex-direction:column;gap:3px}
.bien-row{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:12px 14px;background:var(--nsoft);border:1px solid rgba(184,147,90,.12);flex-wrap:wrap}
.bien-name{font-size:.92rem;color:var(--cream)}
.bien-meta{font-size:.65rem;color:var(--muted);margin-top:2px}
.badge{font-size:.52rem;letter-spacing:.14em;text-transform:uppercase;padding:2px 8px;border-radius:2px}
.bv{background:rgba(90,158,111,.18);color:#7fcf9a}
.bs{background:rgba(184,147,90,.18);color:var(--glight)}
.bt{background:rgba(138,125,106,.18);color:var(--sand)}
/* Photos */
.pgrid{display:grid;grid-template-columns:repeat(auto-fill,minmax(140px,1fr));gap:12px;margin-top:16px}
.pcard{position:relative;aspect-ratio:4/3;background:var(--nsoft);border:1px solid rgba(184,147,90,.15);overflow:hidden}
.pcard img{width:100%;height:100%;object-fit:cover;display:block}
.pcard-n{position:absolute;top:5px;left:5px;background:rgba(0,0,0,.65);color:#fff;font-size:.55rem;padding:2px 6px}
.pcard-bar{position:absolute;bottom:0;left:0;right:0;display:flex;gap:3px;padding:5px;background:rgba(0,0,0,.8)}
/* Misc */
.sep{height:1px;background:rgba(184,147,90,.12);margin:20px 0}
small{font-size:.65rem;color:var(--muted)}
@media(max-width:600px){.grid2,.grid3{grid-template-columns:1fr}.bien-row{flex-direction:column;align-items:flex-start}.bien-row>div:first-child{width:100%;margin-bottom:10px}.bien-row>div:last-child{width:100%;display:flex;gap:8px;flex-wrap:wrap}.bien-row .btn{flex:1;justify-content:center;min-width:80px}}}
</style>
</head>
<body>
<div class="wrap">

<div class="hdr">
  <a href="index.html" target="_blank" style="display:inline-block;line-height:0">
    <img src="logoo.jpg" alt="Baobab Horizon" style="height:54px;width:auto;display:block">
  </a>
  <div style="display:flex;gap:10px;align-items:center">
    <a href="index.html" class="btn btn-g btn-sm" target="_blank">← Voir le site</a>
    <?php if ($logged): ?>
    <a href="admin.php?logout=1" class="btn btn-g btn-sm">Déconnexion</a>
    <?php endif; ?>
  </div>
</div>

<?php if (!empty($msg)): ?>
<div class="msg <?= $msgType ?>"><?= $msg ?></div>
<?php endif; ?>

<?php if (!$logged): ?>
<!-- ═══ LOGIN ═══════════════════════════════════════════ -->
<div class="card" style="max-width:420px">
  <div class="card-title">Connexion</div>
  <form method="POST" action="admin.php">
    <input type="hidden" name="action" value="login">
    <div class="fg">
      <label class="lbl">Mot de passe</label>
      <input type="password" name="password" placeholder="Mot de passe admin" required autofocus>
    </div>
    <button type="submit" class="btn">Se connecter</button>
  </form>
</div>

<?php else: ?>
<!-- ═══ NAV ADMIN ════════════════════════════════════════ -->
<div class="nav">
  <a href="admin.php?p=list" class="<?= $page==='list'?'on':'' ?>">Liste des biens</a>
  <a href="admin.php?p=reservations" class="<?= $page==='reservations'?'on':'' ?>">📅 Réservations</a>
</div>

<?php if ($page === 'list'): ?>
<!-- ═══ LISTE DES BIENS ══════════════════════════════════ -->
<div class="card" style="background:rgba(184,147,90,.06);border-color:rgba(184,147,90,.3);margin-bottom:12px;padding:16px 20px">
  <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px">
    <div style="font-size:.82rem;color:var(--sand)">
      ✅ Les modifications sont <strong>instantanément visibles sur le site</strong> après enregistrement.
    </div>
    <a href="index.html" target="_blank" class="btn btn-g btn-sm">🌐 Voir le site</a>
  </div>
</div>
<div class="card">
  <div class="card-title">Mes biens (<?= count($data) ?>)</div>
  <?php if (empty($data)): ?>
    <p style="color:var(--muted)">Aucun bien.</p>
  <?php else: ?>
  <div class="bien-list">
    <?php foreach ($data as $k => $v): ?>
    <?php
      $bc = $v['type']==='vacances'?'bv':($v['type']==='vente'?'bs':'bt');
      $prix = $v['price'] ? number_format($v['price'],0,',',' ').' '.($v['priceUnit']??'') : 'Sur demande';
    ?>
    <div class="bien-row">
      <div>
        <div class="bien-name"><?= h($v['name']) ?></div>
        <div class="bien-meta">
          <span class="badge <?= $bc ?>"><?= h($v['type']) ?></span>
          &nbsp;<?= h($v['zone']??'') ?>
          &nbsp;·&nbsp;<?= $prix ?>
          &nbsp;·&nbsp;<?= count($v['images']??[]) ?> photo(s)
        </div>
      </div>
      <div style="display:flex;gap:6px;flex-wrap:wrap">
        <a href="admin.php?p=edit&k=<?= urlencode($k) ?>" class="btn btn-g btn-sm">Modifier</a>
        <a href="admin.php?p=edit&k=<?= urlencode($k) ?>#photos" class="btn btn-g btn-sm">Photos</a>
        <form method="POST" action="admin.php?p=list" style="display:inline" onsubmit="return confirm('Supprimer <?= h($v['name']) ?> ?')">
          <input type="hidden" name="action" value="delete">
          <input type="hidden" name="key" value="<?= h($k) ?>">
          <button type="submit" class="btn btn-d btn-sm">Supprimer</button>
        </form>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</div>

<?php elseif ($page === 'edit' || $page === 'new'): ?>
<?php
$isNew = ($page === 'new');
$k = $isNew ? '' : $editKey;
$v = (!$isNew && isset($data[$k])) ? $data[$k] : [
    'type'=>'vacances','name'=>'','zone'=>'','description'=>'',
    'price'=>0,'priceUnit'=>'FCFA · nuit','priceNote'=>'',
    'bedrooms'=>0,'bathrooms'=>0,'persons'=>0,'area'=>'','areaLabel'=>'',
    'tags'=>[],'images'=>[]
];
?>
<!-- ═══ FORMULAIRE BIEN ══════════════════════════════════ -->
<div class="card">
  <div class="card-title"><?= h($v['name']) ?></div>
  <form method="POST" action="admin.php?p=<?= $isNew?'new':'edit' ?>&k=<?= urlencode($k) ?>">
    <input type="hidden" name="action" value="save">
    <input type="hidden" name="old_key" value="<?= h($k) ?>">

    <div class="sep" style="margin-top:0"></div>
    <p style="font-size:.7rem;letter-spacing:.2em;text-transform:uppercase;color:var(--gold);margin-bottom:16px">Informations générales</p>

    <div class="grid2">
      <div class="fg">
        <label class="lbl">Type de bien</label>
        <select name="type">
          <option value="vacances" <?= ($v['type']??'')==='vacances'?'selected':'' ?>>🏖 Location vacances</option>
          <option value="vente"    <?= ($v['type']??'')==='vente'?'selected':'' ?>>🏠 Villa à vendre</option>
          <option value="terrain"  <?= ($v['type']??'')==='terrain'?'selected':'' ?>>🌿 Terrain à vendre</option>
        </select>
      </div>
      <div class="fg">
        <label class="lbl">Clé technique (unique, minuscules-tirets)</label>
        <input type="text" name="key" value="<?= h($k) ?>" placeholder="Ex: villa-ocean" required pattern="[a-z0-9_-]+">
        <small>Ne pas modifier la clé d'un bien existant</small>
      </div>
    </div>

    <div class="fg">
      <label class="lbl">Nom du bien</label>
      <input type="text" name="name" value="<?= h($v['name']) ?>" placeholder="Ex: Villa Guédé Home" required>
    </div>
    <div class="fg">
      <label class="lbl">Zone / Localisation</label>
      <input type="text" name="zone" value="<?= h($v['zone']??'') ?>" placeholder="Ex: Nguerigne · Petite Côte · 300m plage">
    </div>
    <div class="fg">
      <label class="lbl">Description complète</label>
      <textarea name="description" placeholder="Décrivez le bien : emplacement, prestations, points forts..."><?= h($v['description']??'') ?></textarea>
    </div>

    <div class="sep"></div>
    <p style="font-size:.7rem;letter-spacing:.2em;text-transform:uppercase;color:var(--gold);margin-bottom:16px">Prix</p>

    <div class="grid3">
      <div class="fg">
        <label class="lbl">Montant (0 = Sur demande)</label>
        <input type="number" name="price" value="<?= intval($v['price']??0) ?>" min="0">
      </div>
      <div class="fg">
        <label class="lbl">Unité de prix</label>
        <input type="text" name="priceUnit" value="<?= h($v['priceUnit']??'') ?>" placeholder="FCFA · nuit">
      </div>
      <div class="fg">
        <label class="lbl">Note prix (optionnel)</label>
        <input type="text" name="priceNote" value="<?= h($v['priceNote']??'') ?>" placeholder="Ex: Visite sur RDV">
      </div>
    </div>

    <div class="sep"></div>
    <p style="font-size:.7rem;letter-spacing:.2em;text-transform:uppercase;color:var(--gold);margin-bottom:16px">Caractéristiques</p>

    <div class="grid3">
      <div class="fg">
        <label class="lbl">Chambres (0 = masqué)</label>
        <input type="number" name="bedrooms" value="<?= intval($v['bedrooms']??0) ?>" min="0">
      </div>
      <div class="fg">
        <label class="lbl">Salles de bain (0 = masqué)</label>
        <input type="number" name="bathrooms" value="<?= intval($v['bathrooms']??0) ?>" min="0">
      </div>
      <div class="fg">
        <label class="lbl">Personnes max (0 = masqué)</label>
        <input type="number" name="persons" value="<?= intval($v['persons']??0) ?>" min="0">
      </div>
    </div>
    <div class="grid2">
      <div class="fg">
        <label class="lbl">Surface (valeur)</label>
        <input type="text" name="area" value="<?= h($v['area']??'') ?>" placeholder="Ex: 1 200">
      </div>
      <div class="fg">
        <label class="lbl">Surface (label)</label>
        <input type="text" name="areaLabel" value="<?= h($v['areaLabel']??'') ?>" placeholder="Ex: m² hab.">
      </div>
    </div>

    <div class="sep"></div>
    <p style="font-size:.7rem;letter-spacing:.2em;text-transform:uppercase;color:var(--gold);margin-bottom:16px">Équipements / Tags</p>
    <div class="fg">
      <label class="lbl">Tags séparés par virgule</label>
      <input type="text" name="tags" value="<?= h(implode(', ', $v['tags']??[])) ?>" placeholder="Piscine, Pool house, Chef, Climatisation, Jacuzzi...">
    </div>

    <div class="sep"></div>
    <div style="display:flex;gap:10px;flex-wrap:wrap">
      <button type="submit" class="btn">💾 Enregistrer</button>
    </div>
  </form>
</div>

<?php elseif ($page === 'photos'): ?>
<?php
$k = $editKey;
$v = $data[$k] ?? null;
?>
<!-- ═══ PHOTOS ════════════════════════════════════════════ -->
<?php if (!$v): ?>
<div class="card"><p style="color:var(--muted)">Bien introuvable. <a href="admin.php?p=list">← Retour</a></p></div>
<?php else: ?>
<div class="card">
  <div class="card-title" style="margin:0">Photos · <?= h($v['name']) ?></div>
</div>

  <!-- Upload fichier -->
  <p style="font-size:.7rem;letter-spacing:.2em;text-transform:uppercase;color:var(--gold);margin-bottom:12px">Uploader des photos</p>
  <form method="POST" action="admin.php?p=photos&k=<?= urlencode($k) ?>" enctype="multipart/form-data">
    <input type="hidden" name="action" value="upload">
    <input type="hidden" name="key" value="<?= h($k) ?>">
    <div style="display:flex;gap:10px;align-items:flex-end;flex-wrap:wrap">
      <div class="fg" style="flex:1;min-width:150px;margin:0">
        <label class="lbl">Catégorie</label>
        <select name="category" required style="padding:8px;width:100%">
          <option value="exterior">Extérieur</option>
          <option value="interior">Intérieur</option>
          <option value="bedrooms">Chambres</option>
        </select>
      </div>
      <div class="fg" style="flex:1;min-width:200px;margin:0">
        <label class="lbl">Sélectionner une photo (JPG, PNG, WEBP — max 8 Mo)</label>
        <input type="file" name="photo" accept="image/jpeg,image/png,image/webp" required style="padding:8px">
      </div>
      <button type="submit" class="btn">Uploader</button>
    </div>
  </form>

  <div class="sep"></div>

  <!-- Ajouter URL -->
  <p style="font-size:.7rem;letter-spacing:.2em;text-transform:uppercase;color:var(--gold);margin-bottom:12px">Ou ajouter une URL</p>
  <form method="POST" action="admin.php?p=photos&k=<?= urlencode($k) ?>">
    <input type="hidden" name="action" value="add_url">
    <input type="hidden" name="key" value="<?= h($k) ?>">
    <div style="display:flex;gap:10px;align-items:flex-end;flex-wrap:wrap">
      <div class="fg" style="flex:1;min-width:150px;margin:0">
        <label class="lbl">Catégorie</label>
        <select name="category" required style="padding:8px;width:100%">
          <option value="exterior">Extérieur</option>
          <option value="interior">Intérieur</option>
          <option value="bedrooms">Chambres</option>
        </select>
      </div>
      <div class="fg" style="flex:1;min-width:200px;margin:0">
        <input type="url" name="url" placeholder="https://..." required>
      </div>
      <button type="submit" class="btn btn-g">Ajouter</button>
    </div>
  </form>

  <div class="sep"></div>

  <!-- Grille photos -->
  <?php 
  $categoryLabels = ['exterior' => 'Extérieur', 'interior' => 'Intérieur', 'bedrooms' => 'Chambres'];
  $hasCategorizedImages = is_array($v['images']) && (isset($v['images']['exterior']) || isset($v['images']['interior']) || isset($v['images']['bedrooms']));
  if ($hasCategorizedImages): ?>
    <?php foreach (['exterior', 'interior', 'bedrooms'] as $cat): ?>
      <?php if (!empty($v['images'][$cat])): ?>
        <p style="font-size:.7rem;letter-spacing:.2em;text-transform:uppercase;color:var(--gold);margin-bottom:12px;margin-top:24px"><?= $categoryLabels[$cat] ?> (<?= count($v['images'][$cat]) ?>)</p>
        <div class="pgrid">
          <?php foreach ($v['images'][$cat] as $i => $url): ?>
          <div class="pcard">
            <span class="pcard-n"><?= $i+1 ?></span>
            <?php $src = strpos($url,'http')===0 ? $url : '../'.$url; ?>
            <img src="<?= h($src) ?>" alt="<?= $categoryLabels[$cat] ?> <?= $i+1 ?>" loading="lazy" onerror="this.style.opacity='.3'">
            <div class="pcard-bar">
              <?php if ($i > 0): ?>
              <form method="POST" action="admin.php?p=photos&k=<?= urlencode($k) ?>" style="margin:0">
                <input type="hidden" name="action" value="move_photo">
                <input type="hidden" name="key" value="<?= h($k) ?>">
                <input type="hidden" name="category" value="<?= $cat ?>">
                <input type="hidden" name="idx" value="<?= $i ?>">
                <input type="hidden" name="dir" value="up">
                <button type="submit" class="btn btn-g btn-sm" title="Monter">↑</button>
              </form>
              <?php endif; ?>
              <?php if ($i < count($v['images'][$cat])-1): ?>
              <form method="POST" action="admin.php?p=photos&k=<?= urlencode($k) ?>" style="margin:0">
                <input type="hidden" name="action" value="move_photo">
                <input type="hidden" name="key" value="<?= h($k) ?>">
                <input type="hidden" name="category" value="<?= $cat ?>">
                <input type="hidden" name="idx" value="<?= $i ?>">
                <input type="hidden" name="dir" value="dn">
                <button type="submit" class="btn btn-g btn-sm" title="Descendre">↓</button>
              </form>
              <?php endif; ?>
              <form method="POST" action="admin.php?p=photos&k=<?= urlencode($k) ?>" style="margin:0" onsubmit="return confirm('Supprimer ?')">
                <input type="hidden" name="action" value="del_photo">
                <input type="hidden" name="key" value="<?= h($k) ?>">
                <input type="hidden" name="category" value="<?= $cat ?>">
                <input type="hidden" name="idx" value="<?= $i ?>">
                <button type="submit" class="btn btn-d btn-sm">✕</button>
              </form>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    <?php endforeach; ?>
  <?php elseif (!empty($v['images'])): ?>
    <p style="font-size:.7rem;letter-spacing:.2em;text-transform:uppercase;color:var(--gold);margin-bottom:12px">Photos actuelles (<?= count($v['images']) ?>)</p>
    <div class="pgrid">
      <?php foreach ($v['images'] as $i => $url): ?>
      <div class="pcard">
        <span class="pcard-n"><?= $i+1 ?></span>
        <?php $src = strpos($url,'http')===0 ? $url : '../'.$url; ?>
        <img src="<?= h($src) ?>" alt="Photo <?= $i+1 ?>" loading="lazy" onerror="this.style.opacity='.3'">
        <div class="pcard-bar">
          <?php if ($i > 0): ?>
          <form method="POST" action="admin.php?p=photos&k=<?= urlencode($k) ?>" style="margin:0">
            <input type="hidden" name="action" value="move_photo">
            <input type="hidden" name="key" value="<?= h($k) ?>">
            <input type="hidden" name="category" value="exterior">
            <input type="hidden" name="idx" value="<?= $i ?>">
            <input type="hidden" name="dir" value="up">
            <button type="submit" class="btn btn-g btn-sm" title="Monter">↑</button>
          </form>
          <?php endif; ?>
          <?php if ($i < count($v['images'])-1): ?>
          <form method="POST" action="admin.php?p=photos&k=<?= urlencode($k) ?>" style="margin:0">
            <input type="hidden" name="action" value="move_photo">
            <input type="hidden" name="key" value="<?= h($k) ?>">
            <input type="hidden" name="category" value="exterior">
            <input type="hidden" name="idx" value="<?= $i ?>">
            <input type="hidden" name="dir" value="dn">
            <button type="submit" class="btn btn-g btn-sm" title="Descendre">↓</button>
          </form>
          <?php endif; ?>
          <form method="POST" action="admin.php?p=photos&k=<?= urlencode($k) ?>" style="margin:0" onsubmit="return confirm('Supprimer ?')">
            <input type="hidden" name="action" value="del_photo">
            <input type="hidden" name="key" value="<?= h($k) ?>">
            <input type="hidden" name="category" value="exterior">
            <input type="hidden" name="idx" value="<?= $i ?>">
            <button type="submit" class="btn btn-d btn-sm">✕</button>
          </form>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  <?php else: ?>
    <p style="color:var(--muted)">Aucune photo pour ce bien.</p>
  <?php endif; ?>
</div>
<?php endif; ?>
<?php endif; ?>
<?php endif; ?>

</div><!-- /wrap -->
</body>
</html>

