<?php
namespace App\Models;
use App\Core\Model;
class AuthModel extends Model { public function byLogin(string $login):?array{ return $this->fetch("SELECT u.*, r.nombre rol FROM usuarios u JOIN roles r ON r.id=u.rol_id WHERE (u.email=? OR u.usuario=?) AND u.estado='activo' AND u.deleted_at IS NULL",[$login,$login]); } public function permissions(int $rol):array{ $rows=$this->fetchAll("SELECT modulo, permiso FROM rol_permiso WHERE rol_id=? AND permitido=1",[$rol]); $out=[]; foreach($rows as $r) $out[$r['modulo']][$r['permiso']]=true; return $out; }}
