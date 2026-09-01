<?php
header('Content-Type: application/json; charset=utf-8');
error_reporting(E_ALL);
ini_set('display_errors', 0);

require __DIR__ . '/bootstrap.php';

// Auth check
if (empty($_SESSION['admin_logged_in']) && empty($_SESSION['admin'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Non autorisé']);
    exit;
}

$action = $_GET['action'] ?? ($_POST['action'] ?? '');
$method = $_SERVER['REQUEST_METHOD'];

$INVOICES_FILE = __DIR__ . '/../data/invoices.json';
$SETTINGS_FILE = __DIR__ . '/../data/compta_settings.json';

// Initialise folders
if (!is_dir(dirname($INVOICES_FILE))) {
    mkdir(dirname($INVOICES_FILE), 0755, true);
}

// Helpers
function readJson($file, $default = []) {
    if (!file_exists($file)) return $default;
    $d = json_decode(file_get_contents($file), true);
    return is_array($d) ? $d : $default;
}

function writeJson($file, $data) {
    file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

function safeJsonResponse($data, $code = 200) {
    http_response_code($code);
    echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}

// ──────────────────────────────────────────────────────────────
// API ENDPOINTS
// ──────────────────────────────────────────────────────────────

if ($action === 'get_settings' && $method === 'GET') {
    $defaultSettings = [
        'tax_rate' => 18,
        'tax_method' => 'HT', // 'HT' or 'TTC'
        'currency' => 'FCFA',
        'company_name' => 'Baobab Horizon',
        'company_address' => 'Sénégal',
        'company_phone' => '',
        'company_email' => '',
        'payment_methods' => ['Espèces', 'Virement bancaire', 'Wave', 'Orange Money', 'Carte bancaire', 'Autre']
    ];
    $settings = array_merge($defaultSettings, readJson($SETTINGS_FILE, []));
    safeJsonResponse(['settings' => $settings]);
}

if ($action === 'save_settings' && $method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!is_array($input)) safeJsonResponse(['error' => 'Invalid data'], 400);
    
    $settings = readJson($SETTINGS_FILE, []);
    $settings = array_merge($settings, $input);
    writeJson($SETTINGS_FILE, $settings);
    safeJsonResponse(['ok' => true]);
}

if ($action === 'get_invoices' && $method === 'GET') {
    $invoices = readJson($INVOICES_FILE, []);
    
    // Sort by created_at descending
    usort($invoices, function($a, $b) {
        return strcmp($b['created_at'], $a['created_at']);
    });
    
    safeJsonResponse(['invoices' => $invoices]);
}

if ($action === 'save_invoice' && $method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!is_array($input)) safeJsonResponse(['error' => 'Invalid data'], 400);
    
    $invoices = readJson($INVOICES_FILE, []);
    
    $id = $input['id'] ?? '';
    $isNew = false;
    
    if (!$id) {
        // Generate new ID: FAC-YYYY-XXXX
        $year = date('Y');
        $maxNum = 0;
        foreach ($invoices as $inv) {
            if (preg_match('/^FAC-'.$year.'-(\d{4})$/', $inv['id'], $m)) {
                $num = intval($m[1]);
                if ($num > $maxNum) $maxNum = $num;
            }
        }
        $id = sprintf("FAC-%s-%04d", $year, $maxNum + 1);
        $isNew = true;
    }
    
    // Find index if update
    $idx = -1;
    foreach ($invoices as $i => $inv) {
        if ($inv['id'] === $id) { $idx = $i; break; }
    }
    
    $invoice = $idx >= 0 ? $invoices[$idx] : [];
    if (!$isNew && $idx < 0) {
        safeJsonResponse(['error' => 'Facture introuvable'], 404);
    }
    
    $invoice['id'] = $id;
    $invoice['created_at'] = $input['created_at'] ?? ($invoice['created_at'] ?? date('Y-m-d'));
    $invoice['due_date'] = $input['due_date'] ?? '';
    $invoice['client'] = $input['client'] ?? [];
    $invoice['items'] = $input['items'] ?? [];
    $invoice['totals'] = $input['totals'] ?? [];
    $invoice['status'] = $input['status'] ?? ($invoice['status'] ?? 'Brouillon');
    
    if (!isset($invoice['payments'])) $invoice['payments'] = [];
    if (!isset($invoice['history'])) $invoice['history'] = [];
    
    $invoice['history'][] = [
        'action' => $isNew ? 'Création' : 'Modification',
        'date' => date('Y-m-d H:i:s'),
        'user' => $_SESSION['admin_user'] ?? 'Admin'
    ];
    
    if ($idx >= 0) {
        $invoices[$idx] = $invoice;
    } else {
        $invoices[] = $invoice;
    }
    
    writeJson($INVOICES_FILE, $invoices);
    safeJsonResponse(['ok' => true, 'invoice' => $invoice]);
}

if ($action === 'add_payment' && $method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $id = $input['id'] ?? '';
    if (!$id) safeJsonResponse(['error' => 'ID manquant'], 400);
    
    $invoices = readJson($INVOICES_FILE, []);
    $idx = -1;
    foreach ($invoices as $i => $inv) {
        if ($inv['id'] === $id) { $idx = $i; break; }
    }
    if ($idx < 0) safeJsonResponse(['error' => 'Facture introuvable'], 404);
    
    $payment = [
        'id' => uniqid('pay_'),
        'date' => $input['date'] ?? date('Y-m-d'),
        'amount' => floatval($input['amount'] ?? 0),
        'method' => $input['method'] ?? 'Espèces',
        'ref' => $input['ref'] ?? '',
        'comment' => $input['comment'] ?? ''
    ];
    
    $invoices[$idx]['payments'][] = $payment;
    
    // Recalculate totals
    $paid = 0;
    foreach ($invoices[$idx]['payments'] as $p) {
        $paid += $p['amount'];
    }
    $invoices[$idx]['totals']['paid'] = $paid;
    $invoices[$idx]['totals']['due'] = max(0, $invoices[$idx]['totals']['ttc'] - $paid);
    
    // Auto update status
    if ($invoices[$idx]['totals']['due'] <= 0) {
        $invoices[$idx]['status'] = 'Payée';
    } else if ($paid > 0) {
        $invoices[$idx]['status'] = 'Partiellement payée';
    }
    
    $invoices[$idx]['history'][] = [
        'action' => 'Paiement ajouté: ' . $payment['amount'] . ' (' . $payment['method'] . ')',
        'date' => date('Y-m-d H:i:s'),
        'user' => $_SESSION['admin_user'] ?? 'Admin'
    ];
    
    writeJson($INVOICES_FILE, $invoices);
    safeJsonResponse(['ok' => true, 'invoice' => $invoices[$idx]]);
}

if ($action === 'change_status' && $method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $id = $input['id'] ?? '';
    $status = $input['status'] ?? '';
    if (!$id || !$status) safeJsonResponse(['error' => 'Paramètres manquants'], 400);
    
    $invoices = readJson($INVOICES_FILE, []);
    $idx = -1;
    foreach ($invoices as $i => $inv) {
        if ($inv['id'] === $id) { $idx = $i; break; }
    }
    if ($idx < 0) safeJsonResponse(['error' => 'Facture introuvable'], 404);
    
    $oldStatus = $invoices[$idx]['status'];
    $invoices[$idx]['status'] = $status;
    
    $invoices[$idx]['history'][] = [
        'action' => "Statut changé: $oldStatus → $status",
        'date' => date('Y-m-d H:i:s'),
        'user' => $_SESSION['admin_user'] ?? 'Admin'
    ];
    
    writeJson($INVOICES_FILE, $invoices);
    safeJsonResponse(['ok' => true, 'invoice' => $invoices[$idx]]);
}

safeJsonResponse(['error' => 'Action inconnue'], 400);
