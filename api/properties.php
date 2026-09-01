<?php
error_log('Properties.php called - METHOD: ' . $_SERVER['REQUEST_METHOD']);
require __DIR__ . '/bootstrap.php';
error_log('Bootstrap loaded, propertiesFile: ' . $propertiesFile);
requireAuth();
error_log('Auth passed');

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $props = readProperties();
    error_log('Returning properties, count: ' . count($props));
    jsonResponse($props);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    error_log('Wrong method: ' . $_SERVER['REQUEST_METHOD']);
    jsonResponse(['error' => 'Méthode non autorisée'], 405);
}

$input = json_decode(file_get_contents('php://input'), true);
error_log('Input received, is_array: ' . (is_array($input) ? 'yes' : 'no'));

if (!is_array($input)) {
    error_log('Invalid input data');
    jsonResponse(['error' => 'Données invalides'], 400);
}

$validTypes = ['vacances', 'vente', 'terrain'];
error_log('Validating ' . count($input) . ' properties');

foreach ($input as $key => $villa) {
    if (!is_string($key) || !preg_match('/^[a-z0-9_-]+$/', $key)) {
        error_log('Invalid key: ' . $key);
        jsonResponse(['error' => 'Clé bien invalide : ' . $key], 400);
    }
    if (!is_array($villa)) {
        error_log('Invalid format for key: ' . $key);
        jsonResponse(['error' => 'Format invalide pour ' . $key], 400);
    }
    if (empty($villa['name'])) {
        error_log('Missing name for key: ' . $key);
        jsonResponse(['error' => 'Nom manquant pour ' . $key], 400);
    }
    if (!isset($villa['type']) || !in_array($villa['type'], $validTypes)) {
        error_log('Invalid type for key: ' . $key . ', type: ' . ($villa['type'] ?? 'null'));
        jsonResponse(['error' => 'Type invalide pour ' . $key . ' (vacances|vente|terrain)'], 400);
    }
}

error_log('All validations passed, calling writeProperties');
// writeProperties normalise automatiquement images[] et photos{} via synchronizePropertyImages
writeProperties($input);
error_log('Properties written successfully');
jsonResponse(['ok' => true]);
