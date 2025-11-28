<?php
// Ejecuta el script que está fuera de public_html
require __DIR__ . 'registrar_visita.php';

// Devuelve respuesta vacía para el navegador
http_response_code(204); // "No Content"
exit;