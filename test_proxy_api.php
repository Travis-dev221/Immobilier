<?php
// Test des fichiers API proxy
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
    <title>Test API Proxy</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; background: #f5f5f5; }
        .test-section { background: white; padding: 20px; margin-bottom: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .success { color: green; background: #e8f5e9; padding: 10px; border-radius: 4px; }
        .error { color: red; background: #ffebee; padding: 10px; border-radius: 4px; }
        .info { color: #666; background: #e3f2fd; padding: 10px; border-radius: 4px; }
        button { padding: 10px 20px; background: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer; margin: 5px; }
        button:hover { background: #0056b3; }
        pre { background: #f4f4f4; padding: 10px; border-radius: 4px; overflow-x: auto; }
    </style>
</head>
<body>
    <h1>Test API Proxy (Solution LiteSpeed)</h1>
    
    <div class="test-section">
        <h2>1. Test API Availability Proxy</h2>
        <p class="info">Test du fichier api_availability.php à la racine</p>
        <button onclick="testAvailabilityProxy()">Tester availability</button>
        <div id="availabilityResult"></div>
    </div>
    
    <div class="test-section">
        <h2>2. Test API Reservations Proxy</h2>
        <p class="info">Test du fichier api_reservations.php à la racine</p>
        <button onclick="testReservationsProxy()">Tester reservations</button>
        <div id="reservationsResult"></div>
    </div>
    
    <div class="test-section">
        <h2>2b. Test API Reservations Simple</h2>
        <p class="info">Test du fichier api_reservations_simple.php (version débogage)</p>
        <button onclick="testReservationsSimple()">Tester reservations simple</button>
        <div id="reservationsSimpleResult"></div>
    </div>
    
    <div class="test-section">
        <h2>3. Test complet de réservation</h2>
        <p class="info">Simulation d'une demande de réservation complète</p>
        <button onclick="testFullReservation()">Tester réservation complète</button>
        <div id="fullReservationResult"></div>
    </div>
    
    <div class="test-section">
        <h2>3b. Test complet de réservation (version simple)</h2>
        <p class="info">Test avec la version simplifiée de l'API</p>
        <button onclick="testFullReservationSimple()">Tester réservation simple</button>
        <div id="fullReservationSimpleResult"></div>
    </div>
    
    <div class="test-section">
        <h2>État des fichiers proxy</h2>
        <pre>
Fichier api_availability.php: <?php echo file_exists(__DIR__ . '/api_availability.php') ? '✓ EXISTE' : '✗ MANQUANT'; ?>
Fichier api_reservations.php: <?php echo file_exists(__DIR__ . '/api_reservations.php') ? '✓ EXISTE' : '✗ MANQUANT'; ?>
Fichier test_php_root.php: <?php echo file_exists(__DIR__ . '/test_php_root.php') ? '✓ EXISTE' : '✗ MANQUANT'; ?>
        </pre>
    </div>
    
    <div class="test-section">
        <h2>4. Test PHP à la racine</h2>
        <p class="info">Vérification que PHP s'exécute correctement à la racine du site</p>
        <button onclick="testPhpRoot()">Tester PHP racine</button>
        <div id="phpRootResult"></div>
    </div>
    
    <div class="test-section">
        <h2>5. Test POST ultra-simple</h2>
        <p class="info">Test basique pour identifier le problème POST</p>
        <button onclick="testPostSimple()">Tester POST simple</button>
        <div id="postSimpleResult"></div>
    </div>
    
    <div class="test-section">
        <h2>6. Test API Réservation GET (NOUVELLE APPROCHE)</h2>
        <p class="info">Test de la nouvelle API utilisant GET au lieu de POST</p>
        <button onclick="testReservationGet()">Tester réservation GET</button>
        <div id="reservationGetResult"></div>
    </div>

    <script>
        function testAvailabilityProxy() {
            const resultDiv = document.getElementById('availabilityResult');
            resultDiv.innerHTML = '<p class="info">Test en cours...</p>';
            
            fetch('api_availability.php?action=get')
                .then(response => {
                    if (!response.ok) {
                        throw new Error('HTTP ' + response.status);
                    }
                    return response.json();
                })
                .then(data => {
                    resultDiv.innerHTML = '<div class="success">✓ API Availability Proxy fonctionne!</div><pre>' + JSON.stringify(data, null, 2) + '</pre>';
                })
                .catch(error => {
                    resultDiv.innerHTML = '<div class="error">✗ Erreur: ' + error.message + '</div>';
                });
        }
        
        function testReservationsProxy() {
            const resultDiv = document.getElementById('reservationsResult');
            resultDiv.innerHTML = '<p class="info">Test en cours...</p>';
            
            // Test avec un token d'auth pour l'endpoint list
            fetch('api_reservations.php?action=list', {
                headers: {
                    'Authorization': 'Bearer Baobab2026'
                }
            })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('HTTP ' + response.status);
                    }
                    return response.json();
                })
                .then(data => {
                    resultDiv.innerHTML = '<div class="success">✓ API Reservations Proxy fonctionne!</div><pre>' + JSON.stringify(data, null, 2) + '</pre>';
                })
                .catch(error => {
                    resultDiv.innerHTML = '<div class="error">✗ Erreur: ' + error.message + '</div>';
                });
        }
        
        function testFullReservation() {
            const resultDiv = document.getElementById('fullReservationResult');
            resultDiv.innerHTML = '<p class="info">Test en cours...</p>';
            
            const formData = new FormData();
            formData.append('action', 'create_request');
            formData.append('villa', 'guede');
            formData.append('start', '2026-08-01');
            formData.append('end', '2026-08-03');
            formData.append('guests', '2');
            formData.append('chef', 'Non');
            formData.append('contact_method', 'whatsapp');
            formData.append('first_name', 'Test');
            formData.append('last_name', 'Proxy');
            formData.append('phone', '770000000');
            formData.append('email', 'test@example.com');
            
            fetch('api_reservations.php', {
                method: 'POST',
                body: formData
            })
                .then(response => {
                    const status = response.status;
                    const statusText = response.statusText;
                    return response.text().then(text => ({ status, statusText, text }));
                })
                .then(({status, statusText, text}) => {
                    try {
                        const data = JSON.parse(text);
                        if (data.success) {
                            resultDiv.innerHTML = '<div class="success">✓ Réservation créée avec succès!</div><pre>' + JSON.stringify(data, null, 2) + '</pre>';
                        } else {
                            resultDiv.innerHTML = '<div class="error">✗ Erreur API: ' + (data.error || 'Erreur inconnue') + '</div><pre>' + JSON.stringify(data, null, 2) + '</pre>';
                        }
                    } catch (e) {
                        resultDiv.innerHTML = '<div class="error">✗ Réponse invalide (HTTP ' + status + '):</div><pre>' + text.substring(0, 500) + '</pre>';
                    }
                })
                .catch(error => {
                    resultDiv.innerHTML = '<div class="error">✗ Erreur: ' + error.message + '</div>';
                });
        }
        
        function testReservationsSimple() {
            const resultDiv = document.getElementById('reservationsSimpleResult');
            resultDiv.innerHTML = '<p class="info">Test en cours...</p>';
            
            fetch('api_reservations_simple.php?action=list')
                .then(response => {
                    if (!response.ok) {
                        throw new Error('HTTP ' + response.status);
                    }
                    return response.json();
                })
                .then(data => {
                    resultDiv.innerHTML = '<div class="success">✓ API Reservations Simple fonctionne!</div><pre>' + JSON.stringify(data, null, 2) + '</pre>';
                })
                .catch(error => {
                    resultDiv.innerHTML = '<div class="error">✗ Erreur: ' + error.message + '</div>';
                });
        }
        
        function testFullReservationSimple() {
            const resultDiv = document.getElementById('fullReservationSimpleResult');
            resultDiv.innerHTML = '<p class="info">Test en cours...</p>';
            
            const formData = new FormData();
            formData.append('action', 'create_request');
            formData.append('villa', 'guede');
            formData.append('start', '2026-08-01');
            formData.append('end', '2026-08-03');
            formData.append('guests', '2');
            formData.append('first_name', 'Test');
            formData.append('last_name', 'Simple');
            formData.append('phone', '770000000');
            formData.append('email', 'test@example.com');
            
            fetch('api_reservations_simple.php', {
                method: 'POST',
                body: formData
            })
                .then(response => {
                    const status = response.status;
                    const statusText = response.statusText;
                    return response.text().then(text => ({ status, statusText, text }));
                })
                .then(({status, statusText, text}) => {
                    try {
                        const data = JSON.parse(text);
                        if (data.success) {
                            resultDiv.innerHTML = '<div class="success">✓ Réservation créée avec succès (version simple)!</div><pre>' + JSON.stringify(data, null, 2) + '</pre>';
                        } else {
                            resultDiv.innerHTML = '<div class="error">✗ Erreur API: ' + (data.error || 'Erreur inconnue') + '</div><pre>' + JSON.stringify(data, null, 2) + '</pre>';
                        }
                    } catch (e) {
                        resultDiv.innerHTML = '<div class="error">✗ Réponse invalide (HTTP ' + status + '):</div><pre>' + text.substring(0, 500) + '</pre>';
                    }
                })
                .catch(error => {
                    resultDiv.innerHTML = '<div class="error">✗ Erreur: ' + error.message + '</div>';
                });
        }
        
        function testPhpRoot() {
            const resultDiv = document.getElementById('phpRootResult');
            resultDiv.innerHTML = '<p class="info">Test en cours...</p>';
            
            fetch('test_php_root.php')
                .then(response => {
                    if (!response.ok) {
                        throw new Error('HTTP ' + response.status);
                    }
                    return response.json();
                })
                .then(data => {
                    resultDiv.innerHTML = '<div class="success">✓ PHP fonctionne correctement à la racine!</div><pre>' + JSON.stringify(data, null, 2) + '</pre>';
                })
                .catch(error => {
                    resultDiv.innerHTML = '<div class="error">✗ Erreur: ' + error.message + '</div>';
                });
        }
        
        function testPostSimple() {
            const resultDiv = document.getElementById('postSimpleResult');
            resultDiv.innerHTML = '<p class="info">Test en cours...</p>';
            
            const formData = new FormData();
            formData.append('test', 'value');
            formData.append('action', 'test');
            
            fetch('test_post.php', {
                method: 'POST',
                body: formData
            })
                .then(response => {
                    const status = response.status;
                    const statusText = response.statusText;
                    return response.text().then(text => ({ status, statusText, text }));
                })
                .then(({status, statusText, text}) => {
                    try {
                        const data = JSON.parse(text);
                        resultDiv.innerHTML = '<div class="success">✓ POST fonctionne!</div><pre>' + JSON.stringify(data, null, 2) + '</pre>';
                    } catch (e) {
                        resultDiv.innerHTML = '<div class="error">✗ Réponse invalide (HTTP ' + status + '):</div><pre>' + text.substring(0, 500) + '</pre>';
                    }
                })
                .catch(error => {
                    resultDiv.innerHTML = '<div class="error">✗ Erreur: ' + error.message + '</div>';
                });
        }
        
        function testReservationGet() {
            const resultDiv = document.getElementById('reservationGetResult');
            resultDiv.innerHTML = '<p class="info">Test en cours...</p>';
            
            const params = new URLSearchParams();
            params.append('action', 'create_request');
            params.append('villa', 'guede');
            params.append('start', '2026-08-01');
            params.append('end', '2026-08-03');
            params.append('guests', '2');
            params.append('chef', 'Non');
            params.append('contact_method', 'whatsapp');
            params.append('first_name', 'Test');
            params.append('last_name', 'GET');
            params.append('phone', '770000000');
            params.append('email', 'test@example.com');
            
            fetch('api_reservation_get.php?' + params.toString(), { method: 'GET' })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('HTTP ' + response.status);
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        resultDiv.innerHTML = '<div class="success">✓ Réservation créée avec succès via GET!</div><pre>' + JSON.stringify(data, null, 2) + '</pre>';
                    } else {
                        resultDiv.innerHTML = '<div class="error">✗ Erreur API: ' + (data.error || 'Erreur inconnue') + '</div><pre>' + JSON.stringify(data, null, 2) + '</pre>';
                    }
                })
                .catch(error => {
                    resultDiv.innerHTML = '<div class="error">✗ Erreur: ' + error.message + '</div>';
                });
        }
    </script>
</body>
</html>
