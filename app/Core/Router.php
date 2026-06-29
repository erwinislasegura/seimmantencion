<?php
namespace App\Core;

final class Router
{
    private array $routes = [];

    public function get(string $p, array $a): void { $this->add('GET', $p, $a); }
    public function post(string $p, array $a): void { $this->add('POST', $p, $a); }
    private function add($m, $p, $a): void { $this->routes[$m][] = ['p' => trim($p, '/'), 'a' => $a]; }

    private function normalizePath(string $uri): string
    {
        $path = trim($uri, '/');
        $base = trim(parse_url(App::config('base_url'), PHP_URL_PATH) ?: '', '/');
        $projectBase = trim(dirname('/' . $base), '/.');
        foreach (array_filter([$base, $projectBase]) as $prefix) {
            if ($path === $prefix) return '';
            if (str_starts_with($path, $prefix . '/')) {
                $path = trim(substr($path, strlen($prefix)), '/');
                break;
            }
        }
        if ($path === 'public') $path = '';
        if (str_starts_with($path, 'public/')) $path = trim(substr($path, 7), '/');
        if ($path === 'index.php' || str_ends_with($path, '/index.php')) $path = trim(substr($path, 0, -9), '/');
        return $path;
    }

    public function dispatch(string $method, string $uri): void
    {
        $path = $this->normalizePath($uri);
        foreach ($this->routes[$method] ?? [] as $r) {
            $regex = '#^' . preg_replace('#\{([a-z_]+)\}#', '(?P<$1>[^/]+)', $r['p']) . '$#';
            if (preg_match($regex, $path, $m)) {
                [$c, $fn] = $r['a'];
                $params = array_filter($m, 'is_string', ARRAY_FILTER_USE_KEY);
                (new $c)->$fn(...array_values($params));
                return;
            }
        }
        http_response_code(404);
        echo '404 - Ruta no encontrada';
    }
}
