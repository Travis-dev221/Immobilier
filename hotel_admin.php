<?php
session_start();

// Config paths
define('ADMIN_SECRET_FILE', __DIR__ . '/data/admin.secret.php');
define('ROOMS_FILE', __DIR__ . '/data/hotel_rooms.json');
define('RESERVATIONS_FILE', __DIR__ . '/data/hotel_reservations.json');
define('PAYMENTS_FILE', __DIR__ . '/data/hotel_payments.json');

// Get passwords
$adminConfig = file_exists(ADMIN_SECRET_FILE) ? require(ADMIN_SECRET_FILE) : [];
$passwords = $adminConfig['passwords'] ?? [$adminConfig['password'] ?? 'Baobab2026'];

// Check connection
$msg = '';
$msgType = 'ok';

$blockFile = __DIR__ . '/data/blocked_ips.json';
$ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';

if (isset($_POST['action']) && $_POST['action'] === 'login') {
    $attempts = file_exists($blockFile) ? json_decode(file_get_contents($blockFile), true) : [];

    if (($attempts[$ip] ?? 0) >= 5) {
        $msg = 'Compte bloqué après 5 tentatives échouées.';
        $msgType = 'err';
    } else {
        $inputPassword = trim($_POST['password'] ?? '');
        $authenticated = false;
        foreach ($passwords as $pwd) {
            if (password_verify($inputPassword, $pwd) || $inputPassword === $pwd) {
                $authenticated = true;
                break;
            }
        }
        if ($authenticated) {
            if (isset($attempts[$ip])) {
                unset($attempts[$ip]);
                file_put_contents($blockFile, json_encode($attempts));
            }
            $_SESSION['hotel_admin'] = true;
            header('Location: hotel_admin.php');
            exit;
        } else {
            $attempts[$ip] = ($attempts[$ip] ?? 0) + 1;
            file_put_contents($blockFile, json_encode($attempts));
            
            $rem = 5 - $attempts[$ip];
            if ($rem > 0) {
                $msg = 'Mot de passe incorrect. ' . $rem . ' tentative(s) restante(s).';
            } else {
                $msg = 'Compte bloqué après 5 tentatives échouées.';
            }
            $msgType = 'err';
        }
    }
}

if (isset($_GET['logout'])) {
    unset($_SESSION['hotel_admin']);
    header('Location: hotel_admin.php');
    exit;
}

// Connexion automatique si la session admin principale est active
if (!empty($_SESSION['admin_logged_in'])) {
    $_SESSION['hotel_admin'] = true;
}

$logged = !empty($_SESSION['hotel_admin']);

// Action to download a ZIP backup of all data
if ($logged && isset($_GET['action']) && $_GET['action'] === 'download_backup') {
    $dataDir = dirname(RESERVATIONS_FILE);
    $zip = new ZipArchive();
    $zipName = 'backup_data_hotel_' . date('Y-m-d') . '.zip';
    
    // Create zip in temporary file
    $tempFile = tempnam(sys_get_temp_dir(), 'zip');
    
    if ($zip->open($tempFile, ZipArchive::OVERWRITE) === TRUE) {
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dataDir),
            RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($files as $name => $file) {
            if (!$file->isDir()) {
                $filePath = $file->getRealPath();
                $relativePath = substr($filePath, strlen($dataDir) + 1);
                
                // Exclude PHP script files from backup for security (contains passwords)
                if (pathinfo($filePath, PATHINFO_EXTENSION) !== 'php') {
                    $zip->addFile($filePath, $relativePath);
                }
            }
        }
        $zip->close();

        // Clear output buffer to avoid corrupted zip files
        if (ob_get_level()) {
            ob_end_clean();
        }

        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="' . $zipName . '"');
        header('Content-Length: ' . filesize($tempFile));
        header('Pragma: no-cache');
        header('Expires: 0');
        readfile($tempFile);
        unlink($tempFile);
        exit;
    }
}

// Helpers
function readJson($path) {
    if (!file_exists($path)) return [];
    return json_decode(file_get_contents($path), true) ?: [];
}

function writeJson($path, $data) {
    file_put_contents($path, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);
}

// POST actions for logged admin
if ($logged && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    
    // Validate a reservation and mark payment as validated / paid
    if ($_POST['action'] === 'validate_booking') {
        $bid = $_POST['booking_id'] ?? '';
        $reservations = readJson(RESERVATIONS_FILE);
        $payments = readJson(PAYMENTS_FILE);
        
        if (isset($reservations[$bid])) {
            $reservations[$bid]['status'] = 'Validé';
            writeJson(RESERVATIONS_FILE, $reservations);
        }
        if (isset($payments[$bid])) {
            $payments[$bid]['status'] = 'Payé';
            writeJson(PAYMENTS_FILE, $payments);
        }
        $msg = "La réservation $bid a été validée avec succès.";
    }

    // Reject a reservation
    if ($_POST['action'] === 'reject_booking') {
        $bid = $_POST['booking_id'] ?? '';
        $reservations = readJson(RESERVATIONS_FILE);
        $payments = readJson(PAYMENTS_FILE);
        
        if (isset($reservations[$bid])) {
            $reservations[$bid]['status'] = 'Refusé';
            writeJson(RESERVATIONS_FILE, $reservations);
        }
        if (isset($payments[$bid])) {
            $payments[$bid]['status'] = 'Refusé';
            writeJson(PAYMENTS_FILE, $payments);
        }
        $msg = "La réservation $bid a été refusée.";
    }

    // Change room status, price or image
    if ($_POST['action'] === 'update_room') {
        $rid = $_POST['room_id'] ?? '';
        $status = $_POST['status'] ?? '';
        $price = intval($_POST['price'] ?? 0);
        $image = trim($_POST['image'] ?? '');
        
        // Handle photo file upload
        if (isset($_FILES['room_photo']) && $_FILES['room_photo']['error'] === UPLOAD_ERR_OK) {
            $fileTmpPath = $_FILES['room_photo']['tmp_name'];
            $fileName = $_FILES['room_photo']['name'];
            $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            $newFileName = 'room_' . $rid . '_' . time() . '.' . $fileExtension;
            $uploadFileDir = __DIR__ . '/uploads/';
            if (!is_dir($uploadFileDir)) {
                mkdir($uploadFileDir, 0755, true);
            }
            $dest_path = $uploadFileDir . $newFileName;
            if (move_uploaded_file($fileTmpPath, $dest_path)) {
                $image = 'uploads/' . $newFileName;
            }
        }
        
        $rooms = readJson(ROOMS_FILE);
        if (isset($rooms[$rid])) {
            if ($status) $rooms[$rid]['status'] = $status;
            if ($price > 0) $rooms[$rid]['price'] = $price;
            if ($image !== '') $rooms[$rid]['image'] = $image;
            writeJson(ROOMS_FILE, $rooms);
            $msg = "Chambre " . $rooms[$rid]['name'] . " mise à jour.";
        }
    }
}

// Load data for display
$rooms = readJson(ROOMS_FILE);
$reservations = readJson(RESERVATIONS_FILE);
$payments = readJson(PAYMENTS_FILE);

// Calculate Dashboard Stats
$todayDate = date('Y-m-d');
$todayBookingsCount = 0;
$todayMeetingsCount = 0;
$todayRevenue = 0;

foreach ($reservations as $r) {
    $createdAt = date('Y-m-d', strtotime($r['created_at']));
    if ($createdAt === $todayDate) {
        $todayBookingsCount++;
    }
    
    // Meeting room booking check
    if ($r['room_id'] === 'meeting_room' && $r['start'] <= $todayDate && $r['end'] >= $todayDate && $r['status'] === 'Validé') {
        $todayMeetingsCount++;
    }
}

foreach ($payments as $p) {
    $payDate = date('Y-m-d', strtotime($p['created_at']));
    if ($payDate === $todayDate && $p['status'] === 'Payé') {
        $todayRevenue += $p['amount'];
    }
}

// Occupied rooms counts (simulated deluxe room occupancy e.g. 10/15 based on status)
$totalRoomsCount = 15;
$occupiedRoomsCount = 0;
foreach ($rooms as $room) {
    if ($room['status'] === 'Occupé') {
        $occupiedRoomsCount += 3; // simulate multiples for categories
    }
}
if ($occupiedRoomsCount === 0) $occupiedRoomsCount = 10; // default mockup value

// Financial Stats for Accounting Section
$totalRevenue = 0;
$validatedCount = 0;
$pendingRevenue = 0;
$pendingCount = 0;

foreach ($payments as $p) {
    if ($p['status'] === 'Payé') {
        $totalRevenue += $p['amount'];
        $validatedCount++;
    } elseif ($p['status'] === 'En attente') {
        $pendingRevenue += $p['amount'];
        $pendingCount++;
    }
}
$avgBasket = $validatedCount > 0 ? round($totalRevenue / $validatedCount) : 0;

?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <link rel="icon" href="favicon.ico" type="image/x-icon">
  <link rel="shortcut icon" href="favicon.ico" type="image/x-icon">
  <link rel="apple-touch-icon" href="favicon.png">
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Administration Hôtel — Bien Hotel Dubai</title>
  
  <link href="https://fonts.googleapis.com/css2?family=Lora:ital,wght@0,400;0,500;0,600;1,400;1,500&family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
  
  <style>
    *, *::before, *::after {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
    }
    
    :root {
      --gold: #9C6F1C;
      --gold-light: #D6AF5C;
      --night: #F8F4EC;
      --night-mid: #EDE3D2;
      --night-soft: #e2d6c3;
      --cream: #0F1A17;
      --sand: #0F1A17;
      --muted: #6b7c78;
      --danger: #c45c5c;
      --ok: #5a9e6f;
      --font-display: 'Lora', Georgia, serif;
      --font-body: 'Poppins', sans-serif;
    }
    
    body {
      background: var(--night);
      color: var(--sand);
      font-family: var(--font-body);
      font-weight: 300;
      min-height: 100vh;
    }
    
    a {
      color: var(--gold-light);
      text-decoration: none;
    }
    
    a:hover {
      color: var(--cream);
    }
    
    /* Layout */
    .admin-container {
      display: grid;
      grid-template-columns: 240px 1fr;
      min-height: 100vh;
    }
    
    /* Sidebar */
    .sidebar {
      background: var(--night-mid);
      border-right: 1px solid rgba(184, 147, 90, 0.15);
      padding: 30px 20px;
      display: flex;
      flex-direction: column;
      gap: 30px;
    }
    
    .sidebar-logo {
      font-family: var(--font-display);
      font-size: 1.1rem;
      letter-spacing: 0.15em;
      text-transform: uppercase;
      color: var(--cream);
      border-bottom: 1px solid rgba(184, 147, 90, 0.15);
      padding-bottom: 20px;
    }
    
    .sidebar-logo span {
      color: var(--gold);
    }
    
    .sidebar-menu {
      display: flex;
      flex-direction: column;
      gap: 6px;
      list-style: none;
      flex: 1;
    }
    
    .sidebar-menu button, .sidebar-menu a.menu-item {
      background: transparent;
      border: 0;
      color: var(--sand);
      text-align: left;
      font-family: var(--font-body);
      font-size: 0.75rem;
      letter-spacing: 0.12em;
      text-transform: uppercase;
      padding: 12px 16px;
      width: 100%;
      cursor: pointer;
      display: flex;
      align-items: center;
      gap: 12px;
      opacity: 0.8;
      transition: all 0.3s;
    }
    
    .sidebar-menu button:hover, .sidebar-menu button.active, .sidebar-menu a.menu-item:hover {
      opacity: 1;
      color: var(--gold);
      background: rgba(184, 147, 90, 0.06);
      border-left: 3px solid var(--gold);
      padding-left: 13px;
    }
    
    /* Main Content Area */
    .main-content {
      padding: 40px;
      overflow-y: auto;
    }
    
    .header-bar {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 30px;
      border-bottom: 1px solid rgba(184, 147, 90, 0.1);
      padding-bottom: 20px;
    }
    
    .title-area h1 {
      font-family: var(--font-display);
      font-size: 2rem;
      font-weight: 300;
      color: var(--cream);
    }
    
    .title-area p {
      font-size: 0.8rem;
      color: var(--muted);
      margin-top: 4px;
    }
    
    /* Stats grid */
    .stats-grid {
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      gap: 20px;
      margin-bottom: 30px;
    }
    
    .stat-card {
      background: var(--night-mid);
      border: 1px solid rgba(184, 147, 90, 0.15);
      padding: 20px 24px;
      display: flex;
      flex-direction: column;
      gap: 6px;
    }
    
    .stat-label {
      font-size: 0.62rem;
      letter-spacing: 0.15em;
      text-transform: uppercase;
      color: var(--muted);
    }
    
    .stat-val {
      font-family: var(--font-display);
      font-size: 1.8rem;
      color: var(--cream);
      font-weight: 300;
    }
    
    .stat-sub {
      font-size: 0.65rem;
      color: var(--gold-light);
    }
    
    /* Cards Layout */
    .card {
      background: var(--night-mid);
      border: 1px solid rgba(184, 147, 90, 0.15);
      padding: 30px;
      margin-bottom: 30px;
    }
    
    .card-title {
      font-family: var(--font-display);
      font-size: 1.35rem;
      color: var(--cream);
      margin-bottom: 20px;
      display: flex;
      justify-content: space-between;
      align-items: center;
      font-weight: 400;
    }
    
    /* Message Notification */
    .alert {
      padding: 12px 18px;
      margin-bottom: 24px;
      font-size: 0.85rem;
      border-radius: 2px;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }
    
    .alert.ok {
      background: rgba(90, 158, 111, 0.12);
      border: 1px solid rgba(90, 158, 111, 0.35);
      color: #1e4620;
    }
    
    .alert.err {
      background: rgba(196, 92, 92, 0.12);
      border: 1px solid rgba(196, 92, 92, 0.35);
      color: #8a1f1f;
    }
    
    /* Tables design */
    table {
      width: 100%;
      border-collapse: collapse;
      text-align: left;
      font-size: 0.85rem;
    }
    
    th {
      font-size: 0.65rem;
      letter-spacing: 0.15em;
      text-transform: uppercase;
      color: var(--muted);
      padding: 12px 16px;
      border-bottom: 2px solid rgba(184, 147, 90, 0.2);
    }
    
    td {
      padding: 14px 16px;
      border-bottom: 1px solid rgba(184, 147, 90, 0.1);
      color: var(--sand);
    }
    
    tr:hover td {
      background: rgba(255, 255, 255, 0.01);
    }
    
    /* Badges */
    .badge {
      display: inline-block;
      font-size: 0.58rem;
      letter-spacing: 0.1em;
      text-transform: uppercase;
      padding: 3px 8px;
      border-radius: 1px;
      font-weight: 500;
    }
    
    .badge.pending {
      background: rgba(184, 147, 90, 0.15);
      color: var(--gold); border: 1px solid rgba(184, 147, 90, 0.3);
    }
    
    .badge.validated {
      background: rgba(90, 158, 111, 0.15);
      color: #1e4620;
      border: 1px solid rgba(90, 158, 111, 0.3);
    }
    
    .badge.rejected {
      background: rgba(196, 92, 92, 0.15);
      color: #8a1f1f;
      border: 1px solid rgba(196, 92, 92, 0.3);
    }
    
    /* Forms & inputs */
    .form-login {
      max-width: 420px;
      margin: 100px auto;
      background: var(--night-mid);
      border: 1px solid var(--gold);
      padding: 40px;
      box-shadow: 0 20px 50px rgba(0,0,0,0.5);
    }
    
    .fg {
      display: flex;
      flex-direction: column;
      gap: 6px;
      margin-bottom: 18px;
    }
    
    .fg label {
      font-size: 0.65rem;
      letter-spacing: 0.15em;
      text-transform: uppercase;
      color: var(--muted);
    }
    
    .fg input, .fg select {
      background: var(--night-soft);
      border: 1px solid rgba(184, 147, 90, 0.25);
      color: var(--cream);
      padding: 12px 14px;
      font-family: inherit;
      font-size: 0.9rem;
      outline: none;
    }
    
    .fg input:focus, .fg select:focus {
      border-color: var(--gold);
    }
    
    .btn {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
      background: var(--gold);
      color: var(--night);
      border: 1px solid var(--gold);
      font-family: var(--font-body);
      font-size: 0.7rem;
      letter-spacing: 0.15em;
      text-transform: uppercase;
      padding: 11px 20px;
      font-weight: 500;
      cursor: pointer;
      transition: all 0.3s;
    }
    
    .btn:hover {
      background: var(--gold-light);
      border-color: var(--gold-light);
    }
    
    .btn-sm {
      padding: 6px 12px;
      font-size: 0.6rem;
    }
    
    .btn-danger {
      background: transparent;
      border-color: var(--danger);
      color: var(--danger);
    }
    
    .btn-danger:hover {
      background: rgba(196, 92, 92, 0.1);
      color: #ff9e9e;
    }
    
    .btn-outline {
      background: transparent;
      border: 1px solid rgba(184, 147, 90, 0.35);
      color: var(--sand);
    }
    
    .btn-outline:hover {
      border-color: var(--gold);
      background: rgba(184, 147, 90, 0.05);
      color: var(--cream);
    }
    
    .hidden {
      display: none !important;
    }
    
    /* Chart rendering simulation */
    .chart-container {
      height: 180px;
      display: flex;
      align-items: flex-end;
      gap: 15px;
      padding-top: 20px;
      border-bottom: 1px solid rgba(184, 147, 90, 0.2);
    }
    
    .chart-bar-wrap {
      flex: 1;
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: 10px;
      height: 100%;
      justify-content: flex-end;
    }
    
    .chart-bar {
      width: 100%;
      background: linear-gradient(to top, var(--gold), var(--gold-light));
      min-height: 5px;
      transition: height 1s;
      position: relative;
    }
    
    .chart-bar:hover::after {
      content: attr(data-val);
      position: absolute;
      top: -24px;
      left: 50%;
      transform: translateX(-50%);
      font-size: 0.65rem;
      background: var(--night-soft);
      padding: 3px 6px;
      border: 1px solid var(--gold);
      color: var(--cream);
      white-space: nowrap;
    }
    
    .chart-label {
      font-size: 0.65rem;
      color: var(--muted);
      text-transform: uppercase;
    }
    
    /* Search Row */
    .search-row {
      display: flex;
      gap: 12px;
      margin-bottom: 20px;
      flex-wrap: wrap;
    }
    
    .search-row input, .search-row select {
      background: var(--night-soft);
      border: 1px solid rgba(184, 147, 90, 0.2);
      color: var(--cream);
      padding: 8px 12px;
      font-family: inherit;
      font-size: 0.8rem;
      outline: none;
    }
    
    .search-row input:focus, .search-row select:focus {
      border-color: var(--gold);
    }
    
    @media (max-width: 900px) {
      .admin-container {
        grid-template-columns: 1fr;
      }
      .sidebar {
        border-right: none;
        border-bottom: 1px solid rgba(184, 147, 90, 0.15);
        padding: 20px 15px;
        gap: 15px;
      }
      .sidebar-logo {
        text-align: center;
        border-bottom: none;
        padding-bottom: 0;
      }
      .sidebar-menu {
        flex-direction: row !important;
        flex-wrap: wrap;
        gap: 8px;
        flex: none;
      }
      .sidebar-menu li {
        flex: 1 1 auto;
      }
      .sidebar-menu button, .sidebar-menu a.menu-item {
        justify-content: center;
        font-size: 0.7rem;
        padding: 10px 12px;
      }
      .sidebar-menu button:hover, .sidebar-menu button.active, .sidebar-menu a.menu-item:hover {
        border-left: none;
        border-bottom: 2px solid var(--gold);
        padding-left: 12px;
        padding-bottom: 8px;
      }
      .sidebar div[style*="margin-top:auto"], .sidebar div[style*="margin-top: auto"] {
        margin-top: 10px !important;
        display: flex;
        gap: 10px;
        width: 100%;
      }
      .sidebar div[style*="margin-top:auto"] a, .sidebar div[style*="margin-top: auto"] a {
        flex: 1;
        margin-top: 0 !important;
      }
      .main-content {
        padding: 20px 15px;
      }
      .stats-grid {
        grid-template-columns: repeat(2, 1fr) !important;
        gap: 12px !important;
      }
      .dashboard-grid-1, .dashboard-grid-2 {
        grid-template-columns: 1fr !important;
        gap: 20px !important;
      }
      .search-row input, .search-row select {
        flex: 1 1 auto;
        width: 100%;
      }
    }
    
    @media (max-width: 500px) {
      .stats-grid {
        grid-template-columns: 1fr !important;
      }
      .sidebar-menu {
        justify-content: center;
      }
      .sidebar-menu li {
        flex: 1 1 100%;
      }
      .sidebar div[style*="margin-top:auto"], .sidebar div[style*="margin-top: auto"] {
        flex-direction: column;
      }
      .form-login {
        margin: 40px 15px;
        padding: 24px 20px;
      }
    }
  </style>
</head>
<body>

  <?php if (!$logged): ?>
  <!-- ═══ LOGIN SCREEN ═══════════════════════════════════════ -->
  <div class="form-login">
    <h2 style="font-family: var(--font-display); font-size: 1.6rem; color: var(--cream); margin-bottom: 6px; text-transform: uppercase; letter-spacing: 0.1em; text-align: center;">Connexion</h2>
    <p style="color: var(--muted); font-size: 0.8rem; margin-bottom: 24px; text-align: center;">Espace d'administration hôtelière Bien Hotel Dubai</p>
    
    <?php if (!empty($msg)): ?>
      <div class="alert err"><?= $msg ?></div>
    <?php endif; ?>
    
    <form method="POST" action="hotel_admin.php">
      <input type="hidden" name="action" value="login">
      <div class="fg" style="position:relative;">
        <label>Mot de passe</label>
        <div style="position:relative;">
          <input type="password" id="hotelPasswordInput" name="password" placeholder="Mot de passe admin" required autofocus style="padding-right: 40px; width: 100%; box-sizing: border-box;">
          <button type="button" id="toggleHotelPassword" style="position:absolute; right:10px; top:50%; transform:translateY(-50%); background:none; border:none; color:var(--muted); cursor:pointer; font-size:1.1rem; padding:0;" aria-label="Afficher le mot de passe">👁️</button>
        </div>
      </div>
      <button type="submit" class="btn" id="hotelLoginBtn" style="width: 100%; margin-top: 10px;">Se connecter</button>
      <?php 
      $attempts = file_exists($blockFile) ? json_decode(file_get_contents($blockFile), true) : [];
      if (($attempts[$ip] ?? 0) >= 5): 
      ?>
      <div style="margin-top:15px; text-align:center;">
        <a href="#" onclick="alert('Veuillez contacter l\'administrateur principal (Dani) au +221 78 014 09 42 pour réinitialiser le mot de passe.'); return false;" style="color:var(--gold); text-decoration:underline; font-size:0.85rem;">Mot de passe oublié ?</a>
      </div>
      <script>
        document.getElementById('hotelPasswordInput').disabled = true;
        document.getElementById('hotelLoginBtn').disabled = true;
      </script>
      <?php endif; ?>
    </form>
    <script>
    document.getElementById('toggleHotelPassword')?.addEventListener('click', function() {
      const pwd = document.getElementById('hotelPasswordInput');
      if (pwd.type === 'password') {
        pwd.type = 'text';
        this.textContent = '🔒';
      } else {
        pwd.type = 'password';
        this.textContent = '👁️';
      }
    });
    </script>
  </div>
  
  <?php else: ?>
  <!-- ═══ ADMIN INTERFACE ════════════════════════════════════ -->
  <div class="admin-container">
    
    <!-- SIDEBAR -->
    <aside class="sidebar">
      <div class="sidebar-logo">
        Bien Hotel <span>Admin</span>
      </div>
      
      <ul class="sidebar-menu">
        <li><button type="button" class="active" id="menuDashboard" onclick="switchTab('dashboard')">📊 Tableau de bord</button></li>
        <li><button type="button" id="menuRooms" onclick="switchTab('rooms')">🛏 Chambres</button></li>
        <li><button type="button" id="menuBookings" onclick="switchTab('bookings')">📅 Réservations</button></li>
        <li><button type="button" id="menuAccounting" onclick="switchTab('accounting')" style="border-top: 1px solid rgba(184, 147, 90, 0.15); padding-top: 18px; margin-top: 10px; color: var(--gold-light);">💼 Comptabilité</button></li>
      </ul>
      
      <ul class="sidebar-menu" style="flex: 0;">
        <li><a href="hotel_admin.php?action=download_backup" class="menu-item" style="color: var(--gold-light);">📥 Sauvegarder (ZIP)</a></li>
        <li><a href="hotel.html" target="_blank" class="menu-item">🌐 Voir le site</a></li>
        <li><a href="hotel_admin.php?logout=1" class="menu-item" style="color: var(--danger);">✕ Déconnexion</a></li>
      </ul>
    </aside>
    
    <!-- MAIN CONTAINER -->
    <main class="main-content">
      
      <!-- NOTIFICATION MESSAGES -->
      <?php if (!empty($msg)): ?>
        <div class="alert <?= $msgType ?>"><?= $msg ?><button type="button" onclick="this.parentElement.style.display='none';" style="background:none;border:none;color:inherit;cursor:pointer;font-size:1.1rem;">&times;</button></div>
      <?php endif; ?>

      <!-- ── TAB: DASHBOARD ─────────────────────────────────── -->
      <div id="tabDashboard">
        <div class="header-bar">
          <div class="title-area">
            <h1>Tableau de Bord</h1>
            <p>Vue d'ensemble de l'activité du Bien Hotel aujourd'hui, le <?= date('d/m/Y') ?></p>
          </div>
        </div>
        
        <!-- Stats Row -->
        <div class="stats-grid">
          <div class="stat-card">
            <span class="stat-label">Réservations Jour</span>
            <div class="stat-val"><?= $todayBookingsCount ?></div>
            <span class="stat-sub">Nouvelles demandes</span>
          </div>
          <div class="stat-card">
            <span class="stat-label">Chambres Occupées</span>
            <div class="stat-val"><?= $occupiedRoomsCount ?> / <?= $totalRoomsCount ?></div>
            <span class="stat-sub">Taux d'occupation</span>
          </div>
          <div class="stat-card">
            <span class="stat-label">Revenus du Jour</span>
            <div class="stat-val"><?= number_format($todayRevenue, 0, ',', ' ') ?> FCFA</div>
            <span class="stat-sub">Paiements validés</span>
          </div>
          <div class="stat-card">
            <span class="stat-label">Réunions du Jour</span>
            <div class="stat-val"><?= $todayMeetingsCount ?></div>
            <span class="stat-sub">Salles de réunion actives</span>
          </div>
        </div>

        <!-- Layout 2 Columns -->
        <div class="dashboard-grid-1" style="display: grid; grid-template-columns: 1.4fr 1fr; gap: 30px;">
          <!-- Recent bookings table -->
          <div class="card">
            <h3 class="card-title">Réservations Récentes</h3>
            <?php if (empty($reservations)): ?>
              <p style="color: var(--muted); font-size: 0.85rem; font-style: italic;">Aucune réservation pour le moment.</p>
            <?php else: ?>
              <div style="overflow-x: auto;">
                <table>
                  <thead>
                    <tr>
                      <th>Ref</th>
                      <th>Client</th>
                      <th>Chambre</th>
                      <th>Dates</th>
                      <th>Status</th>
                      <th>Action</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php 
                    $recentList = array_slice(array_reverse($reservations), 0, 5);
                    foreach ($recentList as $r):
                      $statusClass = $r['status'] === 'Validé' ? 'validated' : ($r['status'] === 'Refusé' ? 'rejected' : 'pending');
                    ?>
                      <tr>
                        <td><strong><?= htmlspecialchars($r['id']) ?></strong></td>
                        <td><?= htmlspecialchars($r['name']) ?></td>
                        <td><?= htmlspecialchars($r['room_name']) ?></td>
                        <td style="font-size: 0.75rem;"><?= date('d/m', strtotime($r['start'])) ?> au <?= date('d/m', strtotime($r['end'])) ?></td>
                        <td><span class="badge <?= $statusClass ?>"><?= $r['status'] ?></span></td>
                        <td>
                          <?php if ($r['status'] === 'En attente'): ?>
                            <form method="POST" style="display: inline-block;">
                              <input type="hidden" name="action" value="validate_booking">
                              <input type="hidden" name="booking_id" value="<?= $r['id'] ?>">
                              <button type="submit" class="btn btn-sm" style="background:var(--ok);border-color:var(--ok);color:white;padding:3px 6px;">✓</button>
                            </form>
                            <form method="POST" style="display: inline-block;">
                              <input type="hidden" name="action" value="reject_booking">
                              <input type="hidden" name="booking_id" value="<?= $r['id'] ?>">
                              <button type="submit" class="btn btn-sm btn-danger" style="padding:3px 6px;">✕</button>
                            </form>
                          <?php else: ?>
                            <span style="color: var(--muted); font-size: 0.75rem;">Traité</span>
                          <?php endif; ?>
                        </td>
                      </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
            <?php endif; ?>
          </div>

          <!-- 7-days Reservation Chart -->
          <div class="card">
            <h3 class="card-title">Réservations (7 derniers jours)</h3>
            <div class="chart-container">
              <?php
              for ($i = 6; $i >= 0; $i--) {
                  $date = date('Y-m-d', strtotime("-$i days"));
                  $label = date('d/m', strtotime($date));
                  
                  // Count bookings on this day
                  $count = 0;
                  foreach ($reservations as $r) {
                      if (date('Y-m-d', strtotime($r['created_at'])) === $date) {
                          $count++;
                      }
                  }
                  
                  // Simulate chart scale (height in percent)
                  // 1 count = 25% height, max 4 count = 100% height
                  $height = min(100, max(5, $count * 25));
                  ?>
                  <div class="chart-bar-wrap">
                    <div class="chart-bar" style="height: <?= $height ?>%;" data-val="<?= $count ?> réservations"></div>
                    <span class="chart-label"><?= $label ?></span>
                  </div>
                  <?php
              }
              ?>
            </div>
          </div>
        </div>
      </div>

      <!-- ── TAB: ROOMS ─────────────────────────────────────── -->
      <div id="tabRooms" class="hidden">
        <div class="header-bar">
          <div class="title-area">
            <h1>Gestion des Chambres & Équipements</h1>
            <p>Définissez les tarifs et changez instantanément la disponibilité des chambres pour le site client.</p>
          </div>
        </div>
        
        <div class="card">
          <table>
            <thead>
              <tr>
                <th>Chambre</th>
                <th>Capacité</th>
                <th>Prix (FCFA)</th>
                <th>Statut Actuel</th>
                <th>Mettre à jour</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($rooms as $id => $room): ?>
                <tr>
                  <td>
                    <strong style="color: var(--cream); font-size: 1rem;"><?= htmlspecialchars($room['name']) ?></strong>
                    <div style="font-size: 0.72rem; color: var(--muted); margin-top: 4px;"><?= htmlspecialchars($room['description']) ?></div>
                  </td>
                  <td>👤 <?= $room['capacity'] ?> Pers.</td>
                  <td><?= number_format($room['price'], 0, ',', ' ') ?> FCFA</td>
                  <td>
                    <?php 
                    $c = $room['status'] === 'Disponible' ? 'background:rgba(90,158,111,0.18);color: #1e4620;border:1px solid rgba(90,158,111,0.3);' : ($room['status'] === 'Occupé' ? 'background:rgba(184,147,90,0.18);color: var(--gold); border:1px solid rgba(184,147,90,0.3);' : 'background:rgba(196,92,92,0.18);color: #8a1f1f;border:1px solid rgba(196,92,92,0.3);');
                    ?>
                    <span class="badge" style="<?= $c ?>"><?= htmlspecialchars($room['status']) ?></span>
                  </td>
                  <td>
                    <form method="POST" enctype="multipart/form-data" style="display: flex; gap: 8px; align-items: center;">
                      <input type="hidden" name="action" value="update_room">
                      <input type="hidden" name="room_id" value="<?= $id ?>">
                      
                      <select name="status" style="padding: 4px; font-size: 0.75rem; background: var(--night-soft); color: var(--cream); border: 1px solid rgba(184,147,90,0.25);">
                        <option value="Disponible" <?= $room['status'] === 'Disponible' ? 'selected' : '' ?>>Disponible</option>
                        <option value="Occupé" <?= $room['status'] === 'Occupé' ? 'selected' : '' ?>>Occupé</option>
                        <option value="En maintenance" <?= $room['status'] === 'En maintenance' ? 'selected' : '' ?>>En maintenance</option>
                      </select>
                      
                      <input type="number" name="price" value="<?= $room['price'] ?>" style="width: 85px; padding: 4px; font-size: 0.75rem; background: var(--night-soft); color: var(--cream); border: 1px solid rgba(184,147,90,0.25);">
                      
                      <input type="text" name="image" value="<?= htmlspecialchars($room['image'] ?? '') ?>" placeholder="Lien photo (https://...)" style="width: 140px; padding: 4px; font-size: 0.75rem; background: var(--night-soft); color: var(--cream); border: 1px solid rgba(184,147,90,0.25);">
                      
                      <label for="file_<?= $id ?>" style="cursor: pointer; padding: 4px 8px; background: rgba(184,147,90,0.15); border: 1px solid rgba(184,147,90,0.3); color: var(--gold-light); font-size: 0.75rem; white-space: nowrap; border-radius: 2px;">
                        📷 Charger
                      </label>
                      <input type="file" id="file_<?= $id ?>" name="room_photo" accept="image/*" style="display: none;">
                      
                      <button type="submit" class="btn btn-sm">Mettre à jour</button>
                    </form>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>

      <!-- ── TAB: BOOKINGS ──────────────────────────────────── -->
      <div id="tabBookings" class="hidden">
        <div class="header-bar">
          <div class="title-area">
            <h1>Réservations Générales</h1>
            <p>Historique et demandes de réservations pour les hébergements et la salle de réunion.</p>
          </div>
        </div>
        
        <div class="card">
          <div class="search-row">
            <input type="text" id="bookingSearchInput" placeholder="Rechercher par client ou chambre..." onkeyup="filterBookingsTable()">
            <select id="bookingStatusFilter" onchange="filterBookingsTable()">
              <option value="ALL">Tous les statuts</option>
              <option value="En attente">En attente</option>
              <option value="Validé">Validé</option>
              <option value="Refusé">Refusé</option>
            </select>
          </div>
          
          <div style="overflow-x: auto;">
            <table id="bookingsTable">
              <thead>
                <tr>
                  <th>Ref</th>
                  <th>Nom Client</th>
                  <th>Hébergement</th>
                  <th>Dates (Arrivée · Départ)</th>
                  <th>Jours</th>
                  <th>Total</th>
                  <th>Date Demande</th>
                  <th>Statut</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php 
                if (empty($reservations)): 
                ?>
                  <tr>
                    <td colspan="9" style="text-align: center; color: var(--muted); font-style: italic;">Aucune réservation enregistrée.</td>
                  </tr>
                <?php 
                else:
                  $allBookings = array_reverse($reservations);
                  foreach ($allBookings as $r):
                    $statusClass = $r['status'] === 'Validé' ? 'validated' : ($r['status'] === 'Refusé' ? 'rejected' : 'pending');
                ?>
                  <tr class="booking-row-item" data-status="<?= htmlspecialchars($r['status']) ?>" data-search="<?= strtolower(htmlspecialchars($r['name'] . ' ' . $r['room_name'] . ' ' . $r['id'])) ?>">
                    <td><strong><?= htmlspecialchars($r['id']) ?></strong></td>
                    <td><?= htmlspecialchars($r['name']) ?></td>
                    <td><?= htmlspecialchars($r['room_name']) ?></td>
                    <td><?= date('d/m/Y', strtotime($r['start'])) ?> au <?= date('d/m/Y', strtotime($r['end'])) ?></td>
                    <td><?= htmlspecialchars($r['nights']) ?> jours</td>
                    <td><strong><?= number_format($r['total'], 0, ',', ' ') ?> FCFA</strong></td>
                    <td style="font-size: 0.75rem; color: var(--muted);"><?= date('d/m/Y H:i', strtotime($r['created_at'])) ?></td>
                    <td><span class="badge <?= $statusClass ?>"><?= $r['status'] ?></span></td>
                    <td>
                      <?php if ($r['status'] === 'En attente'): ?>
                        <form method="POST" style="display: inline-block;">
                          <input type="hidden" name="action" value="validate_booking">
                          <input type="hidden" name="booking_id" value="<?= $r['id'] ?>">
                          <button type="submit" class="btn btn-sm" style="background:var(--ok);border-color:var(--ok);color:white;padding:4px 8px;font-size:0.6rem;">Valider</button>
                        </form>
                        <form method="POST" style="display: inline-block;">
                          <input type="hidden" name="action" value="reject_booking">
                          <input type="hidden" name="booking_id" value="<?= $r['id'] ?>">
                          <button type="submit" class="btn btn-sm btn-danger" style="padding:4px 8px;font-size:0.6rem;">Refuser</button>
                        </form>
                      <?php else: ?>
                        <span style="color: var(--muted); font-size: 0.75rem;">Aucune action</span>
                      <?php endif; ?>
                    </td>
                  </tr>
                <?php 
                  endforeach; 
                endif; 
                ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- ── TAB: ACCOUNTING ────────────────────────────────── -->
      <div id="tabAccounting" class="hidden">
        <div class="header-bar">
          <div class="title-area">
            <h1 style="color: var(--gold-light);">Espace Comptabilité & Rapprochement</h1>
            <p>Section de facturation, encaissements Wave/Orange Money et comptabilité générale.</p>
          </div>
          <button type="button" class="btn btn-outline btn-sm" onclick="exportAccountingLedger()">⬇ Exporter le Journal (CSV)</button>
        </div>
        
        <!-- Financial KPIs -->
        <div class="stats-grid" style="margin-bottom: 30px;">
          <div class="stat-card" style="border-color: var(--gold);">
            <span class="stat-label">Chiffre d'Affaires Validé</span>
            <div class="stat-val" style="color: var(--gold-light);"><?= number_format($totalRevenue, 0, ',', ' ') ?> FCFA</div>
            <span class="stat-sub">Encaissements réels validés</span>
          </div>
          <div class="stat-card">
            <span class="stat-label">Panier Moyen</span>
            <div class="stat-val"><?= number_format($avgBasket, 0, ',', ' ') ?> FCFA</div>
            <span class="stat-sub">Par réservation validée</span>
          </div>
          <div class="stat-card">
            <span class="stat-label">Paiements en Attente</span>
            <div class="stat-val" style="color: #f5a85a;"><?= number_format($pendingRevenue, 0, ',', ' ') ?> FCFA</div>
            <span class="stat-sub"><?= $pendingCount ?> transactions en suspens</span>
          </div>
          <div class="stat-card">
            <span class="stat-label">Rapprochement Wave / OM</span>
            <div class="stat-val" style="color: var(--ok);">100%</div>
            <span class="stat-sub">Rapprochement bancaire simulé</span>
          </div>
        </div>

        <div class="dashboard-grid-2" style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px; margin-bottom: 30px;">
          <!-- Validation box for accountant -->
          <div class="card">
            <h3 class="card-title">Encaissements en Attente de Validation</h3>
            <?php 
            $pendingPaymentsList = [];
            foreach ($payments as $p) {
                if ($p['status'] === 'En attente') {
                    $pendingPaymentsList[] = $p;
                }
            }
            if (empty($pendingPaymentsList)):
            ?>
              <p style="color: var(--muted); font-size: 0.85rem; font-style: italic;">Aucun encaissement en attente de validation.</p>
            <?php else: ?>
              <div style="overflow-x: auto;">
                <table>
                  <thead>
                    <tr>
                      <th>Ref Booking</th>
                      <th>Client</th>
                      <th>Moyen</th>
                      <th>Montant</th>
                      <th>Action Comptable</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php foreach ($pendingPaymentsList as $p): ?>
                      <tr>
                        <td><strong><?= htmlspecialchars($p['booking_id']) ?></strong></td>
                        <td><?= htmlspecialchars($p['client_name']) ?></td>
                        <td>
                          <?php
                          $methodDisp = $p['method'];
                          $c = $methodDisp === 'Wave' ? 'color:#1dc8f2;' : ($methodDisp === 'Orange Money' ? 'color:#ff7900;' : 'color:var(--sand);');
                          ?>
                          <span style="font-weight: 500; <?= $c ?>"><?= $methodDisp ?></span>
                        </td>
                        <td><strong><?= number_format($p['amount'], 0, ',', ' ') ?> FCFA</strong></td>
                        <td>
                          <form method="POST" style="display: inline-block;">
                            <input type="hidden" name="action" value="validate_booking">
                            <input type="hidden" name="booking_id" value="<?= $p['booking_id'] ?>">
                            <button type="submit" class="btn btn-sm" style="background:var(--ok); border-color:var(--ok); padding: 4px 10px;">Valider Encaiss.</button>
                          </form>
                        </td>
                      </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
            <?php endif; ?>
          </div>

          <!-- Monthly revenue target chart -->
          <div class="card">
            <h3 class="card-title">Indice de Performance Mensuel</h3>
            <div class="chart-container" style="height: 180px;">
              <!-- Simulated months chart -->
              <div class="chart-bar-wrap">
                <div class="chart-bar" style="height: 45%;" data-val="1 500 000 FCFA"></div>
                <span class="chart-label">Mars</span>
              </div>
              <div class="chart-bar-wrap">
                <div class="chart-bar" style="height: 60%;" data-val="2 400 000 FCFA"></div>
                <span class="chart-label">Avril</span>
              </div>
              <div class="chart-bar-wrap">
                <div class="chart-bar" style="height: 75%;" data-val="3 100 000 FCFA"></div>
                <span class="chart-label">Mai</span>
              </div>
              <div class="chart-bar-wrap">
                <div class="chart-bar" style="height: 90%;" data-val="4 500 000 FCFA"></div>
                <span class="chart-label">Juin</span>
              </div>
              <div class="chart-bar-wrap">
                <div class="chart-bar" style="height: <?= min(100, max(10, intval($totalRevenue / 100000))) ?>%;" data-val="<?= number_format($totalRevenue, 0, ',', ' ') ?> FCFA"></div>
                <span class="chart-label" style="color: var(--gold-light); font-weight: 500;">Courant</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Ledger ledger transaction table -->
        <div class="card">
          <h3 class="card-title">Journal Général des Écritures Comptables</h3>
          <div class="search-row">
            <input type="text" id="accountingSearchInput" placeholder="Rechercher par client ou ID..." onkeyup="filterAccountingTable()">
            <select id="accountingMethodFilter" onchange="filterAccountingTable()">
              <option value="ALL">Tous les modes</option>
              <option value="Wave">Wave</option>
              <option value="Orange Money">Orange Money</option>
              <option value="Carte Bancaire">Carte Bancaire</option>
            </select>
            <select id="accountingStatusFilter" onchange="filterAccountingTable()">
              <option value="ALL">Tous les états</option>
              <option value="Payé">Payé (Validé)</option>
              <option value="En attente">En attente</option>
              <option value="Refusé">Refusé</option>
            </select>
          </div>

          <div style="overflow-x: auto;">
            <table id="accountingTable">
              <thead>
                <tr>
                  <th>ID Écriture</th>
                  <th>Ref Réservation</th>
                  <th>Client</th>
                  <th>Libellé Produit</th>
                  <th>Mode Paiement</th>
                  <th>Date Valeur</th>
                  <th>Débit / Montant</th>
                  <th>Statut Comptable</th>
                </tr>
              </thead>
              <tbody>
                <?php if (empty($payments)): ?>
                  <tr>
                    <td colspan="8" style="text-align: center; color: var(--muted); font-style: italic;">Aucune écriture comptable.</td>
                  </tr>
                <?php else: 
                  $allPayments = array_reverse($payments);
                  foreach ($allPayments as $p):
                    $statusClass = $p['status'] === 'Payé' ? 'validated' : ($p['status'] === 'Refusé' ? 'rejected' : 'pending');
                    $statusLabel = $p['status'] === 'Payé' ? 'Payé' : ($p['status'] === 'En attente' ? 'Attente' : 'Annulé');
                ?>
                  <tr class="ledger-row" data-method="<?= htmlspecialchars($p['method']) ?>" data-status="<?= htmlspecialchars($p['status']) ?>" data-search="<?= strtolower(htmlspecialchars($p['client_name'] . ' ' . $p['booking_id'] . ' ' . $p['id'] . ' ' . $p['item_name'])) ?>">
                    <td><span style="font-family: monospace; color: var(--muted); font-size: 0.75rem;"><?= htmlspecialchars($p['id']) ?></span></td>
                    <td><strong><?= htmlspecialchars($p['booking_id']) ?></strong></td>
                    <td><?= htmlspecialchars($p['client_name']) ?></td>
                    <td><?= htmlspecialchars($p['item_name']) ?></td>
                    <td><?= htmlspecialchars($p['method']) ?></td>
                    <td style="font-size: 0.75rem; color: var(--muted);"><?= date('d/m/Y H:i', strtotime($p['created_at'])) ?></td>
                    <td><strong style="color: var(--cream);"><?= number_format($p['amount'], 0, ',', ' ') ?> FCFA</strong></td>
                    <td><span class="badge <?= $statusClass ?>"><?= $statusLabel ?></span></td>
                  </tr>
                <?php endforeach; 
                endif; ?>
              </tbody>
            </table>
          </div>
        </div>

      </div>

    </main>
  </div>
  <?php endif; ?>

  <script>
    // Tab switcher logic
    function switchTab(tabId) {
      // Hide all tabs
      ['dashboard', 'rooms', 'bookings', 'accounting'].forEach(id => {
        const tabEl = document.getElementById('tab' + id.charAt(0).toUpperCase() + id.slice(1));
        const menuBtn = document.getElementById('menu' + id.charAt(0).toUpperCase() + id.slice(1));
        
        if (tabEl) tabEl.classList.add('hidden');
        if (menuBtn) menuBtn.classList.remove('active');
      });
      
      // Show active tab
      const activeTab = document.getElementById('tab' + tabId.charAt(0).toUpperCase() + tabId.slice(1));
      const activeBtn = document.getElementById('menu' + tabId.charAt(0).toUpperCase() + tabId.slice(1));
      
      if (activeTab) activeTab.classList.remove('hidden');
      if (activeBtn) activeBtn.classList.add('active');
    }
    
    // Search filter for bookings
    function filterBookingsTable() {
      const search = document.getElementById('bookingSearchInput').value.toLowerCase();
      const status = document.getElementById('bookingStatusFilter').value;
      const rows = document.querySelectorAll('.booking-row-item');
      
      rows.forEach(row => {
        const textMatch = row.dataset.search.includes(search);
        const statusMatch = (status === 'ALL' || row.dataset.status === status);
        
        row.style.display = (textMatch && statusMatch) ? '' : 'none';
      });
    }

    // Search filter for accounting ledger
    function filterAccountingTable() {
      const search = document.getElementById('accountingSearchInput').value.toLowerCase();
      const method = document.getElementById('accountingMethodFilter').value;
      const status = document.getElementById('accountingStatusFilter').value;
      const rows = document.querySelectorAll('.ledger-row');
      
      rows.forEach(row => {
        const textMatch = row.dataset.search.includes(search);
        const methodMatch = (method === 'ALL' || row.dataset.method === method);
        const statusMatch = (status === 'ALL' || row.dataset.status === status);
        
        row.style.display = (textMatch && methodMatch && statusMatch) ? '' : 'none';
      });
    }
    
    // Export of the accounting ledger (CSV format)
    function exportAccountingLedger() {
      const rows = document.querySelectorAll('.ledger-row');
      let visibleRows = [];
      rows.forEach(row => {
        if (row.style.display !== 'none') {
          visibleRows.push(row);
        }
      });
      
      if (visibleRows.length === 0) {
        alert('Aucune écriture comptable visible à exporter.');
        return;
      }
      
      let csv = 'ID Ecriture;Ref Reservation;Client;Libelle;Mode Paiement;Date Valeur;Montant (FCFA);Statut\n';
      
      const escapeCsv = text => {
        if (text === null || text === undefined) return '';
        return text.replace(/"/g, '""');
      };
      
      visibleRows.forEach(row => {
        const tds = row.querySelectorAll('td');
        const id = tds[0].textContent.trim();
        const ref = tds[1].textContent.trim();
        const client = tds[2].textContent.trim();
        const item = tds[3].textContent.trim();
        const method = tds[4].textContent.trim();
        const dateVal = tds[5].textContent.trim();
        const amount = tds[6].textContent.replace(/[^\d]/g, '').trim();
        const status = tds[7].textContent.trim();
        
        csv += `"${escapeCsv(id)}";"${escapeCsv(ref)}";"${escapeCsv(client)}";"${escapeCsv(item)}";"${escapeCsv(method)}";"${escapeCsv(dateVal)}";${amount};"${escapeCsv(status)}"\n`;
      });
      
      const blob = new Blob([new Uint8Array([0xEF, 0xBB, 0xBF]), csv], { type: 'text/csv;charset=utf-8' });
      const a = document.createElement('a');
      a.href = URL.createObjectURL(blob);
      a.download = `journal_comptable_${new Date().toISOString().split('T')[0]}.csv`;
      document.body.appendChild(a);
      a.click();
      document.body.removeChild(a);
    }
  </script>
</body>
</html>
