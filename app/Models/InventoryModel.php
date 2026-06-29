<?php
namespace App\Models;
class InventoryModel extends BaseCatalog {
 public function materiales():array{return $this->fetchAll('SELECT m.*, c.nombre categoria FROM materiales m LEFT JOIN categorias_materiales c ON c.id=m.categoria_id WHERE m.deleted_at IS NULL ORDER BY m.id DESC');}
 public function guardarMaterial(array $d,?int $id=null):int{ $p=[$d['codigo_interno'],$d['nombre_material'],$d['descripcion'],$d['categoria_id']?:null,$d['unidad_medida'],$d['stock_actual'],$d['stock_minimo'],$d['foto'],$d['estado']]; if($id){$p[]=$id; $this->execSql('UPDATE materiales SET codigo_interno=?,nombre_material=?,descripcion=?,categoria_id=?,unidad_medida=?,stock_actual=?,stock_minimo=?,foto=COALESCE(?,foto),estado=?,updated_at=NOW() WHERE id=?',$p); return $id;} $this->execSql('INSERT INTO materiales(codigo_interno,nombre_material,descripcion,categoria_id,unidad_medida,stock_actual,stock_minimo,foto,estado,created_at,updated_at) VALUES(?,?,?,?,?,?,?,?,?,NOW(),NOW())',$p); return (int)$this->db->lastInsertId(); }
 public function cableOptions():array{return $this->fetchAll('SELECT c.*, mc.nombre marca FROM cables c LEFT JOIN marcas_cable mc ON mc.id=c.marca_id WHERE c.deleted_at IS NULL ORDER BY c.numero_cable');}
 public function existenciasMateriales():array{return $this->fetchAll('SELECT m.id,m.codigo_interno,m.nombre_material,m.unidad_medida,m.stock_actual,m.stock_minimo,m.estado,c.nombre categoria,
  COALESCE((SELECT SUM(d.cantidad_entregada) FROM entrega_material_detalle d WHERE d.material_id=m.id),0) total_entregado,
  COALESCE((SELECT SUM(d.cantidad_disponible) FROM entrega_material_detalle d JOIN entregas_materiales e ON e.id=d.entrega_id WHERE d.material_id=m.id AND e.estado="activa"),0) en_terreno,
  COALESCE((SELECT SUM(r.cantidad_devuelta) FROM recepcion_material_detalle r WHERE r.material_id=m.id),0) total_devuelto,
  COALESCE((SELECT SUM(im.cantidad_utilizada) FROM informe_materiales im WHERE im.material_id=m.id),0) total_usado,
  COALESCE((SELECT COUNT(*) FROM kardex_materiales k WHERE k.material_id=m.id),0) movimientos
  FROM materiales m LEFT JOIN categorias_materiales c ON c.id=m.categoria_id WHERE m.deleted_at IS NULL ORDER BY m.nombre_material');}
 public function totalesExistencias():array{return $this->fetch('SELECT COUNT(*) productos,COALESCE(SUM(m.stock_actual),0) stock_actual,
  COALESCE((SELECT SUM(d.cantidad_entregada) FROM entrega_material_detalle d),0) total_entregado,
  COALESCE((SELECT SUM(d.cantidad_disponible) FROM entrega_material_detalle d JOIN entregas_materiales e ON e.id=d.entrega_id WHERE e.estado="activa"),0) en_terreno,
  COALESCE((SELECT SUM(r.cantidad_devuelta) FROM recepcion_material_detalle r),0) total_devuelto,
  COALESCE((SELECT SUM(im.cantidad_utilizada) FROM informe_materiales im),0) total_usado
  FROM materiales m WHERE m.deleted_at IS NULL');}
 public function historialInventario(int $limit=120):array{return $this->fetchAll('SELECT * FROM (
  SELECT k.fecha,k.tipo_movimiento,k.cantidad,k.stock_antes,k.stock_despues,k.referencia_tipo,k.referencia_id,m.codigo_interno,m.nombre_material,m.unidad_medida,CONCAT(u.nombre," ",u.apellido) usuario
  FROM kardex_materiales k JOIN materiales m ON m.id=k.material_id LEFT JOIN usuarios u ON u.id=k.usuario_id
  UNION ALL
  SELECT COALESCE(i.updated_at,i.created_at,NOW()) fecha,"uso_informe" tipo_movimiento,im.cantidad_utilizada cantidad,COALESCE(im.stock_usuario_antes,0) stock_antes,COALESCE(im.stock_usuario_despues,0) stock_despues,"informe" referencia_tipo,im.informe_id referencia_id,m.codigo_interno,m.nombre_material,m.unidad_medida,CONCAT(u.nombre," ",u.apellido) usuario
  FROM informe_materiales im JOIN informes_cable i ON i.id=im.informe_id JOIN materiales m ON m.id=im.material_id LEFT JOIN usuarios u ON u.id=i.supervisor_id
  ) h ORDER BY fecha DESC LIMIT '.(int)$limit);}
}
