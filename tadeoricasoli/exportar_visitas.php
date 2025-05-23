<?php
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

// Leer visitas
$archivo = "visitas.log";
$visitas = [];

if (file_exists($archivo)) {
    $lineas = file($archivo, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lineas as $linea) {
        $partes = array_map('trim', explode('|', $linea));
        if (count($partes) >= 2) {
            $visitas[] = [
                "fecha" => $partes[0],
                "ip" => $partes[1],
                "pais" => $partes[2] ?? "",
                "region" => $partes[3] ?? "",
                "ciudad" => $partes[4] ?? ""
            ];
        }
    }

    // Ordenar por fecha descendente
    usort($visitas, function($a, $b) {
        return DateTime::createFromFormat('d/m/Y H:i:s', $b['fecha']) <=> DateTime::createFromFormat('d/m/Y H:i:s', $a['fecha']);
    });
} else {
    echo "No se encontró el archivo de visitas.";
    exit;
}
?>

<!DOCTYPE HTML>
<html lang="es">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, user-scalable=no" />
    <title>Visitas Registradas</title>
    <link rel="stylesheet" href="assets/css/main.css" />
    <noscript>
      <link rel="stylesheet" href="assets/css/noscript.css" />
    </noscript>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
      body {
        background-color: #242629;
      }
      .inner {
        max-width: 90%;
        margin: auto;
        padding: 4em 1em;
        background-color: #242629;
      }
      h1 {
        text-align: center;
        color: #ffffff;
        font-size: 2em;
        text-transform: uppercase;
        margin-bottom: 1.5em;
      }
      .table-wrapper {
        overflow-x: auto;
        background-color: #34363b;
        padding: 1em;
        border-radius: 0.5em;
      }
      table {
        width: 100%;
        border-collapse: collapse;
      }
      th, td {
        padding: 0.75em;
        text-align: left;
      }
      th {
        color: #ffffff;
        font-weight: 300;
        font-size: 0.9em;
        border-bottom: 2px solid #36383c;
      }
      td {
        color: #a0a0a1;
        font-size: 0.9em;
        border-bottom: 1px solid #36383c;
      }
      tr:nth-child(even) {
        background-color: #2d2f33;
      }
      tr:hover {
        background-color: #404247;
      }
      @media screen and (max-width: 768px) {
        table {
          font-size: 0.85em;
        }
        h1 {
          font-size: 1.5em;
        }
      }
    </style>
  </head>
    <div id="wrapper">
      <div class="inner">
        <h1><i class="fas fa-eye"></i> Visitas al sitio</h1>
        <div class="table-wrapper">
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
                <td><?= htmlspecialchars($v['fecha']) ?></td>
                <td><?= htmlspecialchars($v['ip']) ?></td>
                <td><?= htmlspecialchars($v['pais']) ?></td>
                <td><?= htmlspecialchars($v['region']) ?></td>
                <td><?= htmlspecialchars($v['ciudad']) ?></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </body>
</html>
