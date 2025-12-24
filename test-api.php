<?php
/**
 * File untuk test API endpoint
 * Akses: http://localhost/bot-wa-real/test-api.php
 */

echo "<h1>Test API Endpoints</h1>";

// Test date.php
echo "<h2>1. Test /api/date.php</h2>";
$dateUrl = 'http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . '/api/date.php';
echo "<p>URL: <a href='$dateUrl' target='_blank'>$dateUrl</a></p>";

$ch = curl_init($dateUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, true);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
$headers = substr($response, 0, $headerSize);
$body = substr($response, $headerSize);
curl_close($ch);

echo "<p>HTTP Code: $httpCode</p>";
echo "<h3>Headers:</h3><pre>" . htmlspecialchars($headers) . "</pre>";
echo "<h3>Response Body:</h3><pre>" . htmlspecialchars($body) . "</pre>";

// Test absensi.php statistics
echo "<hr><h2>2. Test /api/absensi.php?action=statistics</h2>";
$absensiUrl = 'http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . '/api/absensi.php?action=statistics&startDate=' . date('Y-m-d') . '&endDate=' . date('Y-m-d');
echo "<p>URL: <a href='$absensiUrl' target='_blank'>$absensiUrl</a></p>";

$ch = curl_init($absensiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, true);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
$headers = substr($response, 0, $headerSize);
$body = substr($response, $headerSize);
curl_close($ch);

echo "<p>HTTP Code: $httpCode</p>";
echo "<h3>Headers:</h3><pre>" . htmlspecialchars($headers) . "</pre>";
echo "<h3>Response Body:</h3><pre>" . htmlspecialchars($body) . "</pre>";

// Test absensi.php range
echo "<hr><h2>3. Test /api/absensi.php?action=range</h2>";
$rangeUrl = 'http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . '/api/absensi.php?action=range&startDate=' . date('Y-m-d') . '&endDate=' . date('Y-m-d');
echo "<p>URL: <a href='$rangeUrl' target='_blank'>$rangeUrl</a></p>";

$ch = curl_init($rangeUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, true);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
$headers = substr($response, 0, $headerSize);
$body = substr($response, $headerSize);
curl_close($ch);

echo "<p>HTTP Code: $httpCode</p>";
echo "<h3>Headers:</h3><pre>" . htmlspecialchars($headers) . "</pre>";
echo "<h3>Response Body:</h3><pre>" . htmlspecialchars($body) . "</pre>";

echo "<hr><p><strong>Catatan:</strong> Jika response body berisi HTML (bukan JSON), berarti ada error PHP yang menghasilkan HTML error page.</p>";

