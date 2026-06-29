<?php
if (!function_exists('str_starts_with')) {
    function str_starts_with(string $haystack, string $needle): bool { return $needle === '' || strpos($haystack, $needle) === 0; }
}
if (!function_exists('str_ends_with')) {
    function str_ends_with(string $haystack, string $needle): bool { return $needle === '' || substr($haystack, -strlen($needle)) === $needle; }
}
if (!function_exists('str_contains')) {
    function str_contains(string $haystack, string $needle): bool { return $needle === '' || strpos($haystack, $needle) !== false; }
}
function e(?string $v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function url(string $path = ''): string { return App\Core\App::config('base_url') . '/' . ltrim($path, '/'); }
function app_path(string $uri = ''): string {
    $path = trim(parse_url($uri !== '' ? $uri : ($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH) ?: '', '/');
    $base = trim(parse_url(App\Core\App::config('base_url'), PHP_URL_PATH) ?: '', '/');
    $projectBase = trim(dirname('/' . $base), '/.');
    foreach (array_filter([$base, $projectBase]) as $prefix) {
        if ($path === $prefix) return '';
        if (str_starts_with($path, $prefix . '/')) return trim(substr($path, strlen($prefix)), '/');
    }
    if ($path === 'public') return '';
    if (str_starts_with($path, 'public/')) return trim(substr($path, 7), '/');
    return $path;
}
function redirect(string $path): never { header('Location: ' . url($path)); exit; }
function csrf_token(): string { $_SESSION['_csrf'] ??= bin2hex(random_bytes(32)); return $_SESSION['_csrf']; }
function csrf_field(): string { return '<input type="hidden" name="_csrf" value="'.e(csrf_token()).'">'; }
function refresh_csrf_token(): string { $_SESSION['_csrf'] = bin2hex(random_bytes(32)); return $_SESSION['_csrf']; }
function verify_csrf(): void {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;

    $sessionToken = $_SESSION['_csrf'] ?? '';
    $postedToken = $_POST['_csrf'] ?? '';
    if ($sessionToken !== '' && is_string($postedToken) && hash_equals($sessionToken, $postedToken)) return;

    refresh_csrf_token();
    flash('error', 'La sesión de seguridad expiró. Intente nuevamente.');
    $back = trim(parse_url($_SERVER['HTTP_REFERER'] ?? '', PHP_URL_PATH) ?: '', '/');
    $base = trim(parse_url(App\Core\App::config('base_url'), PHP_URL_PATH) ?: '', '/');
    foreach (array_filter([$base, trim(dirname('/' . $base), '/.')]) as $prefix) {
        if ($back === $prefix) { $back = ''; break; }
        if (str_starts_with($back, $prefix . '/')) { $back = trim(substr($back, strlen($prefix)), '/'); break; }
    }
    if ($back === '' || $back === 'public') $back = 'login';
    redirect($back);
}
function flash(string $key, ?string $msg = null): ?string { if ($msg !== null) { $_SESSION['_flash'][$key] = $msg; return null; } $v = $_SESSION['_flash'][$key] ?? null; unset($_SESSION['_flash'][$key]); return $v; }
function current_user(): ?array { return $_SESSION['user'] ?? null; }
function badge(string $estado): string { $map=['activo'=>'success','inactivo'=>'secondary','disponible'=>'success','en reparación'=>'warning','entregado'=>'info','dado de baja'=>'danger','borrador'=>'secondary','finalizado'=>'success','anulado'=>'danger','activa'=>'warning','cerrada'=>'success','crítico'=>'danger','critico'=>'danger','bajo'=>'warning','normal'=>'success']; return '<span class="badge text-bg-'.($map[$estado]??'secondary').'">'.e($estado).'</span>'; }
