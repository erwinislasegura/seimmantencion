<?php
namespace App\Core;
use PDO;
final class App { private static array $config; private static ?PDO $pdo=null; public static function boot(array $config): void { self::$config=$config; } public static function config(string $key, mixed $default=null): mixed { return self::$config[$key] ?? $default; } public static function db(): PDO { if(!self::$pdo){ $db=self::$config['db']; $dsn="mysql:host={$db['host']};dbname={$db['name']};charset={$db['charset']}"; self::$pdo=new PDO($dsn,$db['user'],$db['pass'],[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]); } return self::$pdo; } }
