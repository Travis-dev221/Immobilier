<?php
session_start();
header('Content-Type: application/json');

define('DATA_DIR', __DIR__ . '/../data');
define('RESERVATIONS_FILE', DATA_DIR . '/reservations.json');
define('AVAILABILITY_FILE', DATA_DIR . '/availability.json');
define('PROPERTIES_FILE', DATA_DIR . '/properties.json');

function readReservations() {
    if (!file_exists(RESERVATIONS_FILE)) {
        return ['requests' => [], 'validated' => []];
    }
    $data = json_decode(file_get_contents(RESERVATIONS_FILE), true);
    return is_array($data) ? $data : ['requests' => [], 'validated' => []];
}

function writeReservations($data) {
    if (!is_dir(DATA_DIR)) {
        mkdir(DATA_DIR, 0755, true);
    }
    file_put_contents(RESERVATIONS_FILE, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
}

function readAvailability() {
    if (!file_exists(AVAILABILITY_FILE)) {
        return [];
    }
    $data = json_decode(file_get_contents(AVAILABILITY_FILE), true);
    return is_array($data) ? $data : [];
}

function writeAvailability($data) {
    file_put_contents(AVAILABILITY_FILE, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
}

function readProperties() {
    if (!file_exists(PROPERTIES_FILE)) {
        return [];
    }
    $data = json_decode(file_get_contents(PROPERTIES_FILE), true);
    return is_array($data) ? $data : [];
}

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
        
        // Check blocked dates
        if (in_array($dateStr, $blocked)) {
            return false;
        }
        
        // Check existing reservations
        foreach ($reservations as $res) {
            if ($dateStr >= $res['start'] && $dateStr <= $res['end']) {
                return false;
            }
        }
        
        $current = strtotime('+1 day', $current);
    }
    
    return true;
}

$action = $_GET['action'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];

// Public endpoint - create reservation request
if ($method === 'POST' && $action === 'create_request') {
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
    
    if (!$villa || !$start || !$end || !$guests || !$firstName || !$lastName || !$phone || !$email) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing required fields']);
        exit;
    }
    
    // Validate dates
    $startDate = strtotime($start);
    $endDate = strtotime($end);
    
    if ($startDate >= $endDate) {
        http_response_code(400);
        echo json_encode(['error' => 'End date must be after start date']);
        exit;
    }
    
    // Check availability
    $availability = readAvailability();
    if (!isDateRangeAvailable($villa, $start, $end, $availability)) {
        http_response_code(409);
        echo json_encode(['error' => 'Dates not available']);
        exit;
    }
    
    // Get villa details
    $properties = readProperties();
    if (!isset($properties[$villa])) {
        http_response_code(404);
        echo json_encode(['error' => 'Villa not found']);
        exit;
    }
    
    $villaData = $properties[$villa];
    $nights = intval(($endDate - $startDate) / 86400);
    $basePrice = intval($villaData['price'] ?? 0);
    $totalAmount = $basePrice * $nights;
    
    // Create reservation request
    $reservations = readReservations();
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
    writeReservations($reservations);
    
    echo json_encode(['success' => true, 'request_id' => $request['id']]);
    exit;
}

// Admin endpoints - require authentication
if (!isset($_SESSION['admin'])) {
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
    
    // Generate access key
    $accessKey = generateAccessKey();
    
    // Move to validated
    $request['status'] = 'validated';
    $request['validated_at'] = date('Y-m-d H:i:s');
    $request['access_key'] = $accessKey;
    
    // Remove from requests and add to validated
    array_splice($reservations['requests'], $requestIndex, 1);
    $reservations['validated'][] = $request;
    
    writeReservations($reservations);
    
    // Add to availability
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
    
    echo json_encode(['success' => true, 'access_key' => $accessKey]);
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
    
    // Remove request
    array_splice($reservations['requests'], $requestIndex, 1);
    writeReservations($reservations);
    
    echo json_encode(['success' => true]);
    exit;
}

// Get validated reservation by access key (public)
if ($method === 'GET' && $action === 'get_by_key') {
    $accessKey = $_GET['key'] ?? '';
    
    if (!$accessKey) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing access key']);
        exit;
    }
    
    $reservations = readReservations();
    
    foreach ($reservations['validated'] as $res) {
        if (isset($res['access_key']) && $res['access_key'] === $accessKey) {
            echo json_encode(['success' => true, 'reservation' => $res]);
            exit;
        }
    }
    
    http_response_code(404);
    echo json_encode(['error' => 'Reservation not found']);
    exit;
}

http_response_code(400);
echo json_encode(['error' => 'Invalid request']);
