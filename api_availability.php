<?php
// Proxy API autonome pour la disponibilité - solution LiteSpeed
header('Content-Type: application/json; charset=utf-8');
session_start();

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

$availabilityFile = $root . '/data/availability.json';

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

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET' && $action === 'get') {
    $availability = readAvailability();
    echo json_encode($availability);
    exit;
}

if ($method === 'POST' && $action === 'block') {
    $villa = $_POST['villa'] ?? '';
    $date = $_POST['date'] ?? '';
    
    if (!$villa || !$date) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing villa or date']);
        exit;
    }
    
    $availability = readAvailability();
    if (!isset($availability[$villa])) {
        $availability[$villa] = ['blocked_dates' => [], 'reservations' => []];
    }
    
    if (!in_array($date, $availability[$villa]['blocked_dates'])) {
        $availability[$villa]['blocked_dates'][] = $date;
        writeAvailability($availability);
    }
    
    echo json_encode(['success' => true]);
    exit;
}

if ($method === 'POST' && $action === 'unblock') {
    $villa = $_POST['villa'] ?? '';
    $date = $_POST['date'] ?? '';
    
    if (!$villa || !$date) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing villa or date']);
        exit;
    }
    
    $availability = readAvailability();
    if (isset($availability[$villa])) {
        $availability[$villa]['blocked_dates'] = array_values(array_filter(
            $availability[$villa]['blocked_dates'],
            function($d) use ($date) { return $d !== $date; }
        ));
        writeAvailability($availability);
    }
    
    echo json_encode(['success' => true]);
    exit;
}

if ($method === 'POST' && $action === 'block_range') {
    $villa = $_POST['villa'] ?? '';
    $start = $_POST['start'] ?? '';
    $end = $_POST['end'] ?? '';
    
    if (!$villa || !$start || !$end) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing parameters']);
        exit;
    }
    
    $availability = readAvailability();
    if (!isset($availability[$villa])) {
        $availability[$villa] = ['blocked_dates' => [], 'reservations' => []];
    }
    
    $current = strtotime($start);
    $endDate = strtotime($end);
    
    while ($current <= $endDate) {
        $dateStr = date('Y-m-d', $current);
        if (!in_array($dateStr, $availability[$villa]['blocked_dates'])) {
            $availability[$villa]['blocked_dates'][] = $dateStr;
        }
        $current = strtotime('+1 day', $current);
    }
    
    writeAvailability($availability);
    echo json_encode(['success' => true]);
    exit;
}

http_response_code(400);
echo json_encode(['error' => 'Invalid request']);
