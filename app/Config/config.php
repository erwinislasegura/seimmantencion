<?php
$envAppUrl = $_ENV['APP_URL'] ?? '';
if ($envAppUrl === '' && function_exists('getenv')) {
    $getenvAppUrl = getenv('APP_URL');
    $envAppUrl = is_string($getenvAppUrl) ? $getenvAppUrl : '';
}

$forwardedProto = $_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '';
if (is_string($forwardedProto) && strpos($forwardedProto, ',') !== false) {
    $forwardedProto = trim(explode(',', $forwardedProto)[0]);
}
$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || $forwardedProto === 'https';
$scheme = $isHttps ? 'https' : 'http';

$host = $_SERVER['HTTP_X_FORWARDED_HOST'] ?? $_SERVER['HTTP_HOST'] ?? 'localhost';
if (is_string($host) && strpos($host, ',') !== false) {
    $host = trim(explode(',', $host)[0]);
}
$host = is_string($host) && $host !== '' ? $host : 'localhost';

$scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
$scriptDir = rtrim(dirname($scriptName), '/.');
if ($scriptDir === '/' || $scriptDir === '.') {
    $scriptDir = '';
}

$requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '';
$requestPath = '/' . ltrim($requestPath, '/');

// Si el front controller real está en /public, pero el hosting ejecuta desde la
// raíz del proyecto, SCRIPT_NAME puede llegar como /index.php. En ese escenario
// los assets físicos siguen viviendo bajo /public/assets, por eso se conserva
// /public en la URL base cuando el usuario ya ingresó con ese prefijo.
if (($scriptDir === '' || $scriptDir === '/') && ($requestPath === '/public' || str_starts_with($requestPath, '/public/'))) {
    $scriptDir = '/public';
}

$detectedBaseUrl = $scheme . '://' . $host . ($scriptDir === '' ? '' : $scriptDir);

return [
    'app_name' => 'Sistema de Mantención de Cables Mineros',
    'base_url' => rtrim($envAppUrl !== '' ? $envAppUrl : $detectedBaseUrl, '/'),
    'db' => [
        'host' => $_ENV['DB_HOST'] ?? '127.0.0.1',
        'name' => $_ENV['DB_NAME'] ?? 'seimenergia_mantencion',
        'user' => $_ENV['DB_USER'] ?? 'seimenergia_mantencion',
        'pass' => $_ENV['DB_PASS'] ?? 'lK9H?c$GF1^vE@iQ',
        'charset' => 'utf8mb4',
    ],
    'upload_max_bytes' => 3 * 1024 * 1024,
    'image_mimes' => ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'],
];
