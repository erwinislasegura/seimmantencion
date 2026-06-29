<?php
// Punto de entrada raíz para instalaciones donde el DocumentRoot apunta al
// directorio del proyecto en vez de /public. Mantiene el front controller real
// en public/index.php y evita el 404 al abrir la raíz del proyecto.
require __DIR__ . '/public/index.php';
