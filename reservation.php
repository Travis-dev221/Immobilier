<?php
session_start();
header('Content-Type: text/html; charset=UTF-8');

// ── CHEMINS SYNCHRONISÉS ──────────────────────────────────────
$SYNC_RES_FILES = [
    __DIR__ . '/data/reservations.json',
    __DIR__ . '/Location/data/reservations.json',
];
$SYNC_UPLOADS_DIRS = [
    __DIR__ . '/data/uploads',
    __DIR__ . '/Location/data/uploads',
];
$SYNC_PROPS_FILES = [
    __DIR__ . '/data/properties.json',
    __DIR__ . '/Location/data/properties.json',
];
$SYNC_INVOICE_COUNTER_FILES = [
    __DIR__ . '/data/invoice_counter.json',
    __DIR__ . '/Location/data/invoice_counter.json',
];

function resMergeSync($lists) {
    $seen = ['requests' => [], 'validated' => []];
    $out  = ['requests' => [], 'validated' => []];
    foreach (['requests', 'validated'] as $bucket) {
        foreach ($lists as $list) {
            if (empty($list[$bucket]) || !is_array($list[$bucket])) continue;
            foreach ($list[$bucket] as $r) {
                $id = $r['id'] ?? (isset($r['access_key']) ? 'vk:'.$r['access_key'] : spl_object_hash((object)$r));
                if (isset($seen[$bucket][$id])) continue;
                $seen[$bucket][$id] = true;
                $out[$bucket][] = $r;
            }
        }
    }
    return $out;
}

function readReservations() {
    global $SYNC_RES_FILES;
    $collected = [];
    $defaultEmpty = ['requests' => [], 'validated' => []];
    foreach ($SYNC_RES_FILES as $path) {
        if (!file_exists($path)) { $collected[] = $defaultEmpty; continue; }
        $d = json_decode(@file_get_contents($path), true);
        $collected[] = is_array($d) ? $d : $defaultEmpty;
    }
    return resMergeSync($collected);
}

function writeReservations($data) {
    global $SYNC_RES_FILES;
    $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    foreach ($SYNC_RES_FILES as $path) {
        $dir = dirname($path);
        if (!is_dir($dir)) @mkdir($dir, 0755, true);
        @file_put_contents($path, $json);
    }
}

function syncUploadFile($relFilename, $sourceFullPath) {
    global $SYNC_UPLOADS_DIRS;
    foreach ($SYNC_UPLOADS_DIRS as $dir) {
        if (!is_dir($dir)) @mkdir($dir, 0755, true);
        $dest = rtrim($dir, '/\\') . '/' . $relFilename;
        if (realpath($sourceFullPath) === realpath($dest)) continue;
        @copy($sourceFullPath, $dest);
    }
}

function readProperties() {
    global $SYNC_PROPS_FILES;
    $all = [];
    foreach ($SYNC_PROPS_FILES as $path) {
        if (!file_exists($path)) continue;
        $d = json_decode(@file_get_contents($path), true);
        if (is_array($d)) $all[] = $d;
    }
    return empty($all) ? [] : array_merge(...$all);
}

function readInvoiceCounter() {
    global $SYNC_INVOICE_COUNTER_FILES;
    foreach ($SYNC_INVOICE_COUNTER_FILES as $path) {
        if (!file_exists($path)) continue;
        $d = json_decode(@file_get_contents($path), true);
        if (is_array($d)) return $d;
    }
    return ['last_number' => 0, 'year' => date('Y')];
}

function writeInvoiceCounter($data) {
    global $SYNC_INVOICE_COUNTER_FILES;
    $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    foreach ($SYNC_INVOICE_COUNTER_FILES as $path) {
        $dir = dirname($path);
        if (!is_dir($dir)) @mkdir($dir, 0755, true);
        @file_put_contents($path, $json);
    }
}

function ensureUploadDirs() {
    global $SYNC_UPLOADS_DIRS;
    foreach ($SYNC_UPLOADS_DIRS as $dir) {
        if (!is_dir($dir)) @mkdir($dir, 0755, true);
    }
}

define('UPLOADS_DIR', __DIR__ . '/data/uploads'); // keep primary for local move_uploaded_file (will be synced)

function generateInvoiceNumber() {
    $counter = readInvoiceCounter();
    $currentYear = date('Y');
    
    if ($counter['year'] != $currentYear) {
        $counter['last_number'] = 0;
        $counter['year'] = $currentYear;
    }
    
    $counter['last_number']++;
    $invoiceNumber = 'FAC-' . $currentYear . '-' . str_pad($counter['last_number'], 4, '0', STR_PAD_LEFT);
    
    writeInvoiceCounter($counter);
    
    return $invoiceNumber;
}

function h($s) { return htmlspecialchars($s ?? '', ENT_QUOTES); }

function generateInvoiceText($reservation, $properties) {
    $villaData = isset($reservation['villa']) && isset($properties[$reservation['villa']]) ? $properties[$reservation['villa']] : null;
    $invoiceNumber = $reservation['invoice_number'] ?? 'N/A';
    
    $lines = [];
    $lines[] = "╔══════════════════════════════════════════════════════════════════════╗";
    $lines[] = "║                        FACTURE DE RÉSERVATION                        ║";
    $lines[] = "║                          BAOBAB HORIZON                               ║";
    $lines[] = "╚══════════════════════════════════════════════════════════════════════╝";
    $lines[] = "";
    $lines[] = "Numéro de facture : " . $invoiceNumber;
    $lines[] = "Date d'émission   : " . date('d/m/Y');
    $lines[] = "";
    $lines[] = "────────────────────────────────────────────────────────────────────";
    $lines[] = "INFORMATIONS CLIENT";
    $lines[] = "────────────────────────────────────────────────────────────────────";
    $lines[] = "Nom complet       : " . $reservation['first_name'] . ' ' . $reservation['last_name'];
    $lines[] = "Email             : " . $reservation['email'];
    $lines[] = "Téléphone         : " . $reservation['phone'];
    
    if (isset($reservation['personal_info'])) {
        $pi = $reservation['personal_info'];
        $lines[] = "Date de naissance : " . ($pi['birth_date'] ?? 'Non renseigné');
        $lines[] = "Nationalité       : " . ($pi['nationality'] ?? 'Non renseigné');
        $lines[] = "Adresse           : " . ($pi['address'] ?? 'Non renseigné');
        $lines[] = "N° pièce d'identité : " . ($pi['id_number'] ?? 'Non renseigné');
    }
    
    $lines[] = "";
    $lines[] = "────────────────────────────────────────────────────────────────────";
    $lines[] = "DÉTAILS DE LA RÉSERVATION";
    $lines[] = "────────────────────────────────────────────────────────────────────";
    $lines[] = "Villa             : " . ($villaData ? $villaData['name'] : $reservation['villa']);
    $s = $reservation['start_date'] ?? $reservation['start'] ?? '';
    $e = $reservation['end_date'] ?? $reservation['end'] ?? '';
    $lines[] = "Date d'arrivée    : " . ($s ? date('d/m/Y', strtotime($s)) : '');
    $lines[] = "Date de départ    : " . ($e ? date('d/m/Y', strtotime($e)) : '');
    $lines[] = "Nombre de nuits   : " . $reservation['nights'];
    $lines[] = "Nombre de personnes : " . $reservation['guests'];
    $lines[] = "Option chef       : " . ($reservation['chef'] === 'Oui' ? 'Oui (+25 000 FCFA/nuit)' : 'Non');
    
    $lines[] = "";
    $lines[] = "────────────────────────────────────────────────────────────────────";
    $lines[] = "DÉTAILS DE PAIEMENT";
    $lines[] = "────────────────────────────────────────────────────────────────────";
    
    if (isset($reservation['personal_info'])) {
        $pi = $reservation['personal_info'];
        $paymentMethods = [
            'orange_money' => 'Orange Money',
            'wave' => 'Wave',
            'bank_transfer' => 'Virement bancaire'
        ];
        $lines[] = "Mode de paiement   : " . ($paymentMethods[$pi['payment_method']] ?? $pi['payment_method']);
    }
    
    $lines[] = "";
    $lines[] = "────────────────────────────────────────────────────────────────────";
    $lines[] = "MONTANT TOTAL";
    $lines[] = "────────────────────────────────────────────────────────────────────";
    $lines[] = "TOTAL             : " . number_format($reservation['total_amount'], 0, ',', ' ') . " FCFA";
    $lines[] = "";
    $lines[] = "────────────────────────────────────────────────────────────────────";
    $lines[] = "INFORMATIONS DE CONTACT";
    $lines[] = "────────────────────────────────────────────────────────────────────";
    $lines[] = "Baobab Horizon";
    $lines[] = "Petite Côte du Sénégal";
    $lines[] = "Tél : +221 78 014 09 42";
    $lines[] = "Email : baobabhorizon@gmail.com";
    $lines[] = "";
    $lines[] = "Merci de votre confiance !";
    $lines[] = "══════════════════════════════════════════════════════════════════════";
    
    return implode("\n", $lines);
}

function sendInvoiceRequest($accessKey, $sendMethod) {
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $dir = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/\\');
    $url = $scheme . '://' . $host . $dir . '/api/invoice.php?action=send';
    $ctx = stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => 'Content-Type: application/x-www-form-urlencoded',
            'content' => http_build_query([
                'access_key' => $accessKey,
                'send_method' => $sendMethod
            ]),
            'ignore_errors' => true
        ]
    ]);
    $response = @file_get_contents($url, false, $ctx);
    return $response ? json_decode($response, true) : null;
}

$accessKey = $_GET['key'] ?? '';
$error = null;
$reservation = null;

// Handle PDF download
if ($accessKey && isset($_GET['download']) && $_GET['download'] == '1') {
    $reservations = readReservations();
    
    foreach ($reservations['validated'] as $res) {
        if (isset($res['access_key']) && $res['access_key'] === $accessKey) {
            $reservation = $res;
            break;
        }
    }
    
    if ($reservation && !empty($reservation['invoice_generated'])) {
        $properties = readProperties();
        $villaData = isset($reservation['villa']) && isset($properties[$reservation['villa']]) ? $properties[$reservation['villa']] : null;
        $invoiceNumber = $reservation['invoice_number'] ?? 'N/A';
        $s = $reservation['start_date'] ?? $reservation['start'] ?? '';
        $e = $reservation['end_date'] ?? $reservation['end'] ?? '';
        
        $pi = $reservation['personal_info'] ?? [];
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
        $startDateStr = !empty($s) ? date('M d, Y', strtotime($s)) : '';
        $endDateStr = !empty($e) ? date('M d, Y', strtotime($e)) : '';
        $hasChef = !empty($reservation['chef']) && strtolower($reservation['chef']) !== 'non';
        
        $fullName = trim(($reservation['first_name'] ?? '') . ' ' . ($reservation['last_name'] ?? ''));
        if (empty($fullName)) $fullName = 'Client';
        
        $invDateStr = date('M d, Y', !empty($reservation['validated_at']) ? strtotime($reservation['validated_at']) : time());
        
        $pm = $pi['payment_method'] ?? 'wave';
        $pmDisplay = ($pm === 'orange_money') ? 'Orange Money: +221 77 337 18 13' : (($pm === 'bank_transfer') ? 'Virement bancaire' : 'Wave: +221 78 014 09 42');

        $html = '<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Facture ' . htmlspecialchars($invoiceNumber) . ' — Baobab Horizon</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
  <style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body {
      font-family: \'Poppins\', Arial, sans-serif;
      background: #ece8e1;
      color: #111111;
      -webkit-print-color-adjust: exact;
      print-color-adjust: exact;
      min-height: 100vh;
      padding: 0 0 30px 0;
    }
    
    .action-bar {
      background: #0F1A17;
      padding: 12px 30px;
      display: flex;
      align-items: center;
      gap: 12px;
      flex-wrap: wrap;
      border-bottom: 1px solid rgba(214,175,92,0.3);
      margin-bottom: 24px;
    }
    .action-bar .logo {
      color: #F8F4EC;
      font-size: 1rem;
      letter-spacing: .12em;
      text-transform: uppercase;
      font-weight: 600;
      flex: 1;
    }
    .action-bar .logo span { color: #D6AF5C; }
    .btn-act {
      display: inline-flex; align-items: center; gap: 8px;
      padding: 8px 18px;
      border: 1px solid #D6AF5C;
      background: #D6AF5C;
      color: #0F1A17;
      font-family: \'Poppins\', sans-serif;
      font-size: .75rem;
      font-weight: 600;
      letter-spacing: .06em;
      text-transform: uppercase;
      cursor: pointer;
      text-decoration: none;
      border-radius: 4px;
      transition: all .2s;
    }
    .btn-act:hover { background: #e5be6b; }
    .btn-act.ghost { background: transparent; color: #F8F4EC; border-color: rgba(214,175,92,.4); }
    .btn-act.ghost:hover { background: rgba(214,175,92,.15); border-color: #D6AF5C; }
    .btn-act.green { background: #25D366; border-color: #25D366; color: #fff; }
    .btn-act.green:hover { background: #20ba59; }
      background: #ece8e1;
      color: #111111;
      -webkit-print-color-adjust: exact;
      print-color-adjust: exact;
      min-height: 100vh;
      padding: 20px 10px;
    }
    
    .invoice-wrapper {
      max-width: 860px;
      margin: 0 auto;
      background: #ffffff;
      display: flex;
      box-shadow: 0 12px 40px rgba(0,0,0,0.18);
      min-height: 1180px;
      position: relative;
    }
    
    /* Left African Motif Stripe - Motif d'origine */
    .motif-sidebar {
      width: 65px;
      flex-shrink: 0;
      background-color: #833118;
      background-image: url("data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'65\' height=\'80\' viewBox=\'0 0 65 80\'%3E%3Cpolygon points=\'0,0 35,40 0,80\' fill=\'%23F2C314\'/%3E%3Cpolygon points=\'65,0 30,40 65,80\' fill=\'%23111111\'/%3E%3C/svg%3E");
      background-size: 100% 80px;
      background-repeat: repeat-y;
      border-right: 1px solid #e0e0e0;
    }
    
    .invoice-content {
      flex: 1;
      padding: 36px 44px 40px 38px;
      display: flex;
      flex-direction: column;
      background: #ffffff;
    }
    
    .header-area {
      display: flex;
      justify-content: space-between;
      align-items: flex-start;
      margin-bottom: 24px;
      gap: 20px;
    }
    
    .header-left {
      display: flex;
      align-items: flex-start;
      gap: 16px;
      max-width: 58%;
    }
    .logo-box {
      width: 110px;
      flex-shrink: 0;
      text-align: center;
    }
    .logo-box img {
      width: 100%;
      height: auto;
      max-height: 110px;
      object-fit: contain;
      display: block;
    }
    .company-box {
      font-size: 0.76rem;
      line-height: 1.45;
      color: #222222;
    }
    .company-name {
      font-size: 1.05rem;
      font-weight: 700;
      color: #000000;
      margin-bottom: 2px;
    }
    .company-line {
      margin-bottom: 1px;
    }
    
    .header-right {
      display: flex;
      flex-direction: column;
      align-items: flex-end;
      min-width: 200px;
    }
    .date-row {
      display: flex;
      align-items: center;
      gap: 18px;
      font-size: 0.8rem;
      margin-bottom: 14px;
      color: #111111;
    }
    .date-label {
      font-weight: 600;
      color: #333333;
    }
    .date-val {
      font-weight: 600;
      color: #000000;
    }
    
    .invoiced-to-box {
      border-left: 2px solid #222222;
      padding-left: 14px;
      text-align: left;
      width: 100%;
      font-size: 0.76rem;
      line-height: 1.45;
      color: #222222;
    }
    .invoiced-to-title {
      font-weight: 700;
      font-size: 0.82rem;
      color: #000000;
      margin-bottom: 4px;
    }
    .client-name {
      font-weight: 600;
      color: #000000;
      margin-bottom: 2px;
      text-transform: uppercase;
    }
    
    .inv-table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 10px;
      margin-bottom: 26px;
    }
    .inv-table thead th {
      background: #DE6116;
      color: #ffffff;
      padding: 10px 14px;
      font-size: 0.82rem;
      font-weight: 600;
      text-align: left;
      border: 1px solid #DE6116;
      letter-spacing: 0.02em;
    }
    .inv-table thead th.th-qty {
      width: 12%;
      text-align: left;
    }
    .inv-table thead th.th-desc {
      width: 50%;
    }
    .inv-table thead th.th-unit {
      width: 18%;
      text-align: left;
    }
    .inv-table thead th.th-discount {
      width: 20%;
      text-align: right;
      background: #E88F3C;
      border-color: #E88F3C;
      font-size: 0.72rem;
      line-height: 1.15;
      padding: 6px 12px;
    }
    
    .inv-table tbody td {
      padding: 16px 14px;
      font-size: 0.82rem;
      vertical-align: top;
      background: #FFFFFF;
      border-bottom: 1px solid #ECECEC;
      color: #111111;
      line-height: 1.5;
    }
    .inv-table tbody td.td-qty {
      font-weight: 600;
    }
    .inv-table tbody td.td-desc .desc-title {
      font-weight: 600;
      color: #000000;
      margin-bottom: 3px;
    }
    .inv-table tbody td.td-desc .desc-sub {
      font-size: 0.75rem;
      color: #333333;
      margin-bottom: 2px;
    }
    .inv-table tbody td.td-unit {
      color: #111111;
    }
    .inv-table tbody td.td-total {
      text-align: right;
      font-weight: 700;
      color: #000000;
    }
    
    .summary-wrap {
      display: flex;
      justify-content: flex-end;
      margin-bottom: 26px;
    }
    .summary-box {
      width: 380px;
      font-size: 0.82rem;
      line-height: 1.55;
    }
    .sum-row {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 6px;
    }
    .sum-row.sum-provisional {
      font-weight: 700;
      font-size: 0.88rem;
      color: #000000;
      margin-bottom: 8px;
    }
    .sum-row.sum-provisional .sum-val {
      font-size: 0.95rem;
      font-weight: 700;
    }
    .sum-lbl {
      color: #111111;
    }
    .sum-val {
      color: #111111;
      text-align: right;
    }
    .sum-divider {
      border-bottom: 1px solid #D8D8D8;
      margin: 8px 0 10px;
    }
    .sum-row.sum-due {
      font-weight: 700;
      font-size: 0.88rem;
      color: #000000;
    }
    .sum-row.sum-due .sum-val {
      font-size: 0.95rem;
      font-weight: 700;
    }

    .conditions-area {
      margin-top: 5px;
      font-size: 0.76rem;
      line-height: 1.5;
      color: #222222;
    }
    .cond-block {
      margin-bottom: 15px;
    }
    .cond-title {
      font-weight: 700;
      color: #000000;
      margin-bottom: 3px;
      font-size: 0.8rem;
    }
    .cond-text {
      color: #333333;
    }
    .cond-divider {
      border-bottom: 1px solid #E0E0E0;
      margin: 18px 0 14px;
    }
    .terms-block .cond-title {
      font-size: 0.78rem;
      font-weight: 700;
      margin-bottom: 2px;
    }
    .terms-block .cond-text {
      font-size: 0.72rem;
      color: #444444;
    }
    
    @media print {
      body { background: #ffffff; padding: 0; }
      .invoice-wrapper {
        margin: 0 auto;
        box-shadow: none;
        border: none;
        width: 100%;
        max-width: 100%;
        min-height: auto;
      }
      .motif-sidebar { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
      .inv-table thead th { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    }
  </style>
</head>
<body>
    <div class="action-bar">
      <div class="logo">Baobab <span>Horizon</span> — Facture Officielle</div>
      <a href="./" class="btn-act ghost">← Accueil</a>
      <button type="button" class="btn-act" style="background:#D6AF5C;color:#0F1A17;font-weight:700;" onclick="downloadPDF()">📥 Télécharger PDF</button>
      <button type="button" class="btn-act green" onclick="shareWhatsAppPDF()">💬 Envoyer PDF via WhatsApp</button>
      <button type="button" class="btn-act ghost" onclick="window.print()">🖨️ Imprimer</button>
    </div>

    <div class="invoice-wrapper" id="invoiceBox">
        <div class="motif-sidebar"></div>
        <div class="invoice-content">
            <div class="header-area">
                <div class="header-left">
                    <div class="logo-box">
                        <img src="LOGO.jpg" alt="Baobab Horizon Real Estate">
                    </div>
                    <div class="company-box">
                        <div class="company-name">Daniel Mariat</div>
                        <div class="company-line">Business type: real estate agent</div>
                        <div class="company-line">Address: Saly , 23000 , M\'bour -Thiès region . Senegal</div>
                        <div class="company-line">Phone: +221 78 014 09 42</div>
                        <div class="company-line">Ninea: 012032819</div>
                        <div class="company-line">Rc: 41120919</div>
                    </div>
                </div>

                <div class="header-right">
                    <div class="date-row">
                        <span class="date-label">Date</span>
                        <span class="date-val">' . htmlspecialchars($invDateStr) . '</span>
                    </div>
                    <div class="invoiced-to-box">
                        <div class="invoiced-to-title">Invoiced to</div>
                        <div class="client-name">' . htmlspecialchars($fullName) . '</div>' .
                        (!empty($pi['nationality']) ? '<div class="client-detail">Nationality: ' . htmlspecialchars($pi['nationality']) . '</div>' : '') .
                        (!empty($pi['id_number']) ? '<div class="client-detail">NIE / ID: ' . htmlspecialchars($pi['id_number']) . '</div>' : '') .
                        (!empty($pi['birth_date']) ? '<div class="client-detail">Born on: ' . htmlspecialchars(date('M d, Y', strtotime($pi['birth_date']))) . '</div>' : '') . '
                    </div>
                </div>
            </div>

            <table class="inv-table">
                <thead>
                    <tr>
                        <th class="th-qty">Quantity</th>
                        <th class="th-desc">Description</th>
                        <th class="th-unit">Unit price</th>
                        <th class="th-discount">Price with<br><span style="font-size:0.65rem;opacity:0.95;">discount</span></th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td class="td-qty">' . $nights . '</td>
                        <td class="td-desc">
                            <div class="desc-title">' . htmlspecialchars($villaName) . ' rental - beachfront</div>
                            <div class="desc-sub">Stay from ' . htmlspecialchars($startDateStr) . ' to ' . htmlspecialchars($endDateStr) . ' (' . $nights . ' nights)</div>' .
                            ($hasChef ? '<div class="desc-sub">Private chef included (weekly day off on Sunday)</div>' : '') . '
                        </td>
                        <td class="td-unit">' . number_format($unitPrice, 0, '.', ',') . ' cfa</td>
                        <td class="td-total">' . number_format($totalAmount, 0, '.', ',') . ' cfa</td>
                    </tr>
                </tbody>
            </table>

            <div class="summary-wrap">
                <div class="summary-box">
                    <div class="sum-row sum-provisional">
                        <span class="sum-lbl">Provisional total</span>
                        <span class="sum-val">' . number_format($totalAmount, 0, '.', ',') . ' cfa</span>
                    </div>
                    <div class="sum-row">
                        <span class="sum-lbl">Deposit (50%)</span>
                        <span class="sum-val">' . number_format($deposit50, 0, '.', ',') . ' cfa</span>
                    </div>
                    <div class="sum-row">
                        <span class="sum-lbl">Rental balance (50%)</span>
                        <span class="sum-val">' . number_format($balance50, 0, '.', ',') . ' cfa</span>
                    </div>
                    <div class="sum-row">
                        <span class="sum-lbl">Electricity deposit (' . $nights . ' nights)</span>
                        <span class="sum-val">' . number_format($elecDeposit, 0, '.', ',') . ' cfa</span>
                    </div>
                    <div class="sum-divider"></div>
                    <div class="sum-row sum-due">
                        <span class="sum-lbl">Total balance due on arrival</span>
                        <span class="sum-val">' . number_format($totalDueOnArrival, 0, '.', ',') . ' cfa</span>
                    </div>
                </div>
            </div>

            <div class="conditions-area">
                <div class="cond-block">
                    <div class="cond-title">Electricity at the client\'s expense</div>
                    <div class="cond-text">
                        An electricity deposit of 15,000 cfa/24h is due on arrival, in addition to the balance. At the end of the stay: if actual consumption is below the deposit, the difference is refunded to the client; if it is higher, the client pays the difference recorded.
                    </div>
                </div>' .
                ($hasChef ? '
                <div class="cond-block">
                    <div class="cond-title">Private chef service</div>
                    <div class="cond-text">
                        Private chef included for the entire stay (' . $nights . ' days), with a weekly day off on Sunday. Grocery shopping for the chef is at the client\'s expense.
                    </div>
                </div>' : '') . '
                <div class="cond-block">
                    <div class="cond-title">Payment method</div>
                    <div class="cond-text">' . htmlspecialchars($pmDisplay) . '</div>
                </div>

                <div class="cond-divider"></div>

                <div class="cond-block terms-block">
                    <div class="cond-title">Terms</div>
                    <div class="cond-text">
                        Payments are considered firm and non-refundable, as the selected dates are reserved and blocked for other clients.
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        const _invNum = ' . json_encode($invoiceNumber) . ';
        const _phone = ' . json_encode($reservation['phone'] ?? '') . ';

        function getPdfOptions() {
          return {
            margin:       [0, 0, 0, 0],
            filename:     "Facture_" + (_invNum || "Baobab_Horizon") + ".pdf",
            image:        { type: "jpeg", quality: 0.98 },
            html2canvas:  { scale: 2, useCORS: true, letterRendering: true, scrollY: 0 },
            jsPDF:        { unit: "mm", format: "a4", orientation: "portrait" }
          };
        }

        async function downloadPDF() {
          const element = document.getElementById("invoiceBox");
          if (!element) return;
          const opt = getPdfOptions();
          html2pdf().set(opt).from(element).save();
        }

        async function shareWhatsAppPDF() {
          const element = document.getElementById("invoiceBox");
          if (!element) return;
          const opt = getPdfOptions();
          
          try {
            const pdfBlob = await html2pdf().set(opt).from(element).output("blob");
            const pdfFile = new File([pdfBlob], opt.filename, { type: "application/pdf" });

            // 1. Mobile (iOS / Android) : partage direct du fichier PDF dans WhatsApp
            if (navigator.canShare && navigator.canShare({ files: [pdfFile] })) {
              await navigator.share({
                title: "Facture " + _invNum + " — Baobab Horizon",
                text: "Bonjour, veuillez trouver ci-joint votre facture officielle de réservation Baobab Horizon (N° " + _invNum + ").",
                files: [pdfFile]
              });
              return;
            }

            // 2. PC / Desktop : Téléchargement automatique du PDF + ouverture WhatsApp
            const a = document.createElement("a");
            a.href = URL.createObjectURL(pdfBlob);
            a.download = opt.filename;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);

            const waPhone = _phone ? _phone.replace(/\\D/g, "").replace(/^0/, "221") : "221780140942";
            const waText = "Bonjour,\\n\\nVeuillez trouver ci-joint votre facture officielle de réservation Baobab Horizon (N° " + _invNum + ").\\n\\n📄 Fichier PDF généré : " + opt.filename;
            window.open("https://wa.me/" + waPhone + "?text=" + encodeURIComponent(waText), "_blank");

          } catch (e) {
            console.error("Erreur génération PDF:", e);
            downloadPDF();
          }
        }

        window.onload = function() {
            const params = new URLSearchParams(window.location.search);
            if (params.get("download") === "1") {
                setTimeout(downloadPDF, 400);
            } else if (params.get("print") === "1") {
                window.print();
            }
        };
    </script>
</body>
</html>';
        
        echo $html;
        exit;
    }
}

if (!$accessKey) {
    $error = 'Clé d\'accès manquante';
} else {
    $reservations = readReservations();
    
    foreach ($reservations['validated'] as $res) {
        if (isset($res['access_key']) && $res['access_key'] === $accessKey) {
            $reservation = $res;
            break;
        }
    }
    
    if (!$reservation) {
        $error = 'Réservation non trouvée ou clé invalide';
    }
}

// Handle form submission for personal information and payment
if ($reservation && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'submit_invoice') {
        ensureUploadDirs();
        
        // Handle file upload
        $idDocumentPath = '';
        if (isset($_FILES['id_document']) && $_FILES['id_document']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['id_document'];
            $allowedTypes = ['image/jpeg', 'image/png', 'image/jpg', 'application/pdf'];
            $maxSize = 5 * 1024 * 1024; // 5MB
            
            if (!in_array($file['type'], $allowedTypes)) {
                $error = 'Type de fichier non autorisé. Formats acceptés: JPG, PNG, PDF';
            } elseif ($file['size'] > $maxSize) {
                $error = 'Fichier trop volumineux. Maximum 5MB.';
            } else {
                $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
                $filename = 'id_' . $accessKey . '_' . time() . '.' . $extension;
                $destination = rtrim(UPLOADS_DIR, '/\\') . '/' . $filename;
                
                if (move_uploaded_file($file['tmp_name'], $destination)) {
                    $idDocumentPath = $filename;
                    // Sync the uploaded file to Location/data/uploads too
                    syncUploadFile($filename, $destination);
                } else {
                    $error = 'Erreur lors du téléchargement du fichier.';
                }
            }
        }
        
        if (!$error) {
            // Generate invoice number
            $invoiceNumber = generateInvoiceNumber();
            
            // Collect personal information
            $personalInfo = [
                'birth_date' => trim($_POST['birth_date'] ?? ''),
                'nationality' => trim($_POST['nationality'] ?? ''),
                'address' => trim($_POST['address'] ?? ''),
                'id_number' => trim($_POST['id_number'] ?? ''),
                'id_document' => $idDocumentPath,
                'payment_method' => trim($_POST['payment_method'] ?? ''),
                'invoice_send_method' => trim($_POST['invoice_send_method'] ?? '')
            ];
            
            // Update reservation with personal info and invoice number (BOTH files)
            $reservations = readReservations();
            foreach ($reservations['validated'] as &$res) {
                if (isset($res['access_key']) && $res['access_key'] === $accessKey) {
                    $res['personal_info'] = $personalInfo;
                    $res['invoice_number'] = $invoiceNumber;
                    $res['invoice_generated'] = true;
                    $res['invoice_generated_at'] = date('Y-m-d H:i:s');
                    // Sanity: ensure start_date/end_date exist
                    if (empty($res['start_date'])) $res['start_date'] = $res['start'];
                    if (empty($res['end_date']))   $res['end_date']   = $res['end'];
                    break;
                }
            }
            
            writeReservations($reservations);
            
            $invoiceResult = sendInvoiceRequest($accessKey, $personalInfo['invoice_send_method']);
            
            $invoiceGenerated = true;
            $invoiceSentResult = $invoiceResult ?? null;
            
            // Reload updated reservation
            foreach (readReservations()['validated'] as $res) {
                if (isset($res['access_key']) && $res['access_key'] === $accessKey) {
                    $reservation = $res;
                    break;
                }
            }
        }
    }
}

$properties = readProperties();
$villaData = isset($reservation['villa']) && isset($properties[$reservation['villa']]) ? $properties[$reservation['villa']] : null;
// Normalize display fields
if ($reservation) {
    if (empty($reservation['start'])) $reservation['start'] = $reservation['start_date'] ?? '';
    if (empty($reservation['end']))   $reservation['end']   = $reservation['end_date'] ?? '';
    if (empty($reservation['villa_name']) && $villaData) $reservation['villa_name'] = $villaData['name'];
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Réservation — Baobab Horizon</title>
<link href="https://fonts.googleapis.com/css2?family=Lora:ital,wght@0,400;0,500;0,600;1,400;1,500&family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{--sand: #0F1A17;--gold: #9C6F1C;--gold-light: #D6AF5C;--night: #F8F4EC;--night-mid: #EDE3D2;--night-soft: #e2d6c3;--cream: #0F1A17;--text-muted: #6b7c78;--font-display: 'Lora', Georgia, serif;--font-body: 'Poppins', sans-serif;}
body{background:var(--night);color:var(--sand);font-family:var(--font-body);font-weight:300;min-height:100vh;padding:40px 20px}
.container{max-width:900px;margin:0 auto}
.error-page{text-align:center;padding:60px 20px}
.error-title{font-family:var(--font-display);font-size:2.5rem;font-weight:300;color:var(--gold);margin-bottom:16px}
.error-text{color:var(--text-muted);font-size:1.1rem;line-height:1.6}
.header{margin-bottom:40px;padding-bottom:20px;border-bottom:1px solid rgba(184,147,90,.2)}
.logo{font-family:var(--font-display);font-size:1.8rem;letter-spacing:.2em;text-transform:uppercase;color:var(--cream)}
.logo span{color:var(--gold)}
.section{background:var(--night-mid);border:1px solid rgba(184,147,90,.15);padding:32px 28px;margin-bottom:24px}
.section-title{font-family:var(--font-display);font-size:1.8rem;font-weight:300;color:var(--cream);margin-bottom:20px}
.info-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:16px;margin-bottom:20px}
.info-item{padding:16px;background:rgba(184,147,90,.06);border:1px solid rgba(184,147,90,.12)}
.info-label{font-size:.7rem;letter-spacing:.15em;text-transform:uppercase;color:var(--text-muted);margin-bottom:8px}
.info-value{font-size:1.1rem;color:var(--sand)}
.form-grid{display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:20px}
.form-field{margin-bottom:16px}
.form-field.full{grid-column:1/-1}
.form-field label{display:block;font-size:.7rem;letter-spacing:.15em;text-transform:uppercase;color:var(--sand);margin-bottom:8px}
.form-field input,.form-field select,.form-field textarea{width:100%;border:1px solid rgba(184,147,90,.25);background:rgba(255,255,255,.04);color:var(--cream);padding:14px 16px;font:inherit;font-size:.9rem;outline:none}
.form-field input:focus,.form-field select:focus,.form-field textarea:focus{border-color:var(--gold)}
.btn{display:inline-flex;align-items:center;gap:12px;background:var(--gold);color:var(--night);font-size:.62rem;letter-spacing:.2em;text-transform:uppercase;padding:14px 28px;border:0;cursor:pointer;transition:background .3s}
.btn:hover{background:var(--gold-light)}
.btn-secondary{background:transparent;border:1px solid rgba(184,147,90,.4);color:var(--sand)}
.btn-secondary:hover{border-color:var(--gold);color:var(--gold)}
.payment-options{display:grid;grid-template-columns:repeat(3,1fr);gap:12px;margin-bottom:20px}
.payment-option{padding:20px;border:2px solid rgba(184,147,90,.2);background:rgba(184,147,90,.04);cursor:pointer;transition:border-color .3s,background .3s;text-align:center}
.payment-option:hover{border-color:var(--gold);background:rgba(184,147,90,.1)}
.payment-option.selected{border-color:var(--gold);background:rgba(184,147,90,.15)}
.payment-option input{display:none}
.payment-icon{font-size:2rem;margin-bottom:8px}
.payment-name{font-size:.9rem;color:var(--sand);font-weight:500}
.invoice-preview{background:rgba(90,158,111,.08);border:1px solid rgba(90,158,111,.25);padding:24px;margin-top:20px}
.invoice-title{font-family:var(--font-display);font-size:1.5rem;color:var(--gold);margin-bottom:16px}
.invoice-row{display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid rgba(184,147,90,.1)}
.invoice-row:last-child{border-bottom:none}
.invoice-row.total{font-size:1.2rem;font-weight:500;color:var(--gold);margin-top:12px;padding-top:12px;border-top:2px solid var(--gold)}
@media(max-width:600px){.form-grid,.payment-options{grid-template-columns:1fr}}
</style>
</head>
<body>

<?php if ($error): ?>
<div class="error-page">
  <h1 class="error-title">Accès refusé</h1>
  <p class="error-text"><?= h($error) ?></p>
  <p style="margin-top:20px"><a href="./" class="btn btn-secondary">Retour à l'accueil</a></p>
</div>
<?php elseif ($reservation): ?>
<div class="container">
  <div class="header">
    <div class="logo">Baobab <span>Horizon</span></div>
    <p style="color:var(--text-muted);margin-top:8px">Espace réservation privée</p>
  </div>

  <?php if ((isset($invoiceGenerated) && $invoiceGenerated) || !empty($reservation['invoice_generated'])): ?>
  <div class="section">
    <h2 class="section-title">✅ Facture générée avec succès</h2>
    <p style="color:var(--text-muted);margin-bottom:20px">Votre facture a été générée et envoyée par <?= h($reservation['personal_info']['invoice_send_method'] === 'whatsapp' ? 'WhatsApp' : 'email') ?>.</p>
    
    <?php if (isset($invoiceSentResult) && $invoiceSentResult): ?>
      <?php if (isset($invoiceSentResult['method']) && $invoiceSentResult['method'] === 'whatsapp' && isset($invoiceSentResult['url'])): ?>
        <div style="background:rgba(184,147,90,.1);border:1px solid rgba(184,147,90,.3);padding:16px;margin-bottom:20px">
          <p style="margin-bottom:12px">Cliquez sur le bouton ci-dessous pour envoyer la facture via WhatsApp :</p>
          <a href="<?= h($invoiceSentResult['url']) ?>" target="_blank" class="btn">Ouvrir WhatsApp</a>
        </div>
      <?php endif; ?>
    <?php endif; ?>
    
    <div class="invoice-preview">
      <div class="invoice-title">Facture #<?= h($reservation['invoice_number'] ?? $reservation['id']) ?></div>
      <div class="invoice-row">
        <span>Client:</span>
        <span><?= h($reservation['first_name']) ?> <?= h($reservation['last_name']) ?></span>
      </div>
      <div class="invoice-row">
        <span>Villa:</span>
        <span><?= h($villaData ? $villaData['name'] : $reservation['villa']) ?></span>
      </div>
      <div class="invoice-row">
        <span>Dates:</span>
        <span><?= h(date('d/m/Y', strtotime($reservation['start_date']))) ?> → <?= h(date('d/m/Y', strtotime($reservation['end_date']))) ?></span>
      </div>
      <div class="invoice-row">
        <span>Nombre de nuits:</span>
        <span><?= h($reservation['nights']) ?></span>
      </div>
      <div class="invoice-row">
        <span>Nombre de personnes:</span>
        <span><?= h($reservation['guests']) ?></span>
      </div>
      <div class="invoice-row total">
        <span>Total:</span>
        <span><?= number_format($reservation['total_amount'], 0, ',', ' ') ?> FCFA</span>
      </div>
    </div>
    
    <div style="margin-top:24px;display:flex;gap:12px;flex-wrap:wrap;">
      <a href="?key=<?= h($accessKey) ?>&download=1" class="btn" style="background:var(--gold);color:#0F1A17;text-decoration:none;font-weight:700;">📥 Télécharger la Facture PDF</a>
      <a href="?key=<?= h($accessKey) ?>" target="_blank" class="btn btn-secondary" style="text-decoration:none;">📄 Voir / Partager via WhatsApp</a>
    </div>
    
    <div style="margin-top:20px">
      <a href="./" class="btn btn-secondary">Retour à l'accueil</a>
    </div>
  </div>
  <?php else: ?>
  
  <div class="section">
    <h2 class="section-title">Votre réservation</h2>
    <div class="info-grid">
      <div class="info-item">
        <div class="info-label">Villa</div>
        <div class="info-value"><?= h($reservation['villa_name']) ?></div>
      </div>
      <div class="info-item">
        <div class="info-label">Dates</div>
        <div class="info-value"><?= h($reservation['start']) ?> → <?= h($reservation['end']) ?></div>
      </div>
      <div class="info-item">
        <div class="info-label">Durée</div>
        <div class="info-value"><?= h($reservation['nights']) ?> nuits</div>
      </div>
      <div class="info-item">
        <div class="info-label">Personnes</div>
        <div class="info-value"><?= h($reservation['guests']) ?></div>
      </div>
      <div class="info-item">
        <div class="info-label">Chef cuisinier</div>
        <div class="info-value"><?= h($reservation['chef']) ?></div>
      </div>
      <div class="info-item">
        <div class="info-label">Montant total</div>
        <div class="info-value" style="color:var(--gold)"><?= number_format($reservation['total_amount'], 0, ',', ' ') ?> FCFA</div>
      </div>
    </div>
  </div>

  <div class="section">
    <h2 class="section-title">Informations personnelles</h2>
    <p style="color:var(--text-muted);margin-bottom:20px">Veuillez compléter vos informations pour la facturation.</p>
    
    <form method="POST" enctype="multipart/form-data">
      <input type="hidden" name="action" value="submit_invoice">
      
      <div class="form-grid">
        <div class="info-item" style="grid-column:1/-1">
          <div class="info-label">Nom complet</div>
          <div class="info-value"><?= h($reservation['first_name']) ?> <?= h($reservation['last_name']) ?></div>
        </div>
        <div class="info-item">
          <div class="info-label">Email</div>
          <div class="info-value"><?= h($reservation['email']) ?></div>
        </div>
        <div class="info-item">
          <div class="info-label">Téléphone</div>
          <div class="info-value"><?= h($reservation['phone']) ?></div>
        </div>
      </div>
      
      <div class="form-grid">
        <div class="form-field">
          <label for="birth_date">Date de naissance</label>
          <input type="date" id="birth_date" name="birth_date" required>
        </div>
        <div class="form-field">
          <label for="nationality">Nationalité</label>
          <input type="text" id="nationality" name="nationality" placeholder="Ex: Sénégalaise" required>
        </div>
        <div class="form-field full">
          <label for="address">Adresse complète</label>
          <input type="text" id="address" name="address" placeholder="Votre adresse complète" required>
        </div>
        <div class="form-field">
          <label for="id_number">Numéro de pièce d'identité</label>
          <input type="text" id="id_number" name="id_number" placeholder="CNI ou passeport" required>
        </div>
        <div class="form-field full">
          <label for="id_document">Télécharger votre pièce d'identité</label>
          <input type="file" id="id_document" name="id_document" accept="image/*,.pdf" required>
          <p style="font-size:0.75rem;color:var(--text-muted);margin-top:6px">Formats acceptés: JPG, PNG, PDF (max 5MB)</p>
        </div>
      </div>

      <div class="section" style="margin-top:24px;padding:24px 0 0;border-top:1px solid rgba(184,147,90,.15);background:transparent;border-left:0;border-right:0;border-bottom:0">
        <h2 class="section-title">Mode de paiement</h2>
        <p style="color:var(--text-muted);margin-bottom:20px">Choisissez votre mode de paiement préféré. Aucun paiement en ligne n'est demandé à ce stade.</p>
        
        <div class="payment-options">
          <label class="payment-option">
            <input type="radio" name="payment_method" value="orange_money" required>
            <div class="payment-name">Orange Money</div>
          </label>
          <label class="payment-option">
            <input type="radio" name="payment_method" value="wave">
            <div class="payment-name">Wave</div>
          </label>
          <label class="payment-option">
            <input type="radio" name="payment_method" value="bank_transfer">
            <div class="payment-name">Virement bancaire</div>
          </label>
        </div>
        
        <div class="form-field">
          <label for="invoice_send_method">Recevoir la facture par</label>
          <select id="invoice_send_method" name="invoice_send_method" required>
            <option value="whatsapp">WhatsApp</option>
            <option value="email">Email</option>
          </select>
        </div>
        
        <div style="margin-top:24px;display:flex;gap:12px">
          <button type="submit" class="btn">Générer ma facture</button>
        </div>
      </div>
    </form>
  </div>
  <?php endif; ?>
</div>

<script>
// Payment option selection
document.querySelectorAll('.payment-option').forEach(option => {
  option.addEventListener('click', function() {
    document.querySelectorAll('.payment-option').forEach(o => o.classList.remove('selected'));
    this.classList.add('selected');
    this.querySelector('input').checked = true;
  });
});
</script>

<?php endif; ?>

</body>
</html>
