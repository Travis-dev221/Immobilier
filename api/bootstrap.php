<?php
// ── SESSION ───────────────────────────────────────────────────
// On utilise le nom de session par défaut (PHPSESSID) pour compatibilité
// avec tous les hébergements et navigateurs.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ── DÉTECTION DE L'ESPACE ACTIF ───────────────────────────────
// Priorité 1 : session admin (admin connecté)
// Priorité 2 : paramètre villa dans la requête (pour les APIs publiques)
// Priorité 3 : root par défaut
function resolveActiveSpace() {
    // La session est déjà démarrée au bon nom — on lit directement.
    if (!empty($_SESSION['admin_space'])) {
        return $_SESSION['admin_space'];
    }

    // Pour les appels API publics (réservations, disponibilité) : déduire
    // l'espace depuis la villa ou la clé d'accès passée en paramètre.
    $villa = trim((string)($_GET['villa'] ?? $_POST['villa'] ?? ''));
    if ($villa !== '') {
        $base = dirname(__DIR__);
        $rootProps = $base . '/data/properties.json';
        $locProps  = $base . '/Location/data/properties.json';
        if (file_exists($rootProps)) {
            $props = json_decode(file_get_contents($rootProps), true);
            if (is_array($props) && array_key_exists($villa, $props)) {
                return 'root';
            }
        }
        if (file_exists($locProps)) {
            $props = json_decode(file_get_contents($locProps), true);
            if (is_array($props) && array_key_exists($villa, $props)) {
                return 'location';
            }
        }
    }

    return 'root'; // défaut
}

$space = resolveActiveSpace();

if ($space === 'location') {
    $root = dirname(__DIR__) . '/Location';
} else {
    $root = dirname(__DIR__);
}

$configFile         = $root . '/data/admin.secret.php';
$fallbackConfig     = $space === 'location'
    ? dirname(__DIR__) . '/Location/api/admin.config.php'
    : __DIR__ . '/admin.config.php';
$propertiesFile     = $root . '/data/properties.json';
$imagesDir          = $root . '/images';
$availabilityFile   = $root . '/data/availability.json';
$reservationsFile   = $root . '/data/reservations.json';
$invoiceCounterFile = $root . '/data/invoice_counter.json';

// ── HELPERS JSON ──────────────────────────────────────────────
function jsonResponse($data, $code = 200) {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

// ── AUTH ──────────────────────────────────────────────────────
function getSpaceConfig($spaceName) {
    $base = dirname(__DIR__);
    $dir  = $spaceName === 'location' ? $base . '/Location' : $base;
    $secret   = $dir . '/data/admin.secret.php';
    $fallback  = $spaceName === 'location'
        ? $base . '/Location/api/admin.config.php'
        : __DIR__ . '/admin.config.php';
    if (file_exists($secret))   return require $secret;
    if (file_exists($fallback)) return require $fallback;
    return null;
}

function verifyPasswordAndGetSpace($password) {
    $password = trim((string) $password);
    if ($password === '') return false;

    foreach (['root' => 'Dani', 'location' => 'Mactar'] as $spaceName => $user) {
        $cfg = getSpaceConfig($spaceName);
        if (!is_array($cfg)) continue;
        $stored = !empty($cfg['password_hash'])
            ? $cfg['password_hash']
            : trim((string)($cfg['password'] ?? ''));
        $ok = (str_starts_with($stored, '$2y$') || str_starts_with($stored, '$2b$'))
            ? password_verify($password, $stored)
            : hash_equals($stored, $password);
        if ($ok) return ['space' => $spaceName, 'user' => $user];
    }
    return false;
}

function verifyPassword($password) {
    return verifyPasswordAndGetSpace($password) !== false;
}

function requireAuth() {
    if (empty($_SESSION['admin_logged_in'])) {
        jsonResponse(['error' => 'Non autorisé — veuillez vous reconnecter'], 401);
    }
}

// ── PROPRIÉTÉS ────────────────────────────────────────────────
function readProperties() {
    global $propertiesFile;
    if (!file_exists($propertiesFile)) return [];
    $data = json_decode(file_get_contents($propertiesFile), true);
    return is_array($data) ? $data : [];
}

function synchronizePropertyImages(&$villa) {
    $flatImages = [];

    // Priorité 1 : Si images[] plat existe, l'utiliser en premier
    if (isset($villa['images']) && is_array($villa['images'])) {
        foreach ($villa['images'] as $url) {
            if (is_string($url) && trim($url) !== '' && !in_array($url, $flatImages)) {
                $flatImages[] = trim($url);
            }
        }
    }

    // Priorité 2 : Si photos{} catégorisé existe, en extraire toutes les URLs
    if (isset($villa['photos']) && is_array($villa['photos'])) {
        // Si photos est une liste associative avec des catégories
        if (isset($villa['photos']['exterieur']) || isset($villa['photos']['interieur']) || isset($villa['photos']['chambres'])) {
            foreach (['exterieur', 'interieur', 'chambres'] as $cat) {
                if (!empty($villa['photos'][$cat]) && is_array($villa['photos'][$cat])) {
                    foreach ($villa['photos'][$cat] as $url) {
                        if (is_string($url) && trim($url) !== '' && !in_array($url, $flatImages)) {
                            $flatImages[] = trim($url);
                        }
                    }
                }
            }
        } elseif (array_is_list($villa['photos']) || is_array($villa['photos'])) {
            // Si photos est un simple tableau d'URLs
            foreach ($villa['photos'] as $url) {
                if (is_string($url) && trim($url) !== '' && !in_array($url, $flatImages)) {
                    $flatImages[] = trim($url);
                }
            }
        }
    }

    $villa['images'] = $flatImages;
    $villa['photos'] = [
        'exterieur' => $flatImages,
        'interieur' => [],
        'chambres'  => []
    ];
}

function writeProperties($data) {
    global $propertiesFile;
    $dir = dirname($propertiesFile);
    if (!is_dir($dir)) mkdir($dir, 0755, true);

    // Normaliser la structure des images et nettoyer les clés internes
    foreach ($data as $key => &$villa) {
        synchronizePropertyImages($villa);
        unset($villa['_migrated']); // Ne pas persister les clés internes JS
    }
    unset($villa);

    $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if ($json === false || file_put_contents($propertiesFile, $json) === false) {
        jsonResponse(['error' => "Impossible d'enregistrer properties.json — vérifiez les droits d'écriture"], 500);
    }
}

// ── DISPONIBILITÉ ─────────────────────────────────────────────
function readAvailability() {
    global $availabilityFile;
    if (!file_exists($availabilityFile)) return [];
    $data = json_decode(file_get_contents($availabilityFile), true);
    return is_array($data) ? $data : [];
}

function writeAvailability($data) {
    global $availabilityFile;
    $dir = dirname($availabilityFile);
    if (!is_dir($dir)) mkdir($dir, 0755, true);
    file_put_contents($availabilityFile, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
}

// ── RÉSERVATIONS ──────────────────────────────────────────────
function readReservations() {
    global $reservationsFile;
    if (!file_exists($reservationsFile)) return ['requests' => [], 'validated' => []];
    $data = json_decode(file_get_contents($reservationsFile), true);
    return is_array($data) ? $data : ['requests' => [], 'validated' => []];
}

function writeReservations($data) {
    global $reservationsFile;
    $dir = dirname($reservationsFile);
    if (!is_dir($dir)) mkdir($dir, 0755, true);
    file_put_contents($reservationsFile, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
}

// ── VÉRIFICATION DISPONIBILITÉ ────────────────────────────────
function isDateRangeAvailable($villa, $start, $end, $availability) {
    if (!isset($availability[$villa])) {
        return true;
    }
    $blocked = $availability[$villa]['blocked_dates'] ?? [];
    $reservations = $availability[$villa]['reservations'] ?? [];
    $current = strtotime($start);
    $endDate = strtotime($end);
    while ($current <= $endDate) {
        $dateStr = date('Y-m-d', $current);
        if (in_array($dateStr, $blocked)) {
            return false;
        }
        foreach ($reservations as $res) {
            if ($dateStr >= $res['start'] && $dateStr <= $res['end']) {
                return false;
            }
        }
        $current = strtotime('+1 day', $current);
    }
    return true;
}

// ── COMPTEUR FACTURES ─────────────────────────────────────────
function readInvoiceCounter() {
    global $invoiceCounterFile;
    if (!file_exists($invoiceCounterFile)) return ['last_number' => 0, 'year' => date('Y')];
    $data = json_decode(file_get_contents($invoiceCounterFile), true);
    return is_array($data) ? $data : ['last_number' => 0, 'year' => date('Y')];
}

function writeInvoiceCounter($data) {
    global $invoiceCounterFile;
    $dir = dirname($invoiceCounterFile);
    if (!is_dir($dir)) mkdir($dir, 0755, true);
    file_put_contents($invoiceCounterFile, json_encode($data, JSON_PRETTY_PRINT));
}

// ── LECTURE MOT DE PASSE ──────────────────────────────────────
function readLoginPassword() {
    $raw   = file_get_contents('php://input');
    $input = json_decode($raw, true);
    if (is_array($input) && isset($input['password'])) return $input['password'];
    if (!empty($_POST['password'])) return $_POST['password'];
    return '';
}
