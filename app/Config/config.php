<?php
$envAppUrl = $_ENV['APP_URL'] ?? getenv('APP_URL') ?: '';

$forwardedProto = $_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '';
$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || $forwardedProto === 'https';
$scheme = $isHttps ? 'https' : 'http';
$host = $_SERVER['HTTP_X_FORWARDED_HOST'] ?? $_SERVER['HTTP_HOST'] ?? 'localhost';
$scriptDir = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
$detectedBaseUrl = $scheme . '://' . $host . ($scriptDir === '' ? '' : $scriptDir);

return [
    'app_name' => 'Sistema de Mantención de Cables Mineros',
    'base_url' => rtrim($envAppUrl !== '' ? $envAppUrl : $detectedBaseUrl, '/'),
    'db' => [
        'host' => $_ENV['DB_HOST'] ?? '127.0.0.1',
        'name' => $_ENV['DB_NAME'] ?? 'seim_mantencion',
        'user' => $_ENV['DB_USER'] ?? 'root',
        'pass' => $_ENV['DB_PASS'] ?? '',
        'charset' => 'utf8mb4',
    ],
    'upload_max_bytes' => 3 * 1024 * 1024,
    'image_mimes' => ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'],
];
