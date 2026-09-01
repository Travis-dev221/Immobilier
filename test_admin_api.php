<?php
// Test script pour vérifier les permissions et la configuration PHP
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Test Configuration PHP</h2>";
echo "<p>PHP Version: " . phpversion() . "</p>";
echo "<p>Upload max filesize: " . ini_get('upload_max_filesize') . "</p>";
echo "<p>Post max size: " . ini_get('post_max_size') . "</p>";
echo "<p>Max execution time: " . ini_get('max_execution_time') . "</p>";

echo "<h2>Extensions GD</h2>";
if (extension_loaded('gd')) {
    echo "<p style='color:green'>✓ Extension GD est chargée</p>";
    $gd_info = gd_info();
    echo "<pre>" . print_r($gd_info, true) . "</pre>";
} else {
    echo "<p style='color:red'>✗ Extension GD n'est PAS chargée</p>";
}

echo "<h2>Permissions d'écriture</h2>";
$dirs = [
    __DIR__ . '/data',
    __DIR__ . '/images',
    __DIR__ . '/images/test'
];

foreach ($dirs as $dir) {
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }
    $writable = is_writable($dir);
    $color = $writable ? 'green' : 'red';
    $symbol = $writable ? '✓' : '✗';
    echo "<p style='color:$color'>$symbol $dir : " . ($writable ? 'Writable' : 'NOT writable') . "</p>";
}

echo "<h2>Test d'écriture</h2>";
$testFile = __DIR__ . '/data/test_write_' . time() . '.txt';
$result = file_put_contents($testFile, 'test');
if ($result !== false) {
    echo "<p style='color:green'>✓ Écriture réussie dans data/</p>";
    unlink($testFile);
} else {
    echo "<p style='color:red'>✗ Écriture échouée dans data/</p>";
}

echo "<h2>Session</h2>";
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
echo "<p>Session ID: " . session_id() . "</p>";
$_SESSION['test'] = 'test_value';
echo "<p>Session write: ✓</p>";

echo "<h2>API Bootstrap Test</h2>";
try {
    require_once __DIR__ . '/api/bootstrap.php';
    echo "<p style='color:green'>✓ Bootstrap chargé avec succès</p>";
    echo "<p>Properties file: " . $propertiesFile . "</p>";
    echo "<p>Images dir: " . $imagesDir . "</p>";
    echo "<p>Properties file exists: " . (file_exists($propertiesFile) ? 'Yes' : 'No') . "</p>";
    echo "<p>Images dir exists: " . (is_dir($imagesDir) ? 'Yes' : 'No') . "</p>";
    echo "<p>Images dir writable: " . (is_writable($imagesDir) ? 'Yes' : 'No') . "</p>";
} catch (Exception $e) {
    echo "<p style='color:red'>✗ Erreur bootstrap: " . $e->getMessage() . "</p>";
}
?>
