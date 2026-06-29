<?php
return [
    'app_name' => 'Sistema de Mantención de Cables Mineros',
    'base_url' => rtrim($_ENV['APP_URL'] ?? 'http://localhost/seimmantencion/public', '/'),
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
