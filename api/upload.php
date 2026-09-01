<?php
error_log('Upload.php called - METHOD: ' . $_SERVER['REQUEST_METHOD']);
require __DIR__ . '/bootstrap.php';
error_log('Bootstrap loaded, imagesDir: ' . $imagesDir);
requireAuth();
error_log('Auth passed');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    error_log('Wrong method: ' . $_SERVER['REQUEST_METHOD']);
    jsonResponse(['error' => 'Méthode non autorisée'], 405);
}

$villa = trim($_POST['villa'] ?? '');
error_log('Villa parameter: ' . $villa);
if (!$villa || !preg_match('/^[a-z0-9_-]+$/', $villa)) {
    error_log('Invalid villa: ' . $villa);
    jsonResponse(['error' => 'Villa invalide'], 400);
}

// Catégorie optionnelle (exterieur par défaut)
$validCats = ['exterieur', 'interieur', 'chambres'];
$category  = trim($_POST['category'] ?? 'exterieur');
if (!in_array ($category, $validCats)) $category = 'exterieur';
error_log('Category: ' . $category);

if (!isset($_FILES['photo']) || $_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
    $codes = [
        UPLOAD_ERR_INI_SIZE   => 'Fichier trop volumineux (limite serveur)',
        UPLOAD_ERR_FORM_SIZE  => 'Fichier trop volumineux (limite formulaire)',
        UPLOAD_ERR_PARTIAL    => 'Upload partiel',
        UPLOAD_ERR_NO_FILE    => 'Aucun fichier reçu',
        UPLOAD_ERR_NO_TMP_DIR => 'Dossier temporaire manquant',
        UPLOAD_ERR_CANT_WRITE => 'Impossible d\'écrire sur le disque',
        UPLOAD_ERR_EXTENSION  => 'Upload bloqué par extension PHP',
    ];
    $err = $_FILES['photo']['error'] ?? -1;
    error_log('Upload error code: ' . $err . ' - ' . ($codes[$err] ?? 'Unknown'));
    jsonResponse(['error' => $codes[$err] ?? 'Échec upload (code '.$err.')'], 400);
}

$file = $_FILES['photo'];
error_log('File info - name: ' . $file['name'] . ', size: ' . $file['size'] . ', tmp_name: ' . $file['tmp_name']);

if ($file['size'] > 8 * 1024 * 1024) {
    error_log('File too large: ' . $file['size']);
    jsonResponse(['error' => 'Fichier trop volumineux (max 8 Mo)'], 400);
}

// Vérifier le type MIME réel
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime  = finfo_file($finfo, $file['tmp_name']);
finfo_close($finfo);
error_log('MIME type: ' . $mime);

$map = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
if (!isset($map[$mime])) {
    error_log('Invalid MIME type: ' . $mime);
    jsonResponse(['error' => 'Format non accepté ('.$mime.'). Utilisez JPG, PNG ou WEBP.'], 400);
}

// Créer le dossier images/{villa}/ si nécessaire
$dir = $imagesDir . '/' . $villa;
error_log('Target directory: ' . $dir . ', exists: ' . (is_dir($dir) ? 'yes' : 'no') . ', writable: ' . (is_writable($imagesDir) ? 'yes' : 'no'));

if (!is_dir($dir)) {
    if (!mkdir($dir, 0755, true)) {
        error_log('Failed to create directory: ' . $dir);
        jsonResponse(['error' => 'Impossible de créer le dossier images/'.$villa.' — vérifiez les droits'], 500);
    }
    error_log('Directory created: ' . $dir);
}

$filename = date('Ymd-His') . '-' . bin2hex(random_bytes(4)) . '.' . $map[$mime];
$dest     = $dir . '/' . $filename;
error_log('Target file: ' . $dest);

if (!move_uploaded_file($file['tmp_name'], $dest)) {
    error_log('Failed to move uploaded file from ' . $file['tmp_name'] . ' to ' . $dest);
    jsonResponse(['error' => 'Impossible de déplacer le fichier uploadé — vérifiez les droits sur images/'], 500);
}
error_log('File moved successfully');

$url = 'images/' . $villa . '/' . $filename;
error_log('Generated URL: ' . $url);

// Mettre à jour properties.json immédiatement
$props = readProperties();
error_log('Properties read, villa exists: ' . (isset($props[$villa]) ? 'yes' : 'no'));

if (isset($props[$villa])) {
    if (!isset($props[$villa]['photos']) || !is_array($props[$villa]['photos'])) {
        $props[$villa]['photos'] = ['exterieur' => [], 'interieur' => [], 'chambres' => []];
    }
    if (!isset($props[$villa]['photos'][$category])) {
        $props[$villa]['photos'][$category] = [];
    }
    $props[$villa]['photos'][$category][] = $url;
    error_log('Adding URL to properties: ' . $url);
    // synchronizePropertyImages est appelé dans writeProperties
    writeProperties($props);
    error_log('Properties written successfully');
} else {
    error_log('Villa not found in properties: ' . $villa);
}

jsonResponse(['ok' => true, 'url' => $url, 'category' => $category]);
