<?php
// Test simple de l'exécution PHP à la racine
header('Content-Type: application/json; charset=utf-8');

echo json_encode([
    'status' => 'success',
    'php_working' => true,
    'php_version' => phpversion(),
    'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'unknown',
    'script_filename' => $_SERVER['SCRIPT_FILENAME'] ?? 'unknown',
    'document_root' => $_SERVER['DOCUMENT_ROOT'] ?? 'unknown',
    'timestamp' => date('Y-m-d H:i:s')
], JSON_PRETTY_PRINT);
