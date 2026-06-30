-- Actualización para instalaciones existentes del módulo Informes.
-- Ejecutar una sola vez en la base de datos del hosting.
-- Compatible con MySQL/MariaDB aunque no soporte "ADD COLUMN IF NOT EXISTS".

DELIMITER //

CREATE PROCEDURE seim_add_column_if_missing(
    IN p_table_name VARCHAR(64),
    IN p_column_name VARCHAR(64),
    IN p_column_definition VARCHAR(255)
)
BEGIN
    IF NOT EXISTS (
        SELECT 1
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = p_table_name
          AND COLUMN_NAME = p_column_name
    ) THEN
        SET @seim_sql = CONCAT(
            'ALTER TABLE `', p_table_name, '` ADD COLUMN `',
            p_column_name, '` ', p_column_definition
        );
        PREPARE seim_stmt FROM @seim_sql;
        EXECUTE seim_stmt;
        DEALLOCATE PREPARE seim_stmt;
    END IF;
END//

DELIMITER ;

CREATE TABLE IF NOT EXISTS informes_cable(id INT AUTO_INCREMENT PRIMARY KEY,supervisor_id INT NULL,fecha_recepcion_cable DATE NULL,fecha_entrega_cable DATE NULL,cable_id INT NULL,origen_cable VARCHAR(150) NULL,recepcion_numero_cable VARCHAR(80) NULL,recepcion_calibre VARCHAR(80) NULL,recepcion_tipo_enchufe VARCHAR(120) NULL,recepcion_aislacion VARCHAR(120) NULL,recepcion_largo VARCHAR(80) NULL,recepcion_capacidad_aislacion VARCHAR(120) NULL,recepcion_marca_cable VARCHAR(100) NULL,estado_informe ENUM('borrador','finalizado','anulado') DEFAULT 'borrador',rep_ing_mufas_termo INT DEFAULT 0,rep_ing_mufa_union INT DEFAULT 0,rep_ing_chaquetas INT DEFAULT 0,rep_sal_mufas_termo INT DEFAULT 0,rep_sal_mufa_union INT DEFAULT 0,rep_sal_chaquetas INT DEFAULT 0,estado_operativo VARCHAR(40) NULL,destino_cable VARCHAR(120) NULL,tipo_enchufe_entrega VARCHAR(120) NULL,largo_entrega VARCHAR(80) NULL,marca_entrega VARCHAR(100) NULL,capacidad_aislacion_entrega VARCHAR(120) NULL,fallas_chaquetas LONGTEXT NULL,fallas_enchufe LONGTEXT NULL,lugares_falla LONGTEXT NULL,causas_probables LONGTEXT NULL,pruebas_continuidad LONGTEXT NULL,prueba_ez_thump LONGTEXT NULL,continuidad_final LONGTEXT NULL,vlf LONGTEXT NULL,pruebas_finales LONGTEXT NULL,observacion_final TEXT NULL,creado_por INT NULL,actualizado_por INT NULL,created_at TIMESTAMP NULL,updated_at TIMESTAMP NULL,deleted_at TIMESTAMP NULL);
CREATE TABLE IF NOT EXISTS informe_materiales(id INT AUTO_INCREMENT PRIMARY KEY,informe_id INT NOT NULL,material_id INT NOT NULL,cantidad_utilizada DECIMAL(12,2) NOT NULL);
CREATE TABLE IF NOT EXISTS informe_pruebas(id INT AUTO_INCREMENT PRIMARY KEY,informe_id INT NOT NULL,campo VARCHAR(80) NOT NULL,item VARCHAR(120) NOT NULL,realizada TINYINT(1) NOT NULL DEFAULT 0,con_falla TINYINT(1) NOT NULL DEFAULT 0,valor VARCHAR(80) NULL,unidad VARCHAR(40) NULL);
CREATE TABLE IF NOT EXISTS informe_datos(id INT AUTO_INCREMENT PRIMARY KEY,informe_id INT NOT NULL,campo VARCHAR(190) NOT NULL,valor LONGTEXT NULL);
CREATE TABLE IF NOT EXISTS informe_fallas_chaquetas(id INT AUTO_INCREMENT PRIMARY KEY,informe_id INT,opcion VARCHAR(120));
CREATE TABLE IF NOT EXISTS informe_fallas_enchufe(id INT AUTO_INCREMENT PRIMARY KEY,informe_id INT,opcion VARCHAR(120));
CREATE TABLE IF NOT EXISTS informe_lugares_falla(id INT AUTO_INCREMENT PRIMARY KEY,informe_id INT,opcion VARCHAR(120));
CREATE TABLE IF NOT EXISTS informe_causas_probables(id INT AUTO_INCREMENT PRIMARY KEY,informe_id INT,opcion VARCHAR(120));

CALL seim_add_column_if_missing('informes_cable', 'supervisor_id', 'INT NULL');
CALL seim_add_column_if_missing('informes_cable', 'fecha_recepcion_cable', 'DATE NULL');
CALL seim_add_column_if_missing('informes_cable', 'fecha_entrega_cable', 'DATE NULL');
CALL seim_add_column_if_missing('informes_cable', 'cable_id', 'INT NULL');
CALL seim_add_column_if_missing('informes_cable', 'origen_cable', 'VARCHAR(150) NULL');
CALL seim_add_column_if_missing('informes_cable', 'recepcion_numero_cable', 'VARCHAR(80) NULL');
CALL seim_add_column_if_missing('informes_cable', 'recepcion_calibre', 'VARCHAR(80) NULL');
CALL seim_add_column_if_missing('informes_cable', 'recepcion_tipo_enchufe', 'VARCHAR(120) NULL');
CALL seim_add_column_if_missing('informes_cable', 'recepcion_aislacion', 'VARCHAR(120) NULL');
CALL seim_add_column_if_missing('informes_cable', 'recepcion_largo', 'VARCHAR(80) NULL');
CALL seim_add_column_if_missing('informes_cable', 'recepcion_capacidad_aislacion', 'VARCHAR(120) NULL');
CALL seim_add_column_if_missing('informes_cable', 'recepcion_marca_cable', 'VARCHAR(100) NULL');
CALL seim_add_column_if_missing('informes_cable', 'estado_informe', 'ENUM(''borrador'',''finalizado'',''anulado'') DEFAULT ''borrador''');
CALL seim_add_column_if_missing('informes_cable', 'rep_ing_mufas_termo', 'INT DEFAULT 0');
CALL seim_add_column_if_missing('informes_cable', 'rep_ing_mufa_union', 'INT DEFAULT 0');
CALL seim_add_column_if_missing('informes_cable', 'rep_ing_chaquetas', 'INT DEFAULT 0');
CALL seim_add_column_if_missing('informes_cable', 'rep_sal_mufas_termo', 'INT DEFAULT 0');
CALL seim_add_column_if_missing('informes_cable', 'rep_sal_mufa_union', 'INT DEFAULT 0');
CALL seim_add_column_if_missing('informes_cable', 'rep_sal_chaquetas', 'INT DEFAULT 0');
CALL seim_add_column_if_missing('informes_cable', 'estado_operativo', 'VARCHAR(40) NULL');
CALL seim_add_column_if_missing('informes_cable', 'destino_cable', 'VARCHAR(120) NULL');
CALL seim_add_column_if_missing('informes_cable', 'tipo_enchufe_entrega', 'VARCHAR(120) NULL');
CALL seim_add_column_if_missing('informes_cable', 'largo_entrega', 'VARCHAR(80) NULL');
CALL seim_add_column_if_missing('informes_cable', 'marca_entrega', 'VARCHAR(100) NULL');
CALL seim_add_column_if_missing('informes_cable', 'capacidad_aislacion_entrega', 'VARCHAR(120) NULL');
CALL seim_add_column_if_missing('informes_cable', 'fallas_chaquetas', 'LONGTEXT NULL');
CALL seim_add_column_if_missing('informes_cable', 'fallas_enchufe', 'LONGTEXT NULL');
CALL seim_add_column_if_missing('informes_cable', 'lugares_falla', 'LONGTEXT NULL');
CALL seim_add_column_if_missing('informes_cable', 'causas_probables', 'LONGTEXT NULL');
CALL seim_add_column_if_missing('informes_cable', 'pruebas_continuidad', 'LONGTEXT NULL');
CALL seim_add_column_if_missing('informes_cable', 'prueba_ez_thump', 'LONGTEXT NULL');
CALL seim_add_column_if_missing('informes_cable', 'continuidad_final', 'LONGTEXT NULL');
CALL seim_add_column_if_missing('informes_cable', 'vlf', 'LONGTEXT NULL');
CALL seim_add_column_if_missing('informes_cable', 'pruebas_finales', 'LONGTEXT NULL');
CALL seim_add_column_if_missing('informes_cable', 'observacion_final', 'TEXT NULL');
CALL seim_add_column_if_missing('informes_cable', 'creado_por', 'INT NULL');
CALL seim_add_column_if_missing('informes_cable', 'actualizado_por', 'INT NULL');
CALL seim_add_column_if_missing('informes_cable', 'created_at', 'TIMESTAMP NULL');
CALL seim_add_column_if_missing('informes_cable', 'updated_at', 'TIMESTAMP NULL');
CALL seim_add_column_if_missing('informes_cable', 'deleted_at', 'TIMESTAMP NULL');
CALL seim_add_column_if_missing('informe_materiales', 'informe_id', 'INT NULL');
CALL seim_add_column_if_missing('informe_materiales', 'material_id', 'INT NULL');
CALL seim_add_column_if_missing('informe_materiales', 'cantidad_utilizada', 'DECIMAL(12,2) NOT NULL DEFAULT 0');
CALL seim_add_column_if_missing('informe_fallas_chaquetas', 'informe_id', 'INT NULL');
CALL seim_add_column_if_missing('informe_fallas_chaquetas', 'opcion', 'VARCHAR(120) NULL');
CALL seim_add_column_if_missing('informe_fallas_enchufe', 'informe_id', 'INT NULL');
CALL seim_add_column_if_missing('informe_fallas_enchufe', 'opcion', 'VARCHAR(120) NULL');
CALL seim_add_column_if_missing('informe_lugares_falla', 'informe_id', 'INT NULL');
CALL seim_add_column_if_missing('informe_lugares_falla', 'opcion', 'VARCHAR(120) NULL');
CALL seim_add_column_if_missing('informe_causas_probables', 'informe_id', 'INT NULL');
CALL seim_add_column_if_missing('informe_causas_probables', 'opcion', 'VARCHAR(120) NULL');
CALL seim_add_column_if_missing('informe_pruebas', 'informe_id', 'INT NULL');
CALL seim_add_column_if_missing('informe_pruebas', 'campo', 'VARCHAR(80) NULL');
CALL seim_add_column_if_missing('informe_pruebas', 'item', 'VARCHAR(120) NULL');
CALL seim_add_column_if_missing('informe_pruebas', 'realizada', 'TINYINT(1) NOT NULL DEFAULT 0');
CALL seim_add_column_if_missing('informe_pruebas', 'con_falla', 'TINYINT(1) NOT NULL DEFAULT 0');
CALL seim_add_column_if_missing('informe_pruebas', 'valor', 'VARCHAR(80) NULL');
CALL seim_add_column_if_missing('informe_pruebas', 'unidad', 'VARCHAR(40) NULL');
CALL seim_add_column_if_missing('informe_datos', 'informe_id', 'INT NULL');
CALL seim_add_column_if_missing('informe_datos', 'campo', 'VARCHAR(190) NULL');
CALL seim_add_column_if_missing('informe_datos', 'valor', 'LONGTEXT NULL');
CALL seim_add_column_if_missing('informe_materiales', 'entrega_detalle_id', 'INT NULL');
CALL seim_add_column_if_missing('informe_materiales', 'stock_usuario_antes', 'DECIMAL(12,2) NULL');
CALL seim_add_column_if_missing('informe_materiales', 'stock_usuario_despues', 'DECIMAL(12,2) NULL');

CREATE TABLE IF NOT EXISTS informe_fallas_chaquetas(id INT AUTO_INCREMENT PRIMARY KEY,informe_id INT,opcion VARCHAR(120));
CREATE TABLE IF NOT EXISTS informe_fallas_enchufe(id INT AUTO_INCREMENT PRIMARY KEY,informe_id INT,opcion VARCHAR(120));
CREATE TABLE IF NOT EXISTS informe_lugares_falla(id INT AUTO_INCREMENT PRIMARY KEY,informe_id INT,opcion VARCHAR(120));
CREATE TABLE IF NOT EXISTS informe_causas_probables(id INT AUTO_INCREMENT PRIMARY KEY,informe_id INT,opcion VARCHAR(120));

DROP PROCEDURE seim_add_column_if_missing;
