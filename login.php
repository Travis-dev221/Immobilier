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

if (!isset($_SESSION['login_attempts'])) {
    $_SESSION['login_attempts'] = 0;
}
if ($_SESSION['login_attempts'] >= 5) {
    jsonResponse(['error' => 'Compte bloqué après 5 tentatives échouées.', 'locked' => true], 403);
}

$password = readLoginPassword();
$authInfo = verifyPasswordAndGetSpace($password);

if ($authInfo !== false) {
    $_SESSION['login_attempts'] = 0;
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

$_SESSION['login_attempts']++;
$remaining = 5 - $_SESSION['login_attempts'];
if ($remaining > 0) {
    jsonResponse(['error' => 'Mot de passe incorrect. ' . $remaining . ' tentative(s) restante(s).'], 401);
} else {
    jsonResponse(['error' => 'Compte bloqué après 5 tentatives échouées.', 'locked' => true], 403);
}
