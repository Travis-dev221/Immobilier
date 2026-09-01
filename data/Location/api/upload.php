<?php
require __DIR__ . '/bootstrap.php';
requireAuth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['error' => 'Méthode non autorisée'], 405);
}

$villa = $_POST['villa'] ?? '';
if (!preg_match('/^[a-z0-9_-]+$/', $villa)) {
    jsonResponse(['error' => 'Villa invalide'], 400);
}

if (!isset($_FILES['photo']) || $_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
    jsonResponse(['error' => 'Échec de l\'upload'], 400);
}

$file = $_FILES['photo'];
if ($file['size'] > 8 * 1024 * 1024) {
    jsonResponse(['error' => 'Fichier trop volumineux (max 8 Mo)'], 400);
}

$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime = finfo_file($finfo, $file['tmp_name']);
finfo_close($finfo);

$map = ['image/jpeg' => 'jpg', 'image/png' => 'jpg', 'image/webp' => 'jpg'];
if (!isset($map[$mime])) {
    jsonResponse(['error' => 'Format non accepté (JPG, PNG, WEBP)'], 400);
}

$dir = $imagesDir . '/' . $villa;
if (!is_dir($dir) && !mkdir($dir, 0755, true)) {
    jsonResponse(['error' => 'Impossible de créer le dossier images'], 500);
}

$name = date('Ymd-His') . '-' . bin2hex(random_bytes(4)) . '.jpg';
$dest = $dir . '/' . $name;

// ── Redimensionner à 1200×800 (crop centré) ──────────────
$TARGET_W = 1200;
$TARGET_H = 800;

// Créer image source selon le mime
switch ($mime) {
    case 'image/jpeg': $src = imagecreatefromjpeg($file['tmp_name']); break;
    case 'image/png':  $src = imagecreatefrompng($file['tmp_name']); break;
    case 'image/webp': $src = imagecreatefromwebp($file['tmp_name']); break;
    default: jsonResponse(['error' => 'Format non supporté'], 400);
}

if (!$src) {
    // Fallback : sauvegarder sans redimensionner
    move_uploaded_file($file['tmp_name'], $dest);
    jsonResponse(['ok' => true, 'url' => 'images/' . $villa . '/' . $name]);
}

$origW = imagesx($src);
$origH = imagesy($src);

// Calculer le ratio pour couvrir 1200×800 (cover)
$ratioW = $TARGET_W / $origW;
$ratioH = $TARGET_H / $origH;
$ratio  = max($ratioW, $ratioH);

$scaledW = (int)round($origW * $ratio);
$scaledH = (int)round($origH * $ratio);

// Crop centré
$srcX = (int)round(($origW - $TARGET_W / $ratio) / 2);
$srcY = (int)round(($origH - $TARGET_H / $ratio) / 2);
$srcCropW = (int)round($TARGET_W / $ratio);
$srcCropH = (int)round($TARGET_H / $ratio);

$dst = imagecreatetruecolor($TARGET_W, $TARGET_H);

// Fond blanc pour les PNG transparents
$white = imagecolorallocate($dst, 255, 255, 255);
imagefill($dst, 0, 0, $white);

imagecopyresampled($dst, $src, 0, 0, $srcX, $srcY, $TARGET_W, $TARGET_H, $srcCropW, $srcCropH);

// Sauvegarder en JPEG qualité 85
imagejpeg($dst, $dest, 85);

imagedestroy($src);
imagedestroy($dst);

jsonResponse(['ok' => true, 'url' => 'images/' . $villa . '/' . $name]);
