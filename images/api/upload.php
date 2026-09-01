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
$maxSize = 8 * 1024 * 1024;
if ($file['size'] > $maxSize) {
    jsonResponse(['error' => 'Fichier trop volumineux (max 8 Mo)'], 400);
}

$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime = finfo_file($finfo, $file['tmp_name']);
finfo_close($finfo);

$map = [
    'image/jpeg' => 'jpg',
    'image/png' => 'png',
    'image/webp' => 'webp',
];

if (!isset($map[$mime])) {
    jsonResponse(['error' => 'Format non accepté (JPG, PNG, WEBP)'], 400);
}

$dir = $imagesDir . '/' . $villa;
if (!is_dir($dir) && !mkdir($dir, 0755, true)) {
    jsonResponse(['error' => 'Impossible de créer le dossier images'], 500);
}

$name = date('Ymd-His') . '-' . bin2hex(random_bytes(4)) . '.' . $map[$mime];
$dest = $dir . '/' . $name;

if (!move_uploaded_file($file['tmp_name'], $dest)) {
    jsonResponse(['error' => 'Impossible de sauvegarder le fichier'], 500);
}

jsonResponse(['ok' => true, 'url' => 'images/' . $villa . '/' . $name]);
