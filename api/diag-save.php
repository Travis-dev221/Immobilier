<?php
// Test sauvegarde — À SUPPRIMER après diagnostic
if (session_status() === PHP_SESSION_NONE) session_start();
header('Content-Type: application/json; charset=utf-8');

$root = dirname(__DIR__);
$propertiesFile = $root . '/data/properties.json';

// Lire les données actuelles
$current = [];
if (file_exists($propertiesFile)) {
    $current = json_decode(file_get_contents($propertiesFile), true) ?? [];
}

// Tenter d'écrire un test
$testData = $current;
$testData['_diag_test'] = ['name' => 'Test', 'type' => 'vacances', 'images' => [], 'zone' => '', 'price' => 0, 'priceUnit' => '', 'description' => ''];

$json = json_encode($testData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
$writeResult = file_put_contents($propertiesFile, $json);

// Nettoyer immédiatement
unset($testData['_diag_test']);
file_put_contents($propertiesFile, json_encode($current, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

echo json_encode([
    'session_ok'        => !empty($_SESSION['admin_logged_in']),
    'admin_user'        => $_SESSION['admin_user'] ?? null,
    'file_exists'       => file_exists($propertiesFile),
    'file_writable'     => is_writable($propertiesFile),
    'write_test'        => $writeResult !== false ? 'OK ('.$writeResult.' bytes)' : 'ECHEC',
    'nb_biens'          => count($current),
    'biens_keys'        => array_keys($current),
    'php_version'       => PHP_VERSION,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
