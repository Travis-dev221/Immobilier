<?php
// Diagnostic session — même logique que bootstrap.php
// À SUPPRIMER après diagnostic
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
header('Content-Type: application/json; charset=utf-8');
echo json_encode([
    'session_name'    => session_name(),
    'session_id'      => session_id(),
    'admin_logged_in' => $_SESSION['admin_logged_in'] ?? null,
    'admin_space'     => $_SESSION['admin_space'] ?? null,
    'admin_user'      => $_SESSION['admin_user'] ?? null,
    'all_session_keys'=> array_keys($_SESSION),
    'cookies'         => array_keys($_COOKIE),
    'data_writable'   => is_writable(dirname(__DIR__).'/data'),
    'images_writable' => is_writable(dirname(__DIR__).'/images'),
    'properties_file' => dirname(__DIR__).'/data/properties.json',
    'properties_exists'=> file_exists(dirname(__DIR__).'/data/properties.json'),
    'properties_writable' => is_writable(dirname(__DIR__).'/data/properties.json'),
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
