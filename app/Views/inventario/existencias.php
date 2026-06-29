<?php
$fmt = fn($v) => rtrim(rtrim(number_format((float)$v, 2, ',', '.'), '0'), ',');
$tipo = [
  'salida_entrega' => 'Entrega',
  'entrada_recepcion' => 'Recepción',
  'recepcion_sin_stock' => 'Recepción sin stock',
  'uso_informe' => 'Uso en informe',
];
?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <div>
    <h5 class="mb-1">Existencias y uso de inventario</h5>
    <div class="muted">Listado consolidado de productos, stock actual, cantidades entregadas/usadas e historial de movimientos.</div>
  </div>
  <a class="btn btn-sm btn-outline-light" href="<?= url('materiales') ?>">Volver a materiales</a>
</div>

<div class="row g-2 mb-3">
  <div class="col-md-2 col-sm-6"><div class="kpi compact"><span>Productos</span><strong><?= e($fmt($totales['productos'] ?? 0)) ?></strong></div></div>
  <div class="col-md-2 col-sm-6"><div class="kpi compact"><span>Stock actual</span><strong><?= e($fmt($totales['stock_actual'] ?? 0)) ?></strong></div></div>
  <div class="col-md-2 col-sm-6"><div class="kpi compact"><span>Entregado</span><strong><?= e($fmt($totales['total_entregado'] ?? 0)) ?></strong></div></div>
  <div class="col-md-2 col-sm-6"><div class="kpi compact"><span>En terreno</span><strong><?= e($fmt($totales['en_terreno'] ?? 0)) ?></strong></div></div>
  <div class="col-md-2 col-sm-6"><div class="kpi compact"><span>Devuelto</span><strong><?= e($fmt($totales['total_devuelto'] ?? 0)) ?></strong></div></div>
  <div class="col-md-2 col-sm-6"><div class="kpi compact"><span>Usado</span><strong><?= e($fmt($totales['total_usado'] ?? 0)) ?></strong></div></div>
</div>

<div class="card-dark mb-3">
  <h6>Resumen por producto</h6>
  <table class="table table-dark table-sm datatable" data-page-size="12">
    <thead><tr><th>Código</th><th>Producto</th><th>Categoría</th><th>Existencia</th><th>Entregado</th><th>En terreno</th><th>Devuelto</th><th>Usado</th><th>Total controlado</th><th>Estado</th></tr></thead>
    <tbody>
      <?php foreach($rows as $r): $controlado=(float)$r['stock_actual']+(float)$r['en_terreno']+(float)$r['total_usado']; $estado=((float)$r['stock_actual']<=0?'critico':((float)$r['stock_actual']<=(float)$r['stock_minimo']?'bajo':'normal')); ?>
        <tr>
          <td><?= e($r['codigo_interno']) ?></td>
          <td><strong><?= e($r['nombre_material']) ?></strong><br><span class="muted"><?= e($r['unidad_medida']) ?> · <?= e($fmt($r['movimientos'])) ?> movimientos</span></td>
          <td><?= e($r['categoria'] ?? '') ?></td>
          <td><?= e($fmt($r['stock_actual'])) ?></td>
          <td><?= e($fmt($r['total_entregado'])) ?></td>
          <td><?= e($fmt($r['en_terreno'])) ?></td>
          <td><?= e($fmt($r['total_devuelto'])) ?></td>
          <td><?= e($fmt($r['total_usado'])) ?></td>
          <td><?= e($fmt($controlado)) ?></td>
          <td><?= badge($estado) ?></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<div class="card-dark">
  <h6>Historial de usos y entregas</h6>
  <table class="table table-dark table-sm datatable" data-page-size="15">
    <thead><tr><th>Fecha</th><th>Movimiento</th><th>Producto</th><th>Cantidad</th><th>Stock antes</th><th>Stock después</th><th>Referencia</th><th>Usuario</th></tr></thead>
    <tbody>
      <?php foreach($historial as $h): ?>
        <tr>
          <td><?= e($h['fecha']) ?></td>
          <td><?= badge($tipo[$h['tipo_movimiento']] ?? $h['tipo_movimiento']) ?></td>
          <td><strong><?= e($h['nombre_material']) ?></strong><br><span class="muted"><?= e($h['codigo_interno']) ?></span></td>
          <td><?= e($fmt($h['cantidad']).' '.$h['unidad_medida']) ?></td>
          <td><?= e($fmt($h['stock_antes'])) ?></td>
          <td><?= e($fmt($h['stock_despues'])) ?></td>
          <td><?= e(($h['referencia_tipo'] ?? '').' #'.($h['referencia_id'] ?? '')) ?></td>
          <td><?= e($h['usuario'] ?? '') ?></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
