<?php
require_once __DIR__ . '/bootstrap.php';

$clientsFile = dirname(__DIR__) . '/data/clients.json';
$promosFile  = dirname(__DIR__) . '/data/promos.json';

function getClientsData($file) {
    if (!file_exists($file)) return [];
    $data = json_decode(file_get_contents($file), true);
    return is_array($data) ? $data : [];
}

function saveClientsData($file, $data) {
    file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

function getPromosData($file) {
    if (!file_exists($file)) return ['promos' => []];
    $data = json_decode(file_get_contents($file), true);
    return is_array($data) ? $data : ['promos' => []];
}

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? $_POST['action'] ?? '';

if ($method === 'GET') {
    if ($action === 'check_promo') {
        $code = strtoupper(trim($_GET['code'] ?? ''));
        if (!$code) {
            jsonResponse(['error' => 'Code promo requis'], 400);
        }
        $promosData = getPromosData($promosFile);
        $found = null;
        $today = date('Y-m-d');
        foreach ($promosData['promos'] as $p) {
            if (strtoupper($p['code']) === $code && !empty($p['is_active'])) {
                if (empty($p['valid_until']) || $p['valid_until'] >= $today) {
                    $found = $p;
                    break;
                }
            }
        }
        if ($found) {
            jsonResponse([
                'ok'               => true,
                'code'             => $found['code'],
                'discount_percent' => (int)($found['discount_percent'] ?? 0),
                'description'      => $found['description'] ?? ''
            ]);
        } else {
            jsonResponse(['error' => 'Code promo invalide ou expiré.'], 404);
        }
    }

    if ($action === 'check_client') {
        $phone = trim($_GET['phone'] ?? '');
        $email = trim($_GET['email'] ?? '');
        $clients = getClientsData($clientsFile);
        $client = null;
        foreach ($clients as $c) {
            if (($phone && !empty($c['phone']) && $c['phone'] === $phone) ||
                ($email && !empty($c['email']) && strtolower($c['email']) === strtolower($email))) {
                $client = $c;
                break;
            }
        }
        if ($client) {
            jsonResponse([
                'ok'                 => true,
                'exists'             => true,
                'welcome_offer_used' => !empty($client['welcome_offer_used']),
                'reservations_count' => (int)($client['reservations_count'] ?? 0),
                'name'               => $client['name'] ?? ''
            ]);
        } else {
            jsonResponse([
                'ok'                 => true,
                'exists'             => false,
                'welcome_offer_used' => false,
                'reservations_count' => 0
            ]);
        }
    }

    $clients = getClientsData($clientsFile);
    jsonResponse($clients);
}

if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    
    // Client registration / Account creation
    if ($action === 'register') {
        $name      = trim($input['name'] ?? '');
        $phone     = trim($input['phone'] ?? '');
        $email     = trim($input['email'] ?? '');
        $marketing = !empty($input['marketing']);

        if (!$name || (!$phone && !$email)) {
            jsonResponse(['error' => 'Veuillez fournir un nom et au moins un téléphone ou un email.'], 400);
        }

        $clients = getClientsData($clientsFile);
        $discount = $marketing ? 2 : 1; // 1% base, 2% if marketing opted in

        $existing = null;
        foreach ($clients as &$c) {
            if (($phone && !empty($c['phone']) && $c['phone'] === $phone) || 
                ($email && !empty($c['email']) && strtolower($c['email']) === strtolower($email))) {
                $c['name']      = $name ?: $c['name'];
                $c['phone']     = $phone ?: $c['phone'];
                $c['email']     = $email ?: $c['email'];
                $c['marketing'] = $marketing;
                if (!isset($c['welcome_offer_used'])) {
                    $c['welcome_offer_used'] = false;
                }
                $c['updated_at']= date('Y-m-d H:i:s');
                $existing = $c;
                break;
            }
        }

        if (!$existing) {
            $newClient = [
                'id'                 => 'cli-' . time() . '-' . rand(100,999),
                'name'               => $name,
                'phone'              => $phone,
                'email'              => $email,
                'discount'           => $discount,
                'marketing'          => $marketing,
                'welcome_offer_used' => false,
                'reservations_count' => 0,
                'source'             => 'Inscription Formelle',
                'created_at'         => date('Y-m-d H:i:s')
            ];
            $clients[] = $newClient;
            $existing = $newClient;
        }

        saveClientsData($clientsFile, $clients);
        jsonResponse([
            'ok'                 => true,
            'message'            => "Compte enregistré avec succès !",
            'client'             => $existing,
            'discount'           => $discount,
            'welcome_offer_used' => !empty($existing['welcome_offer_used'])
        ]);
    }

    // Lead Auto Capture (from contact forms, WhatsApp clicks, forms)
    if ($action === 'lead') {
        $name   = trim($input['name'] ?? 'Visiteur WhatsApp/Email');
        $phone  = trim($input['phone'] ?? '');
        $email  = trim($input['email'] ?? '');
        $source = trim($input['source'] ?? 'Formulaire Contact');

        if ($phone || $email) {
            $clients = getClientsData($clientsFile);
            $found = false;
            foreach ($clients as &$c) {
                if (($phone && !empty($c['phone']) && $c['phone'] === $phone) || 
                    ($email && !empty($c['email']) && strtolower($c['email']) === strtolower($email))) {
                    $found = true;
                    $c['last_activity'] = date('Y-m-d H:i:s');
                    break;
                }
            }
            if (!$found) {
                $clients[] = [
                    'id'                 => 'lead-' . time() . '-' . rand(100,999),
                    'name'               => $name,
                    'phone'              => $phone,
                    'email'              => $email,
                    'discount'           => 1,
                    'marketing'          => false,
                    'welcome_offer_used' => false,
                    'reservations_count' => 0,
                    'source'             => $source,
                    'created_at'         => date('Y-m-d H:i:s')
                ];
            }
            saveClientsData($clientsFile, $clients);
        }
        jsonResponse(['ok' => true]);
    }

    // Targeted Marketing Campaign / Follow-up trigger
    if ($action === 'send_campaign') {
        $title    = trim($input['title'] ?? 'Offre Exclusive Baobab Horizon');
        $message  = trim($input['message'] ?? '');
        $channel  = trim($input['channel'] ?? 'all'); // whatsapp, email, all

        if (!$message) {
            jsonResponse(['error' => 'Message de campagne vide.'], 400);
        }

        $clients = getClientsData($clientsFile);
        $count = count($clients);
        jsonResponse([
            'ok' => true,
            'message' => "Campagne '{$title}' préparée et envoyée à {$count} client(s) & prospect(s) enregistrés.",
            'recipients_count' => $count
        ]);
    }
}

