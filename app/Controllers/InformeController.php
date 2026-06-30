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
        $base = new BaseCatalog;
        $this->ensureInformeSchema($base);
        $rows = (new InformeModel)->list();
        $this->view('informes/index', compact('rows'));
    }

    public function form($id = null): void
    {
        $this->requirePermission('Informes de cable', $id ? 'editar' : 'crear');
        $m = new BaseCatalog;
        $this->ensureInformeSchema($m);
        $inf = new InformeModel;
        $item = $id ? $m->find('informes_cable', (int)$id) : null;
        if ($id && !$item) {
            http_response_code(404);
            exit('Informe no encontrado');
        }
        $cables = $m->fetchAll('SELECT c.*, mc.nombre marca FROM cables c LEFT JOIN marcas_cable mc ON mc.id=c.marca_id WHERE c.deleted_at IS NULL');
        $supervisores = $m->all('usuarios', 'nombre');
        $origenes = $m->all('origenes_cable', 'nombre');
        $informeId = $item ? (int)$item['id'] : 0;
        $materialesUsuario = $m->fetchAll('SELECT d.id detalle_id,e.usuario_receptor_id,CONCAT(u.nombre," ",u.apellido) usuario,m.id material_id,m.codigo_interno,m.nombre_material,m.unidad_medida,(d.cantidad_disponible+COALESCE(im.cantidad_utilizada,0)) cantidad_disponible,COALESCE(im.cantidad_utilizada,0) cantidad_utilizada FROM entrega_material_detalle d JOIN entregas_materiales e ON e.id=d.entrega_id JOIN usuarios u ON u.id=e.usuario_receptor_id JOIN materiales m ON m.id=d.material_id LEFT JOIN informe_materiales im ON im.entrega_detalle_id=d.id AND im.informe_id=? WHERE (e.estado="activa" AND d.cantidad_disponible>0) OR im.id IS NOT NULL ORDER BY u.nombre,u.apellido,m.nombre_material', [$informeId]);
        $materialesInforme = array_values(array_filter($materialesUsuario, fn($material) => (float)($material['cantidad_utilizada'] ?? 0) > 0));
        $this->view('informes/form', compact('item', 'cables', 'supervisores', 'origenes', 'materialesUsuario', 'materialesInforme', 'inf'));
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
        $this->ensureInformeSchema($m);
        $db = \App\Core\App::db();
        $db->beginTransaction();
        try {
            $this->sanitizeRevisionCards();
            $jsonFields = ['fallas_chaquetas', 'fallas_enchufe', 'lugares_falla', 'causas_probables', 'pruebas_continuidad', 'prueba_ez_thump', 'continuidad_final', 'vlf', 'pruebas_finales'];
            foreach ($jsonFields as $f) {
                $_POST[$f] = $_POST[$f] ?? [];
                $_POST[$f . '_raw'] = $_POST[$f];
            }
            $datosInforme = $this->buildInformeDataPayload($m);
            foreach ($jsonFields as $f) {
                $_POST[$f] = json_encode($_POST[$f], JSON_UNESCAPED_UNICODE);
            }

            $cols = ['supervisor_id', 'fecha_recepcion_cable', 'fecha_entrega_cable', 'cable_id', 'origen_cable', 'estado_informe', 'rep_ing_mufas_termo', 'rep_ing_mufa_union', 'rep_ing_chaquetas', 'rep_sal_mufas_termo', 'rep_sal_mufa_union', 'rep_sal_chaquetas', 'estado_operativo', 'destino_cable', 'tipo_enchufe_entrega', 'largo_entrega', 'marca_entrega', 'capacidad_aislacion_entrega', 'fallas_chaquetas', 'fallas_enchufe', 'lugares_falla', 'causas_probables', 'pruebas_continuidad', 'prueba_ez_thump', 'continuidad_final', 'vlf', 'pruebas_finales', 'observacion_final'];
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

            $this->guardarDatosInforme($m, $id, $datosInforme);
            $this->guardarOpcionesInforme($m, $id);
            $this->guardarPruebasInforme($m, $id);
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

    private function sanitizeRevisionCards(): void
    {
        foreach (['pruebas_continuidad', 'prueba_ez_thump', 'continuidad_final', 'vlf', 'pruebas_finales'] as $field) {
            if (empty($_POST[$field]) || !is_array($_POST[$field])) {
                $_POST[$field] = [];
                continue;
            }
            foreach ($_POST[$field] as $item => $values) {
                if (!is_array($values)) {
                    unset($_POST[$field][$item]);
                    continue;
                }
                $isOn = !empty($values['realizada']);
                $_POST[$field][$item]['realizada'] = $isOn;
                $_POST[$field][$item]['con_falla'] = $isOn && !empty($values['con_falla']);
            }
        }
    }

    private function ensureInformeSchema(BaseCatalog $m): void
    {
        $this->ensureInformeTables($m);
        $this->ensureColumns($m, 'informes_cable', [
            'supervisor_id' => 'INT NULL',
            'fecha_recepcion_cable' => 'DATE NULL',
            'fecha_entrega_cable' => 'DATE NULL',
            'origen_cable' => 'VARCHAR(150) NULL',
            'estado_informe' => "ENUM('borrador','finalizado','anulado') DEFAULT 'borrador'",
            'rep_ing_mufas_termo' => 'INT DEFAULT 0',
            'rep_ing_mufa_union' => 'INT DEFAULT 0',
            'rep_ing_chaquetas' => 'INT DEFAULT 0',
            'rep_sal_mufas_termo' => 'INT DEFAULT 0',
            'rep_sal_mufa_union' => 'INT DEFAULT 0',
            'rep_sal_chaquetas' => 'INT DEFAULT 0',
            'estado_operativo' => 'VARCHAR(40) NULL',
            'destino_cable' => 'VARCHAR(120) NULL',
            'tipo_enchufe_entrega' => 'VARCHAR(120) NULL',
            'largo_entrega' => 'VARCHAR(80) NULL',
            'marca_entrega' => 'VARCHAR(100) NULL',
            'capacidad_aislacion_entrega' => 'VARCHAR(120) NULL',
            'fallas_chaquetas' => 'LONGTEXT NULL',
            'fallas_enchufe' => 'LONGTEXT NULL',
            'lugares_falla' => 'LONGTEXT NULL',
            'causas_probables' => 'LONGTEXT NULL',
            'pruebas_continuidad' => 'LONGTEXT NULL',
            'prueba_ez_thump' => 'LONGTEXT NULL',
            'continuidad_final' => 'LONGTEXT NULL',
            'vlf' => 'LONGTEXT NULL',
            'pruebas_finales' => 'LONGTEXT NULL',
            'observacion_final' => 'TEXT NULL',
            'creado_por' => 'INT NULL',
            'actualizado_por' => 'INT NULL',
            'created_at' => 'TIMESTAMP NULL',
            'updated_at' => 'TIMESTAMP NULL',
            'deleted_at' => 'TIMESTAMP NULL',
        ]);
        $this->ensureColumns($m, 'informe_materiales', [
            'informe_id' => 'INT NULL',
            'material_id' => 'INT NULL',
            'entrega_detalle_id' => 'INT NULL',
            'cantidad_utilizada' => 'DECIMAL(12,2) NOT NULL DEFAULT 0',
            'stock_usuario_antes' => 'DECIMAL(12,2) NULL',
            'stock_usuario_despues' => 'DECIMAL(12,2) NULL',
        ]);
        foreach (['informe_fallas_chaquetas', 'informe_fallas_enchufe', 'informe_lugares_falla', 'informe_causas_probables'] as $table) {
            $this->ensureColumns($m, $table, [
                'informe_id' => 'INT NULL',
                'opcion' => 'VARCHAR(120) NULL',
            ]);
        }
        $this->ensureColumns($m, 'informe_pruebas', [
            'informe_id' => 'INT NULL',
            'campo' => 'VARCHAR(80) NULL',
            'item' => 'VARCHAR(120) NULL',
            'realizada' => 'TINYINT(1) NOT NULL DEFAULT 0',
            'con_falla' => 'TINYINT(1) NOT NULL DEFAULT 0',
            'valor' => 'VARCHAR(80) NULL',
            'unidad' => 'VARCHAR(40) NULL',
        ]);
        $this->ensureColumns($m, 'informe_datos', [
            'informe_id' => 'INT NULL',
            'campo' => 'VARCHAR(190) NULL',
            'valor' => 'LONGTEXT NULL',
        ]);
    }


    private function ensureInformeTables(BaseCatalog $m): void
    {
        foreach (['informe_fallas_chaquetas', 'informe_fallas_enchufe', 'informe_lugares_falla', 'informe_causas_probables'] as $table) {
            $m->execSql("CREATE TABLE IF NOT EXISTS `$table`(id INT AUTO_INCREMENT PRIMARY KEY,informe_id INT,opcion VARCHAR(120))");
        }
        $m->execSql('CREATE TABLE IF NOT EXISTS informe_materiales(id INT AUTO_INCREMENT PRIMARY KEY,informe_id INT NOT NULL,material_id INT NOT NULL,cantidad_utilizada DECIMAL(12,2) NOT NULL)');
        $m->execSql('CREATE TABLE IF NOT EXISTS informe_pruebas(id INT AUTO_INCREMENT PRIMARY KEY,informe_id INT NOT NULL,campo VARCHAR(80) NOT NULL,item VARCHAR(120) NOT NULL,realizada TINYINT(1) NOT NULL DEFAULT 0,con_falla TINYINT(1) NOT NULL DEFAULT 0,valor VARCHAR(80) NULL,unidad VARCHAR(40) NULL)');
        $m->execSql('CREATE TABLE IF NOT EXISTS informe_datos(id INT AUTO_INCREMENT PRIMARY KEY,informe_id INT NOT NULL,campo VARCHAR(190) NOT NULL,valor LONGTEXT NULL)');
    }

    private function ensureColumns(BaseCatalog $m, string $table, array $columns): void
    {
        $tableExists = $m->fetch('SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?', [$table]);
        if (!$tableExists) {
            throw new \RuntimeException("La tabla requerida `$table` no existe en la base de datos.");
        }
        foreach ($columns as $column => $definition) {
            $exists = $m->fetch("SHOW COLUMNS FROM `$table` LIKE ?", [$column]);
            if (!$exists) {
                $m->execSql("ALTER TABLE `$table` ADD COLUMN `$column` $definition");
            }
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



    private function buildInformeDataPayload(BaseCatalog $m): array
    {
        $payload = $_POST;
        unset($payload['_csrf'], $payload['id']);

        $cableId = filter_var($payload['cable_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($cableId !== false) {
            $cable = $m->fetch('SELECT c.numero_cable,c.calibre,c.tipo_enchufe,c.aislacion,c.largo,c.capacidad_aislacion,mc.nombre marca FROM cables c LEFT JOIN marcas_cable mc ON mc.id=c.marca_id WHERE c.id=?', [$cableId]);
            if ($cable) {
                foreach ($cable as $field => $value) {
                    if (is_string($field)) {
                        $payload['recepcion_cable'][$field] = $value;
                    }
                }
            }
        }

        $rows = [];
        $this->flattenInformeData($payload, '', $rows);
        return $rows;
    }

    private function flattenInformeData(array $data, string $prefix, array &$rows): void
    {
        foreach ($data as $key => $value) {
            $path = $prefix === '' ? (string)$key : $prefix . '.' . (string)$key;
            if (is_array($value)) {
                $this->flattenInformeData($value, $path, $rows);
                if ($value === []) {
                    $rows[$path] = '';
                }
                continue;
            }
            $rows[$path] = $this->informeScalarToString($value);
        }
    }


    private function informeScalarToString($value): string
    {
        if (is_bool($value)) {
            return $value ? '1' : '0';
        }
        if ($value === null) {
            return '';
        }
        return (string)$value;
    }

    private function guardarDatosInforme(BaseCatalog $m, int $informeId, array $datos): void
    {
        $m->execSql('DELETE FROM informe_datos WHERE informe_id=?', [$informeId]);
        foreach ($datos as $campo => $valor) {
            $campo = substr((string)$campo, 0, 190);
            $m->execSql('INSERT INTO informe_datos(informe_id,campo,valor) VALUES(?,?,?)', [$informeId, $campo, (string)$valor]);
        }
    }

    private function guardarOpcionesInforme(BaseCatalog $m, int $informeId): void
    {
        $map = [
            'fallas_chaquetas' => 'informe_fallas_chaquetas',
            'fallas_enchufe' => 'informe_fallas_enchufe',
            'lugares_falla' => 'informe_lugares_falla',
            'causas_probables' => 'informe_causas_probables',
        ];
        foreach ($map as $field => $table) {
            $m->execSql("DELETE FROM `$table` WHERE informe_id=?", [$informeId]);
            foreach ($_POST[$field . '_raw'] ?? [] as $opcion) {
                $opcion = trim((string)$opcion);
                if ($opcion === '') continue;
                $m->execSql("INSERT INTO `$table`(informe_id,opcion) VALUES(?,?)", [$informeId, $opcion]);
            }
        }
    }


    private function guardarPruebasInforme(BaseCatalog $m, int $informeId): void
    {
        $fields = ['pruebas_continuidad', 'prueba_ez_thump', 'continuidad_final', 'vlf', 'pruebas_finales'];
        $m->execSql('DELETE FROM informe_pruebas WHERE informe_id=?', [$informeId]);
        foreach ($fields as $field) {
            foreach ($_POST[$field . '_raw'] ?? [] as $item => $values) {
                if (!is_array($values)) continue;
                $item = trim((string)$item);
                if ($item === '') continue;
                $realizada = !empty($values['realizada']) ? 1 : 0;
                $conFalla = $realizada && !empty($values['con_falla']) ? 1 : 0;
                $valor = isset($values['valor']) ? trim((string)$values['valor']) : null;
                $unidad = isset($values['unidad']) ? trim((string)$values['unidad']) : null;
                $m->execSql(
                    'INSERT INTO informe_pruebas(informe_id,campo,item,realizada,con_falla,valor,unidad) VALUES(?,?,?,?,?,?,?)',
                    [$informeId, $field, $item, $realizada, $conFalla, $valor !== '' ? $valor : null, $unidad !== '' ? $unidad : null]
                );
            }
        }
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

    public function delete($id): void
    {
        verify_csrf();
        $this->requirePermission('Informes de cable', 'eliminar');
        $m = new BaseCatalog;
        $this->ensureInformeSchema($m);
        try {
            if (!$m->find('informes_cable', (int)$id)) {
                throw new \RuntimeException('Informe no encontrado.');
            }
            $m->softDelete('informes_cable', (int)$id);
            $m->audit('Eliminar', 'Informes de cable', (int)$id, 'Informe eliminado');
            flash('success', 'Informe eliminado.');
        } catch (\Throwable $e) {
            flash('error', 'No se pudo eliminar el informe: ' . $e->getMessage());
        }
        redirect('informes-cable');
    }

    public function print($id): void
    {
        $this->requirePermission('Informes de cable', 'imprimir');
        $m = new BaseCatalog;
        $this->ensureInformeSchema($m);
        $item = $m->fetch('SELECT i.*, c.numero_cable FROM informes_cable i JOIN cables c ON c.id=i.cable_id WHERE i.id=?', [$id]);
        if (!$item) {
            http_response_code(404);
            exit('Informe no encontrado');
        }
        $this->view('informes/print', compact('item'));
    }
}
