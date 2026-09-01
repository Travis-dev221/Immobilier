<?php
header('Content-Type: text/html; charset=utf-8');
echo '<pre style="font-family:monospace;background:#111;color:#0f0;padding:20px;font-size:.9rem;line-height:1.5;">';
echo "=== DIAGNOSTIC COMPLET BAOBAB HORIZON ===\n\n";
echo "PHP Version : " . PHP_VERSION . "\n";
echo "OS          : " . PHP_OS . "\n";
echo "Server Software : " . ($_SERVER['SERVER_SOFTWARE'] ?? 'N/A') . "\n";
echo "Directory   : " . __DIR__ . "\n\n";

// --- TEST SESSION ---
echo "=== TEST DES SESSIONS PHP ===\n";
$sessionName = 'admin_root_session';
session_name($sessionName);
$sessionStarted = @session_start();
echo "Démarrage session : " . ($sessionStarted ? "✅ Réussi" : "❌ Échoué") . "\n";
echo "Session ID        : " . session_id() . "\n";
$_SESSION['diag_test'] = 'ok_' . time();
echo "Écriture session  : " . (isset($_SESSION['diag_test']) ? "✅ Réussi" : "❌ Échoué") . "\n\n";

// --- STRUCTURE ET DROITS DES DOSSIERS ---
echo "=== FIABILITÉ DU DOSSIER DATA ===\n";
$dataDir = __DIR__ . '/data';
echo "Dossier data/ existe : " . (is_dir($dataDir) ? "✅ Oui" : "❌ Non (Sera créé au besoin)") . "\n";
if (is_dir($dataDir)) {
    echo "Dossier data/ inscriptible : " . (is_writable($dataDir) ? "✅ Oui" : "❌ Non (Vérifier les permissions CHMOD 755)") . "\n";
}

// --- VERIFICATION ECRITURE DES FICHIERS ---
echo "=== DROITS D'ECRITURE SUR LES FICHIERS DE DONNEES ===\n";
function checkWritableFile($filePath, $label) {
    if (file_exists($filePath)) {
        echo "  $label (" . basename($filePath) . ") : " . (is_writable($filePath) ? "✅ Inscriptible" : "❌ Protégé en écriture (Vérifier CHMOD / Permissions)") . "\n";
    } else {
        echo "  $label (" . basename($filePath) . ") : ⚠️ Inexistant (Sera créé à la première écriture)\n";
    }
}
checkWritableFile(__DIR__ . '/data/properties.json', 'Propriétés');
checkWritableFile(__DIR__ . '/data/reservations.json', 'Réservations');
checkWritableFile(__DIR__ . '/data/availability.json', 'Disponibilités');
checkWritableFile(__DIR__ . '/data/invoice_counter.json', 'Compteur factures');
echo "\n";

// --- FICHIERS CONFIGS ---
echo "=== DIAGNOSTIC DES CONFIGURATIONS ADMIN ===\n";

function inspectConfig($space, $secretFile, $fallbackFile) {
    echo "--- Espace : " . strtoupper($space) . " ---\n";
    echo "  Fichier secret : $secretFile\n";
    if (file_exists($secretFile)) {
        echo "    ✅ Existe\n";
        echo "    Lisible par PHP : " . (is_readable($secretFile) ? "✅ Oui" : "❌ Non (Permissions incorrectes)") . "\n";
        $cfg = @include $secretFile;
        echo "    Retourne un tableau : " . (is_array($cfg) ? "✅ Oui" : "❌ Non (Reçoit : " . gettype($cfg) . ")") . "\n";
        if (is_array($cfg)) {
            echo "    Clé 'password' présente : " . (isset($cfg['password']) ? "✅ Oui" : "❌ Non") . "\n";
            echo "    Clé 'password_hash' présente : " . (isset($cfg['password_hash']) ? "✅ Oui" : "❌ Non") . "\n";
        }
    } else {
        echo "    ❌ Absent (Sera ignoré)\n";
    }
    
    echo "  Fichier fallback : $fallbackFile\n";
    if (file_exists($fallbackFile)) {
        echo "    ✅ Existe\n";
        $cfg = @include $fallbackFile;
        echo "    Retourne un tableau : " . (is_array($cfg) ? "✅ Oui" : "❌ Non") . "\n";
    } else {
        echo "    ❌ Absent\n";
    }
    echo "\n";
}

inspectConfig('principal (Dani)', __DIR__ . '/data/admin.secret.php', __DIR__ . '/api/admin.config.php');
inspectConfig('location (Mactar)', __DIR__ . '/Location/data/admin.secret.php', __DIR__ . '/Location/api/admin.config.php');

// --- TESTEUR DE MOT DE PASSE ---
echo "=== TESTEUR DE MOT DE PASSE ===\n";
if (isset($_GET['test_pwd'])) {
    $submitted = $_GET['test_pwd'];
    echo "Mot de passe soumis à tester : '" . htmlspecialchars($submitted) . "'\n";
    
    // Test verification logic manually
    $base = __DIR__;
    
    // Check root
    $secret = $base . '/data/admin.secret.php';
    $fallback = $base . '/api/admin.config.php';
    $cfgRoot = file_exists($secret) ? @include $secret : (file_exists($fallback) ? @include $fallback : null);
    
    if (is_array($cfgRoot)) {
        $stored = !empty($cfgRoot['password_hash']) ? $cfgRoot['password_hash'] : trim((string)($cfgRoot['password'] ?? ''));
        $ok = str_starts_with($stored, '$2y$') || str_starts_with($stored, '$2b$')
            ? password_verify($submitted, $stored)
            : hash_equals($stored, $submitted);
        if ($ok) {
            echo "✅ MATCH avec l'Espace Principal (Dani) !\n";
        } else {
            echo "❌ ÉCHEC de correspondance avec l'Espace Principal (Dani).\n";
            echo "   (Password stocké dans la config : '" . (empty($cfgRoot['password_hash']) ? htmlspecialchars($stored) : "[HASH BCRYPT]") . "')\n";
        }
    } else {
        echo "❌ Impossible de lire la config de l'Espace Principal.\n";
    }
    
    // Check location
    $secretLoc = $base . '/Location/data/admin.secret.php';
    $fallbackLoc = $base . '/Location/api/admin.config.php';
    $cfgLoc = file_exists($secretLoc) ? @include $secretLoc : (file_exists($fallbackLoc) ? @include $fallbackLoc : null);
    
    if (is_array($cfgLoc)) {
        $stored = !empty($cfgLoc['password_hash']) ? $cfgLoc['password_hash'] : trim((string)($cfgLoc['password'] ?? ''));
        $ok = str_starts_with($stored, '$2y$') || str_starts_with($stored, '$2b$')
            ? password_verify($submitted, $stored)
            : hash_equals($stored, $submitted);
        if ($ok) {
            echo "✅ MATCH avec l'Espace Location (Mactar) !\n";
        } else {
            echo "❌ ÉCHEC de correspondance avec l'Espace Location (Mactar).\n";
            echo "   (Password stocké dans la config : '" . (empty($cfgLoc['password_hash']) ? htmlspecialchars($stored) : "[HASH BCRYPT]") . "')\n";
        }
    } else {
        echo "❌ Impossible de lire la config de l'Espace Location.\n";
    }
} else {
    echo "Pour tester un mot de passe en direct, ajoutez le paramètre test_pwd à l'URL de cette page.\n";
    echo "Exemple : diag.php?test_pwd=VotreMotDePasse\n";
}

echo "\n=== FICHIERS LOGIQUES ASSOCIES ===\n";
$checks = ['admin.php','admin/panel.js','admin/index.html','api/ping.php','api/login.php','data/properties.json','data/reservations.json'];
foreach ($checks as $f) {
    echo (file_exists(__DIR__.'/'.$f) ? "✅ " : "❌ ") . $f . "\n";
}

echo '</pre>';
