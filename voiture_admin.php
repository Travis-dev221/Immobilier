<?php
session_start();

define('ADMIN_SECRET_FILE', __DIR__ . '/data/admin.secret.php');
define('VEHICLES_FILE', __DIR__ . '/data/vehicles.json');
define('RESERVATIONS_FILE', __DIR__ . '/data/vehicle_reservations.json');

$adminConfig = file_exists(ADMIN_SECRET_FILE) ? require(ADMIN_SECRET_FILE) : [];
$passwords = $adminConfig['passwords'] ?? [$adminConfig['password'] ?? 'Baobab2026'];

$msg = '';
$msgType = 'ok';

if (isset($_POST['action']) && $_POST['action'] === 'login') {
    $inputPassword = trim($_POST['password'] ?? '');
    $authenticated = false;
    foreach ($passwords as $pwd) {
        if (password_verify($inputPassword, $pwd) || $inputPassword === $pwd) {
            $authenticated = true;
            break;
        }
    }
    if ($authenticated) {
        $_SESSION['voiture_admin'] = true;
        header('Location: voiture_admin.php');
        exit;
    } else {
        $msg = 'Mot de passe incorrect.';
        $msgType = 'err';
    }
}

if (isset($_GET['logout'])) {
    unset($_SESSION['voiture_admin']);
    header('Location: voiture_admin.php');
    exit;
}

$logged = !empty($_SESSION['voiture_admin']);

// Download Backup ZIP
if ($logged && isset($_GET['action']) && $_GET['action'] === 'download_backup') {
    $zip = new ZipArchive();
    $zipName = 'backup_location_voitures_' . date('Y-m-d') . '.zip';
    $tempFile = tempnam(sys_get_temp_dir(), 'zip');
    if ($zip->open($tempFile, ZipArchive::CREATE) === TRUE) {
        if (file_exists(VEHICLES_FILE)) $zip->addFile(VEHICLES_FILE, 'vehicles.json');
        if (file_exists(RESERVATIONS_FILE)) $zip->addFile(RESERVATIONS_FILE, 'vehicle_reservations.json');
        $zip->close();
        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="' . $zipName . '"');
        header('Content-Length: ' . filesize($tempFile));
        readfile($tempFile);
        unlink($tempFile);
        exit;
    }
}

// Read data
$vehicles = file_exists(VEHICLES_FILE) ? (json_decode(file_get_contents(VEHICLES_FILE), true) ?: []) : [];
$reservations = file_exists(RESERVATIONS_FILE) ? (json_decode(file_get_contents(RESERVATIONS_FILE), true) ?: []) : [];

// Process Vehicle Actions
if ($logged && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $postAction = $_POST['form_action'] ?? '';
    
    if ($postAction === 'save_vehicle') {
        $id = trim($_POST['v_id'] ?? '');
        $name = trim($_POST['v_name'] ?? '');
        $cat = trim($_POST['v_category'] ?? '');
        $price = floatval($_POST['v_price'] ?? 0);
        $seats = intval($_POST['v_seats'] ?? 5);
        $trans = trim($_POST['v_trans'] ?? 'Automatique');
        $fuel = trim($_POST['v_fuel'] ?? 'Essence');
        $color = trim($_POST['v_color'] ?? '#9C6F1C');
        $desc = trim($_POST['v_desc'] ?? '');
        $image = trim($_POST['v_image'] ?? 'images/car1.jpg');
        $avail = !empty($_POST['v_available']);

        if (!$name || $price <= 0) {
            $msg = 'Nom du véhicule et prix par jour valides requis.';
            $msgType = 'err';
        } else {
            $found = false;
            foreach ($vehicles as &$v) {
                if ($v['id'] === $id) {
                    $v['name'] = $name;
                    $v['category'] = $cat;
                    $v['price_per_day'] = $price;
                    $v['seats'] = $seats;
                    $v['transmission'] = $trans;
                    $v['fuel'] = $fuel;
                    $v['color'] = $color;
                    $v['description'] = $desc;
                    $v['image'] = $image ?: 'images/car1.jpg';
                    $v['available'] = $avail;
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                $vehicles[] = [
                    'id' => 'veh-' . time(),
                    'name' => $name,
                    'category' => $cat,
                    'price_per_day' => $price,
                    'seats' => $seats,
                    'transmission' => $trans,
                    'fuel' => $fuel,
                    'color' => $color,
                    'description' => $desc,
                    'available' => $avail,
                    'image' => $image ?: 'images/car1.jpg'
                ];
            }
            file_put_contents(VEHICLES_FILE, json_encode($vehicles, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            $msg = 'Véhicule enregistré avec succès.';
        }
    }

    if ($postAction === 'delete_vehicle') {
        $id = trim($_POST['v_id'] ?? '');
        $vehicles = array_values(array_filter($vehicles, fn($v) => $v['id'] !== $id));
        file_put_contents(VEHICLES_FILE, json_encode($vehicles, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        $msg = 'Véhicule supprimé.';
    }

    if ($postAction === 'update_res') {
        $resId = trim($_POST['res_id'] ?? '');
        $newStatus = trim($_POST['res_status'] ?? '');
        foreach ($reservations as &$r) {
            if ($r['id'] === $resId) {
                $r['status'] = $newStatus;
                break;
            }
        }
        file_put_contents(RESERVATIONS_FILE, json_encode($reservations, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        $msg = 'Statut de réservation mis à jour.';
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Location de Voitures — Baobab Horizon</title>
  <link href="https://fonts.googleapis.com/css2?family=Lora:ital,wght@0,400;0,600;1,400&family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
  <style>
    :root{--gold:#9C6F1C;--gold-light:#D6AF5C;--bg:#0F1A17;--card-bg:#162420;--text:#F8F4EC;--muted:#8A9B97;--danger:#c45c5c;--ok:#5a9e6f}
    *,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
    body{font-family:'Poppins',sans-serif;background:var(--bg);color:var(--text);min-height:100vh;padding:24px 16px}
    .header{display:flex;justify-content:space-between;align-items:center;max-width:1100px;margin:0 auto 24px;border-bottom:1px solid rgba(156,111,28,0.25);padding-bottom:16px}
    .logo{font-family:'Lora',serif;font-size:1.4rem;letter-spacing:.1em;text-transform:uppercase;color:#fff}
    .logo span{color:var(--gold-light)}
    .container{max-width:1100px;margin:0 auto}
    .card{background:var(--card-bg);border:1px solid rgba(156,111,28,0.2);border-radius:4px;padding:24px;margin-bottom:24px}
    .card-title{font-family:'Lora',serif;font-size:1.3rem;color:var(--gold-light);margin-bottom:16px;display:flex;justify-content:space-between;align-items:center}
    .btn{display:inline-flex;align-items:center;justify-content:center;gap:8px;padding:10px 18px;background:var(--gold);color:var(--bg);border:none;border-radius:2px;font-weight:600;font-size:.8rem;text-transform:uppercase;letter-spacing:.08em;cursor:pointer;text-decoration:none;transition:.2s}
    .btn:hover{background:var(--gold-light)}
    .btn-ghost{background:transparent;border:1px solid var(--gold);color:var(--gold-light)}
    .btn-ghost:hover{background:rgba(156,111,28,0.15)}
    .btn-danger{background:var(--danger);color:#fff}
    .btn-sm{padding:6px 12px;font-size:.7rem}
    .msg{padding:12px;border-radius:3px;margin-bottom:18px;font-size:.85rem}
    .msg.ok{background:rgba(90,158,111,0.2);border:1px solid var(--ok);color:#a8dab5}
    .msg.err{background:rgba(196,92,92,0.2);border:1px solid var(--danger);color:#f2a8a8}
    .form-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:16px;margin-bottom:16px}
    label{display:block;font-size:.7rem;text-transform:uppercase;letter-spacing:.1em;color:var(--muted);margin-bottom:6px}
    input,select,textarea{width:100%;padding:10px;background:#0b1412;border:1px solid rgba(156,111,28,0.3);color:#fff;font-family:inherit;font-size:.85rem;border-radius:2px}
    input:focus,select:focus,textarea:focus{outline:none;border-color:var(--gold-light)}
    .table-responsive{overflow-x:auto}
    table{width:100%;border-collapse:collapse;margin-top:12px;font-size:.85rem}
    th,td{padding:12px;text-align:left;border-bottom:1px solid rgba(156,111,28,0.15)}
    th{color:var(--muted);font-size:.7rem;text-transform:uppercase;letter-spacing:.1em}
    tr:hover td{background:rgba(255,255,255,0.02)}
    .badge{padding:3px 8px;border-radius:2px;font-size:.65rem;text-transform:uppercase;letter-spacing:.05em}
    .badge-ok{background:rgba(90,158,111,0.2);color:#5a9e6f;border:1px solid #5a9e6f}
    .badge-pending{background:rgba(214,175,92,0.2);color:#d6af5c;border:1px solid #d6af5c}
    .badge-danger{background:rgba(196,92,92,0.2);color:#c45c5c;border:1px solid #c45c5c}
  </style>
</head>
<body>

<div class="header">
  <div class="logo">Baobab <span>Voitures Admin</span></div>
  <div>
    <?php if ($logged): ?>
      <a href="voiture_admin.php?action=download_backup" class="btn btn-ghost btn-sm">💾 Sauvegarde ZIP</a>
      <a href="voiture_admin.php?logout=1" class="btn btn-danger btn-sm">Déconnexion</a>
    <?php endif; ?>
  </div>
</div>

<div class="container">
  <?php if ($msg): ?>
    <div class="msg <?= $msgType ?>"><?= htmlspecialchars($msg) ?></div>
  <?php endif; ?>

  <?php if (!$logged): ?>
    <!-- LOGIN FORM -->
    <div class="card" style="max-width:420px;margin:60px auto;">
      <div class="card-title">Connexion Espace Voitures</div>
      <form method="POST">
        <input type="hidden" name="action" value="login">
        <div style="margin-bottom:16px">
          <label>Mot de passe administrateur</label>
          <input type="password" name="password" required placeholder="Mot de passe admin">
        </div>
        <button type="submit" class="btn" style="width:100%">Se Connecter</button>
      </form>
    </div>
  <?php else: ?>
    <!-- ADMIN DASHBOARD -->

    <?php
      $totalVehicles = count($vehicles);
      $availableVehicles = count(array_filter($vehicles, fn($v) => !empty($v['available'])));
      $pendingRes = count(array_filter($reservations, fn($r) => ($r['status'] ?? '') === 'pending'));
      $confirmedRes = count(array_filter($reservations, fn($r) => ($r['status'] ?? '') === 'confirmed'));
    ?>
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:16px;margin-bottom:24px;">
      <div class="card" style="padding:16px;margin:0;border-top:3px solid var(--gold);">
        <div style="font-size:0.75rem;color:var(--muted);text-transform:uppercase;letter-spacing:0.1em;">Total Véhicules</div>
        <div style="font-size:1.8rem;font-weight:700;color:#fff;margin-top:4px;"><?= $totalVehicles ?></div>
      </div>
      <div class="card" style="padding:16px;margin:0;border-top:3px solid var(--ok);">
        <div style="font-size:0.75rem;color:var(--muted);text-transform:uppercase;letter-spacing:0.12em;">Véhicules Disponibles</div>
        <div style="font-size:1.8rem;font-weight:700;color:var(--ok);margin-top:4px;"><?= $availableVehicles ?></div>
      </div>
      <div class="card" style="padding:16px;margin:0;border-top:3px solid var(--gold-light);">
        <div style="font-size:0.75rem;color:var(--muted);text-transform:uppercase;letter-spacing:0.12em;">Réservations en Attente</div>
        <div style="font-size:1.8rem;font-weight:700;color:var(--gold-light);margin-top:4px;"><?= $pendingRes ?></div>
      </div>
      <div class="card" style="padding:16px;margin:0;border-top:3px solid #5a8a9e;">
        <div style="font-size:0.75rem;color:var(--muted);text-transform:uppercase;letter-spacing:0.12em;">Réservations Validées</div>
        <div style="font-size:1.8rem;font-weight:700;color:#5a8a9e;margin-top:4px;"><?= $confirmedRes ?></div>
      </div>
    </div>

    <!-- AJOUTER / MODIFIER UN VÉHICULE -->
    <div class="card">
      <div class="card-title">🚘 Ajouter / Modifier un Véhicule</div>
      <form method="POST">
        <input type="hidden" name="form_action" value="save_vehicle">
        <div class="form-grid">
          <div>
            <label>ID Véhicule (Laisser vide pour créer)</label>
            <input type="text" name="v_id" id="v_id" placeholder="Nouveau véhicule">
          </div>
          <div>
            <label>Nom / Modèle du véhicule *</label>
            <input type="text" name="v_name" id="v_name" required placeholder="Ex: Toyota Prado VX 4x4">
          </div>
          <div>
            <label>Catégorie</label>
            <input type="text" name="v_category" id="v_category" placeholder="Ex: SUV 4x4 Luxe">
          </div>
          <div>
            <label>Prix par jour (FCFA) *</label>
            <input type="number" name="v_price" id="v_price" required min="0" placeholder="Ex: 65000">
          </div>
          <div>
            <label>Chemin de la photo (URL / Fichier)</label>
            <input type="text" name="v_image" id="v_image" placeholder="images/car1.jpg">
          </div>
          <div>
            <label>Nombre de places</label>
            <input type="number" name="v_seats" id="v_seats" value="5" min="1">
          </div>
          <div>
            <label>Transmission</label>
            <select name="v_trans" id="v_trans">
              <option value="Automatique">Automatique</option>
              <option value="Manuelle">Manuelle</option>
            </select>
          </div>
          <div>
            <label>Carburant</label>
            <select name="v_fuel" id="v_fuel">
              <option value="Diesel">Diesel</option>
              <option value="Essence">Essence</option>
              <option value="Hybride / Électrique">Hybride / Électrique</option>
            </select>
          </div>
          <div>
            <label>Couleur d'identification (Hex)</label>
            <input type="color" name="v_color" id="v_color" value="#9C6F1C" style="height:42px;padding:4px">
          </div>
        </div>

        <div style="margin-bottom:16px">
          <label>Description & Options incluses</label>
          <textarea name="v_desc" id="v_desc" placeholder="Équipements, climatisation, conditions..."></textarea>
        </div>

        <div style="display:flex;align-items:center;gap:12px;margin-bottom:16px">
          <input type="checkbox" name="v_available" id="v_available" value="1" checked style="width:auto">
          <label for="v_available" style="margin:0;cursor:pointer">Disponible à la réservation</label>
        </div>

        <div style="display:flex;gap:12px">
          <button type="submit" class="btn">💾 Enregistrer le véhicule</button>
          <button type="button" class="btn btn-ghost" onclick="resetVForm()">Réinitialiser le formulaire</button>
        </div>
      </form>
    </div>

    <!-- CATALOGUE DES VÉHICULES -->
    <div class="card">
      <div class="card-title">📋 Flotte de Véhicules Enregistrés (<?= count($vehicles) ?>)</div>
      <div class="table-responsive">
        <table>
          <thead>
            <tr>
              <th>Couleur</th>
              <th>Modèle</th>
              <th>Catégorie</th>
              <th>Prix / Jour</th>
              <th>Places</th>
              <th>Transmission</th>
              <th>Statut</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($vehicles as $v): ?>
              <tr>
                <td><span style="display:inline-block;width:20px;height:20px;border-radius:50%;background:<?= htmlspecialchars($v['color'] ?? '#9C6F1C') ?>"></span></td>
                <td><strong><?= htmlspecialchars($v['name']) ?></strong></td>
                <td><?= htmlspecialchars($v['category'] ?? '') ?></td>
                <td><strong><?= number_format($v['price_per_day'], 0, ',', ' ') ?> FCFA</strong></td>
                <td><?= htmlspecialchars($v['seats'] ?? 5) ?> pers.</td>
                <td><?= htmlspecialchars($v['transmission'] ?? '') ?></td>
                <td>
                  <?php if (!empty($v['available'])): ?>
                    <span class="badge badge-ok">Disponible</span>
                  <?php else: ?>
                    <span class="badge badge-danger">Indisponible</span>
                  <?php endif; ?>
                </td>
                <td>
                  <button type="button" class="btn btn-ghost btn-sm" onclick='editV(<?= json_encode($v) ?>)'>Éditer</button>
                  <form method="POST" style="display:inline" onsubmit="return confirm('Supprimer ce véhicule ?')">
                    <input type="hidden" name="form_action" value="delete_vehicle">
                    <input type="hidden" name="v_id" value="<?= htmlspecialchars($v['id']) ?>">
                    <button type="submit" class="btn btn-danger btn-sm">Supprimer</button>
                  </form>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- DEMANDES DE RÉSERVATION VOITURES -->
    <div class="card">
      <div class="card-title">📥 Demandes de Réservation de Véhicules (<?= count($reservations) ?>)</div>
      <div class="table-responsive">
        <table>
          <thead>
            <tr>
              <th>ID & Date</th>
              <th>Client & Contact</th>
              <th>Véhicule</th>
              <th>Période</th>
              <th>Chauffeur ?</th>
              <th>Offre</th>
              <th>Statut</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach (array_reverse($reservations) as $r): ?>
              <tr>
                <td><small><?= htmlspecialchars($r['id']) ?><br><?= htmlspecialchars($r['created_at'] ?? '') ?></small></td>
                <td>
                  <strong><?= htmlspecialchars($r['client_name']) ?></strong><br>
                  📞 <?= htmlspecialchars($r['phone']) ?><br>
                  ✉️ <?= htmlspecialchars($r['email'] ?? '') ?>
                </td>
                <td><?= htmlspecialchars($r['vehicle_id']) ?></td>
                <td>du <?= htmlspecialchars($r['start_date']) ?><br>au <?= htmlspecialchars($r['end_date']) ?></td>
                <td><?= !empty($r['with_driver']) ? '✅ Avec chauffeur' : '🚗 Sans chauffeur' ?></td>
                <td><span class="badge badge-pending"><?= htmlspecialchars($r['offer_type'] ?? 'Standard') ?></span></td>
                <td>
                  <?php if (($r['status'] ?? '') === 'confirmed'): ?>
                    <span class="badge badge-ok">Confirmé</span>
                  <?php elseif (($r['status'] ?? '') === 'cancelled'): ?>
                    <span class="badge badge-danger">Annulé</span>
                  <?php else: ?>
                    <span class="badge badge-pending">En attente</span>
                  <?php endif; ?>
                </td>
                <td>
                  <form method="POST" style="display:flex;gap:4px">
                    <input type="hidden" name="form_action" value="update_res">
                    <input type="hidden" name="res_id" value="<?= htmlspecialchars($r['id']) ?>">
                    <select name="res_status" onchange="this.form.submit()" style="padding:4px;font-size:.75rem">
                      <option value="pending" <?= ($r['status'] ?? '') === 'pending' ? 'selected' : '' ?>>En attente</option>
                      <option value="confirmed" <?= ($r['status'] ?? '') === 'confirmed' ? 'selected' : '' ?>>Valider</option>
                      <option value="cancelled" <?= ($r['status'] ?? '') === 'cancelled' ? 'selected' : '' ?>>Annuler</option>
                    </select>
                  </form>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>

  <?php endif; ?>
</div>

<script>
function editV(v) {
  document.getElementById('v_id').value = v.id || '';
  document.getElementById('v_name').value = v.name || '';
  document.getElementById('v_category').value = v.category || '';
  document.getElementById('v_price').value = v.price_per_day || '';
  document.getElementById('v_image').value = v.image || 'images/car1.jpg';
  document.getElementById('v_seats').value = v.seats || 5;
  document.getElementById('v_trans').value = v.transmission || 'Automatique';
  document.getElementById('v_fuel').value = v.fuel || 'Diesel';
  document.getElementById('v_color').value = v.color || '#9C6F1C';
  document.getElementById('v_desc').value = v.description || '';
  document.getElementById('v_available').checked = !!v.available;
  window.scrollTo({top: 0, behavior: 'smooth'});
}
function resetVForm() {
  document.getElementById('v_id').value = '';
  document.getElementById('v_name').value = '';
  document.getElementById('v_category').value = '';
  document.getElementById('v_price').value = '';
  document.getElementById('v_image').value = '';
  document.getElementById('v_seats').value = '5';
  document.getElementById('v_desc').value = '';
}
</script>

</body>
</html>
