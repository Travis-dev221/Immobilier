<?php
session_start();

define('ROOMS_FILE', __DIR__ . '/data/hotel_rooms.json');
define('RESERVATIONS_FILE', __DIR__ . '/data/hotel_reservations.json');
define('PAYMENTS_FILE', __DIR__ . '/data/hotel_payments.json');

function readJsonFile($path) {
    if (!file_exists($path)) {
        return [];
    }
    $content = file_get_contents($path);
    return json_decode($content, true) ?: [];
}

function writeJsonFile($path, $data) {
    $dir = dirname($path);
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    file_put_contents($path, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'book') {
    $roomId = $_POST['room_id'] ?? 'standard';
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $start = $_POST['start_date'] ?? '';
    $end = $_POST['end_date'] ?? '';
    $guests = intval($_POST['guests'] ?? 1);
    $payMethod = $_POST['payment_method'] ?? 'Wave';
    $isMeetingRoom = ($roomId === 'meeting_room');

    $rooms = readJsonFile(ROOMS_FILE);
    $pricePerNight = 0;
    $roomName = '';
    
    if ($isMeetingRoom) {
        $pricePerNight = 50000;
        $roomName = 'Salle de réunion';
    } else {
        $room = $rooms[$roomId] ?? null;
        if ($room) {
            $pricePerNight = $room['price'];
            $roomName = $room['name'];
        }
    }

    if (!empty($name) && !empty($email) && !empty($start) && !empty($end) && $pricePerNight > 0) {
        $nights = max(1, round((strtotime($end) - strtotime($start)) / 86400));
        $subtotal = $pricePerNight * $nights;
        $tax = intval($subtotal * 0.05);
        $total = $subtotal + $tax;

        $bookingId = 'BH' . date('ymd') . '-' . bin2hex(random_bytes(2));

        $reservations = readJsonFile(RESERVATIONS_FILE);
        $reservations[$bookingId] = [
            'id' => $bookingId,
            'room_id' => $roomId,
            'room_name' => $roomName,
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'start' => $start,
            'end' => $end,
            'guests' => $guests,
            'nights' => $nights,
            'total' => $total,
            'status' => 'En attente',
            'created_at' => date('Y-m-d H:i:s')
        ];
        writeJsonFile(RESERVATIONS_FILE, $reservations);

        $payments = readJsonFile(PAYMENTS_FILE);
        $payments[$bookingId] = [
            'id' => 'PAY-' . bin2hex(random_bytes(3)),
            'booking_id' => $bookingId,
            'client_name' => $name,
            'item_name' => $roomName,
            'amount' => $total,
            'method' => $payMethod,
            'status' => 'En attente',
            'created_at' => date('Y-m-d H:i:s')
        ];
        writeJsonFile(PAYMENTS_FILE, $payments);

        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'booking_id' => $bookingId,
            'room_name' => $roomName,
            'total' => $total,
            'nights' => $nights,
            'method' => $payMethod
        ]);
        exit;
    } else {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Veuillez remplir tous les champs obligatoires.']);
        exit;
    }
} else {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Requête invalide.']);
    exit;
}
