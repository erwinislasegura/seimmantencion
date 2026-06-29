<?php
$isLogin = str_contains($view, 'auth/');
$currentPath = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
$currentSection = explode('/', $currentPath)[0] ?: 'dashboard';
$menuGroups = [
    [
        'label' => 'Operación',
        'summary' => 'Trabajo diario',
        'icon' => '01',
        'items' => [
            ['dashboard', 'Dashboard', 'Panel ejecutivo'],
            ['entregas-materiales', 'Entregas', 'Salida de materiales'],
            ['recepciones-materiales', 'Recepciones', 'Devoluciones'],
        ],
    ],
    [
        'label' => 'Inventario',
        'summary' => 'Materiales y activos',
        'icon' => '02',
        'items' => [
            ['materiales', 'Materiales', 'Stock y fichas'],
            ['inventario/existencias', 'Existencias', 'Usos e historial'],
            ['cables', 'Cables', 'Activos mineros'],
            ['marcas-cable', 'Marcas', 'Catálogo de marcas'],
        ],
    ],
    [
        'label' => 'Gestión técnica',
        'summary' => 'Informes y reportes',
        'icon' => '03',
        'items' => [
            ['informes-cable', 'Informes', 'Reparación de cables'],
            ['reportes', 'Reportes', 'Análisis y métricas'],
        ],
    ],
    [
        'label' => 'Configuración',
        'summary' => 'Administración',
        'icon' => '04',
        'items' => [
            ['usuarios', 'Usuarios', 'Cuentas del sistema'],
            ['roles', 'Roles', 'Permisos y accesos'],
        ],
    ],
];
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title><?= e(\App\Core\App::config('app_name')) ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="<?= url('assets/css/app.css') ?>" rel="stylesheet">
</head>
<body class="<?= $isLogin ? 'login-bg' : '' ?>">
<?php if (!$isLogin): ?>
  <aside class="sidebar">
    <div class="brand">
      <img src="<?= url('assets/img/logo.png') ?>" alt="SEIM Energía">
      <div class="brand-text"><span>SEIM</span><small>Energía</small></div>
    </div>
    <nav class="sidebar-nav" aria-label="Navegación principal">
      <?php foreach ($menuGroups as $group): ?>
        <?php $groupActive = false; foreach ($group['items'] as $item) { if ($currentPath === $item[0] || $currentSection === $item[0]) { $groupActive = true; break; } } ?>
        <details class="nav-group<?= $groupActive ? ' active' : '' ?>" <?= $groupActive ? 'open' : '' ?>>
          <summary>
            <span class="nav-index"><?= e($group['icon']) ?></span>
            <span><strong><?= e($group['label']) ?></strong><small><?= e($group['summary']) ?></small></span>
            <span class="nav-chevron">⌄</span>
          </summary>
          <div class="submenu">
            <?php foreach ($group['items'] as [$u, $t, $desc]): $active = $currentPath === $u || $currentSection === $u; ?>
              <a class="nav-link<?= $active ? ' active' : '' ?>" href="<?= url($u) ?>"<?= $active ? ' aria-current="page"' : '' ?>>
                <span class="nav-dot"></span>
                <span><strong><?= e($t) ?></strong><small><?= e($desc) ?></small></span>
              </a>
            <?php endforeach; ?>
          </div>
        </details>
      <?php endforeach; ?>
    </nav>
  </aside>
  <main class="main">
    <header class="topbar">
      <div>
        <strong><?= e(\App\Core\App::config('app_name')) ?></strong>
        <div class="crumb">Sistema de gestión / <?= e($currentPath ?: 'dashboard') ?></div>
      </div>
      <div class="user-actions">
        <span><?= e(current_user()['nombre'] ?? '') ?></span>
        <a class="btn btn-sm btn-outline-light" href="<?= url('logout') ?>">Salir</a>
      </div>
    </header>
    <section class="content">
      <?php if ($m = flash('success')): ?><div class="alert alert-success py-2"><?= e($m) ?></div><?php endif; ?>
      <?php if ($m = flash('error')): ?><div class="alert alert-danger py-2"><?= e($m) ?></div><?php endif; ?>
      <?php require $viewFile; ?>
    </section>
  </main>
<?php else: ?>
  <?php require $viewFile; ?>
<?php endif; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="<?= url('assets/js/app.js') ?>"></script>
</body>
</html>
