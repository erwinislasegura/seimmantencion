<?php
declare(strict_types=1);
session_start();
require_once __DIR__ . '/../app/Core/helpers.php';
spl_autoload_register(function (string $class): void {
    $prefix = 'App\\';
    if (str_starts_with($class, $prefix)) {
        $path = __DIR__ . '/../app/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
        if (is_file($path)) require $path;
    }
});
$config = require __DIR__ . '/../app/Config/config.php';
App\Core\App::boot($config);
$router = new App\Core\Router();
require __DIR__ . '/../routes.php';
$router->dispatch($_SERVER['REQUEST_METHOD'], parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/');
