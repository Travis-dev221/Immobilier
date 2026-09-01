<?php
require __DIR__ . '/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    jsonResponse(['logged_in' => !empty($_SESSION['admin_logged_in'])]);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['error' => 'Méthode non autorisée'], 405);
}

$password = readLoginPassword();

if (verifyPassword($password)) {
    $_SESSION['admin_logged_in'] = true;
    jsonResponse(['ok' => true]);
}

jsonResponse(['error' => 'Mot de passe incorrect'], 401);
