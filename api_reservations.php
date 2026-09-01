<?php
// Proxy API autonome pour les réservations - solution LiteSpeed
header('Content-Type: application/json; charset=utf-8');
session_start();

// Error handler pour capturer les erreurs PHP
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    error_log("PHP Error: [$errno] $errstr in $errfile on line $errline");
    echo json_encode(['error' => 'PHP Error: ' . $errstr, 'file' => $errfile, 'line' => $errline]);
    exit;
});

// Exception handler
set_exception_handler(function($exception) {
    error_log("Uncaught exception: " . $exception->getMessage());
    echo json_encode(['error' => 'Exception: ' . $exception->getMessage()]);
    exit;
});

// Détection de l'espace actif
function resolveActiveSpace() {
    if (!empty($_SESSION['admin_space'])) {
        return $_SESSION['admin_space'];
    }
    return 'root';
}

$space = resolveActiveSpace();

if ($space === 'location') {
    $root = __DIR__ . '/Location';
} else {
    $root = __DIR__;
}

$propertiesFile = $root . '/data/properties.json';
$availabilityFile = $root . '/data/availability.json';
$reservationsFile = $root . '/data/reservations.json';

function readProperties() {
    global $propertiesFile;
    error_log("DEBUG: propertiesFile path = " . $propertiesFile);
    error_log("DEBUG: file_exists = " . (file_exists($propertiesFile) ? 'true' : 'false'));
    if (!file_exists($propertiesFile)) {
        error_log("DEBUG: Properties file not found at: " . $propertiesFile);
        return [];
    }
    $data = json_decode(file_get_contents($propertiesFile), true);
    error_log("DEBUG: Properties data keys = " . implode(', ', array_keys($data ?? [])));
    return is_array($data) ? $data : [];
}

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

// Copie du code de api/reservations.php
function generateUniqueId() {
    return uniqid('RES-', true);
}

function generateAccessKey() {
    return bin2hex(random_bytes(16));
}

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

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];

function buildReservationUrl($accessKey) {
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $base = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/\\');
    return $scheme . '://' . $host . $base . '/reservation.php?key=' . urlencode($accessKey);
}

function notifyClientValidation($request, $reservationUrl) {
    $name = trim(($request['first_name'] ?? '') . ' ' . ($request['last_name'] ?? ''));
    $message = "Bonjour {$name},\n\n"
        . "Votre demande de réservation pour {$request['villa_name']} a été validée par Baobab Horizon.\n\n"
        . "Dates : {$request['start']} → {$request['end']}\n"
        . "Personnes : {$request['guests']}\n"
        . "Chef cuisinier : {$request['chef']}\n\n"
        . "Accédez à votre espace privé pour compléter vos informations et recevoir votre facture :\n"
        . $reservationUrl . "\n\n"
        . "Baobab Horizon";

    $method = $request['contact_method'] ?? 'whatsapp';

    if ($method === 'email' && !empty($request['email'])) {
        $headers = "From: Baobab Horizon <baobabhorizon@gmail.com>\r\n"
            . "MIME-Version: 1.0\r\n"
            . "Content-Type: text/plain; charset=UTF-8\r\n";
        @mail(
            $request['email'],
            'Réservation validée — Baobab Horizon',
            $message,
            $headers
        );
        return ['method' => 'email'];
    }

    $phone = preg_replace('/[^0-9]/', '', $request['phone'] ?? '');
    if ($phone && substr($phone, 0, 3) !== '221') {
        $phone = '221' . $phone;
    }
    return [
        'method' => 'whatsapp',
        'url' => 'https://wa.me/' . $phone . '?text=' . rawurlencode($message)
    ];
}

// Public endpoint - create reservation request
if ($method === 'POST' && $action === 'create_request') {
    error_log("RESERVATION: Received create_request");
    $villa = $_POST['villa'] ?? '';
    $start = $_POST['start'] ?? '';
    $end = $_POST['end'] ?? '';
    $guests = intval($_POST['guests'] ?? 0);
    $chef = $_POST['chef'] ?? 'Non';
    $contactMethod = $_POST['contact_method'] ?? 'whatsapp';
    $firstName = trim($_POST['first_name'] ?? '');
    $lastName = trim($_POST['last_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');

    error_log("RESERVATION: Data - villa=$villa, start=$start, end=$end, guests=$guests, firstName=$firstName, lastName=$lastName");

    if (!$villa || !$start || !$end || !$guests || !$firstName || !$lastName || !$phone || !$email) {
        error_log("RESERVATION: Missing required fields");
        http_response_code(400);
        echo json_encode(['error' => 'Missing required fields']);
        exit;
    }
    
    $startDate = strtotime($start);
    $endDate = strtotime($end);
    
    if ($startDate >= $endDate) {
        http_response_code(400);
        echo json_encode(['error' => 'End date must be after start date']);
        exit;
    }
    
    $availability = readAvailability();
    error_log("RESERVATION: Availability loaded");
    if (!isDateRangeAvailable($villa, $start, $end, $availability)) {
        error_log("RESERVATION: Dates not available");
        http_response_code(409);
        echo json_encode(['error' => 'Dates not available']);
        exit;
    }

    $properties = readProperties();
    error_log("RESERVATION: Properties loaded");
    if (!isset($properties[$villa])) {
        error_log("RESERVATION: Villa not found: $villa");
        error_log("RESERVATION: Available villas: " . implode(', ', array_keys($properties)));
        http_response_code(404);
        echo json_encode([
            'error' => 'Villa not found',
            'requested_villa' => $villa,
            'available_villas' => array_keys($properties),
            'debug' => [
                'properties_file' => $propertiesFile,
                'file_exists' => file_exists($propertiesFile),
                'properties_count' => count($properties)
            ]
        ]);
        exit;
    }

    $villaData = $properties[$villa];
    $nights = intval(($endDate - $startDate) / 86400);
    $basePrice = intval($villaData['price'] ?? 0);
    $totalAmount = $basePrice * $nights;

    error_log("RESERVATION: Creating request - nights=$nights, price=$basePrice, total=$totalAmount");

    $reservations = readReservations();
    error_log("RESERVATION: Current reservations count: " . count($reservations['requests']));
    $request = [
        'id' => generateUniqueId(),
        'created_at' => date('Y-m-d H:i:s'),
        'villa' => $villa,
        'villa_name' => $villaData['name'],
        'start' => $start,
        'end' => $end,
        'nights' => $nights,
        'guests' => $guests,
        'chef' => $chef,
        'contact_method' => $contactMethod,
        'first_name' => $firstName,
        'last_name' => $lastName,
        'phone' => $phone,
        'email' => $email,
        'status' => 'pending',
        'base_price' => $basePrice,
        'total_amount' => $totalAmount
    ];

    $reservations['requests'][] = $request;
    error_log("RESERVATION: Writing reservations, new count: " . count($reservations['requests']));
    writeReservations($reservations);
    error_log("RESERVATION: Write completed, ID: " . $request['id']);

    echo json_encode(['success' => true, 'request_id' => $request['id']]);
    exit;
}

// Admin endpoints
if (empty($_SESSION['admin_logged_in']) && empty($_SESSION['admin'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

if ($method === 'GET' && $action === 'list') {
    $reservations = readReservations();
    echo json_encode($reservations);
    exit;
}

if ($method === 'POST' && $action === 'validate') {
    $requestId = $_POST['request_id'] ?? '';
    
    if (!$requestId) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing request ID']);
        exit;
    }
    
    $reservations = readReservations();
    $requestIndex = -1;
    $request = null;
    
    foreach ($reservations['requests'] as $i => $req) {
        if ($req['id'] === $requestId) {
            $requestIndex = $i;
            $request = $req;
            break;
        }
    }
    
    if ($requestIndex === -1) {
        http_response_code(404);
        echo json_encode(['error' => 'Request not found']);
        exit;
    }
    
    $accessKey = generateAccessKey();
    
    $request['status'] = 'validated';
    $request['validated_at'] = date('Y-m-d H:i:s');
    $request['access_key'] = $accessKey;
    
    array_splice($reservations['requests'], $requestIndex, 1);
    $reservations['validated'][] = $request;
    
    writeReservations($reservations);
    
    $availability = readAvailability();
    if (!isset($availability[$request['villa']])) {
        $availability[$request['villa']] = ['blocked_dates' => [], 'reservations' => []];
    }
    
    $availability[$request['villa']]['reservations'][] = [
        'start' => $request['start'],
        'end' => $request['end'],
        'request_id' => $request['id']
    ];
    
    writeAvailability($availability);
    
    $reservationUrl = buildReservationUrl($accessKey);
    $notification = notifyClientValidation($request, $reservationUrl);
    
    echo json_encode([
        'success' => true,
        'access_key' => $accessKey,
        'reservation_url' => $reservationUrl,
        'notification' => $notification
    ]);
    exit;
}

if ($method === 'POST' && $action === 'reject') {
    $requestId = $_POST['request_id'] ?? '';
    
    if (!$requestId) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing request ID']);
        exit;
    }
    
    $reservations = readReservations();
    $requestIndex = -1;
    
    foreach ($reservations['requests'] as $i => $req) {
        if ($req['id'] === $requestId) {
            $requestIndex = $i;
            break;
        }
    }
    
    if ($requestIndex === -1) {
        http_response_code(404);
        echo json_encode(['error' => 'Request not found']);
        exit;
    }
    
    array_splice($reservations['requests'], $requestIndex, 1);
    writeReservations($reservations);
    
    echo json_encode(['success' => true]);
    exit;
}

http_response_code(400);
echo json_encode(['error' => 'Invalid request']);
