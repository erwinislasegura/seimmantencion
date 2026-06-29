<?php
namespace App\Models;
class InventoryModel extends BaseCatalog {
 public function materiales():array{return $this->fetchAll('SELECT m.*, c.nombre categoria FROM materiales m LEFT JOIN categorias_materiales c ON c.id=m.categoria_id WHERE m.deleted_at IS NULL ORDER BY m.id DESC');}
 public function guardarMaterial(array $d,?int $id=null):int{ $p=[$d['codigo_interno'],$d['nombre_material'],$d['descripcion'],$d['categoria_id']?:null,$d['unidad_medida'],$d['stock_actual'],$d['stock_minimo'],$d['foto'],$d['estado']]; if($id){$p[]=$id; $this->execSql('UPDATE materiales SET codigo_interno=?,nombre_material=?,descripcion=?,categoria_id=?,unidad_medida=?,stock_actual=?,stock_minimo=?,foto=COALESCE(?,foto),estado=?,updated_at=NOW() WHERE id=?',$p); return $id;} $this->execSql('INSERT INTO materiales(codigo_interno,nombre_material,descripcion,categoria_id,unidad_medida,stock_actual,stock_minimo,foto,estado,created_at,updated_at) VALUES(?,?,?,?,?,?,?,?,?,NOW(),NOW())',$p); return (int)$this->db->lastInsertId(); }
 public function cableOptions():array{return $this->fetchAll('SELECT c.*, mc.nombre marca FROM cables c LEFT JOIN marcas_cable mc ON mc.id=c.marca_id WHERE c.deleted_at IS NULL ORDER BY c.numero_cable');}
}
