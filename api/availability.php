<?php
require __DIR__ . '/bootstrap.php';

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];

if ($method !== 'GET' || $action !== 'get') {
    requireAuth();
}

header('Content-Type: application/json; charset=utf-8');

// ── SYNCHRO ROOT ↔ LOCATION (availability) ────────────────
$SYNC_AVAIL_PATHS = [
    __DIR__ . '/../data/availability.json',
    __DIR__ . '/../Location/data/availability.json',
];

function availMergeAll($avails) {
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

function readAvailabilitySyncedAll() {
    global $SYNC_AVAIL_PATHS;
    $list = [];
    foreach ($SYNC_AVAIL_PATHS as $path) {
        if (!file_exists($path)) continue;
        $d = json_decode(@file_get_contents($path), true);
        if (is_array($d)) $list[] = $d;
    }
    return availMergeAll($list);
}

function writeAvailabilitySyncedAll($data) {
    global $SYNC_AVAIL_PATHS;
    $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    foreach ($SYNC_AVAIL_PATHS as $path) {
        $dir = dirname($path);
        if (!is_dir($dir)) @mkdir($dir, 0755, true);
        @file_put_contents($path, $json);
    }
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET' && $action === 'get') {
    echo json_encode(readAvailabilitySyncedAll());
    exit;
}

if ($method === 'POST' && $action === 'block') {
    $villa = $_POST['villa'] ?? '';
    $date = $_POST['date'] ?? '';
    if (!$villa || !$date) { http_response_code(400); echo json_encode(['error'=>'Missing parameters']); exit; }
    $data = readAvailabilitySyncedAll();
    if (!isset($data[$villa])) $data[$villa] = ['blocked_dates' => [], 'reservations' => []];
    if (!in_array($date, $data[$villa]['blocked_dates'], true)) {
        $data[$villa]['blocked_dates'][] = $date;
        sort($data[$villa]['blocked_dates']);
        writeAvailabilitySyncedAll($data);
    }
    echo json_encode(['success' => true]);
    exit;
}

if ($method === 'POST' && $action === 'unblock') {
    $villa = $_POST['villa'] ?? '';
    $date = $_POST['date'] ?? '';
    if (!$villa || !$date) { http_response_code(400); echo json_encode(['error'=>'Missing parameters']); exit; }
    $data = readAvailabilitySyncedAll();
    if (isset($data[$villa])) {
        $data[$villa]['blocked_dates'] = array_values(array_filter($data[$villa]['blocked_dates'], function($d) use ($date) { return $d !== $date; }));
        writeAvailabilitySyncedAll($data);
    }
    echo json_encode(['success' => true]);
    exit;
}

if ($method === 'POST' && $action === 'block_range') {
    $villa = $_POST['villa'] ?? '';
    $start = $_POST['start'] ?? '';
    $end = $_POST['end'] ?? '';
    if (!$villa || !$start || !$end) { http_response_code(400); echo json_encode(['error'=>'Missing parameters']); exit; }
    $data = readAvailabilitySyncedAll();
    if (!isset($data[$villa])) $data[$villa] = ['blocked_dates' => [], 'reservations' => []];
    $current = strtotime($start);
    $endDate = strtotime($end);
    while ($current <= $endDate) {
        $dateStr = date('Y-m-d', $current);
        if (!in_array($dateStr, $data[$villa]['blocked_dates'], true)) $data[$villa]['blocked_dates'][] = $dateStr;
        $current = strtotime('+1 day', $current);
    }
    sort($data[$villa]['blocked_dates']);
    writeAvailabilitySyncedAll($data);
    echo json_encode(['success' => true]);
    exit;
}

http_response_code(400);
echo json_encode(['error' => 'Invalid request']);
