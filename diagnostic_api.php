<?php
// Script de diagnostic pour l'API de réservation
header('Content-Type: text/html; charset=UTF-8');
error_reporting(E_ALL);
ini_set('display_errors', 1);

$issues = [];
$warnings = [];

// Test 1: Vérification de la configuration PHP
$phpVersion = phpversion();
if (version_compare($phpVersion, '7.4', '<')) {
    $issues[] = "Version PHP trop ancienne: $phpVersion (minimum 7.4 requis)";
} else {
    $warnings[] = "Version PHP: $phpVersion (OK)";
}

// Test 2: Extensions requises
$requiredExtensions = ['json', 'session', 'fileinfo'];
foreach ($requiredExtensions as $ext) {
    if (!extension_loaded($ext)) {
        $issues[] = "Extension PHP manquante: $ext";
    }
}

// Test 3: Vérification des fichiers et dossiers
$requiredFiles = [
    __DIR__ . '/api/bootstrap.php' => 'Fichier bootstrap.php',
    __DIR__ . '/api/reservations.php' => 'Fichier reservations.php',
    __DIR__ . '/data/properties.json' => 'Fichier properties.json',
    __DIR__ . '/data/reservations.json' => 'Fichier reservations.json',
    __DIR__ . '/data/availability.json' => 'Fichier availability.json',
];

foreach ($requiredFiles as $file => $description) {
    if (!file_exists($file)) {
        $issues[] = "$description manquant: $file";
    } elseif (!is_readable($file)) {
        $issues[] = "$description non lisible: $file";
    }
}

// Test 4: Permissions d'écriture
$writableDirs = [
    __DIR__ . '/data' => 'Dossier data',
    __DIR__ . '/api' => 'Dossier api',
];

foreach ($writableDirs as $dir => $description) {
    if (!is_writable($dir)) {
        $issues[] = "$description non inscriptible: $dir";
    }
}

// Test 4.5: Permissions de lecture du dossier api
if (!is_readable(__DIR__ . '/api')) {
    $issues[] = "Dossier api non lisible";
}

// Test 5: Validation des fichiers JSON
$jsonFiles = [
    __DIR__ . '/data/properties.json' => 'properties.json',
    __DIR__ . '/data/reservations.json' => 'reservations.json',
    __DIR__ . '/data/availability.json' => 'availability.json',
];

foreach ($jsonFiles as $file => $name) {
    if (file_exists($file)) {
        $content = file_get_contents($file);
        $data = json_decode($content, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $issues[] = "Fichier $name invalide: " . json_last_error_msg();
        }
    }
}

// Test 6: Test de base PHP dans le dossier api
$testPhpUrl = 'api/test.php';
$testPhpResult = @file_get_contents($testPhpUrl);
if ($testPhpResult === false) {
    $issues[] = "Le fichier test.php dans le dossier api ne s'exécute pas (PHP non configuré correctement)";
} else {
    $testPhpData = json_decode($testPhpResult, true);
    if (!$testPhpData || !isset($testPhpData['php_working'])) {
        $issues[] = "Le fichier test.php ne retourne pas le format attendu: " . substr($testPhpResult, 0, 200);
    } else {
        $warnings[] = "PHP s'exécute correctement dans le dossier api (version: " . $testPhpData['php_version'] . ")";
    }
}

// Test 7: Test de l'API availability
$availabilityUrl = 'api/availability.php?action=get';
$availabilityTest = @file_get_contents($availabilityUrl);
if ($availabilityTest === false) {
    $issues[] = "L'API availability ne répond pas (URL: $availabilityUrl)";
} else {
    $availabilityData = json_decode($availabilityTest, true);
    if (!$availabilityData) {
        $issues[] = "L'API availability retourne un JSON invalide";
    } else {
        $warnings[] = "API availability fonctionne correctement";
    }
}

// Test 7: Vérification de la configuration .htaccess
$htaccessContent = @file_get_contents(__DIR__ . '/api/.htaccess');
if ($htaccessContent === false) {
    $warnings[] = "Fichier .htaccess manquant dans le dossier api";
} else {
    if (strpos($htaccessContent, 'AddType application/x-httpd-php .php') === false) {
        $warnings[] = "Le fichier .htaccess ne force pas l'exécution PHP";
    }
}

// Test 8: Test direct de l'API reservations avec une requête POST simulée
$postData = http_build_query([
    'action' => 'create_request',
    'villa' => 'guede',
    'start' => '2026-08-01',
    'end' => '2026-08-03',
    'guests' => '2',
    'chef' => 'Non',
    'contact_method' => 'whatsapp',
    'first_name' => 'Test',
    'last_name' => 'Diagnostic',
    'phone' => '770000000',
    'email' => 'test@example.com'
]);

$context = stream_context_create([
    'http' => [
        'method' => 'POST',
        'header' => 'Content-Type: application/x-www-form-urlencoded',
        'content' => $postData,
        'timeout' => 10
    ]
]);

$reservationsTest = @file_get_contents('http://localhost' . dirname($_SERVER['SCRIPT_NAME']) . '/api/reservations.php', false, $context);
if ($reservationsTest === false) {
    $issues[] = "L'API reservations ne répond pas (erreur de connexion)";
} else {
    $reservationsData = json_decode($reservationsTest, true);
    if (!$reservationsData) {
        $issues[] = "L'API reservations retourne un JSON invalide: " . substr($reservationsTest, 0, 200);
    } elseif (isset($reservationsData['error'])) {
        $warnings[] = "L'API reservations fonctionne mais retourne une erreur: " . $reservationsData['error'];
    } else {
        $warnings[] = "API reservations fonctionne correctement";
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <link rel="icon" href="favicon.ico" type="image/x-icon">
  <link rel="shortcut icon" href="favicon.ico" type="image/x-icon">
  <link rel="apple-touch-icon" href="favicon.png">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Diagnostic API Réservation</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 900px; margin: 30px auto; padding: 20px; background: #f5f5f5; }
        .section { background: white; padding: 20px; margin-bottom: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        h1 { color: #333; border-bottom: 3px solid #007bff; padding-bottom: 10px; }
        h2 { color: #555; margin-top: 0; }
        .error { color: #d32f2f; background: #ffebee; padding: 10px; border-left: 4px solid #d32f2f; margin: 10px 0; }
        .warning { color: #f57c00; background: #fff3e0; padding: 10px; border-left: 4px solid #f57c00; margin: 10px 0; }
        .success { color: #388e3c; background: #e8f5e9; padding: 10px; border-left: 4px solid #388e3c; margin: 10px 0; }
        .info { color: #1976d2; background: #e3f2fd; padding: 10px; border-left: 4px solid #1976d2; margin: 10px 0; }
        pre { background: #f4f4f4; padding: 10px; border-radius: 4px; overflow-x: auto; font-size: 12px; }
        .status-badge { display: inline-block; padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: bold; }
        .status-ok { background: #e8f5e9; color: #388e3c; }
        .status-error { background: #ffebee; color: #d32f2f; }
        .status-warning { background: #fff3e0; color: #f57c00; }
    </style>
</head>
<body>
    <h1>🔍 Diagnostic API Réservation</h1>
    
    <div class="section">
        <h2>Statut général</h2>
        <?php if (empty($issues)): ?>
            <div class="success">
                <strong>✓ Aucune erreur critique détectée</strong>
            </div>
        <?php else: ?>
            <div class="error">
                <strong>✗ <?= count($issues) ?> erreur(s) critique(s) détectée(s)</strong>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($warnings)): ?>
            <div class="warning">
                <strong>⚠ <?= count($warnings) ?> avertissement(s)</strong>
            </div>
        <?php endif; ?>
    </div>
    
    <?php if (!empty($issues)): ?>
    <div class="section">
        <h2>Erreurs critiques</h2>
        <?php foreach ($issues as $issue): ?>
            <div class="error">❌ <?= htmlspecialchars($issue) ?></div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
    
    <?php if (!empty($warnings)): ?>
    <div class="section">
        <h2>Avertissements</h2>
        <?php foreach ($warnings as $warning): ?>
            <div class="warning">⚠️ <?= htmlspecialchars($warning) ?></div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
    
    <div class="section">
        <h2>Informations système</h2>
        <table style="width: 100%; border-collapse: collapse;">
            <tr>
                <td style="padding: 8px; border-bottom: 1px solid #ddd;"><strong>Version PHP:</strong></td>
                <td style="padding: 8px; border-bottom: 1px solid #ddd;"><?= $phpVersion ?></td>
            </tr>
            <tr>
                <td style="padding: 8px; border-bottom: 1px solid #ddd;"><strong>Serveur:</strong></td>
                <td style="padding: 8px; border-bottom: 1px solid #ddd;"><?= $_SERVER['SERVER_SOFTWARE'] ?? 'Inconnu' ?></td>
            </tr>
            <tr>
                <td style="padding: 8px; border-bottom: 1px solid #ddd;"><strong>Racine document:</strong></td>
                <td style="padding: 8px; border-bottom: 1px solid #ddd;"><?= $_SERVER['DOCUMENT_ROOT'] ?? 'Inconnu' ?></td>
            </tr>
            <tr>
                <td style="padding: 8px; border-bottom: 1px solid #ddd;"><strong>Script actuel:</strong></td>
                <td style="padding: 8px; border-bottom: 1px solid #ddd;"><?= $_SERVER['SCRIPT_FILENAME'] ?></td>
            </tr>
        </table>
    </div>
    
    <div class="section">
        <h2>Actions recommandées</h2>
        <?php if (!empty($issues)): ?>
            <div class="error">
                <strong>Corrigez les erreurs critiques ci-dessus avant de continuer.</strong>
            </div>
        <?php elseif (!empty($warnings)): ?>
            <div class="warning">
                <strong>Vérifiez les avertissements pour optimiser le fonctionnement.</strong>
            </div>
        <?php else: ?>
            <div class="success">
                <strong>✓ Tout semble correct. Essayez de faire une réservation depuis le site principal.</strong>
            </div>
        <?php endif; ?>
        
        <div class="info" style="margin-top: 20px;">
            <strong>Si le problème persiste:</strong>
            <ul style="margin: 10px 0 0 20px;">
                <li>Vérifiez les logs du serveur pour les erreurs PHP</li>
                <li>Assurez-vous que le module Apache mod_rewrite est activé</li>
                <li>Vérifiez que PHP est correctement configuré pour exécuter les fichiers .php</li>
                <li>Contactez votre hébergeur si vous êtes sur un serveur mutualisé</li>
            </ul>
        </div>
    </div>
    
    <div class="section">
        <h2>Test manuel de l'API</h2>
        <p>Cliquez sur le bouton ci-dessous pour tester l'API directement depuis votre navigateur:</p>
        <button onclick="testApi()" style="padding: 10px 20px; background: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer;">Tester l'API</button>
        <div id="apiTestResult" style="margin-top: 15px;"></div>
    </div>
    
    <script>
        function testApi() {
            const resultDiv = document.getElementById('apiTestResult');
            resultDiv.innerHTML = '<p style="color: #666;">Test en cours...</p>';
            
            const formData = new FormData();
            formData.append('action', 'create_request');
            formData.append('villa', 'guede');
            formData.append('start', '2026-08-01');
            formData.append('end', '2026-08-03');
            formData.append('guests', '2');
            formData.append('chef', 'Non');
            formData.append('contact_method', 'whatsapp');
            formData.append('first_name', 'Test');
            formData.append('last_name', 'Diagnostic');
            formData.append('phone', '770000000');
            formData.append('email', 'test@example.com');
            
            fetch('api/reservations.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                const status = response.status;
                const statusText = response.statusText;
                return response.text().then(text => ({ status, statusText, text }));
            })
            .then(({status, statusText, text}) => {
                try {
                    const data = JSON.parse(text);
                    if (data.success) {
                        resultDiv.innerHTML = '<div class="success">✓ API fonctionne correctement! Réponse: ' + JSON.stringify(data) + '</div>';
                    } else {
                        resultDiv.innerHTML = '<div class="warning">⚠️ API répond mais retourne une erreur: ' + (data.error || 'Erreur inconnue') + '</div>';
                    }
                } catch (e) {
                    resultDiv.innerHTML = '<div class="error">✗ Réponse invalide (HTTP ' + status + '): ' + text.substring(0, 500) + '</div>';
                }
            })
            .catch(error => {
                resultDiv.innerHTML = '<div class="error">✗ Erreur de connexion: ' + error.message + '</div>';
            });
        }
    </script>
</body>
</html>
