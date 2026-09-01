<?php
require __DIR__ . '/api/bootstrap.php';
echo "<h1>Test API</h1>";
echo "<h2>Testing availability read</h2>";
$avail = readAvailability();
echo "<pre>" . print_r($avail, true) . "</pre>";

echo "<h2>Testing reservations read</h2>";
$res = readReservations();
echo "<pre>" . print_r($res, true) . "</pre>";

echo "<h2>Testing writeAvailability</h2>";
$testAvail = $avail;
$testAvail['test'] = ['blocked_dates' => ['2026-08-01'], 'reservations' => []];
writeAvailability($testAvail);
echo "<p>Done writing test availability</p>";

echo "<h2>Testing read after write</h2>";
$avail2 = readAvailability();
echo "<pre>" . print_r($avail2, true) . "</pre>";
?>