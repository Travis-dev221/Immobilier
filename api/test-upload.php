<?php
// Fichier de diagnostic upload — À SUPPRIMER après test
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

$result = [
    'php_version'       => PHP_VERSION,
    'session_name'      => session_name(),
    'session_status'    => session_status(), // 1=none, 2=active
    'method'            => $_SERVER['REQUEST_METHOD'],
    'post'              => $_POST,
    'files'             => [],
    'session_data'      => [],
    'images_dir'        => '',
    'images_writable'   => false,
    'data_dir'          => '',
    'data_writable'     => false,
    'errors'            => [],
];

// Démarrer session avec le bon nom
session_name('admin_root_session');
@session_start();
$result['session_data'] = [
    'admin_logged_in' => $_SESSION['admin_logged_in'] ?? null,
    'admin_space'     => $_SESSION['admin_space'] ?? null,
    'admin_user'      => $_SESSION['admin_user'] ?? null,
];

// Dossiers
$root = dirname(__DIR__);
$imagesDir = $root . '/images';
$dataDir   = $root . '/data';
$result['images_dir']      = $imagesDir;
$result['images_writable'] = is_writable($imagesDir);
$result['data_dir']        = $dataDir;
$result['data_writable']   = is_writable($dataDir);

// Fichiers uploadés
if (!empty($_FILES)) {
    foreach ($_FILES as $key => $f) {
        $result['files'][$key] = [
            'name'  => $f['name'],
            'type'  => $f['type'],
            'size'  => $f['size'],
            'error' => $f['error'], // 0 = OK, voir https://www.php.net/manual/fr/features.file-upload.errors.php
            'tmp'   => $f['tmp_name'],
            'tmp_exists' => file_exists($f['tmp_name']),
        ];
    }
}

// Config upload PHP
$result['upload_max_filesize'] = ini_get('upload_max_filesize');
$result['post_max_size']       = ini_get('post_max_size');
$result['file_uploads']        = ini_get('file_uploads');

echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
