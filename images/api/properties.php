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

$validTypes = ['vacances', 'vente', 'terrain'];

foreach ($input as $key => $villa) {
    if (!is_string($key) || !preg_match('/^[a-z0-9_-]+$/', $key)) {
        jsonResponse(['error' => 'Clé bien invalide : ' . $key], 400);
    }
    if (!is_array($villa)) {
        jsonResponse(['error' => 'Format invalide pour ' . $key], 400);
    }
    if (empty($villa['name'])) {
        jsonResponse(['error' => 'Nom manquant pour ' . $key], 400);
    }
    if (!isset($villa['images']) || !is_array($villa['images'])) {
        jsonResponse(['error' => 'Images manquantes pour ' . $key], 400);
    }
    if (!isset($villa['type']) || !in_array($villa['type'], $validTypes)) {
        jsonResponse(['error' => 'Type invalide pour ' . $key . ' (vacances|vente|terrain)'], 400);
    }
    foreach ($villa['images'] as $url) {
        if (!is_string($url) || $url === '') {
            jsonResponse(['error' => 'URL image invalide pour ' . $key], 400);
        }
    }
}

writeProperties($input);
jsonResponse(['ok' => true]);
