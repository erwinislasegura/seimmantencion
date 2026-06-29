<?php
namespace App\Models;
use App\Core\Model;
class BaseCatalog extends Model {
 public function all(string $table,string $order='id DESC'): array { return $this->fetchAll("SELECT * FROM $table WHERE deleted_at IS NULL ORDER BY $order"); }
 public function find(string $table,int $id): ?array { return $this->fetch("SELECT * FROM $table WHERE id=? AND deleted_at IS NULL",[$id]); }
 public function softDelete(string $table,int $id): bool { return $this->execSql("UPDATE $table SET deleted_at=NOW(), estado='inactivo' WHERE id=?",[$id]); }
 public function audit(string $accion,string $modulo,?int $id,string $desc=''): void { $u=$_SESSION['user']['id']??null; $this->execSql("INSERT INTO audit_logs(usuario_id,accion,modulo,registro_id,descripcion,ip,user_agent,fecha) VALUES(?,?,?,?,?,?,?,NOW())",[$u,$accion,$modulo,$id,$desc,$_SERVER['REMOTE_ADDR']??'',$_SERVER['HTTP_USER_AGENT']??'']); }
}
