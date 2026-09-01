<?php
session_start();
header('Content-Type: application/json');

// Check authentication
if (!isset($_SESSION['admin'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

define('DATA_DIR', __DIR__ . '/../data');
define('AVAILABILITY_FILE', DATA_DIR . '/availability.json');

function readAvailability() {
    if (!file_exists(AVAILABILITY_FILE)) {
        return [];
    }
    $data = json_decode(file_get_contents(AVAILABILITY_FILE), true);
    return is_array($data) ? $data : [];
}

function writeAvailability($data) {
    if (!is_dir(DATA_DIR)) {
        mkdir(DATA_DIR, 0755, true);
    }
    file_put_contents(AVAILABILITY_FILE, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
}

$action = $_GET['action'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET' && $action === 'get') {
    $data = readAvailability();
    echo json_encode($data);
    exit;
}

if ($method === 'POST' && $action === 'block') {
    $villa = $_POST['villa'] ?? '';
    $date = $_POST['date'] ?? '';
    
    if (!$villa || !$date) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing parameters']);
        exit;
    }
    
    $data = readAvailability();
    if (!isset($data[$villa])) {
        $data[$villa] = ['blocked_dates' => [], 'reservations' => []];
    }
    
    if (!in_array($date, $data[$villa]['blocked_dates'])) {
        $data[$villa]['blocked_dates'][] = $date;
        sort($data[$villa]['blocked_dates']);
        writeAvailability($data);
    }
    
    echo json_encode(['success' => true]);
    exit;
}

if ($method === 'POST' && $action === 'unblock') {
    $villa = $_POST['villa'] ?? '';
    $date = $_POST['date'] ?? '';
    
    if (!$villa || !$date) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing parameters']);
        exit;
    }
    
    $data = readAvailability();
    if (isset($data[$villa])) {
        $data[$villa]['blocked_dates'] = array_values(array_filter($data[$villa]['blocked_dates'], function($d) use ($date) {
            return $d !== $date;
        }));
        writeAvailability($data);
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
    
    $data = readAvailability();
    if (!isset($data[$villa])) {
        $data[$villa] = ['blocked_dates' => [], 'reservations' => []];
    }
    
    $current = strtotime($start);
    $endDate = strtotime($end);
    
    while ($current <= $endDate) {
        $dateStr = date('Y-m-d', $current);
        if (!in_array($dateStr, $data[$villa]['blocked_dates'])) {
            $data[$villa]['blocked_dates'][] = $dateStr;
        }
        $current = strtotime('+1 day', $current);
    }
    
    sort($data[$villa]['blocked_dates']);
    writeAvailability($data);
    
    echo json_encode(['success' => true]);
    exit;
}

http_response_code(400);
echo json_encode(['error' => 'Invalid request']);
