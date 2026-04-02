<?php
declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use App\Infrastructure\Database\Connection;

header('Content-Type: application/json');

$results = [
    'php_version' => PHP_VERSION,
    'allow_url_fopen' => ini_get('allow_url_fopen'),
    'curl_enabled' => function_exists('curl_init'),
    'db_connection' => 'Pending',
    'external_api_photon' => 'Pending',
    'env' => [
        'DB_HOST' => getenv('DB_HOST') ?: 'not set',
        'APP_ENV' => getenv('APP_ENV') ?: 'not set',
    ]
];

// Test DB
try {
    $pdo = Connection::getInstance();
    $results['db_connection'] = 'SUCCESS';
} catch (\Throwable $e) {
    $results['db_connection'] = 'FAILED: ' . $e->getMessage();
}

// Test External API (Photon)
$testQuery = urlencode("Florianópolis, Brasil");
$url = "https://photon.komoot.io/api/?q={$testQuery}&limit=1";
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 5);
curl_setopt($ch, CURLOPT_USERAGENT, 'DriverEliteDebug/1.0');
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
if ($response !== false && $httpCode === 200) {
    $results['external_api_photon'] = 'SUCCESS (HTTP 200)';
} else {
    $results['external_api_photon'] = 'FAILED: ' . curl_error($ch) . " (HTTP $httpCode)";
}

echo json_encode($results, JSON_PRETTY_PRINT);
