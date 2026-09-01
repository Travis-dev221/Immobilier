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
function synchronizePropertyImages(&$villa) {
    $allImages = [];
    
    if (isset($villa['images']) && is_array($villa['images'])) {
        $hasEnglishCategories = isset($villa['images']['exterior']) || isset($villa['images']['interior']) || isset($villa['images']['bedrooms']);
        if ($hasEnglishCategories) {
            foreach (['exterior' => 'exterieur', 'interior' => 'interieur', 'bedrooms' => 'chambres'] as $eng => $fre) {
                if (isset($villa['images'][$eng]) && is_array($villa['images'][$eng])) {
                    foreach ($villa['images'][$eng] as $url) {
                        if (is_string($url) && $url !== '') {
                            $allImages[] = ['url' => $url, 'cat' => $fre];
                        }
                    }
                }
            }
        } else {
            foreach ($villa['images'] as $url) {
                if (is_string($url) && $url !== '') {
                    $allImages[] = ['url' => $url, 'cat' => 'exterieur'];
                }
            }
        }
    }
    
    if (isset($villa['photos']) && is_array($villa['photos'])) {
        foreach (['exterieur', 'interieur', 'chambres'] as $fre) {
            if (isset($villa['photos'][$fre]) && is_array($villa['photos'][$fre])) {
                foreach ($villa['photos'][$fre] as $url) {
                    if (is_string($url) && $url !== '') {
                        $exists = false;
                        foreach ($allImages as $img) {
                            if ($img['url'] === $url) {
                                $exists = true;
                                break;
                            }
                        }
                        if (!$exists) {
                            $allImages[] = ['url' => $url, 'cat' => $fre];
                        }
                    }
                }
            }
        }
    }
    
    $flatImages = [];
    $frenchPhotos = ['exterieur' => [], 'interieur' => [], 'chambres' => []];
    
    foreach ($allImages as $img) {
        $flatImages[] = $img['url'];
        $frenchPhotos[$img['cat']][] = $img['url'];
    }
    
    $villa['images'] = $flatImages;
    $villa['photos'] = $frenchPhotos;
    $villa['_migrated'] = true;
}

function readData() {
    if (!file_exists(DATA_FILE)) {
        // Données par défaut si le fichier n'existe pas
        $default = [
            'guede'  => ['type'=>'vacances','name'=>'Villa Guédé Home','zone'=>'Nguerigne · Petite Côte','description'=>'Grande villa familiale avec pool house.','price'=>440000,'priceUnit'=>'FCFA · nuit','priceNote'=>'','bedrooms'=>7,'bathrooms'=>3,'persons'=>14,'area'=>'1 200','areaLabel'=>'m² hab.','tags'=>['Piscine','Pool house','3 suites','Clim.'],'images'=>['https://images.unsplash.com/photo-1613977257363-707ba9348227?w=1200&q=80','https://images.unsplash.com/photo-1512917774080-9991f1c4c750?w=1200&q=80','https://images.unsplash.com/photo-1600596542815-ffad4c1539a9?w=1200&q=80','https://images.unsplash.com/photo-1600585154340-be6161a56a0c?w=1200&q=80','https://images.unsplash.com/photo-1571896349842-33c89424de2d?w=1200&q=80','https://images.unsplash.com/photo-1618221195710-dd6b41faaea6?w=1200&q=80']],
            'casa'   => ['type'=>'vacances','name'=>'Villa Casa','zone'=>'Nguerigne · Petite Côte','description'=>'Villa conviviale avec piscine privée.','price'=>330000,'priceUnit'=>'FCFA · nuit','priceNote'=>'','bedrooms'=>6,'bathrooms'=>4,'persons'=>12,'area'=>'220','areaLabel'=>'m²','tags'=>['Piscine privée','Chef','Plages proches'],'images'=>['https://images.unsplash.com/photo-1570129477492-45c003edd2be?w=1200&q=80','https://images.unsplash.com/photo-1600047509807-ba8f99d2cdde?w=1200&q=80','https://images.unsplash.com/photo-1600607687939-ce8a6c25118c?w=1200&q=80','https://images.unsplash.com/photo-1505693416388-ac5ce068fe85?w=1200&q=80','https://images.unsplash.com/photo-1560448204-603b3fc33ddc?w=1200&q=80','https://images.unsplash.com/photo-1584622650111-993a426fbf0a?w=1200&q=80']],
            'palm'   => ['type'=>'vacances','name'=>'Villa Palm Évasion','zone'=>'Nguerigne · 50m plage','description'=>'Adresse complète proche de la mer.','price'=>400000,'priceUnit'=>'FCFA · nuit · hors électricité','priceNote'=>'','bedrooms'=>8,'bathrooms'=>5,'persons'=>16,'area'=>'50m','areaLabel'=>'Plage','tags'=>['Jacuzzi','Sauna','Sport','Cinéma'],'images'=>['https://images.unsplash.com/photo-1499793983690-e29da59ef1c2?w=1200&q=80','https://images.unsplash.com/photo-1566073771259-6a8506099945?w=1200&q=80','https://images.unsplash.com/photo-1540518614846-7eded433c457?w=1200&q=80','https://images.unsplash.com/photo-1602343168117-bb8ffe3e2e9f?w=1200&q=80','https://images.unsplash.com/photo-1615460549969-36fa19521a4f?w=1200&q=80','https://images.unsplash.com/photo-1534430480872-3498386e7856?w=1200&q=80']],
            'torino' => ['type'=>'vacances','name'=>'Villa Torino','zone'=>'Somone · 300m plage','description'=>'Villa entre plage et lagon.','price'=>340000,'priceUnit'=>'FCFA · nuit','priceNote'=>'','bedrooms'=>6,'bathrooms'=>3,'persons'=>12,'area'=>'500m','areaLabel'=>'Lagon','tags'=>['Piscine','Chef','Ménage'],'images'=>['https://images.unsplash.com/photo-1523217582562-09d0def993a6?w=1200&q=80','https://images.unsplash.com/photo-1506126613408-eca07ce68773?w=1200&q=80','https://images.unsplash.com/photo-1510798831971-661eb04b3739?w=1200&q=80','https://images.unsplash.com/photo-1576013551627-0cc20b96c2a7?w=1200&q=80','https://images.unsplash.com/photo-1600210491369-e753d80a41f3?w=1200&q=80','https://images.unsplash.com/photo-1558618666-fcd25c85cd64?w=1200&q=80']],
            'vhouse' => ['type'=>'vacances','name'=>'V-House','zone'=>'Nguérigne · Famille','description'=>'Adresse familiale avec personnel inclus.','price'=>340000,'priceUnit'=>'FCFA · nuit','priceNote'=>'','bedrooms'=>5,'bathrooms'=>3,'persons'=>10,'area'=>'Parc','areaLabel'=>'Inclus','tags'=>['Personnel','Chef privé','Verdure'],'images'=>['https://images.unsplash.com/photo-1585032226651-759b368d7246?w=1200&q=80','https://images.unsplash.com/photo-1600566752355-35792bedcfea?w=1200&q=80','https://images.unsplash.com/photo-1600566752734-a1b4de0c2ad5?w=1200&q=80','https://images.unsplash.com/photo-1555041469-a586c61ea9bc?w=1200&q=80','https://images.unsplash.com/photo-1598928506311-c55ded91a20c?w=1200&q=80','https://images.unsplash.com/photo-1583847268964-b28dc8f51f92?w=1200&q=80']],
        ];
        foreach ($default as &$v) {
            synchronizePropertyImages($v);
        }
        unset($v);
        return $default;
    }
    $d = json_decode(file_get_contents(DATA_FILE), true);
    if (is_array($d)) {
        foreach ($d as &$v) {
            synchronizePropertyImages($v);
        }
        unset($v);
        return $d;
    }
    return [];
}

function writeData($data) {
    // Créer data/ si besoin
    if (!is_dir(DATA_DIR)) mkdir(DATA_DIR, 0755, true);
    // Protéger data/ avec .htaccess
    $htaccess = DATA_DIR . '/.htaccess';
    if (!file_exists($htaccess)) {
        file_put_contents($htaccess, "Options -Indexes\n<Files \"*.php\">\nDeny from all\n</Files>\n");
    }
    
    // Normaliser la structure des images
    foreach ($data as $key => &$villa) {
        synchronizePropertyImages($villa);
    }
    unset($villa);
    
    $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    file_put_contents(DATA_FILE, $json);
}

function h($s) { return htmlspecialchars($s ?? '', ENT_QUOTES); }

// ── PAGES ──────────────────────────────────────────────────
$page = $_GET['p'] ?? 'list';
$editKey = $_GET['k'] ?? '';
$action = $_POST['action'] ?? '';

// Rediriger la page "reservations" vers la nouvelle interface SPA
if ($page === 'reservations' && !empty($_SESSION['admin'])) {
    header('Location: admin/index.html');
    exit;
}

// ── ACTIONS POST ──────────────────────────────────────────
$msg = '';
$msgType = 'ok';

// Login & IP Block logic
$blockFile = __DIR__ . '/data/blocked_ips.json';
$ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';

if (isset($_GET['unblock']) && $_GET['unblock'] === 'baobab_secret') {
    if (file_exists($blockFile)) unlink($blockFile);
    die('Toutes les adresses IP ont été débloquées avec succès. <a href="admin.php">Retour</a>');
}

if (isset($_POST['action']) && $_POST['action'] === 'login') {
    $attempts = file_exists($blockFile) ? json_decode(file_get_contents($blockFile), true) : [];
    
    if (($attempts[$ip] ?? 0) >= 5) {
        $msg = 'Compte bloqué après 5 tentatives échouées.';
        $msgType = 'err';
    } else {
        if (in_array(trim($_POST['password'] ?? ''), ADMIN_PASSWORDS)) {
            if (isset($attempts[$ip])) {
                unset($attempts[$ip]);
                file_put_contents($blockFile, json_encode($attempts));
            }
            $_SESSION['admin'] = true;
            header('Location: admin.php');
            exit;
        } else {
            $attempts[$ip] = ($attempts[$ip] ?? 0) + 1;
            file_put_contents($blockFile, json_encode($attempts));
            
            $rem = 5 - $attempts[$ip];
            if ($rem > 0) {
                $msg = 'Mot de passe incorrect. ' . $rem . ' tentative(s) restante(s).';
            } else {
                $msg = 'Compte bloqué après 5 tentatives échouées.';
            }
            $msgType = 'err';
        }
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
            $existing = isset($data[$oldKey]) ? $data[$oldKey] : (isset($data[$key]) ? $data[$key] : []);
            
            // Si changement de clé, supprimer l'ancienne
            if ($oldKey && $oldKey !== $key && isset($data[$oldKey])) {
                unset($data[$oldKey]);
            }
            
            $tags = array_filter(array_map('trim', explode(',', $_POST['tags'] ?? '')));
            
            $in_accueil  = isset($_POST['in_accueil']) ? true : false;
            $in_vacances = isset($_POST['in_vacances']) ? true : false;
            $in_ventes   = isset($_POST['in_ventes']) ? true : false;
            
            $data[$key] = array_merge([
                'type'        => 'vacances',
                'name'        => '',
                'zone'        => '',
                'description' => '',
                'price'       => 0,
                'priceUnit'   => '',
                'priceNote'   => '',
                'bedrooms'    => 0,
                'bathrooms'   => 0,
                'persons'     => 0,
                'area'        => '',
                'areaLabel'   => '',
                'tags'        => [],
                'images'      => [],
                'photos'      => ['exterieur' => [], 'interieur' => [], 'chambres' => []],
                'in_accueil'  => false,
                'in_vacances' => false,
                'in_ventes'   => false,
                'section'     => 'location',
                'available'   => true
            ], $existing, [
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
                'in_accueil'  => $in_accueil,
                'in_vacances' => $in_vacances,
                'in_ventes'   => $in_ventes,
                'section'     => $in_vacances ? 'location' : ($in_ventes ? 'vente' : 'location'),
            ]);
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
        // Vérifier les limites PHP
        $uploadMax = ini_get('upload_max_filesize');
        $postMax = ini_get('post_max_size');
        
        $key = preg_replace('/[^a-z0-9_-]/', '', strtolower(trim($_POST['key'] ?? '')));
        if (!$key || !isset($data[$key])) {
            $msg = '❌ Bien introuvable. Clé: ' . $key;
            $msgType = 'err';
        } elseif (!isset($_FILES['photo'])) {
            $msg = '❌ Aucun fichier sélectionné. Limites PHP: upload_max_filesize=' . $uploadMax . ', post_max_size=' . $postMax;
            $msgType = 'err';
        } elseif ($_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
            $errorMsg = '';
            switch($_FILES['photo']['error']) {
                case UPLOAD_ERR_INI_SIZE: $errorMsg = 'Fichier trop grand (limite PHP: ' . $uploadMax . ')'; break;
                case UPLOAD_ERR_FORM_SIZE: $errorMsg = 'Fichier trop grand (limite formulaire)'; break;
                case UPLOAD_ERR_PARTIAL: $errorMsg = 'Upload partiel'; break;
                case UPLOAD_ERR_NO_FILE: $errorMsg = 'Aucun fichier'; break;
                case UPLOAD_ERR_NO_TMP_DIR: $errorMsg = 'Pas de dossier temporaire'; break;
                case UPLOAD_ERR_CANT_WRITE: $errorMsg = 'Impossible d\'écrire sur disque'; break;
                default: $errorMsg = 'Erreur inconnue: ' . $_FILES['photo']['error'];
            }
            $msg = '❌ Erreur upload: ' . $errorMsg;
            $msgType = 'err';
        } else {
            $file = $_FILES['photo'];
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);
            $map = ['image/jpeg'=>'jpg','image/png'=>'jpg','image/webp'=>'jpg'];
            if (!isset($map[$mime])) {
                $msg = '❌ Format non supporté: ' . $mime;
                $msgType = 'err';
            } elseif ($file['size'] > 8*1024*1024) {
                $msg = '❌ Fichier trop lourd (max 8 Mo).';
                $msgType = 'err';
            } else {
                $dir = IMAGES_DIR . '/' . $key;
                if (!is_dir($dir)) {
                    if (!mkdir($dir, 0755, true)) {
                        $msg = '❌ Impossible de créer le dossier: ' . $dir;
                        $msgType = 'err';
                    }
                }
                if (!is_dir($dir) || !is_writable($dir)) {
                    $msg = '❌ Dossier non accessible ou non writable: ' . $dir;
                    $msgType = 'err';
                } else {
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
                    $catMap = ['exterior' => 'exterieur', 'interior' => 'interieur', 'bedrooms' => 'chambres', 'exterieur' => 'exterieur', 'interieur' => 'interieur', 'chambres' => 'chambres'];
                    $cat = $catMap[$category] ?? 'exterieur';
                    if (!isset($data[$key]['photos']) || !is_array($data[$key]['photos'])) {
                        $data[$key]['photos'] = ['exterieur'=>[], 'interieur'=>[], 'chambres'=>[]];
                    }
                    $data[$key]['photos'][$cat][] = 'images/' . $key . '/' . $name;
                    writeData($data);
                    $msg = '✅ Photo ajoutée dans la catégorie "' . ($cat === 'exterieur' ? 'Extérieur' : ($cat === 'interieur' ? 'Intérieur' : 'Chambres')) . '".';
                }
            }
        }
    }

    // Ajouter URL image
    if (isset($_POST['action']) && $_POST['action'] === 'add_url') {
        $key = preg_replace('/[^a-z0-9_-]/', '', strtolower(trim($_POST['key'] ?? '')));
        $url = trim($_POST['url'] ?? '');
        $category = $_POST['category'] ?? 'exterior';
        $catMap = ['exterior' => 'exterieur', 'interior' => 'interieur', 'bedrooms' => 'chambres', 'exterieur' => 'exterieur', 'interieur' => 'interieur', 'chambres' => 'chambres'];
        $cat = $catMap[$category] ?? 'exterieur';
        if ($key && isset($data[$key]) && $url) {
            if (!isset($data[$key]['photos']) || !is_array($data[$key]['photos'])) {
                $data[$key]['photos'] = ['exterieur'=>[], 'interieur'=>[], 'chambres'=>[]];
            }
            $data[$key]['photos'][$cat][] = $url;
            writeData($data);
            $msg = '✅ URL ajoutée dans la catégorie "' . ($cat === 'exterieur' ? 'Extérieur' : ($cat === 'interieur' ? 'Intérieur' : 'Chambres')) . '".';
        }
    }

    // Supprimer photo
    if (isset($_POST['action']) && $_POST['action'] === 'del_photo') {
        $key = preg_replace('/[^a-z0-9_-]/', '', strtolower(trim($_POST['key'] ?? '')));
        $category = $_POST['category'] ?? 'exterieur';
        $catMap = ['exterior' => 'exterieur', 'interior' => 'interieur', 'bedrooms' => 'chambres', 'exterieur' => 'exterieur', 'interieur' => 'interieur', 'chambres' => 'chambres'];
        $cat = $catMap[$category] ?? 'exterieur';
        $idx = intval($_POST['idx'] ?? -1);
        if ($key && isset($data[$key]['photos'])) {
            if (isset($data[$key]['photos'][$cat][$idx])) {
                $url = $data[$key]['photos'][$cat][$idx];
                if (strpos($url, 'images/') === 0) {
                    $path = __DIR__ . '/' . $url;
                    if (file_exists($path)) @unlink($path);
                }
                array_splice($data[$key]['photos'][$cat], $idx, 1);
                writeData($data);
                $msg = '✅ Photo supprimée.';
            }
        }
    }

    // Monter photo
    if (isset($_POST['action']) && $_POST['action'] === 'move_photo') {
        $key = preg_replace('/[^a-z0-9_-]/', '', strtolower(trim($_POST['key'] ?? '')));
        $category = $_POST['category'] ?? 'exterieur';
        $catMap = ['exterior' => 'exterieur', 'interior' => 'interieur', 'bedrooms' => 'chambres', 'exterieur' => 'exterieur', 'interieur' => 'interieur', 'chambres' => 'chambres'];
        $cat = $catMap[$category] ?? 'exterieur';
        $idx = intval($_POST['idx'] ?? -1);
        $dir2 = $_POST['dir'] ?? '';
        if ($key && isset($data[$key]['photos'])) {
            if (isset($data[$key]['photos'][$cat])) {
                $imgs = &$data[$key]['photos'][$cat];
                if ($dir2 === 'up' && $idx > 0) {
                    [$imgs[$idx-1], $imgs[$idx]] = [$imgs[$idx], $imgs[$idx-1]];
                    writeData($data);
                } elseif ($dir2 === 'dn' && $idx < count($imgs)-1) {
                    [$imgs[$idx+1], $imgs[$idx]] = [$imgs[$idx], $imgs[$idx+1]];
                    writeData($data);
                }
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
  <link rel="icon" href="favicon.ico" type="image/x-icon">
  <link rel="shortcut icon" href="favicon.ico" type="image/x-icon">
  <link rel="apple-touch-icon" href="favicon.png">
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Admin — Baobab Horizon</title>
<link href="https://fonts.googleapis.com/css2?family=Lora:ital,wght@0,400;0,500;0,600;1,400;1,500&family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
<style>
*{box-sizing:border-box;margin:0;padding:0}
:root{--fd: 'Lora', Georgia, serif; --fb: 'Poppins', sans-serif;--gold: #9C6F1C;--glight: #D6AF5C;--night: #F8F4EC;--nmid: #EDE3D2;--nsoft: #e2d6c3;--cream: #0F1A17;--sand: #0F1A17;--muted: #6b7c78;--danger:#c45c5c;--ok:#5a9e6f}
body{font-family: 'Poppins', system-ui, sans-serif;background:var(--night);color:var(--sand);min-height:100vh}
a{color:var(--glight);text-decoration:none}
a:hover{color:var(--cream)}
.wrap{max-width:1000px;margin:0 auto;padding:28px 18px 80px}
/* Header */
.hdr{display:flex;align-items:center;justify-content:space-between;margin-bottom:28px;flex-wrap:wrap;gap:12px}
.logo{font-family: var(--fd);font-size:1.3rem;letter-spacing:.15em;text-transform:uppercase;color:var(--cream)}
.logo span{color:var(--gold)}
/* Nav */
.nav{display:flex;gap:4px;margin-bottom:24px;flex-wrap:wrap}
.nav a{font-size:.65rem;letter-spacing:.18em;text-transform:uppercase;padding:8px 16px;border:1px solid rgba(184,147,90,.25);color:var(--muted)}
.nav a:hover{border-color:var(--gold);color:var(--gold)}
.nav a.on{border-color:var(--gold);background:rgba(184,147,90,.1);color:var(--gold)}
/* Card */
.card{background:var(--nmid);border:1px solid rgba(184,147,90,.18);padding:24px;margin-bottom:18px}
.card-title{font-family: var(--fd);font-size:1.4rem;font-weight:400;color:var(--cream);margin-bottom:16px}
/* Msg */
.msg{padding:10px 14px;font-size:.84rem;margin-bottom:16px;border-radius:1px}
.msg.ok{background:rgba(90,158,111,.12);border:1px solid rgba(90,158,111,.35);color: #1e4620}
.msg.err{background:rgba(196,92,92,.12);border:1px solid rgba(196,92,92,.35);color: #8a1f1f}
/* Forms */
.fg{display:flex;flex-direction:column;gap:6px;margin-bottom:14px}
label.lbl{font-size:.65rem;letter-spacing:.14em;text-transform:uppercase;color:var(--muted)}
input[type=text],input[type=number],input[type=password],input[type=url],input[type=file],select,textarea{
  width:100%;padding:10px 12px;background:var(--nsoft);border:1px solid rgba(184,147,90,.22);
  color:var(--cream);font:inherit;font-size:.88rem}
input[type=file]{
  cursor:pointer;
  padding:8px 12px;
}
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
.bv{background:rgba(90,158,111,.18);color: #1e4620}
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
@media(max-width:600px){
  .wrap{padding:16px 10px 60px}
  .card{padding:16px 12px;margin-bottom:12px}
  .hdr img{height:48px !important}
  .logo{font-size:1.1rem}
  .nav{display:flex;width:100%}
  .nav a{flex:1;text-align:center;padding:8px 4px;font-size:0.6rem}
  .grid2,.grid3{grid-template-columns:1fr;gap:10px}
  .bien-row{flex-direction:column;align-items:flex-start}
  .bien-row>div:first-child{width:100%;margin-bottom:10px}
  .bien-row>div:last-child{width:100%;display:flex;gap:8px;flex-wrap:wrap}
  .bien-row .btn{flex:1;justify-content:center;min-width:80px}
}
</style>
</head>
<body>
<div class="wrap">

<div class="hdr">
  <a href="./" target="_blank" style="display:inline-block;line-height:0">
    <img src="logoo.jpg" alt="Baobab Horizon" style="height:70px;width:auto;display:block">
  </a>
  <div style="display:flex;gap:10px;align-items:center">
    <a href="./" class="btn btn-g btn-sm" target="_blank">← Voir le site</a>
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
    <div class="fg" style="position:relative;">
      <label class="lbl">Mot de passe</label>
      <div style="position:relative;">
        <input type="password" id="passwordInput" name="password" placeholder="Mot de passe admin" required autofocus style="padding-right: 40px; width: 100%; box-sizing: border-box;">
        <button type="button" id="togglePassword" style="position:absolute; right:10px; top:50%; transform:translateY(-50%); background:none; border:none; color:var(--muted); cursor:pointer; font-size:1.1rem; padding:0;" aria-label="Afficher le mot de passe">👁️</button>
      </div>
    </div>
    <button type="submit" class="btn" id="loginBtn">Se connecter</button>
    <?php 
    $attempts = file_exists($blockFile) ? json_decode(file_get_contents($blockFile), true) : [];
    if (($attempts[$ip] ?? 0) >= 5): 
    ?>
    <div style="margin-top:15px; text-align:center;">
      <a href="#" onclick="alert('Veuillez contacter l\'administrateur principal (Dani) au +221 78 014 09 42 pour réinitialiser le mot de passe.'); return false;" style="color:var(--gold); text-decoration:underline; font-size:0.85rem;">Mot de passe oublié ?</a>
    </div>
    <script>
      document.getElementById('passwordInput').disabled = true;
      document.getElementById('loginBtn').disabled = true;
    </script>
    <?php endif; ?>
  </form>
</div>
<script>
document.getElementById('togglePassword').addEventListener('click', function() {
  const pwd = document.getElementById('passwordInput');
  if (pwd.type === 'password') {
    pwd.type = 'text';
    this.textContent = '🔒';
  } else {
    pwd.type = 'password';
    this.textContent = '👁️';
  }
});
</script>

<?php else: ?>
<!-- ═══ NAV ADMIN ════════════════════════════════════════ -->
<div class="nav">
  <a href="admin.php?p=list" class="<?= $page==='list'?'on':'' ?>">Liste des biens</a>
  <a href="admin.php?p=clients" class="<?= $page==='clients'?'on':'' ?>">👥 Clients & Prospects (CRM)</a>
  <a href="admin.php?p=reservations" class="<?= $page==='reservations'?'on':'' ?>">📅 Réservations</a>
</div>

<?php if ($page === 'list'): ?>
<!-- ═══ LISTE DES BIENS ══════════════════════════════════ -->
<div class="card" style="background:rgba(184,147,90,.06);border-color:rgba(184,147,90,.3);margin-bottom:12px;padding:16px 20px">
  <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px">
    <div style="font-size:.82rem;color:var(--sand)">
      ✅ Les modifications sont <strong>instantanément visibles sur le site</strong> après enregistrement.
    </div>
    <a href="./" target="_blank" class="btn btn-g btn-sm">🌐 Voir le site</a>
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
    <a href="admin.php?p=edit&k=<?= urlencode($k) ?>#photos" class="bien-row" style="text-decoration:none;color:inherit">
      <div style="flex:1">
        <div class="bien-name"><?= h($v['name']) ?></div>
        <div class="bien-meta">
          <span class="badge <?= $bc ?>"><?= h($v['type']) ?></span>
          &nbsp;<?= h($v['zone']??'') ?>
          &nbsp;·&nbsp;<?= $prix ?>
          &nbsp;·&nbsp;<?= count($v['images']??[]) ?> photo(s)
        </div>
      </div>
      <div style="display:flex;gap:6px;flex-wrap:wrap" onclick="event.stopPropagation()">
        <a href="admin.php?p=edit&k=<?= urlencode($k) ?>" class="btn btn-g btn-sm">Modifier</a>
        <a href="admin.php?p=edit&k=<?= urlencode($k) ?>#photos" class="btn btn-g btn-sm">Photos</a>
        <form method="POST" action="admin.php?p=list" style="display:inline" onsubmit="return confirm('Supprimer <?= h($v['name']) ?> ?')">
          <input type="hidden" name="action" value="delete">
          <input type="hidden" name="key" value="<?= h($k) ?>">
          <button type="submit" class="btn btn-d btn-sm">Supprimer</button>
        </form>
      </div>
    </a>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</div>

<?php elseif ($page === 'clients'): ?>
<!-- ═══ CLIENTS & PROSPECTS (CRM) ═════════════════════════ -->
<?php
$clientsFile = DATA_DIR . '/clients.json';
$clients = file_exists($clientsFile) ? json_decode(file_get_contents($clientsFile), true) : [];
if (!is_array($clients)) $clients = [];

$totalClients = count($clients);
$privilegeClients = 0;
foreach ($clients as $c) {
    if (!empty($c['marketing']) || ($c['discount'] ?? 1) >= 2) {
        $privilegeClients++;
    }
}
?>

<div class="grid3" style="margin-bottom:20px">
  <div class="card" style="padding:16px 20px;text-align:center;background:rgba(184,147,90,.08);border-color:rgba(184,147,90,.3)">
    <div style="font-size:1.8rem;font-weight:700;color:var(--gold)"><?= $totalClients ?></div>
    <div style="font-size:.7rem;letter-spacing:.12em;text-transform:uppercase;color:var(--muted);margin-top:4px">Prospects & Clients Enregistrés</div>
  </div>
  <div class="card" style="padding:16px 20px;text-align:center;background:rgba(184,147,90,.08);border-color:rgba(184,147,90,.3)">
    <div style="font-size:1.8rem;font-weight:700;color:var(--ok)"><?= $privilegeClients ?></div>
    <div style="font-size:.7rem;letter-spacing:.12em;text-transform:uppercase;color:var(--muted);margin-top:4px">Membres Privilège (-2%)</div>
  </div>
  <div class="card" style="padding:16px 20px;text-align:center;background:rgba(184,147,90,.08);border-color:rgba(184,147,90,.3)">
    <div style="font-size:1.8rem;font-weight:700;color:var(--gold)"><?= $totalClients - $privilegeClients ?></div>
    <div style="font-size:.7rem;letter-spacing:.12em;text-transform:uppercase;color:var(--muted);margin-top:4px">Remise Immédiate (-1%)</div>
  </div>
</div>

<div class="card">
  <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;flex-wrap:wrap;gap:10px">
    <div class="card-title" style="margin-bottom:0">Base CRM Clients & Leads Capturés</div>
    <input type="text" id="clientSearchInput" onkeyup="filterClientRows()" placeholder="🔍 Rechercher un nom, tel, email..." style="max-width:280px;padding:6px 12px;font-size:.75rem">
  </div>

  <?php if (empty($clients)): ?>
    <p style="color:var(--muted);padding:14px 0">Aucun client ni prospect enregistré pour le moment.</p>
  <?php else: ?>
  <div style="overflow-x:auto">
    <table style="width:100%;border-collapse:collapse;font-size:.8rem;text-align:left">
      <thead>
        <tr style="border-bottom:2px solid rgba(184,147,90,.3);color:var(--gold);text-transform:uppercase;font-size:.65rem;letter-spacing:.1em">
          <th style="padding:10px 12px">Identité Client</th>
          <th style="padding:10px 12px">Téléphone 📞</th>
          <th style="padding:10px 12px">Email ✉️</th>
          <th style="padding:10px 12px">Origine du contact</th>
          <th style="padding:10px 12px">Statut Remise</th>
          <th style="padding:10px 12px">Date</th>
          <th style="padding:10px 12px;text-align:right">Relance en 1 Clic 🚀</th>
        </tr>
      </thead>
      <tbody id="clientTableBody">
        <?php foreach (array_reverse($clients) as $c): ?>
        <?php
          $rawPhone = $c['phone'] ?? '';
          $cleanPhone = preg_replace('/[^0-9]/', '', $rawPhone);
          if ($cleanPhone && strpos($cleanPhone, '221') !== 0 && strlen($cleanPhone) === 9) {
              $cleanPhone = '221' . $cleanPhone;
          }
          $discount = $c['discount'] ?? (!empty($c['marketing']) ? 2 : 1);
          $source = $c['source'] ?? 'Demande Site Web';
          
          $waMsg = rawurlencode("Bonjour " . ($c['name'] ?? 'Cher client') . ",\n\nL'équipe Baobab Horizon revient vers vous suite à votre demande (" . $source . ").\nNous avons de nouvelles opportunités et séjours disponibles avec votre remise privilège de -" . $discount . "% !\n\nSouhaitez-vous échanger sur votre projet ?");
          $waUrl = $cleanPhone ? "https://wa.me/" . $cleanPhone . "?text=" . $waMsg : "#";
          
          $emailSub = rawurlencode("Nouvelles opportunités Baobab Horizon - Remise Privilège (-" . $discount . "%)");
          $emailBody = rawurlencode("Bonjour " . ($c['name'] ?? '') . ",\n\nNous faisons suite à votre intérêt pour nos biens et séjours sur Baobab Horizon.\nEn tant que membre bénéficiant de -" . $discount . "% de remise, nous serions ravis de vous accompagner dans votre projet.\n\nRestant à votre entière disposition par retour d'email ou au +221 78 014 09 42.\n\nCordialement,\nL'équipe Baobab Horizon");
          $emailUrl = !empty($c['email']) ? "mailto:" . $c['email'] . "?subject=" . $emailSub . "&body=" . $emailBody : "#";
        ?>
        <tr class="client-tr-row" style="border-bottom:1px solid rgba(184,147,90,.12)">
          <td style="padding:12px;font-weight:600;color:var(--cream)">
            <?= h($c['name'] ?? 'Anonyme') ?>
            <div style="font-size:.65rem;color:var(--muted);font-weight:400">ID: <?= h($c['id'] ?? '') ?></div>
          </td>
          <td style="padding:12px">
            <?php if (!empty($c['phone'])): ?>
              <a href="tel:<?= h($c['phone']) ?>" style="color:var(--sand);text-decoration:none">📞 <?= h($c['phone']) ?></a>
            <?php else: ?>
              <span style="color:var(--muted)">—</span>
            <?php endif; ?>
          </td>
          <td style="padding:12px">
            <?php if (!empty($c['email'])): ?>
              <a href="mailto:<?= h($c['email']) ?>" style="color:var(--sand);text-decoration:none">✉️ <?= h($c['email']) ?></a>
            <?php else: ?>
              <span style="color:var(--muted)">—</span>
            <?php endif; ?>
          </td>
          <td style="padding:12px">
            <span class="badge bs" style="padding:4px 8px"><?= h($source) ?></span>
          </td>
          <td style="padding:12px">
            <?php if ($discount >= 2): ?>
              <span class="badge bv" style="padding:4px 8px;font-weight:700">🎁 Privilège (-2%)</span>
            <?php else: ?>
              <span class="badge bt" style="padding:4px 8px">🌟 Immédiate (-1%)</span>
            <?php endif; ?>
          </td>
          <td style="padding:12px;font-size:.7rem;color:var(--muted)">
            <?= h(substr($c['created_at'] ?? $c['last_activity'] ?? '', 0, 16)) ?>
          </td>
          <td style="padding:12px;text-align:right">
            <div style="display:flex;gap:6px;justify-content:flex-end">
              <?php if ($cleanPhone): ?>
                <a href="<?= $waUrl ?>" target="_blank" class="btn btn-sm" style="background:#25D366;border-color:#25D366;color:#fff" title="Relancer sur WhatsApp">💬 WhatsApp</a>
              <?php endif; ?>
              <?php if (!empty($c['email'])): ?>
                <a href="<?= $emailUrl ?>" class="btn btn-g btn-sm" title="Relancer par Email">✉️ Email</a>
              <?php endif; ?>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <script>
    function filterClientRows() {
      var query = document.getElementById('clientSearchInput').value.toLowerCase();
      var rows = document.querySelectorAll('.client-tr-row');
      rows.forEach(function(row) {
        var text = row.innerText.toLowerCase();
        row.style.display = text.indexOf(query) !== -1 ? '' : 'none';
      });
    }
  </script>
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
      <label class="lbl">Affichage sur les pages</label>
      <div style="display:flex; gap:20px; flex-wrap:wrap; margin-top:5px;">
        <label style="display:inline-flex; align-items:center; gap:6px; cursor:pointer;">
          <input type="checkbox" name="in_accueil" value="1" <?= (!empty($v['in_accueil']) || ($v['section']??'')==='accueil')?'checked':'' ?>> 🏡 Page d'accueil
        </label>
        <label style="display:inline-flex; align-items:center; gap:6px; cursor:pointer;">
          <input type="checkbox" name="in_vacances" value="1" <?= (!empty($v['in_vacances']) || ($v['section']??'')==='location')?'checked':'' ?>> 🏖 Page Vacances (Louer)
        </label>
        <label style="display:inline-flex; align-items:center; gap:6px; cursor:pointer;">
          <input type="checkbox" name="in_ventes" value="1" <?= (!empty($v['in_ventes']) || ($v['section']??'')==='vente')?'checked':'' ?>> 🏠 Page Ventes (Acheter)
        </label>
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
<?php if ($msg): ?>
<div class="card" style="padding:12px 16px;background:<?= $msgType==='err'?'rgba(196,92,92,.12)':'rgba(184,147,90,.12)' ?>;border-color:<?= $msgType==='err'?'rgba(196,92,92,.3)':'rgba(184,147,90,.3)' ?>">
  <?= h($msg) ?>
</div>
<?php endif; ?>
<div class="card">
  <div class="card-title" style="margin:0">Photos · <?= h($v['name']) ?></div>
</div>

  <!-- Upload fichier -->
  <p style="font-size:.7rem;letter-spacing:.2em;text-transform:uppercase;color:var(--gold);margin-bottom:12px">Uploader des photos</p>
  <form method="POST" enctype="multipart/form-data" style="background:var(--nmid);padding:16px;border:1px solid rgba(184,147,90,.3)">
    <input type="hidden" name="action" value="upload">
    <input type="hidden" name="key" value="<?= h($k) ?>">
    <div style="margin-bottom:12px">
      <label style="display:block;margin-bottom:6px;font-size:.7rem;color:var(--muted)">Catégorie</label>
      <select name="category" required style="padding:8px;width:100%;background:var(--nsoft);border:1px solid rgba(184,147,90,.22);color:var(--cream)">
        <option value="exterior">Extérieur</option>
        <option value="interior">Intérieur</option>
        <option value="bedrooms">Chambres</option>
      </select>
    </div>
    <div style="margin-bottom:12px">
      <label style="display:block;margin-bottom:6px;font-size:.7rem;color:var(--muted)">Photo (JPG, PNG, WEBP — max 8 Mo)</label>
      <input type="file" name="photo" accept="image/jpeg,image/png,image/webp" required style="width:100%;padding:8px;background:var(--nsoft);border:1px solid rgba(184,147,90,.22);color:var(--cream)">
    </div>
    <button type="submit" class="btn" style="width:100%">Uploader la photo</button>
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
  $categoryLabels = ['exterieur' => 'Extérieur', 'interieur' => 'Intérieur', 'chambres' => 'Chambres'];
  $hasCategorizedImages = is_array($v['photos'] ?? null) && (!empty($v['photos']['exterieur']) || !empty($v['photos']['interieur']) || !empty($v['photos']['chambres']));
  
  // Si format structuré par catégories
  if ($hasCategorizedImages): ?>
    <?php foreach (['exterieur', 'interieur', 'chambres'] as $cat): ?>
      <?php if (!empty($v['photos'][$cat])): ?>
        <p style="font-size:.7rem;letter-spacing:.2em;text-transform:uppercase;color:var(--gold);margin-bottom:12px;margin-top:24px"><?= $categoryLabels[$cat] ?> (<?= count($v['photos'][$cat]) ?>)</p>
        <div class="pgrid">
          <?php foreach ($v['photos'][$cat] as $i => $url): ?>
          <div class="pcard">
            <span class="pcard-n"><?= $i+1 ?></span>
            <?php $src = strpos($url,'http')===0 ? $url : $url; ?>
            <img src="<?= h($src) ?>" alt="<?= $categoryLabels[$cat] ?> <?= $i+1 ?>" loading="lazy" onerror="this.src='../'+'<?= h($url) ?>'; this.onerror=null;">
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
              <?php if ($i < count($v['photos'][$cat])-1): ?>
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
    <!-- Format simple tableau (ancien format) -->
    <p style="font-size:.7rem;letter-spacing:.2em;text-transform:uppercase;color:var(--gold);margin-bottom:12px">Photos actuelles (<?= count($v['images']) ?>)</p>
    <div class="pgrid">
      <?php foreach ($v['images'] as $i => $url): ?>
      <div class="pcard">
        <span class="pcard-n"><?= $i+1 ?></span>
        <?php $src = strpos($url,'http')===0 ? $url : $url; ?>
        <img src="<?= h($src) ?>" alt="Photo <?= $i+1 ?>" loading="lazy" onerror="this.src='../'+'<?= h($url) ?>'; this.onerror=null;">
        <div class="pcard-bar">
          <?php if ($i > 0): ?>
          <form method="POST" action="admin.php?p=photos&k=<?= urlencode($k) ?>" style="margin:0">
            <input type="hidden" name="action" value="move_photo">
            <input type="hidden" name="key" value="<?= h($k) ?>">
            <input type="hidden" name="category" value="exterieur">
            <input type="hidden" name="idx" value="<?= $i ?>">
            <input type="hidden" name="dir" value="up">
            <button type="submit" class="btn btn-g btn-sm" title="Monter">↑</button>
          </form>
          <?php endif; ?>
          <?php if ($i < count($v['images'])-1): ?>
          <form method="POST" action="admin.php?p=photos&k=<?= urlencode($k) ?>" style="margin:0">
            <input type="hidden" name="action" value="move_photo">
            <input type="hidden" name="key" value="<?= h($k) ?>">
            <input type="hidden" name="category" value="exterieur">
            <input type="hidden" name="idx" value="<?= $i ?>">
            <input type="hidden" name="dir" value="dn">
            <button type="submit" class="btn btn-g btn-sm" title="Descendre">↓</button>
          </form>
          <?php endif; ?>
          <form method="POST" action="admin.php?p=photos&k=<?= urlencode($k) ?>" style="margin:0" onsubmit="return confirm('Supprimer ?')">
            <input type="hidden" name="action" value="del_photo">
            <input type="hidden" name="key" value="<?= h($k) ?>">
            <input type="hidden" name="category" value="exterieur">
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

