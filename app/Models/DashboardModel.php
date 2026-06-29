<?php
namespace App\Models;
use App\Core\Model;

class DashboardModel extends Model
{
    private function where(array $f, string $alias='i'): array {
        $w=[]; $p=[];
        if(!empty($f['fecha_desde'])) { $w[]="DATE($alias.fecha_recepcion_cable)>=:fd"; $p['fd']=$f['fecha_desde']; }
        if(!empty($f['fecha_hasta'])) { $w[]="DATE($alias.fecha_recepcion_cable)<=:fh"; $p['fh']=$f['fecha_hasta']; }
        if(!empty($f['supervisor_id'])) { $w[]="$alias.supervisor_id=:sup"; $p['sup']=$f['supervisor_id']; }
        if(!empty($f['estado_cable'])) { $w[]='c.estado=:ec'; $p['ec']=$f['estado_cable']; }
        if(!empty($f['origen_cable'])) { $w[]="$alias.origen_cable=:origen"; $p['origen']=$f['origen_cable']; }
        if(!empty($f['usuario_id'])) { $w[]="($alias.creado_por=:uid OR $alias.supervisor_id=:uid)"; $p['uid']=$f['usuario_id']; }
        return [$w ? ' WHERE '.implode(' AND ',$w) : '', $p];
    }
    private function scalar(string $sql,array $p=[]): int { return (int)($this->fetch($sql,$p)['c']??0); }
    private function sum(string $sql,array $p=[]): float { return (float)($this->fetch($sql,$p)['c']??0); }

    public function getFiltros(): array { return [
        'supervisores'=>$this->fetchAll("SELECT id,CONCAT(nombre,' ',apellido) nombre FROM usuarios WHERE deleted_at IS NULL AND estado='activo' ORDER BY nombre,apellido"),
        'origenes'=>$this->fetchAll("SELECT DISTINCT origen_cable FROM informes_cable WHERE origen_cable IS NOT NULL AND origen_cable<>'' ORDER BY origen_cable")
    ]; }
    public function getTotalCables(): int { return $this->scalar("SELECT COUNT(*) c FROM cables WHERE deleted_at IS NULL"); }
    public function getCablesPorEstado(): array { return $this->fetchAll("SELECT estado,COUNT(*) total FROM cables WHERE deleted_at IS NULL GROUP BY estado"); }
    public function getInformesHoy(): int { return $this->scalar("SELECT COUNT(*) c FROM informes_cable WHERE DATE(created_at)=CURDATE()"); }
    public function getInformesPendientes(): int { return $this->scalar("SELECT COUNT(*) c FROM informes_cable WHERE estado_informe='borrador'"); }
    public function getInformesPorEstado(array $f=[]): array { [$w,$p]=$this->where($f); return $this->fetchAll("SELECT i.estado_informe estado,COUNT(*) total FROM informes_cable i JOIN cables c ON c.id=i.cable_id $w GROUP BY i.estado_informe",$p); }
    public function getInformesFinalizados(array $f=[]): int { [$w,$p]=$this->where($f); $w.=($w?' AND':' WHERE')." i.estado_informe='finalizado'"; return $this->scalar("SELECT COUNT(*) c FROM informes_cable i JOIN cables c ON c.id=i.cable_id $w",$p); }
    public function getMaterialesStockBajo(): int { return $this->scalar("SELECT COUNT(*) c FROM materiales WHERE deleted_at IS NULL AND stock_actual<=stock_minimo"); }
    public function getMaterialesCriticos(): array { return $this->fetchAll("SELECT m.*,cm.nombre categoria FROM materiales m LEFT JOIN categorias_materiales cm ON cm.id=m.categoria_id WHERE m.deleted_at IS NULL AND m.stock_actual<=m.stock_minimo ORDER BY m.stock_actual ASC,m.nombre_material LIMIT 10"); }
    public function getMaterialesMasUsados(array $f=[]): array { [$w,$p]=$this->where($f); return $this->fetchAll("SELECT m.id,m.codigo_interno,m.nombre_material,m.foto,m.stock_actual,m.stock_minimo,m.unidad_medida,cm.nombre categoria,SUM(im.cantidad_utilizada) cantidad_utilizada FROM informe_materiales im JOIN informes_cable i ON i.id=im.informe_id JOIN cables c ON c.id=i.cable_id JOIN materiales m ON m.id=im.material_id LEFT JOIN categorias_materiales cm ON cm.id=m.categoria_id $w GROUP BY m.id ORDER BY cantidad_utilizada DESC LIMIT 8",$p); }
    public function getFallasMasFrecuentes(array $f=[]): array { [$w,$p]=$this->where($f); $sql="SELECT x.opcion,COUNT(*) total FROM (SELECT informe_id,opcion FROM informe_fallas_chaquetas UNION ALL SELECT informe_id,opcion FROM informe_fallas_enchufe UNION ALL SELECT informe_id,opcion FROM informe_lugares_falla UNION ALL SELECT informe_id,opcion FROM informe_causas_probables) x JOIN informes_cable i ON i.id=x.informe_id JOIN cables c ON c.id=i.cable_id $w GROUP BY x.opcion ORDER BY total DESC LIMIT 12"; return $this->fetchAll($sql,$p); }
    public function getCausasMasFrecuentes(array $f=[]): array { [$w,$p]=$this->where($f); return $this->fetchAll("SELECT cp.opcion,COUNT(*) total FROM informe_causas_probables cp JOIN informes_cable i ON i.id=cp.informe_id JOIN cables c ON c.id=i.cable_id $w GROUP BY cp.opcion ORDER BY total DESC LIMIT 12",$p); }
    public function getCablesMasReparados(): array { return $this->fetchAll("SELECT c.id,c.numero_cable,mc.nombre marca,c.calibre,c.estado,COUNT(i.id) total_informes,COALESCE(SUM(i.rep_ing_mufas_termo+i.rep_ing_mufa_union+i.rep_ing_chaquetas+i.rep_sal_mufas_termo+i.rep_sal_mufa_union+i.rep_sal_chaquetas),0) total_reparaciones,MAX(COALESCE(i.fecha_entrega_cable,i.fecha_recepcion_cable,DATE(i.created_at))) ultima_reparacion FROM cables c LEFT JOIN marcas_cable mc ON mc.id=c.marca_id LEFT JOIN informes_cable i ON i.cable_id=c.id WHERE c.deleted_at IS NULL GROUP BY c.id ORDER BY total_reparaciones DESC,total_informes DESC LIMIT 10"); }
    public function getUltimosInformes(): array { return $this->fetchAll("SELECT i.*,c.numero_cable,CONCAT(u.nombre,' ',u.apellido) supervisor FROM informes_cable i JOIN cables c ON c.id=i.cable_id LEFT JOIN usuarios u ON u.id=i.supervisor_id ORDER BY i.id DESC LIMIT 10"); }
    public function getEntregasPendientes(): array { return $this->fetchAll("SELECT e.id,e.fecha_entrega,CONCAT(u.nombre,' ',u.apellido) receptor,e.estado,COUNT(d.id) materiales,SUM(d.cantidad_entregada) entregada,SUM(d.cantidad_entregada-d.cantidad_disponible) usada,SUM(d.cantidad_disponible) pendiente FROM entregas_materiales e JOIN usuarios u ON u.id=e.usuario_receptor_id JOIN entrega_material_detalle d ON d.entrega_id=e.id WHERE e.estado='activa' GROUP BY e.id HAVING pendiente>0 ORDER BY e.fecha_entrega DESC LIMIT 10"); }
    public function getRendimientoSupervisores(): array { return $this->fetchAll("SELECT CONCAT(u.nombre,' ',u.apellido) supervisor,COUNT(i.id) informes_creados,SUM(i.estado_informe='finalizado') informes_finalizados,SUM(c.estado='entregado') cables_entregados,COALESCE(SUM(i.rep_ing_mufas_termo+i.rep_ing_mufa_union+i.rep_ing_chaquetas+i.rep_sal_mufas_termo+i.rep_sal_mufa_union+i.rep_sal_chaquetas),0) reparaciones,COALESCE(SUM(im.cantidad_utilizada),0) materiales_usados FROM usuarios u LEFT JOIN informes_cable i ON i.supervisor_id=u.id LEFT JOIN cables c ON c.id=i.cable_id LEFT JOIN informe_materiales im ON im.informe_id=i.id WHERE u.deleted_at IS NULL GROUP BY u.id ORDER BY informes_creados DESC LIMIT 10"); }
    public function getActividadReciente(): array { return $this->fetchAll("SELECT a.*,CONCAT(u.nombre,' ',u.apellido) usuario FROM audit_logs a LEFT JOIN usuarios u ON u.id=a.usuario_id ORDER BY a.fecha DESC LIMIT 12"); }
    public function getAlertasOperativas(): array { return [
        'rojas'=>$this->fetchAll("SELECT 'Material crítico' tipo,nombre_material descripcion FROM materiales WHERE deleted_at IS NULL AND stock_actual=0 LIMIT 5"),
        'amarillas'=>$this->fetchAll("SELECT 'Informe borrador' tipo,CONCAT('Informe #',id,' pendiente') descripcion FROM informes_cable WHERE estado_informe='borrador' LIMIT 5"),
        'verdes'=>$this->fetchAll("SELECT 'Finalizado hoy' tipo,CONCAT('Informe #',id,' finalizado') descripcion FROM informes_cable WHERE estado_informe='finalizado' AND DATE(updated_at)=CURDATE() LIMIT 5")
    ]; }
    public function getDashboardData(array $f=[]): array { $est=$this->getCablesPorEstado(); $by=[]; foreach($est as $e)$by[$e['estado']]=$e['total']; return ['kpis'=>['Total cables'=>$this->getTotalCables(),'Disponibles'=>$by['disponible']??0,'En reparación'=>$by['en reparación']??0,'Entregados'=>$by['entregado']??0,'Dados de baja'=>$by['dado de baja']??0,'Informes hoy'=>$this->getInformesHoy(),'Informes pendientes'=>$this->getInformesPendientes(),'Informes finalizados'=>$this->getInformesFinalizados($f),'Stock bajo'=>$this->getMaterialesStockBajo(),'Materiales usados mes'=>$this->sum("SELECT COALESCE(SUM(im.cantidad_utilizada),0) c FROM informe_materiales im JOIN informes_cable i ON i.id=im.informe_id WHERE MONTH(i.created_at)=MONTH(CURDATE()) AND YEAR(i.created_at)=YEAR(CURDATE())"),'Entregas activas'=>$this->scalar("SELECT COUNT(*) c FROM entregas_materiales WHERE estado='activa'"),'Reparaciones'=>$this->sum("SELECT COALESCE(SUM(rep_ing_mufas_termo+rep_ing_mufa_union+rep_ing_chaquetas+rep_sal_mufas_termo+rep_sal_mufa_union+rep_sal_chaquetas),0) c FROM informes_cable")],'cable_estados'=>$est,'informes_estado'=>$this->getInformesPorEstado($f),'fallas'=>$this->getFallasMasFrecuentes($f),'causas'=>$this->getCausasMasFrecuentes($f),'materiales_usados'=>$this->getMaterialesMasUsados($f),'cables_reparados'=>$this->getCablesMasReparados(),'ultimos_informes'=>$this->getUltimosInformes(),'stock_bajo'=>$this->getMaterialesCriticos(),'entregas_pendientes'=>$this->getEntregasPendientes(),'rendimiento'=>$this->getRendimientoSupervisores(),'actividad'=>$this->getActividadReciente(),'alertas'=>$this->getAlertasOperativas()]; }
    public function stats():array{return $this->getDashboardData();}
}
