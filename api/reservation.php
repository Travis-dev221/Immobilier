<?php
session_start();
header('Content-Type: text/html; charset=UTF-8');

define('DATA_DIR', __DIR__ . '/data');
define('RESERVATIONS_FILE', DATA_DIR . '/reservations.json');
define('PROPERTIES_FILE', DATA_DIR . '/properties.json');

function readReservations() {
    if (!file_exists(RESERVATIONS_FILE)) {
        return ['requests' => [], 'validated' => []];
    }
    $data = json_decode(file_get_contents(RESERVATIONS_FILE), true);
    return is_array($data) ? $data : ['requests' => [], 'validated' => []];
}

function readProperties() {
    if (!file_exists(PROPERTIES_FILE)) {
        return [];
    }
    $data = json_decode(file_get_contents(PROPERTIES_FILE), true);
    return is_array($data) ? $data : [];
}

function h($s) { return htmlspecialchars($s ?? '', ENT_QUOTES); }

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
        // Collect personal information
        $personalInfo = [
            'birth_date' => trim($_POST['birth_date'] ?? ''),
            'nationality' => trim($_POST['nationality'] ?? ''),
            'address' => trim($_POST['address'] ?? ''),
            'id_number' => trim($_POST['id_number'] ?? ''),
            'payment_method' => trim($_POST['payment_method'] ?? ''),
            'invoice_send_method' => trim($_POST['invoice_send_method'] ?? '')
        ];
        
        // Update reservation with personal info
        $reservations = readReservations();
        foreach ($reservations['validated'] as &$res) {
            if (isset($res['access_key']) && $res['access_key'] === $accessKey) {
                $res['personal_info'] = $personalInfo;
                $res['invoice_generated'] = true;
                $res['invoice_generated_at'] = date('Y-m-d H:i:s');
                break;
            }
        }
        
        file_put_contents(RESERVATIONS_FILE, json_encode($reservations, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        
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

$properties = readProperties();
$villaData = isset($reservation['villa']) && isset($properties[$reservation['villa']]) ? $properties[$reservation['villa']] : null;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <link rel="icon" href="../favicon.ico" type="image/x-icon">
  <link rel="shortcut icon" href="../favicon.ico" type="image/x-icon">
  <link rel="apple-touch-icon" href="../favicon.png">
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Réservation — Baobab Horizon</title>
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,600;1,300;1,400&family=Jost:wght@200;300;400;500&display=swap" rel="stylesheet">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{--sand:#e8dcc8;--gold:#b8935a;--gold-light:#d4ab72;--night:#0f0d0a;--night-mid:#1a1612;--night-soft:#2a2520;--cream:#faf7f2;--text-muted:#8a7d6a;--font-display:'Cormorant Garamond',Georgia,serif;--font-body:'Jost',sans-serif}
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
  <p style="margin-top:20px"><a href="../" class="btn btn-secondary">Retour à l'accueil</a></p>
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
      <div class="invoice-title">Facture #<?= h($reservation['id']) ?></div>
      <div class="invoice-row">
        <span>Client:</span>
        <span><?= h($reservation['first_name']) ?> <?= h($reservation['last_name']) ?></span>
      </div>
      <div class="invoice-row">
        <span>Villa:</span>
        <span><?= h($reservation['villa_name']) ?></span>
      </div>
      <div class="invoice-row">
        <span>Dates:</span>
        <span><?= h($reservation['start']) ?> → <?= h($reservation['end']) ?></span>
      </div>
      <div class="invoice-row">
        <span>Nuitées:</span>
        <span><?= h($reservation['nights']) ?></span>
      </div>
      <div class="invoice-row">
        <span>Personnes:</span>
        <span><?= h($reservation['guests']) ?></span>
      </div>
      <div class="invoice-row">
        <span>Chef cuisinier:</span>
        <span><?= h($reservation['chef']) ?></span>
      </div>
      <div class="invoice-row total">
        <span>Total:</span>
        <span><?= number_format($reservation['total_amount'], 0, ',', ' ') ?> FCFA</span>
      </div>
    </div>
    
    <div style="margin-top:24px">
      <a href="../" class="btn">Retour à l'accueil</a>
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
    
    <form method="POST">
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
