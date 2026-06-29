<?php
namespace App\Core;
abstract class Model { protected \PDO $db; public function __construct(){ $this->db=App::db(); } public function fetchAll(string $sql,array $p=[]):array{ $s=$this->db->prepare($sql); $s->execute($p); return $s->fetchAll(); } public function fetch(string $sql,array $p=[]):?array{ $s=$this->db->prepare($sql); $s->execute($p); return $s->fetch()?:null; } public function execSql(string $sql,array $p=[]):bool{ $s=$this->db->prepare($sql); return $s->execute($p); } }
