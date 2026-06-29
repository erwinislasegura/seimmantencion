<?php
function e(?string $v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function url(string $path = ''): string { return App\Core\App::config('base_url') . '/' . ltrim($path, '/'); }
function redirect(string $path): never { header('Location: ' . url($path)); exit; }
function csrf_token(): string { $_SESSION['_csrf'] ??= bin2hex(random_bytes(32)); return $_SESSION['_csrf']; }
function csrf_field(): string { return '<input type="hidden" name="_csrf" value="'.e(csrf_token()).'">'; }
function verify_csrf(): void { if ($_SERVER['REQUEST_METHOD'] === 'POST' && !hash_equals($_SESSION['_csrf'] ?? '', $_POST['_csrf'] ?? '')) { http_response_code(419); exit('Token CSRF inválido'); } }
function flash(string $key, ?string $msg = null): ?string { if ($msg !== null) { $_SESSION['_flash'][$key] = $msg; return null; } $v = $_SESSION['_flash'][$key] ?? null; unset($_SESSION['_flash'][$key]); return $v; }
function current_user(): ?array { return $_SESSION['user'] ?? null; }
function badge(string $estado): string { $map=['activo'=>'success','inactivo'=>'secondary','disponible'=>'success','en reparación'=>'warning','entregado'=>'info','dado de baja'=>'danger','borrador'=>'secondary','finalizado'=>'success','anulado'=>'danger']; return '<span class="badge text-bg-'.($map[$estado]??'secondary').'">'.e($estado).'</span>'; }
