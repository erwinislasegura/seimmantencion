# Sistema de Mantención de Cables Mineros

Sistema web MVC en PHP 8.1+ para SEIM / JC Servicios, orientado a mantención de cables mineros, inventario de materiales, entregas, recepciones, informes diarios, reportes y auditoría.

## Requisitos
- PHP 8.1 o superior con extensiones `pdo_mysql`, `fileinfo` y `mbstring`.
- MySQL 8+ o MariaDB 10.4+.
- Apache con `mod_rewrite` habilitado.
- XAMPP, WAMP, Laragon o hosting cPanel compatible.

## Instalación paso a paso
1. Copie el proyecto en su servidor, por ejemplo `htdocs/seimmantencion`.
2. Cree la base de datos importando `database/schema.sql` desde phpMyAdmin o consola:
   ```bash
   mysql -u root -p < database/schema.sql
   ```
3. Configure conexión en `app/Config/config.php` o variables de entorno:
   - `APP_URL=http://localhost/seimmantencion/public`
   - `DB_HOST=127.0.0.1`
   - `DB_NAME=seim_mantencion`
   - `DB_USER=root`
   - `DB_PASS=`
4. Verifique que Apache permita `.htaccess` (`AllowOverride All`).
5. Dé permisos de escritura a:
   - `public/uploads/materiales`
   - `public/uploads/cables`
6. Ingrese a `/login`.

## Usuario inicial
- Usuario: `admin`
- Contraseña: `Admin123*`

## Estructura MVC
- `app/Core`: router, controlador base, modelo base, helpers y conexión PDO.
- `app/Controllers`: controladores de autenticación, dashboard, catálogos, movimientos, informes y reportes.
- `app/Models`: modelos con consultas PDO y prepared statements.
- `app/Views`: vistas Bootstrap 5 con layout oscuro industrial.
- `public`: front controller, assets, uploads y `.htaccess`.
- `database/schema.sql`: migración completa con tablas, claves foráneas, índices y datos iniciales.

## Seguridad implementada
- Passwords con `password_hash` / `password_verify`.
- PDO con prepared statements.
- Token CSRF en formularios POST.
- Sesiones regeneradas al iniciar sesión.
- Middleware de permisos por módulo/acción.
- Validación de imágenes JPG/PNG/WEBP y máximo 3 MB.
- Sanitización de salida con `htmlspecialchars`.
- Soft delete disponible en catálogos principales mediante `deleted_at`.

## Rutas principales
- `/login`, `/logout`, `/dashboard`
- `/usuarios`, `/roles`, `/materiales`, `/entregas-materiales`, `/recepciones-materiales`
- `/cables`, `/cables/historial/{id}`
- `/informes-cable`, `/informes-cable/imprimir/{id}`
- `/marcas-cable`, `/reportes`

## Notas
La aplicación usa Bootstrap 5, DataTables, Chart.js, HTML/CSS/JS vanilla y PHP puro sin Laravel ni frameworks pesados. La interfaz utiliza una paleta industrial oscura basada en SEIM Energía.
