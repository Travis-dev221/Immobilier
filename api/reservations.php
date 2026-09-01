<?php
/**
 * API unique de gestion des réservations
 * SYNCHRONISÉ : écrit et lit les DEUX espaces (root + Location/)
 *   → les 2 admins voient les mêmes réservations
 *
 * Endpoints :
 *   POST action=create_request  (public)  → création d'une demande client
 *   GET  action=list            (admin)   → liste demandes + validées (fusion root+Location)
 *   POST action=validate        (admin)   → valide + bloque dates + retourne access_url
 *   POST action=reject          (admin)   → refuse une demande
 *   GET  action=get_by_key      (public)  → récupère une réservation validée par access_key
 */

header('Content-Type: application/json; charset=utf-8');
error_reporting(E_ALL);
ini_set('display_errors', 0);

require __DIR__ . '/bootstrap.php';

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? ($_POST['action'] ?? '');

// ──────────────────────────────────────────────────────────────
// OUTILS
// ──────────────────────────────────────────────────────────────
function adminCheck() {
    if (!empty($_SESSION['admin_logged_in'])) return true;
    safeJsonResponse(['error' => 'Non autorisé — veuillez vous reconnecter'], 401);
}

function safeJsonResponse($data, $code = 200) {
    http_response_code($code);
    echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

// ──────────────────────────────────────────────────────────────
// SYNCHRO ROOT ↔ LOCATION
// ──────────────────────────────────────────────────────────────
$SYNC_PATHS = [
    'root'     => ['dir' => __DIR__ . '/..'],
    'location' => ['dir' => __DIR__ . '/../Location'],
];

$SYNC_FILES_RES = [];
$SYNC_FILES_AVAIL = [];
$SYNC_FILES_PROPS = [];
foreach ($SYNC_PATHS as $k => $p) {
    $SYNC_FILES_RES[$k]   = $p['dir'] . '/data/reservations.json';
    $SYNC_FILES_AVAIL[$k] = $p['dir'] . '/data/availability.json';
    $SYNC_FILES_PROPS[$k] = $p['dir'] . '/data/properties.json';
}

function resMerge($lists) {
    $seen = ['requests' => [], 'validated' => []];
    $out  = ['requests' => [], 'validated' => []];
    foreach (['requests', 'validated'] as $bucket) {
        foreach ($lists as $list) {
            if (empty($list[$bucket]) || !is_array($list[$bucket])) continue;
            foreach ($list[$bucket] as $r) {
                $id = $r['id'] ?? (isset($r['access_key']) ? 'vk:'.$r['access_key'] : spl_object_hash((object)$r));
                if (isset($seen[$bucket][$id])) continue;
                $seen[$bucket][$id] = true;
                $out[$bucket][] = $r;
            }
        }
    }
    return $out;
}

function readReservationsSynced() {
    global $SYNC_FILES_RES;
    $collected = [];
    $defaultEmpty = ['requests' => [], 'validated' => []];
    foreach ($SYNC_FILES_RES as $path) {
        if (!file_exists($path)) { $collected[] = $defaultEmpty; continue; }
        $d = json_decode(@file_get_contents($path), true);
        $collected[] = is_array($d) ? $d : $defaultEmpty;
    }
    return resMerge($collected);
}

function writeReservationsSynced($data) {
    global $SYNC_FILES_RES;
    $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    foreach ($SYNC_FILES_RES as $path) {
        $dir = dirname($path);
        if (!is_dir($dir)) @mkdir($dir, 0755, true);
        @file_put_contents($path, $json);
    }
}

function availMerge($avails) {
    $out = [];
    foreach ($avails as $a) {
        if (!is_array($a)) continue;
        foreach ($a as $villa => $entry) {
            if (!isset($out[$villa])) $out[$villa] = ['blocked_dates' => [], 'reservations' => []];
            if (isset($entry['blocked_dates']) && is_array($entry['blocked_dates'])) {
                foreach ($entry['blocked_dates'] as $bd) {
                    if (!in_array($bd, $out[$villa]['blocked_dates'], true)) $out[$villa]['blocked_dates'][] = $bd;
                }
            }
            if (isset($entry['reservations']) && is_array($entry['reservations'])) {
                $seen = [];
                foreach (array_merge($out[$villa]['reservations'], $entry['reservations']) as $res) {
                    $k = ($res['id'] ?? '') . '|' . ($res['start'] ?? '') . '|' . ($res['end'] ?? '');
                    if (isset($seen[$k])) continue;
                    $seen[$k] = true;
                    $out[$villa]['reservations'][] = $res;
                }
            }
        }
    }
    return $out;
}

function readAvailabilitySynced() {
    global $SYNC_FILES_AVAIL;
    $list = [];
    foreach ($SYNC_FILES_AVAIL as $path) {
        if (!file_exists($path)) continue;
        $d = json_decode(@file_get_contents($path), true);
        if (is_array($d)) $list[] = $d;
    }
    return availMerge($list);
}

function writeAvailabilitySynced($data) {
    global $SYNC_FILES_AVAIL;
    $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    foreach ($SYNC_FILES_AVAIL as $path) {
        $dir = dirname($path);
        if (!is_dir($dir)) @mkdir($dir, 0755, true);
        @file_put_contents($path, $json);
    }
}

function buildAccessUrl($accessKey) {
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $req = $_SERVER['SCRIPT_NAME'] ?? '';
    // part depuis la racine : supprimer /api/reservations.php
    $base = preg_replace('#/api/reservations\.php$#i', '/', $req);
    // Si on est dans /Location/api/reservations.php, base est /Location/
    // On renvoie toujours vers /reservation à la RACINE car c'est le seul
    // fichier reservation existant.
    if (stripos($base, '/Location/') !== false) {
        $base = substr($base, 0, stripos($base, '/Location/')) . '/';
    }
    $base = rtrim($base, '/') . '/';
    return $scheme . '://' . $host . $base . 'reservation?key=' . rawurlencode($accessKey);
}

// ──────────────────────────────────────────────────────────────
// 1) PUBLIC — Création d'une demande de réservation (client)
// ──────────────────────────────────────────────────────────────
if ($method === 'POST' && $action === 'create_request') {
    $villa       = trim($_POST['villa'] ?? '');
    $start       = trim($_POST['start'] ?? '');
    $end         = trim($_POST['end'] ?? '');
    $guests      = intval($_POST['guests'] ?? 0);
    $chef        = trim($_POST['chef'] ?? 'Non');
    $contactM    = trim($_POST['contact_method'] ?? 'whatsapp');
    $firstName   = trim($_POST['first_name'] ?? '');
    $lastName    = trim($_POST['last_name'] ?? '');
    $phone       = trim($_POST['phone'] ?? '');
    $email       = trim($_POST['email'] ?? '');

    if (!$villa || !$start || !$end || !$guests || !$firstName || !$lastName || !$phone || !$email) {
        safeJsonResponse(['error' => 'Champs obligatoires manquants. Veuillez remplir prénom, nom, téléphone, email, dates et nombre de personnes.', 'missing' => array_filter([
            !$villa ? 'villa' : null, !$start ? 'start' : null, !$end ? 'end' : null,
            !$guests ? 'guests' : null, !$firstName ? 'first_name' : null, !$lastName ? 'last_name' : null,
            !$phone ? 'phone' : null, !$email ? 'email' : null
        ])], 400);
    }

    $startTs = strtotime($start);
    $endTs   = strtotime($end);
    if (!$startTs || !$endTs || $startTs >= $endTs) {
        safeJsonResponse(['error' => 'Dates invalides : la date de fin doit être postérieure à la date de début.'], 400);
    }

    // Lecture props depuis l'espace qui contient la villa (plus récent)
    $propsRaw = [];
    global $SYNC_FILES_PROPS;
    foreach ($SYNC_FILES_PROPS as $path) {
        if (!file_exists($path)) continue;
        $d = json_decode(@file_get_contents($path), true);
        if (is_array($d)) $propsRaw[] = $d;
    }
    $props = empty($propsRaw) ? [] : array_merge(...$propsRaw);

    if (!isset($props[$villa])) {
        safeJsonResponse(['error' => 'Bien introuvable : ' . $villa, 'available' => array_keys($props)], 404);
    }

    $avail = readAvailabilitySynced();
    if (!isDateRangeAvailable($villa, $start, $end, $avail)) {
        safeJsonResponse(['error' => 'Ces dates sont déjà réservées ou indisponibles pour ce bien.'], 409);
    }

    $villaData = $props[$villa];
    $nights    = intval(($endTs - $startTs) / 86400);
    $basePrice = intval($villaData['price'] ?? 0);
    $totalAmt  = $basePrice * $nights;

    $reservations = readReservationsSynced();
    $promoCode   = trim($_POST['promo_code'] ?? '');
    $discountPct = floatval($_POST['discount_percent'] ?? 0);
    $discountAmt = floatval($_POST['discount_amount'] ?? 0);

    $req = [
        'id'               => uniqid('RES-', true),
        'created_at'       => date('Y-m-d H:i:s'),
        'villa'            => $villa,
        'villa_name'       => $villaData['name'],
        'start'            => $start,
        'end'              => $end,
        'start_date'       => $start,
        'end_date'         => $end,
        'nights'           => $nights,
        'guests'           => $guests,
        'chef'             => $chef,
        'contact_method'   => $contactM,
        'first_name'       => $firstName,
        'last_name'        => $lastName,
        'phone'            => $phone,
        'email'            => $email,
        'promo_code'       => $promoCode,
        'discount_percent' => $discountPct,
        'discount_amount'  => $discountAmt,
        'status'           => 'pending',
        'base_price'       => $basePrice,
        'total_amount'     => $totalAmt - $discountAmt
    ];

    $reservations['requests'][] = $req;
    writeReservationsSynced($reservations);

    // Update clients database: mark welcome_offer_used & increment count
    $clientsFile = dirname(__DIR__) . '/data/clients.json';
    if (file_exists($clientsFile)) {
        $clients = json_decode(@file_get_contents($clientsFile), true) ?: [];
        $updated = false;
        foreach ($clients as &$c) {
            if (($phone && !empty($c['phone']) && $c['phone'] === $phone) ||
                ($email && !empty($c['email']) && strtolower($c['email']) === strtolower($email))) {
                $c['welcome_offer_used'] = true;
                $c['reservations_count'] = intval($c['reservations_count'] ?? 0) + 1;
                $c['last_reservation']   = date('Y-m-d H:i:s');
                $updated = true;
                break;
            }
        }
        if ($updated) {
            @file_put_contents($clientsFile, json_encode($clients, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        }
    }

    safeJsonResponse([
        'success'    => true,
        'request_id' => $req['id']
    ]);
}

// ──────────────────────────────────────────────────────────────
// 2) ADMIN — Liste complète (fusion root + Location)
// ──────────────────────────────────────────────────────────────
if ($method === 'GET' && $action === 'list') {
    adminCheck();
    $d = readReservationsSynced();
    safeJsonResponse([
        'requests'  => array_values($d['requests'] ?? []),
        'validated' => array_values($d['validated'] ?? []),
        'stats'     => [
            'total_requests'  => count($d['requests'] ?? []),
            'total_validated' => count($d['validated'] ?? []),
        ]
    ]);
}

// ──────────────────────────────────────────────────────────────
// 3) ADMIN — Valider une demande → synchrone dans les 2 espaces
// ──────────────────────────────────────────────────────────────
if ($method === 'POST' && $action === 'validate') {
    adminCheck();

    $requestId = trim($_POST['request_id'] ?? ($_GET['request_id'] ?? ''));
    if (!$requestId) safeJsonResponse(['error' => 'ID de demande manquant'], 400);

    $reservations = readReservationsSynced();
    $found = null;
    $idx   = -1;
    foreach (($reservations['requests'] ?? []) as $i => $r) {
        if (($r['id'] ?? '') === $requestId) { $found = $r; $idx = $i; break; }
    }
    if (!$found) safeJsonResponse(['error' => 'Demande introuvable'], 404);

    $vkey = $found['villa'] ?? '';
    $startStr = $found['start'] ?? $found['start_date'] ?? '';
    $endStr   = $found['end']   ?? $found['end_date']   ?? '';
    $startTs  = strtotime($startStr);
    $endTs    = strtotime($endStr);
    $nights   = ($startTs && $endTs && $endTs > $startTs) ? intval(($endTs - $startTs) / 86400) : 1;
    $found['nights'] = $nights;

    // Recalculer le prix total s'il est à 0 ou absent
    if (empty($found['total_amount']) || intval($found['total_amount']) <= 0) {
        $propsRaw = [];
        global $SYNC_FILES_PROPS;
        foreach ($SYNC_FILES_PROPS as $path) {
            if (!file_exists($path)) continue;
            $d = json_decode(@file_get_contents($path), true);
            if (is_array($d)) $propsRaw[] = $d;
        }
        $props = empty($propsRaw) ? [] : array_merge(...$propsRaw);

        $basePrice = 0;
        if (!empty($vkey) && isset($props[$vkey])) {
            $basePrice = intval($props[$vkey]['price'] ?? 0);
        } else {
            foreach ($props as $pk => $pv) {
                if (isset($pv['name']) && ($pv['name'] === ($found['villa_name'] ?? '') || $pv['name'] === $vkey)) {
                    $basePrice = intval($pv['price'] ?? 0);
                    $found['villa'] = $pk;
                    break;
                }
            }
        }
        $found['base_price'] = $basePrice;
        $found['total_amount'] = $basePrice * $nights;
    }

    $found['status']       = 'validated';
    $found['validated_at'] = date('Y-m-d H:i:s');
    $found['access_key']   = bin2hex(random_bytes(16));
    if (!isset($found['start_date'])) $found['start_date'] = $startStr;
    if (!isset($found['end_date']))   $found['end_date']   = $endStr;
    $found['access_url']   = buildAccessUrl($found['access_key']);

    array_splice($reservations['requests'], $idx, 1);
    $reservations['validated'][] = $found;
    writeReservationsSynced($reservations);

    $avail = readAvailabilitySynced();
    if (!empty($vkey)) {
        if (!isset($avail[$vkey])) $avail[$vkey] = ['blocked_dates' => [], 'reservations' => []];
        $avail[$vkey]['reservations'][] = [
            'id'         => $found['id'],
            'start'      => $startStr,
            'end'        => $endStr,
            'start_date' => $startStr,
            'end_date'   => $endStr,
            'guest_name' => trim(($found['first_name'] ?? '') . ' ' . ($found['last_name'] ?? '')),
            'access_key' => $found['access_key']
        ];
        writeAvailabilitySynced($avail);
    }

    safeJsonResponse([
        'success'      => true,
        'access_key'   => $found['access_key'],
        'access_url'   => $found['access_url'],
        'phone'        => $found['phone'] ?? '',
        'email'        => $found['email'] ?? '',
        'first_name'   => $found['first_name'] ?? '',
        'last_name'    => $found['last_name'] ?? '',
        'villa'        => $found['villa'] ?? '',
        'villa_name'   => $found['villa_name'] ?? $found['villa'] ?? '',
        'start'        => $startStr,
        'end'          => $endStr,
        'start_date'   => $startStr,
        'end_date'     => $endStr,
        'nights'       => $found['nights'],
        'guests'       => $found['guests'] ?? ($found['persons'] ?? 'Non spécifié'),
        'chef'         => $found['chef'] ?? 'Non',
        'total'        => $found['total_amount'],
        'total_amount' => $found['total_amount']
    ]);
}

// ──────────────────────────────────────────────────────────────
// 4) ADMIN — Refuser une demande (sync les 2 espaces)
// ──────────────────────────────────────────────────────────────
if ($method === 'POST' && $action === 'reject') {
    adminCheck();

    $requestId = trim($_POST['request_id'] ?? ($_GET['request_id'] ?? ''));
    if (!$requestId) safeJsonResponse(['error' => 'ID de demande manquant'], 400);

    $reservations = readReservationsSynced();
    $removed = false;
    foreach (($reservations['requests'] ?? []) as $i => $r) {
        if (($r['id'] ?? '') === $requestId) {
            array_splice($reservations['requests'], $i, 1);
            $removed = true;
            break;
        }
    }
    if (!$removed) safeJsonResponse(['error' => 'Demande introuvable'], 404);

    writeReservationsSynced($reservations);
    safeJsonResponse(['success' => true]);
}

// ──────────────────────────────────────────────────────────────
// 5) PUBLIC — Récupérer une réservation validée (fusion root/Location)
// ──────────────────────────────────────────────────────────────
if ($method === 'GET' && $action === 'get_by_key') {
    $key = trim($_GET['key'] ?? '');
    if (!$key) safeJsonResponse(['error' => 'Clé manquante'], 400);

    $d = readReservationsSynced();
    foreach (($d['validated'] ?? []) as $r) {
        if (($r['access_key'] ?? '') === $key) {
            if (!isset($r['access_url'])) $r['access_url'] = buildAccessUrl($r['access_key']);
            safeJsonResponse(['success' => true, 'reservation' => $r]);
        }
    }
    safeJsonResponse(['error' => 'Réservation introuvable'], 404);
}

// ──────────────────────────────────────────────────────────────
// 6) ADMIN — Supprimer une réservation validée (et débloquer les dates)
// ──────────────────────────────────────────────────────────────
if ($method === 'POST' && $action === 'delete_validated') {
    adminCheck();
    $accessKey = trim($_POST['access_key'] ?? '');
    if (!$accessKey) safeJsonResponse(['error' => 'Clé manquante'], 400);

    $reservations = readReservationsSynced();
    $found = null; $idx = -1;
    foreach (($reservations['validated'] ?? []) as $i => $r) {
        if (($r['access_key'] ?? '') === $accessKey) { $found = $r; $idx = $i; break; }
    }
    if (!$found) safeJsonResponse(['error' => 'Réservation validée introuvable'], 404);

    array_splice($reservations['validated'], $idx, 1);
    writeReservationsSynced($reservations);

    // Débloque aussi les dates correspondantes dans availability
    $avail = readAvailabilitySynced();
    if (isset($avail[$found['villa']]['reservations']) && is_array($avail[$found['villa']]['reservations'])) {
        $new = [];
        foreach ($avail[$found['villa']]['reservations'] as $entry) {
            if (($entry['access_key'] ?? '') !== $accessKey) $new[] = $entry;
        }
        $avail[$found['villa']]['reservations'] = $new;
        writeAvailabilitySynced($avail);
    }

    safeJsonResponse(['success' => true]);
}

safeJsonResponse(['error' => 'Action inconnue', 'action' => $action, 'method' => $method], 400);
