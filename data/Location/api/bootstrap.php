<?php
if (session_status() === PHP_SESSION_NONE) {
    @session_start();
}

$root = dirname(__DIR__);
$configFile = $root . '/data/admin.secret.php';
$fallbackConfig = __DIR__ . '/admin.config.php';
$propertiesFile = $root . '/data/properties.json';
$imagesDir = $root . '/images';

function jsonResponse($data, $code = 200) {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function getConfig() {
    global $configFile, $fallbackConfig;
    if (file_exists($configFile)) {
        return require $configFile;
    }
    if (file_exists($fallbackConfig)) {
        return require $fallbackConfig;
    }
    jsonResponse(['error' => 'Config admin introuvable. Uploadez api/admin.config.php ou data/admin.secret.php'], 500);
}

function verifyPassword($password) {
    $password = trim((string) $password);
    if ($password === '') {
        return false;
    }
    $config = getConfig();
    if (!empty($config['password_hash'])) {
        return password_verify($password, $config['password_hash']);
    }
    if (!empty($config['password'])) {
        return hash_equals(trim((string) $config['password']), $password);
    }
    return false;
}

function requireAuth() {
    if (empty($_SESSION['admin_logged_in'])) {
        jsonResponse(['error' => 'Non autorisé'], 401);
    }
}

function readProperties() {
    global $propertiesFile;
    if (!file_exists($propertiesFile)) {
        return [];
    }
    $data = json_decode(file_get_contents($propertiesFile), true);
    return is_array($data) ? $data : [];
}

function writeProperties($data) {
    global $propertiesFile, $root;
    $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if ($json === false || file_put_contents($propertiesFile, $json) === false) {
        jsonResponse(['error' => 'Impossible d\'enregistrer properties.json — vérifiez les droits du dossier data/'], 500);
    }
    $parent = dirname($root);
    $altFile = basename($root) === 'Location'
        ? $parent . '/data/properties.json'
        : $root . '/Location/data/properties.json';
    if (is_dir(dirname($altFile))) {
        @file_put_contents($altFile, $json);
    }
}

function readLoginPassword() {
    $raw = file_get_contents('php://input');
    $input = json_decode($raw, true);
    if (is_array($input) && isset($input['password'])) {
        return $input['password'];
    }
    if (!empty($_POST['password'])) {
        return $_POST['password'];
    }
    return '';
}
