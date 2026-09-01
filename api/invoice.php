<?php
require __DIR__ . '/bootstrap.php';


function generateInvoiceNumber() {
    $counter = readInvoiceCounter();
    $currentYear = date('Y');
    
    // Reset counter if year changed
    if ($counter['year'] != $currentYear) {
        $counter['last_number'] = 0;
        $counter['year'] = $currentYear;
    }
    
    $counter['last_number']++;
    $invoiceNumber = 'FAC-' . $currentYear . '-' . str_pad($counter['last_number'], 4, '0', STR_PAD_LEFT);
    
    writeInvoiceCounter($counter);
    
    return $invoiceNumber;
}

function generateInvoiceText($reservation, $properties) {
    $villaData = isset($reservation['villa']) && isset($properties[$reservation['villa']]) ? $properties[$reservation['villa']] : null;
    $invoiceNumber = $reservation['invoice_number'] ?? 'N/A';
    
    // Read tax settings
    $settingsFile = __DIR__ . '/../data/compta_settings.json';
    $settings = [];
    if (file_exists($settingsFile)) {
        $settings = json_decode(file_get_contents($settingsFile), true) ?: [];
    }
    $taxRate = isset($settings['tax_rate']) ? floatval($settings['tax_rate']) : 18;
    $taxMethod = $settings['tax_method'] ?? 'HT';
    
    $totalHT_raw = floatval($reservation['total_amount']);
    $tvaAmt = 0;
    $totalTTC = $totalHT_raw;
    $displayHT = $totalHT_raw;
    
    if ($taxMethod === 'TTC') {
        $displayHT = $totalHT_raw / (1 + 0.05 * ($taxRate / 100));
        $tvaAmt = $totalHT_raw - $displayHT;
        $totalTTC = $totalHT_raw;
    } else {
        $tvaAmt = ($totalHT_raw * 0.05) * ($taxRate / 100);
        $totalTTC = $totalHT_raw + $tvaAmt;
        $displayHT = $totalHT_raw;
    }
    
    $nights = intval($reservation['nights'] ?? 1);
    $basePrice = floatval($reservation['base_price'] ?? 0);
    $totalAmount = floatval($reservation['total_amount'] ?? ($basePrice * $nights));
    $unitPrice = $nights > 0 ? ($totalAmount / $nights) : $basePrice;
    $deposit50 = round($totalAmount * 0.5);
    $balance50 = $totalAmount - $deposit50;
    $elecPerNight = 15000;
    $elecDeposit = $nights * $elecPerNight;
    $totalDueOnArrival = $balance50 + $elecDeposit;
    
    $villaName = $villaData ? $villaData['name'] : ($reservation['villa_name'] ?? $reservation['villa'] ?? 'Villa');
    $startDateStr = !empty($reservation['start_date']) ? date('d/m/Y', strtotime($reservation['start_date'])) : '';
    $endDateStr = !empty($reservation['end_date']) ? date('d/m/Y', strtotime($reservation['end_date'])) : '';
    $hasChef = !empty($reservation['chef']) && strtolower($reservation['chef']) !== 'non';
    
    $lines = [];
    $lines[] = "╔══════════════════════════════════════════════════════════════════════╗";
    $lines[] = "║               BAOBAB HORIZON — FACTURE DE RÉSERVATION                ║";
    $lines[] = "╚══════════════════════════════════════════════════════════════════════╝";
    $lines[] = "";
    $lines[] = "Facture N°        : " . $invoiceNumber;
    $lines[] = "Date              : " . date('d/m/Y');
    $lines[] = "Émetteur          : Daniel Mariat (Agent immobilier - Saly, Sénégal)";
    $lines[] = "Téléphone         : +221 78 014 09 42";
    $lines[] = "";
    $lines[] = "────────────────────────────────────────────────────────────────────";
    $lines[] = "FACTURÉ À";
    $lines[] = "────────────────────────────────────────────────────────────────────";
    $lines[] = "Client            : " . ($reservation['first_name'] ?? '') . ' ' . ($reservation['last_name'] ?? '');
    if (!empty($reservation['phone'])) $lines[] = "Téléphone         : " . $reservation['phone'];
    if (!empty($reservation['email'])) $lines[] = "Email             : " . $reservation['email'];
    
    if (isset($reservation['personal_info'])) {
        $pi = $reservation['personal_info'];
        if (!empty($pi['nationality'])) $lines[] = "Nationalité       : " . $pi['nationality'];
        if (!empty($pi['id_number']))   $lines[] = "N° Pièce Identité : " . $pi['id_number'];
        if (!empty($pi['birth_date']))  $lines[] = "Date de naissance : " . $pi['birth_date'];
    }
    
    $lines[] = "";
    $lines[] = "────────────────────────────────────────────────────────────────────";
    $lines[] = "DÉSIGNATION & SÉJOUR";
    $lines[] = "────────────────────────────────────────────────────────────────────";
    $lines[] = "Bien              : " . $villaName . " (front de mer)";
    $lines[] = "Dates du séjour   : Du " . $startDateStr . " au " . $endDateStr . " (" . $nights . " nuits)";
    $lines[] = "Tarif / nuit      : " . number_format($unitPrice, 0, ',', ' ') . " CFA";
    if ($hasChef) $lines[] = "Option chef       : Inclus pour le séjour (jour de repos le dimanche)";
    $lines[] = "";
    $lines[] = "────────────────────────────────────────────────────────────────────";
    $lines[] = "RÉCAPITULATIF FINANCIER";
    $lines[] = "────────────────────────────────────────────────────────────────────";
    $lines[] = "Total prévisionnel séjour    : " . number_format($totalAmount, 0, ',', ' ') . " CFA";
    $lines[] = "Acompte de réservation (50%) : " . number_format($deposit50, 0, ',', ' ') . " CFA";
    $lines[] = "Solde loyer restant (50%)    : " . number_format($balance50, 0, ',', ' ') . " CFA";
    $lines[] = "Caution électricité (" . $nights . " nuits) : " . number_format($elecDeposit, 0, ',', ' ') . " CFA";
    $lines[] = "--------------------------------------------------------------------";
    $lines[] = "TOTAL SOLDE DÛ À L'ARRIVÉE   : " . number_format($totalDueOnArrival, 0, ',', ' ') . " CFA";
    $lines[] = "────────────────────────────────────────────────────────────────────";
    $lines[] = "";
    $lines[] = "Mode de paiement : Wave / Orange Money / Virement bancaire";
    $lines[] = "";
    $lines[] = "Électricité : Caution de 15 000 CFA/24h due à l'arrivée. Relevé compteur au départ.";
    $lines[] = "Conditions : Les paiements sont fermes et non remboursables (dates bloquées).";
    $lines[] = "";
    $lines[] = "Merci de votre confiance !";
    $lines[] = "Baobab Horizon — Tél: +221 78 014 09 42 — Email: baobhorizon@gmail.com";
    $lines[] = "══════════════════════════════════════════════════════════════════════";
    
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
    if (substr($formattedPhone, 0, 3) !== '221') {
        $formattedPhone = '221' . $formattedPhone;
    }
    
    // Generate WhatsApp URL
    $whatsappUrl = 'https://wa.me/' . $formattedPhone . '?text=' . urlencode($message);
    
    return ['success' => true, 'url' => $whatsappUrl];
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';
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
