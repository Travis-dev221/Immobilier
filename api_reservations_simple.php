<?php
// Version simplifiée pour débogage
header('Content-Type: application/json; charset=utf-8');
error_reporting(E_ALL);
ini_set('display_errors', 0);

try {
    $action = $_POST['action'] ?? $_GET['action'] ?? '';
    $method = $_SERVER['REQUEST_METHOD'];
    
    // Test simple de réponse
    if ($method === 'GET' && $action === 'test') {
        echo json_encode(['status' => 'ok', 'message' => 'API fonctionne']);
        exit;
    }
    
    // Endpoint create_request simplifié
    if ($method === 'POST' && $action === 'create_request') {
        $villa = $_POST['villa'] ?? '';
        $start = $_POST['start'] ?? '';
        $end = $_POST['end'] ?? '';
        $guests = intval($_POST['guests'] ?? 0);
        $firstName = trim($_POST['first_name'] ?? '');
        $lastName = trim($_POST['last_name'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $email = trim($_POST['email'] ?? '');
        
        // Validation basique
        if (!$villa || !$start || !$end || !$guests || !$firstName || !$lastName || !$phone || !$email) {
            echo json_encode(['error' => 'Missing required fields', 'received' => $_POST]);
            exit;
        }
        
        // Lecture properties.json
        $propertiesFile = __DIR__ . '/data/properties.json';
        if (!file_exists($propertiesFile)) {
            echo json_encode(['error' => 'Properties file not found', 'path' => $propertiesFile]);
            exit;
        }
        
        $propertiesJson = file_get_contents($propertiesFile);
        $properties = json_decode($propertiesJson, true);
        
        if (!$properties || !isset($properties[$villa])) {
            echo json_encode([
                'error' => 'Villa not found',
                'villa' => $villa,
                'available' => array_keys($properties ?? [])
            ]);
            exit;
        }
        
        // Lecture reservations.json
        $reservationsFile = __DIR__ . '/data/reservations.json';
        if (!file_exists($reservationsFile)) {
            $reservations = ['requests' => [], 'validated' => []];
        } else {
            $reservationsJson = file_get_contents($reservationsFile);
            $reservations = json_decode($reservationsJson, true);
        }
        
        // Création de la demande
        $request = [
            'id' => uniqid('RES-', true),
            'created_at' => date('Y-m-d H:i:s'),
            'villa' => $villa,
            'villa_name' => $properties[$villa]['name'],
            'start' => $start,
            'end' => $end,
            'guests' => $guests,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'phone' => $phone,
            'email' => $email,
            'status' => 'pending'
        ];
        
        $reservations['requests'][] = $request;
        
        // Sauvegarde
        file_put_contents($reservationsFile, json_encode($reservations, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        
        echo json_encode(['success' => true, 'request_id' => $request['id']]);
        exit;
    }
    
    // Endpoint list
    if ($method === 'GET' && $action === 'list') {
        $reservationsFile = __DIR__ . '/data/reservations.json';
        if (!file_exists($reservationsFile)) {
            echo json_encode(['requests' => [], 'validated' => []]);
            exit;
        }
        $reservationsJson = file_get_contents($reservationsFile);
        echo $reservationsJson;
        exit;
    }
    
    echo json_encode(['error' => 'Invalid request', 'action' => $action, 'method' => $method]);
    
} catch (Exception $e) {
    echo json_encode(['error' => 'Exception: ' . $e->getMessage(), 'trace' => $e->getTraceAsString()]);
} catch (Error $e) {
    echo json_encode(['error' => 'Error: ' . $e->getMessage(), 'trace' => $e->getTraceAsString()]);
}
