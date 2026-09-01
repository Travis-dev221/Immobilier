<?php
// API de réservation utilisant GET - solution pour LiteSpeed
header('Content-Type: application/json; charset=utf-8');
error_reporting(E_ALL);
ini_set('display_errors', 0);

try {
    $action = $_GET['action'] ?? '';
    
    // Endpoint create_request via GET
    if ($action === 'create_request') {
        $villa = $_GET['villa'] ?? '';
        $start = $_GET['start'] ?? '';
        $end = $_GET['end'] ?? '';
        $guests = intval($_GET['guests'] ?? 0);
        $firstName = trim($_GET['first_name'] ?? '');
        $lastName = trim($_GET['last_name'] ?? '');
        $phone = trim($_GET['phone'] ?? '');
        $email = trim($_GET['email'] ?? '');
        $chef = $_GET['chef'] ?? 'Non';
        $contactMethod = $_GET['contact_method'] ?? 'whatsapp';
        
        // Validation
        if (!$villa || !$start || !$end || !$guests || !$firstName || !$lastName || !$phone || !$email) {
            echo json_encode(['error' => 'Missing required fields']);
            exit;
        }
        
        // Lecture properties.json
        $propertiesFile = __DIR__ . '/data/properties.json';
        if (!file_exists($propertiesFile)) {
            echo json_encode(['error' => 'Properties file not found']);
            exit;
        }
        
        $properties = json_decode(file_get_contents($propertiesFile), true);
        
        if (!$properties || !isset($properties[$villa])) {
            echo json_encode(['error' => 'Villa not found', 'available' => array_keys($properties ?? [])]);
            exit;
        }
        
        // Lecture reservations.json
        $reservationsFile = __DIR__ . '/data/reservations.json';
        if (!file_exists($reservationsFile)) {
            $reservations = ['requests' => [], 'validated' => []];
        } else {
            $reservations = json_decode(file_get_contents($reservationsFile), true);
        }
        
        // Création de la demande
        $villaData = $properties[$villa];
        $startDate = strtotime($start);
        $endDate = strtotime($end);
        $nights = intval(($endDate - $startDate) / 86400);
        $basePrice = intval($villaData['price'] ?? 0);
        $totalAmount = $basePrice * $nights;
        
        $request = [
            'id' => uniqid('RES-', true),
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
        
        // Sauvegarde
        $writeResult = @file_put_contents($reservationsFile, json_encode($reservations, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        if ($writeResult === false) {
            $folder = dirname($reservationsFile);
            $isWritable = is_writable($folder);
            $fileExists = file_exists($reservationsFile);
            $fileWritable = $fileExists ? is_writable($reservationsFile) : true;
            echo json_encode([
                'error' => 'Impossible d\'enregistrer la réservation sur le serveur (droits d\'écriture refusés)',
                'debug_folder' => $folder,
                'debug_folder_writable' => $isWritable,
                'debug_file_exists' => $fileExists,
                'debug_file_writable' => $fileWritable
            ]);
            exit;
        }
        
        echo json_encode(['success' => true, 'request_id' => $request['id'], 'saved_in' => $reservationsFile]);
        exit;
    }
    
    // Endpoint list
    if ($action === 'list') {
        $reservationsFile = __DIR__ . '/data/reservations.json';
        if (!file_exists($reservationsFile)) {
            echo json_encode(['requests' => [], 'validated' => []]);
            exit;
        }
        echo file_get_contents($reservationsFile);
        exit;
    }
    
    // Endpoint validate (admin)
    if ($action === 'validate') {
        $requestId = $_GET['request_id'] ?? '';
        
        if (!$requestId) {
            echo json_encode(['error' => 'Missing request ID']);
            exit;
        }
        
        $reservationsFile = __DIR__ . '/data/reservations.json';
        $reservations = json_decode(file_get_contents($reservationsFile), true);
        
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
            echo json_encode(['error' => 'Request not found']);
            exit;
        }
        
        $accessKey = bin2hex(random_bytes(16));
        $request['status'] = 'validated';
        $request['validated_at'] = date('Y-m-d H:i:s');
        $request['access_key'] = $accessKey;
        
        array_splice($reservations['requests'], $requestIndex, 1);
        $reservations['validated'][] = $request;
        
        file_put_contents($reservationsFile, json_encode($reservations, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        
        // Update availability
        $availabilityFile = __DIR__ . '/data/availability.json';
        $availability = json_decode(file_get_contents($availabilityFile), true);
        
        if (!isset($availability[$request['villa']])) {
            $availability[$request['villa']] = ['blocked_dates' => [], 'reservations' => []];
        }
        
        $availability[$request['villa']]['reservations'][] = [
            'start' => $request['start'],
            'end' => $request['end'],
            'request_id' => $request['id']
        ];
        
        file_put_contents($availabilityFile, json_encode($availability, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        
        echo json_encode(['success' => true, 'access_key' => $accessKey]);
        exit;
    }
    
    // Endpoint reject (admin)
    if ($action === 'reject') {
        $requestId = $_GET['request_id'] ?? '';
        
        if (!$requestId) {
            echo json_encode(['error' => 'Missing request ID']);
            exit;
        }
        
        $reservationsFile = __DIR__ . '/data/reservations.json';
        $reservations = json_decode(file_get_contents($reservationsFile), true);
        
        $requestIndex = -1;
        
        foreach ($reservations['requests'] as $i => $req) {
            if ($req['id'] === $requestId) {
                $requestIndex = $i;
                break;
            }
        }
        
        if ($requestIndex === -1) {
            echo json_encode(['error' => 'Request not found']);
            exit;
        }
        
        array_splice($reservations['requests'], $requestIndex, 1);
        file_put_contents($reservationsFile, json_encode($reservations, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        
        echo json_encode(['success' => true]);
        exit;
    }
    
    echo json_encode(['error' => 'Invalid action']);
    
} catch (Exception $e) {
    echo json_encode(['error' => 'Exception: ' . $e->getMessage()]);
}
