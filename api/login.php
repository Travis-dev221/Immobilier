<?php
require __DIR__ . '/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    jsonResponse([
        'logged_in' => !empty($_SESSION['admin_logged_in']),
        'user' => $_SESSION['admin_user'] ?? '',
        'space' => $_SESSION['admin_space'] ?? ''
    ]);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['error' => 'Méthode non autorisée'], 405);
}

$blockFile = dirname(__DIR__) . '/data/blocked_ips.json';
$ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
$attempts = file_exists($blockFile) ? json_decode(file_get_contents($blockFile), true) : [];

if (($attempts[$ip] ?? 0) >= 5) {
    jsonResponse(['error' => 'Compte bloqué après 5 tentatives échouées.', 'locked' => true], 403);
}

$password = readLoginPassword();
$authInfo = verifyPasswordAndGetSpace($password);

if ($authInfo !== false) {
    if (isset($attempts[$ip])) {
        unset($attempts[$ip]);
        file_put_contents($blockFile, json_encode($attempts));
    }
    $_SESSION['admin_logged_in'] = true;
    $_SESSION['admin_space'] = $authInfo['space'];
    $_SESSION['admin_user'] = $authInfo['user'];
    $_SESSION['admin'] = true; // Compatibilité
    
    jsonResponse([
        'ok' => true,
        'user' => $authInfo['user'],
        'space' => $authInfo['space']
    ]);
}

$attempts[$ip] = ($attempts[$ip] ?? 0) + 1;
file_put_contents($blockFile, json_encode($attempts));

$remaining = 5 - $attempts[$ip];
if ($remaining > 0) {
    jsonResponse(['error' => 'Mot de passe incorrect. ' . $remaining . ' tentative(s) restante(s).'], 401);
} else {
    jsonResponse(['error' => 'Compte bloqué après 5 tentatives échouées.', 'locked' => true], 403);
}
