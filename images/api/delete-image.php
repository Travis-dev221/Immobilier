<?php
require __DIR__ . '/bootstrap.php';
requireAuth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['error' => 'Méthode non autorisée'], 405);
}

$input = json_decode(file_get_contents('php://input'), true);
$url = $input['url'] ?? '';

if (strpos($url, 'images/') === 0) {
    $path = $root . '/' . $url;
    $realRoot = realpath($imagesDir);
    $realPath = realpath($path);
    if ($realRoot && $realPath && strpos($realPath, $realRoot) === 0 && file_exists($realPath)) {
        unlink($realPath);
    }
}

jsonResponse(['ok' => true]);
