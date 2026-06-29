-- Actualización para instalaciones existentes del módulo Informes.
-- Ejecutar una sola vez si no se permite que la app cree columnas automáticamente.
ALTER TABLE informes_cable
  ADD COLUMN IF NOT EXISTS pruebas_continuidad JSON NULL,
  ADD COLUMN IF NOT EXISTS prueba_ez_thump JSON NULL,
  ADD COLUMN IF NOT EXISTS continuidad_final JSON NULL,
  ADD COLUMN IF NOT EXISTS vlf JSON NULL,
  ADD COLUMN IF NOT EXISTS pruebas_finales JSON NULL,
  ADD COLUMN IF NOT EXISTS deleted_at TIMESTAMP NULL;
