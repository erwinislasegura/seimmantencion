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

CALL seim_add_column_if_missing('informes_cable', 'pruebas_continuidad', 'JSON NULL');
CALL seim_add_column_if_missing('informes_cable', 'prueba_ez_thump', 'JSON NULL');
CALL seim_add_column_if_missing('informes_cable', 'continuidad_final', 'JSON NULL');
CALL seim_add_column_if_missing('informes_cable', 'vlf', 'JSON NULL');
CALL seim_add_column_if_missing('informes_cable', 'pruebas_finales', 'JSON NULL');
CALL seim_add_column_if_missing('informes_cable', 'deleted_at', 'TIMESTAMP NULL');
CALL seim_add_column_if_missing('informe_materiales', 'entrega_detalle_id', 'INT NULL');
CALL seim_add_column_if_missing('informe_materiales', 'stock_usuario_antes', 'DECIMAL(12,2) NULL');
CALL seim_add_column_if_missing('informe_materiales', 'stock_usuario_despues', 'DECIMAL(12,2) NULL');

DROP PROCEDURE seim_add_column_if_missing;
