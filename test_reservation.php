<?php
// Test script to verify reservation system functionality

define('DATA_DIR', __DIR__ . '/data');
define('AVAILABILITY_FILE', DATA_DIR . '/availability.json');
define('RESERVATIONS_FILE', DATA_DIR . '/reservations.json');
define('PROPERTIES_FILE', DATA_DIR . '/properties.json');

echo "<h1>Test du système de réservation</h1>";

// Test 1: Check data files exist
echo "<h2>1. Vérification des fichiers de données</h2>";
$files = [
    'availability.json' => AVAILABILITY_FILE,
    'reservations.json' => RESERVATIONS_FILE,
    'properties.json' => PROPERTIES_FILE
];

foreach ($files as $name => $path) {
    if (file_exists($path)) {
        echo "<p>✅ $name existe</p>";
    } else {
        echo "<p>❌ $name n'existe pas</p>";
    }
}

// Test 2: Check API files exist
echo "<h2>2. Vérification des fichiers API</h2>";
$apiFiles = [
    'availability.php' => __DIR__ . '/api/availability.php',
    'reservations.php' => __DIR__ . '/api/reservations.php',
    'invoice.php' => __DIR__ . '/api/invoice.php'
];

foreach ($apiFiles as $name => $path) {
    if (file_exists($path)) {
        echo "<p>✅ api/$name existe</p>";
    } else {
        echo "<p>❌ api/$name n'existe pas</p>";
    }
}

// Test 3: Check reservation page exists
echo "<h2>3. Vérification de la page de réservation</h2>";
if (file_exists(__DIR__ . '/reservation.php')) {
    echo "<p>✅ reservation.php existe</p>";
} else {
    echo "<p>❌ reservation.php n'existe pas</p>";
}

// Test 4: Read and display availability data
echo "<h2>4. Données de disponibilité</h2>";
if (file_exists(AVAILABILITY_FILE)) {
    $availability = json_decode(file_get_contents(AVAILABILITY_FILE), true);
    echo "<pre>" . htmlspecialchars(json_encode($availability, JSON_PRETTY_PRINT)) . "</pre>";
}

// Test 5: Read and display reservations data
echo "<h2>5. Données de réservations</h2>";
if (file_exists(RESERVATIONS_FILE)) {
    $reservations = json_decode(file_get_contents(RESERVATIONS_FILE), true);
    echo "<pre>" . htmlspecialchars(json_encode($reservations, JSON_PRETTY_PRINT)) . "</pre>";
}

// Test 6: Check admin.php has been updated
echo "<h2>6. Vérification de admin.php</h2>";
$adminContent = file_get_contents(__DIR__ . '/admin.php');
if (strpos($adminContent, 'calendar') !== false) {
    echo "<p>✅ admin.php contient la section calendrier</p>";
} else {
    echo "<p>❌ admin.php ne contient pas la section calendrier</p>";
}

if (strpos($adminContent, 'reservations') !== false) {
    echo "<p>✅ admin.php contient la section réservations</p>";
} else {
    echo "<p>❌ admin.php ne contient pas la section réservations</p>";
}

// Test 7: Check vacances.html has been updated
echo "<h2>7. Vérification de vacances.html</h2>";
$vacancesContent = file_get_contents(__DIR__ . '/vacances.html');
if (strpos($vacancesContent, 'reservationModal') !== false) {
    echo "<p>✅ vacances.html contient le modal de réservation</p>";
} else {
    echo "<p>❌ vacances.html ne contient pas le modal de réservation</p>";
}

if (strpos($vacancesContent, 'openReservationModal') !== false) {
    echo "<p>✅ vacances.html contient la fonction openReservationModal</p>";
} else {
    echo "<p>❌ vacances.html ne contient pas la fonction openReservationModal</p>";
}

echo "<h2>✅ Tests terminés</h2>";
echo "<p><a href='admin.php'>Accéder à l'administration</a></p>";
echo "<p><a href='vacances.html'>Voir les villas</a></p>";
