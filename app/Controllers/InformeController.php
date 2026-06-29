<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Models\BaseCatalog;
use App\Models\InformeModel;

class InformeController extends Controller
{
    public function index(): void
    {
        $this->requirePermission('Informes de cable');
        $rows = (new InformeModel)->list();
        $this->view('informes/index', compact('rows'));
    }

    public function form($id = null): void
    {
        $this->requirePermission('Informes de cable', $id ? 'editar' : 'crear');
        $m = new BaseCatalog;
        $inf = new InformeModel;
        $item = $id ? $m->find('informes_cable', (int)$id) : null;
        if ($id && !$item) {
            http_response_code(404);
            exit('Informe no encontrado');
        }
        $cables = $m->fetchAll('SELECT c.*, mc.nombre marca FROM cables c LEFT JOIN marcas_cable mc ON mc.id=c.marca_id WHERE c.deleted_at IS NULL');
        $supervisores = $m->all('usuarios', 'nombre');
        $origenes = $m->all('origenes_cable', 'nombre');
        $materialesUsuario = $m->fetchAll('SELECT d.id detalle_id,e.usuario_receptor_id,CONCAT(u.nombre," ",u.apellido) usuario,m.id material_id,m.codigo_interno,m.nombre_material,m.unidad_medida,d.cantidad_disponible FROM entrega_material_detalle d JOIN entregas_materiales e ON e.id=d.entrega_id JOIN usuarios u ON u.id=e.usuario_receptor_id JOIN materiales m ON m.id=d.material_id WHERE e.estado="activa" AND d.cantidad_disponible>0 ORDER BY u.nombre,u.apellido,m.nombre_material');
        $this->view('informes/form', compact('item', 'cables', 'supervisores', 'origenes', 'materialesUsuario', 'inf'));
    }

    public function save($routeId = null): void
    {
        verify_csrf();
        $id = $this->resolveInformeId($routeId);
        if ($id !== null) {
            $_POST['id'] = (string)$id;
        }
        $this->requirePermission('Informes de cable', $id ? 'editar' : 'crear');

        $m = new BaseCatalog;
        $db = \App\Core\App::db();
        $db->beginTransaction();
        try {
            $jsonFields = ['fallas_chaquetas', 'fallas_enchufe', 'lugares_falla', 'causas_probables'];
            foreach ($jsonFields as $f) {
                $_POST[$f] = json_encode($_POST[$f] ?? [], JSON_UNESCAPED_UNICODE);
            }

            $cols = ['supervisor_id', 'fecha_recepcion_cable', 'fecha_entrega_cable', 'cable_id', 'origen_cable', 'estado_informe', 'rep_ing_mufas_termo', 'rep_ing_mufa_union', 'rep_ing_chaquetas', 'rep_sal_mufas_termo', 'rep_sal_mufa_union', 'rep_sal_chaquetas', 'estado_operativo', 'destino_cable', 'tipo_enchufe_entrega', 'largo_entrega', 'marca_entrega', 'capacidad_aislacion_entrega', 'fallas_chaquetas', 'fallas_enchufe', 'lugares_falla', 'causas_probables', 'observacion_final'];
            $p = array_map(fn($c) => $_POST[$c] ?? null, $cols);

            if ($id !== null) {
                if (!$m->find('informes_cable', $id)) {
                    throw new \RuntimeException('El informe que intenta editar no existe.');
                }
                $anteriores = $m->fetchAll('SELECT entrega_detalle_id,cantidad_utilizada FROM informe_materiales WHERE informe_id=? AND entrega_detalle_id IS NOT NULL', [$id]);
                foreach ($anteriores as $a) {
                    $m->execSql('UPDATE entrega_material_detalle SET cantidad_disponible=cantidad_disponible+? WHERE id=?', [$a['cantidad_utilizada'], $a['entrega_detalle_id']]);
                    $m->execSql('UPDATE entregas_materiales e JOIN entrega_material_detalle d ON d.entrega_id=e.id SET e.estado=IF((SELECT COALESCE(SUM(x.cantidad_disponible),0) FROM entrega_material_detalle x WHERE x.entrega_id=e.id)<=0,"cerrada","activa"),e.updated_at=NOW() WHERE d.id=?', [$a['entrega_detalle_id']]);
                }
                $m->execSql('DELETE FROM informe_materiales WHERE informe_id=?', [$id]);
                $set = implode('=?,', $cols) . '=?';
                $p[] = current_user()['id'];
                $p[] = $id;
                $m->execSql("UPDATE informes_cable SET $set, actualizado_por=?, updated_at=NOW() WHERE id=?", $p);
            } else {
                $p[] = current_user()['id'];
                $p[] = current_user()['id'];
                $q = rtrim(str_repeat('?,', count($p)), ',');
                $m->execSql('INSERT INTO informes_cable(' . implode(',', $cols) . ',creado_por,actualizado_por,created_at,updated_at) VALUES(' . $q . ',NOW(),NOW())', $p);
                $id = (int)$db->lastInsertId();
            }

            $this->guardarMaterialesUsados($m, $id);
            $m->audit('Guardar', 'Informes de cable', $id, 'Informe guardado');
            $db->commit();
            flash('success', 'Informe guardado.');
            redirect('informes-cable');
        } catch (\Throwable $e) {
            $db->rollBack();
            flash('error', $e->getMessage());
            redirect($id ? 'informes-cable/editar/' . $id : 'informes-cable/crear');
        }
    }

    private function resolveInformeId($routeId = null): ?int
    {
        $candidate = $routeId ?: ($_POST['id'] ?? null);
        if (!$candidate && !empty($_SERVER['HTTP_REFERER'])) {
            $path = parse_url($_SERVER['HTTP_REFERER'], PHP_URL_PATH) ?: '';
            if (preg_match('#/informes-cable/editar/(\d+)$#', $path, $matches)) {
                $candidate = $matches[1];
            }
        }
        $id = filter_var($candidate, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        return $id === false ? null : $id;
    }

    private function guardarMaterialesUsados(BaseCatalog $m, int $informeId): void
    {
        foreach ($_POST['material_detalle_id'] ?? [] as $i => $detalleId) {
            $detalleId = (int)$detalleId;
            $cantidad = (float)($_POST['material_cantidad'][$i] ?? 0);
            if ($detalleId <= 0 || $cantidad <= 0) continue;
            $det = $m->fetch('SELECT d.*,m.id material_id FROM entrega_material_detalle d JOIN materiales m ON m.id=d.material_id WHERE d.id=? FOR UPDATE', [$detalleId]);
            if (!$det) throw new \RuntimeException('Material entregado no encontrado.');
            if ($cantidad > (float)$det['cantidad_disponible']) throw new \RuntimeException('La cantidad usada supera lo disponible para el usuario.');
            $antes = (float)$det['cantidad_disponible'];
            $despues = $antes - $cantidad;
            $m->execSql('UPDATE entrega_material_detalle SET cantidad_disponible=? WHERE id=?', [$despues, $detalleId]);
            $m->execSql('INSERT INTO informe_materiales(informe_id,material_id,entrega_detalle_id,cantidad_utilizada,stock_usuario_antes,stock_usuario_despues) VALUES(?,?,?,?,?,?)', [$informeId, $det['material_id'], $detalleId, $cantidad, $antes, $despues]);
            $m->execSql('UPDATE entregas_materiales e SET estado=IF((SELECT COALESCE(SUM(cantidad_disponible),0) FROM entrega_material_detalle WHERE entrega_id=e.id)<=0,"cerrada","activa"),updated_at=NOW() WHERE e.id=?', [$det['entrega_id']]);
        }
    }

    public function print($id): void
    {
        $this->requirePermission('Informes de cable', 'imprimir');
        $m = new BaseCatalog;
        $item = $m->fetch('SELECT i.*, c.numero_cable FROM informes_cable i JOIN cables c ON c.id=i.cable_id WHERE i.id=?', [$id]);
        if (!$item) {
            http_response_code(404);
            exit('Informe no encontrado');
        }
        $this->view('informes/print', compact('item'));
    }
}
