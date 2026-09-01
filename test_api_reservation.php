<?php
// Fichier de test pour l'API de réservation
header('Content-Type: text/html; charset=UTF-8');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <link rel="icon" href="favicon.ico" type="image/x-icon">
  <link rel="shortcut icon" href="favicon.ico" type="image/x-icon">
  <link rel="apple-touch-icon" href="favicon.png">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test API Réservation</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; background: #f5f5f5; }
        .test-section { background: white; padding: 20px; margin-bottom: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .success { color: green; }
        .error { color: red; }
        .info { color: #666; }
        button { padding: 10px 20px; background: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer; }
        button:hover { background: #0056b3; }
        pre { background: #f4f4f4; padding: 10px; border-radius: 4px; overflow-x: auto; }
    </style>
</head>
<body>
    <h1>Test API Réservation</h1>
    
    <div class="test-section">
        <h2>1. Test de connexion à l'API</h2>
        <p class="info">Cliquez sur le bouton pour tester si l'API répond</p>
        <button onclick="testApiConnection()">Tester la connexion</button>
        <div id="connectionResult"></div>
    </div>
    
    <div class="test-section">
        <h2>2. Test de création de réservation</h2>
        <p class="info">Cliquez pour tester la création d'une demande de réservation</p>
        <button onclick="testReservationCreation()">Tester la création</button>
        <div id="reservationResult"></div>
    </div>
    
    <div class="test-section">
        <h2>3. Informations PHP</h2>
        <pre>
Version PHP: <?php echo phpversion(); ?>
Extensions JSON: <?php echo extension_loaded('json') ? 'OK' : 'MANQUANTE'; ?>
Extensions Session: <?php echo extension_loaded('session') ? 'OK' : 'MANQUANTE'; ?>
Dossier api: <?php echo is_dir(__DIR__ . '/api') ? 'EXISTE' : 'MANQUANT'; ?>
Fichier reservations.php: <?php echo file_exists(__DIR__ . '/api/reservations.php') ? 'EXISTE' : 'MANQUANT'; ?>
Fichier bootstrap.php: <?php echo file_exists(__DIR__ . '/api/bootstrap.php') ? 'EXISTE' : 'MANQUANT'; ?>
        </pre>
    </div>

    <script>
        function testApiConnection() {
            const resultDiv = document.getElementById('connectionResult');
            resultDiv.innerHTML = '<p class="info">Test en cours...</p>';
            
            fetch('api/ping.php')
                .then(response => {
                    if (!response.ok) {
                        throw new Error('HTTP ' + response.status);
                    }
                    return response.json();
                })
                .then(data => {
                    resultDiv.innerHTML = '<p class="success">✓ Connexion réussie!</p><pre>' + JSON.stringify(data, null, 2) + '</pre>';
                })
                .catch(error => {
                    resultDiv.innerHTML = '<p class="error">✗ Erreur de connexion: ' + error.message + '</p>';
                });
        }
        
        function testReservationCreation() {
            const resultDiv = document.getElementById('reservationResult');
            resultDiv.innerHTML = '<p class="info">Test en cours...</p>';
            
            const formData = new FormData();
            formData.append('action', 'create_request');
            formData.append('villa', 'test-villa');
            formData.append('start', '2026-08-01');
            formData.append('end', '2026-08-03');
            formData.append('guests', '2');
            formData.append('chef', 'Non');
            formData.append('contact_method', 'whatsapp');
            formData.append('first_name', 'Test');
            formData.append('last_name', 'User');
            formData.append('phone', '770000000');
            formData.append('email', 'test@example.com');
            
            fetch('api/reservations.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                const statusText = response.statusText;
                const status = response.status;
                return response.text().then(text => ({
                    status,
                    statusText,
                    text
                }));
            })
            .then(({status, statusText, text}) => {
                try {
                    const data = JSON.parse(text);
                    if (data.success) {
                        resultDiv.innerHTML = '<p class="success">✓ Test réussi!</p><pre>' + JSON.stringify(data, null, 2) + '</pre>';
                    } else {
                        resultDiv.innerHTML = '<p class="error">✗ Erreur API: ' + (data.error || 'Erreur inconnue') + '</p><pre>' + JSON.stringify(data, null, 2) + '</pre>';
                    }
                } catch (e) {
                    resultDiv.innerHTML = '<p class="error">✗ Réponse invalide (HTTP ' + status + '):</p><pre>' + text + '</pre>';
                }
            })
            .catch(error => {
                resultDiv.innerHTML = '<p class="error">✗ Erreur de connexion: ' + error.message + '</p>';
            });
        }
    </script>
</body>
</html>
