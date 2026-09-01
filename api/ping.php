<?php
header('Content-Type: application/json; charset=utf-8');

$root = dirname(__DIR__);
$configOk = file_exists($root . '/data/admin.secret.php') || file_exists(__DIR__ . '/admin.config.php');
$dataOk = file_exists($root . '/data/properties.json');
$imagesOk = is_dir($root . '/images') && is_writable($root . '/images');
$dataWritable = is_dir($root . '/data') && is_writable($root . '/data');

echo json_encode([
    'ok' => true,
    'php' => true,
    'version' => PHP_VERSION,
    'config' => $configOk,
    'properties' => $dataOk,
    'images_writable' => $imagesOk,
    'data_writable' => $dataWritable,
], JSON_UNESCAPED_UNICODE);
