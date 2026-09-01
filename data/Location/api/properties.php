<?php
require __DIR__ . '/bootstrap.php';
requireAuth();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    jsonResponse(readProperties());
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['error' => 'Méthode non autorisée'], 405);
}

$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) {
    jsonResponse(['error' => 'Données invalides'], 400);
}

foreach ($input as $key => $villa) {
    if (!is_string($key) || !preg_match('/^[a-z0-9_-]+$/', $key)) {
        jsonResponse(['error' => 'Clé villa invalide'], 400);
    }
    if (!is_array($villa) || empty($villa['name']) || !isset($villa['images']) || !is_array($villa['images'])) {
        jsonResponse(['error' => 'Format invalide pour ' . $key], 400);
    }
    foreach ($villa['images'] as $url) {
        if (!is_string($url) || $url === '') {
            jsonResponse(['error' => 'URL image invalide'], 400);
        }
    }
}

writeProperties($input);
jsonResponse(['ok' => true]);
