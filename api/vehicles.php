<?php
require_once __DIR__ . '/bootstrap.php';

$vehiclesFile = dirname(__DIR__) . '/data/vehicles.json';
$resFile      = dirname(__DIR__) . '/data/vehicle_reservations.json';

function readVehicles($file) {
    if (!file_exists($file)) return [];
    $data = json_decode(file_get_contents($file), true);
    return is_array($data) ? $data : [];
}

function writeVehicles($file, $data) {
    file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? $_POST['action'] ?? '';

// Public read: Get all vehicles or available ones
if ($method === 'GET') {
    $vehicles = readVehicles($vehiclesFile);
    if (isset($_GET['available_only'])) {
        $vehicles = array_values(array_filter($vehicles, fn($v) => !empty($v['available'])));
    }
    jsonResponse($vehicles);
}

// Reservation submission (Public or Admin)
if ($method === 'POST' && $action === 'book') {
    $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    $clientName  = trim($input['name'] ?? '');
    $clientPhone = trim($input['phone'] ?? '');
    $vehicleId   = trim($input['vehicle_id'] ?? '');
    $startDate   = trim($input['start_date'] ?? '');
    $endDate     = trim($input['end_date'] ?? '');
    $withDriver  = !empty($input['with_driver']);

    if (!$clientName || !$clientPhone || !$vehicleId || !$startDate || !$endDate) {
        jsonResponse(['error' => 'Veuillez remplir tous les champs obligatoires.'], 400);
    }

    $reservations = readVehicles($resFile);
    $newRes = [
        'id'          => 'vres-' . time() . '-' . rand(100,999),
        'vehicle_id'  => $vehicleId,
        'client_name' => $clientName,
        'phone'       => $clientPhone,
        'email'       => trim($input['email'] ?? ''),
        'start_date'  => $startDate,
        'end_date'    => $endDate,
        'with_driver' => $withDriver,
        'offer_type'  => trim($input['offer_type'] ?? 'Offre Découverte'),
        'status'      => 'pending',
        'created_at'  => date('Y-m-d H:i:s')
    ];

    $reservations[] = $newRes;
    writeVehicles($resFile, $reservations);

    jsonResponse(['ok' => true, 'message' => 'Demande de réservation enregistrée !', 'reservation' => $newRes]);
}

// Admin actions
if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    
    // Save vehicles catalog
    if (isset($input['vehicles']) && is_array($input['vehicles'])) {
        writeVehicles($vehiclesFile, $input['vehicles']);
        jsonResponse(['ok' => true]);
    }

    // Update reservation status
    if ($action === 'update_res_status') {
        $resId  = trim($input['res_id'] ?? '');
        $status = trim($input['status'] ?? '');
        $reservations = readVehicles($resFile);
        foreach ($reservations as &$r) {
            if ($r['id'] === $resId) {
                $r['status'] = $status;
                break;
            }
        }
        writeVehicles($resFile, $reservations);
        jsonResponse(['ok' => true]);
    }
}
