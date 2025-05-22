<?php
date_default_timezone_set('America/Argentina/Buenos_Aires');

// Autenticación
$usuario_valido = "admin";
$contrasena_valida = "1234";

if (!isset($_SERVER['PHP_AUTH_USER']) || !isset($_SERVER['PHP_AUTH_PW']) ||
    $_SERVER['PHP_AUTH_USER'] !== $usuario_valido || $_SERVER['PHP_AUTH_PW'] !== $contrasena_valida) {
    header('WWW-Authenticate: Basic realm="Zona protegida"');
    header('HTTP/1.0 401 Unauthorized');
    echo 'Acceso denegado.';
    exit;
}

// Leer archivo de visitas
$archivo = "visitas.log";
$visitas = [];

if (file_exists($archivo)) {
    $lineas = file($archivo, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    foreach ($lineas as $linea) {
        $partes = array_map('trim', explode('|', $linea));
        if (count($partes) >= 2) {
            $fecha = $partes[0];
            $ip = $partes[1];
            $pais = $partes[2] ?? "";
            $region = $partes[3] ?? "";
            $ciudad = $partes[4] ?? "";

            $visitas[] = [
                "fecha" => $fecha,
                "ip" => $ip,
                "pais" => $pais,
                "region" => $region,
                "ciudad" => $ciudad,
            ];
        }
    }

    // Ordenar por fecha descendente (último primero)
    usort($visitas, function($a, $b) {
        $formato = 'd/m/Y H:i:s';
        $fechaA = DateTime::createFromFormat($formato, $a['fecha']);
        $fechaB = DateTime::createFromFormat($formato, $b['fecha']);
        return $fechaB <=> $fechaA;
    });
} else {
    echo "El archivo de visitas no existe.";
    exit();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8" />
<title>Visitas al Sitio</title>
<style>
  body { font-family: Arial, sans-serif; margin: 40px; background: #f7f7f7; }
  table { border-collapse: collapse; width: 100%; background: #fff; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
  th, td { padding: 10px; border: 1px solid #ddd; text-align: left; }
  th { background-color: #f2f2f2; }
  h1 { margin-bottom: 20px; }
</style>
</head>
<body>
  <h1>Visitas al Sitio</h1>
  <table>
    <thead>
      <tr>
        <th>Fecha</th>
        <th>IP</th>
        <th>País</th>
        <th>Región</th>
        <th>Ciudad</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($visitas as $v): ?>
      <tr>
        <td><?= htmlspecialchars($v["fecha"]) ?></td>
        <td><?= htmlspecialchars($v["ip"]) ?></td>
        <td><?= htmlspecialchars($v["pais"]) ?></td>
        <td><?= htmlspecialchars($v["region"]) ?></td>
        <td><?= htmlspecialchars($v["ciudad"]) ?></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</body>
</html>
