<?php
session_start();
header('Content-Type: application/json');

define('DATA_DIR', __DIR__ . '/../data');
define('RESERVATIONS_FILE', DATA_DIR . '/reservations.json');
define('PROPERTIES_FILE', DATA_DIR . '/properties.json');

function readReservations() {
    if (!file_exists(RESERVATIONS_FILE)) {
        return ['requests' => [], 'validated' => []];
    }
    $data = json_decode(file_get_contents(RESERVATIONS_FILE), true);
    return is_array($data) ? $data : ['requests' => [], 'validated' => []];
}

function writeReservations($data) {
    file_put_contents(RESERVATIONS_FILE, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
}

function readProperties() {
    if (!file_exists(PROPERTIES_FILE)) {
        return [];
    }
    $data = json_decode(file_get_contents(PROPERTIES_FILE), true);
    return is_array($data) ? $data : [];
}

function generateInvoiceText($reservation, $properties) {
    $villaData = isset($reservation['villa']) && isset($properties[$reservation['villa']]) ? $properties[$reservation['villa']] : null;
    
    $lines = [];
    $lines[] = "═══════════════════════════════════════════════════════════════";
    $lines[] = "                    FACTURE DE RÉSERvation";
    $lines[] = "                      Baobab Horizon";
    $lines[] = "═══════════════════════════════════════════════════════════════";
    $lines[] = "";
    $lines[] = "Numéro de facture : " . $reservation['id'];
    $lines[] = "Date : " . date('d/m/Y');
    $lines[] = "";
    $lines[] = "───────────────────────────────────────────────────────────────────";
    $lines[] = "CLIENT";
    $lines[] = "───────────────────────────────────────────────────────────────────";
    $lines[] = "Nom : " . $reservation['first_name'] . ' ' . $reservation['last_name'];
    $lines[] = "Email : " . $reservation['email'];
    $lines[] = "Téléphone : " . $reservation['phone'];
    
    if (isset($reservation['personal_info'])) {
        $pi = $reservation['personal_info'];
        $lines[] = "Date de naissance : " . ($pi['birth_date'] ?? 'Non renseigné');
        $lines[] = "Nationalité : " . ($pi['nationality'] ?? 'Non renseigné');
        $lines[] = "Adresse : " . ($pi['address'] ?? 'Non renseigné');
        $lines[] = "Pièce d'identité : " . ($pi['id_number'] ?? 'Non renseigné');
    }
    
    $lines[] = "";
    $lines[] = "───────────────────────────────────────────────────────────────────";
    $lines[] = "DÉTAILS DE LA RÉSERVATION";
    $lines[] = "───────────────────────────────────────────────────────────────────";
    $lines[] = "Villa : " . $reservation['villa_name'];
    $lines[] = "Date d'arrivée : " . $reservation['start'];
    $lines[] = "Date de départ : " . $reservation['end'];
    $lines[] = "Nombre de nuits : " . $reservation['nights'];
    $lines[] = "Nombre de personnes : " . $reservation['guests'];
    $lines[] = "Option chef cuisinier : " . $reservation['chef'];
    
    if ($villaData) {
        $lines[] = "Zone : " . ($villaData['zone'] ?? 'Non renseigné');
    }
    
    $lines[] = "";
    $lines[] = "───────────────────────────────────────────────────────────────────";
    $lines[] = "MONTANT";
    $lines[] = "───────────────────────────────────────────────────────────────────";
    $lines[] = "Prix par nuit : " . number_format($reservation['base_price'], 0, ',', ' ') . ' FCFA';
    $lines[] = "Nombre de nuits : " . $reservation['nights'];
    $lines[] = "";
    $lines[] = "TOTAL : " . number_format($reservation['total_amount'], 0, ',', ' ') . ' FCFA';
    $lines[] = "";
    $lines[] = "───────────────────────────────────────────────────────────────────";
    $lines[] = "COORDONNÉES DE PAIEMENT";
    $lines[] = "───────────────────────────────────────────────────────────────────";
    
    $paymentMethod = isset($reservation['personal_info']['payment_method']) ? $reservation['personal_info']['payment_method'] : 'Non sélectionné';
    
    switch ($paymentMethod) {
        case 'orange_money':
            $lines[] = "ORANGE MONEY";
            $lines[] = "Numéro : 77 801 40 94";
            $lines[] = "USSD : *144*1*778014094*" . $reservation['total_amount'] . "#";
            break;
        case 'wave':
            $lines[] = "WAVE";
            $lines[] = "Numéro : 77 801 40 94";
            $lines[] = "Ouvrez l'application Wave et envoyez le montant";
            break;
        case 'bank_transfer':
            $lines[] = "VIREMENT BANCAIRE";
            $lines[] = "Banque : [À compléter]";
            $lines[] = "IBAN : [À compléter]";
            $lines[] = "Titulaire : Baobab Horizon";
            break;
        default:
            $lines[] = "Mode de paiement : " . $paymentMethod;
    }
    
    $lines[] = "";
    $lines[] = "───────────────────────────────────────────────────────────────────";
    $lines[] = "CONTACT";
    $lines[] = "───────────────────────────────────────────────────────────────────";
    $lines[] = "Téléphone : +221 77 801 40 94";
    $lines[] = "Email : baoabhorizon@gmail.com";
    $lines[] = "";
    $lines[] = "═══════════════════════════════════════════════════════════════";
    $lines[] = "Merci de votre confiance !";
    $lines[] = "═══════════════════════════════════════════════════════════════";
    
    return implode("\n", $lines);
}

function sendEmail($to, $subject, $body) {
    $headers = [];
    $headers[] = 'From: Baobab Horizon <baoabhorizon@gmail.com>';
    $headers[] = 'MIME-Version: 1.0';
    $headers[] = 'Content-Type: text/plain; charset=UTF-8';
    $headers[] = 'X-Mailer: PHP/' . phpversion();
    
    $headersStr = implode("\r\n", $headers);
    
    return mail($to, $subject, $body, $headersStr);
}

function sendWhatsApp($phone, $message) {
    // Format phone number for WhatsApp (remove +, spaces, dashes)
    $formattedPhone = preg_replace('/[^0-9]/', '', $phone);
    
    // Ensure it starts with country code for Senegal (221)
    if (!str_starts_with($formattedPhone, '221')) {
        $formattedPhone = '221' . $formattedPhone;
    }
    
    // Generate WhatsApp URL
    $whatsappUrl = 'https://wa.me/' . $formattedPhone . '?text=' . urlencode($message);
    
    return ['success' => true, 'url' => $whatsappUrl];
}

$action = $_GET['action'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];

// Send invoice endpoint
if ($method === 'POST' && $action === 'send') {
    $accessKey = $_POST['access_key'] ?? '';
    
    if (!$accessKey) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing access key']);
        exit;
    }
    
    $reservations = readReservations();
    $reservation = null;
    
    foreach ($reservations['validated'] as $res) {
        if (isset($res['access_key']) && $res['access_key'] === $accessKey) {
            $reservation = $res;
            break;
        }
    }
    
    if (!$reservation) {
        http_response_code(404);
        echo json_encode(['error' => 'Reservation not found']);
        exit;
    }
    
    $properties = readProperties();
    $invoiceText = generateInvoiceText($reservation, $properties);
    $sendMethod = $_POST['send_method'] ?? 'email';
    
    $result = [];
    
    if ($sendMethod === 'email') {
        $subject = 'Facture Baobab Horizon - Réservation #' . $reservation['id'];
        $emailSent = sendEmail($reservation['email'], $subject, $invoiceText);
        
        if ($emailSent) {
            $result = ['success' => true, 'method' => 'email', 'message' => 'Facture envoyée par email'];
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to send email']);
            exit;
        }
    } elseif ($sendMethod === 'whatsapp') {
        $whatsappResult = sendWhatsApp($reservation['phone'], $invoiceText);
        $result = ['success' => true, 'method' => 'whatsapp', 'url' => $whatsappResult['url'], 'message' => 'Facture prête pour WhatsApp'];
    }
    
    // Mark invoice as sent
    foreach ($reservations['validated'] as &$res) {
        if (isset($res['access_key']) && $res['access_key'] === $accessKey) {
            $res['invoice_sent'] = true;
            $res['invoice_sent_method'] = $sendMethod;
            $res['invoice_sent_at'] = date('Y-m-d H:i:s');
            break;
        }
    }
    writeReservations($reservations);
    
    echo json_encode($result);
    exit;
}

// Generate invoice preview endpoint
if ($method === 'GET' && $action === 'preview') {
    $accessKey = $_GET['key'] ?? '';
    
    if (!$accessKey) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing access key']);
        exit;
    }
    
    $reservations = readReservations();
    $reservation = null;
    
    foreach ($reservations['validated'] as $res) {
        if (isset($res['access_key']) && $res['access_key'] === $accessKey) {
            $reservation = $res;
            break;
        }
    }
    
    if (!$reservation) {
        http_response_code(404);
        echo json_encode(['error' => 'Reservation not found']);
        exit;
    }
    
    $properties = readProperties();
    $invoiceText = generateInvoiceText($reservation, $properties);
    
    echo json_encode(['success' => true, 'invoice' => $invoiceText]);
    exit;
}

http_response_code(400);
echo json_encode(['error' => 'Invalid request']);
