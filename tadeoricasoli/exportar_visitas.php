<?php
// Autenticación
$usuario_valido = "admin";
$contrasena_valida = "1234";

if (
    !isset($_SERVER["PHP_AUTH_USER"]) ||
    !isset($_SERVER["PHP_AUTH_PW"]) ||
    $_SERVER["PHP_AUTH_USER"] !== $usuario_valido ||
    $_SERVER["PHP_AUTH_PW"] !== $contrasena_valida
) {
    header('WWW-Authenticate: Basic realm="Zona protegida"');
    header("HTTP/1.0 401 Unauthorized");
    echo "Acceso denegado.";
    exit();
}

// Leer visitas
$archivo = "visitas.log";
$visitas = [];

if (file_exists($archivo)) {
    $lineas = file($archivo, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lineas as $linea) {
        $partes = array_map("trim", explode("|", $linea));
        if (count($partes) >= 2) {
            $visitas[] = [
                "fecha" => $partes[0],
                "ip" => $partes[1],
                "pais" => $partes[2] ?? "",
                "region" => $partes[3] ?? "",
                "ciudad" => $partes[4] ?? "",
            ];
        }
    }

    // Ordenar por fecha descendente
    usort($visitas, function ($a, $b) {
        return DateTime::createFromFormat("d/m/Y H:i:s", $b["fecha"]) <=>
            DateTime::createFromFormat("d/m/Y H:i:s", $a["fecha"]);
    });
} else {
    echo "No se encontró el archivo de visitas.";
    exit();
}

// Exportar si se envió el formulario
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    header("Content-Type: application/vnd.ms-excel; charset=UTF-8");
    header(
        "Content-Disposition: attachment; filename=visitas_" .
            date("Ymd_His") .
            ".xls"
    );
    header("Pragma: no-cache");
    header("Expires: 0");

    echo "\xEF\xBB\xBF"; // UTF-8 BOM
    echo "<table border='1'>";
    echo "<thead><tr><th>Fecha</th><th>IP</th><th>País</th><th>Región</th><th>Ciudad</th></tr></thead><tbody>";
    foreach ($visitas as $v) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($v["fecha"]) . "</td>";
        echo "<td>" . htmlspecialchars($v["ip"]) . "</td>";
        echo "<td>" . htmlspecialchars($v["pais"]) . "</td>";
        echo "<td>" . htmlspecialchars($v["region"]) . "</td>";
        echo "<td>" . htmlspecialchars($v["ciudad"]) . "</td>";
        echo "</tr>";
    }
    echo "</tbody></table>";
    exit();
}
?>

<!DOCTYPE HTML>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Exportar visitas</title>
  <link rel="stylesheet" href="assets/css/main.css" />
  <noscript><link rel="stylesheet" href="assets/css/noscript.css" /></noscript>
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

    /* Botón flotante con borde sutil */
    .export-float-form {
      position: fixed;
      bottom: 20px;
      right: 20px;
      z-index: 1000;
    }
    .export-float-form button {
  background-color: transparent;
  color: #4CAF50;
  border: 2px solid #4CAF50; /* borde más visible */
  width: 40px;               /* ancho fijo reducido */
  height: 40px;              /* altura pareja */
  font-size: 1.1em;
  border-radius: 0.3em;
  cursor: pointer;
  transition: background-color 0.3s ease, color 0.3s ease;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 0;
}
.export-float-form button:hover {
  background-color: #4CAF50;
  color: white;
.export-float-form button i {
  margin: 0;
  padding: 0;
  display: block;
}
  </style>
</head>
<body>
  <div id="wrapper">
    <div class="inner">
      <h1><i class="fas fa-eye"></i> Visitas registradas</h1>

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
              <td><?= htmlspecialchars($v["fecha"]) ?></td>
              <td><?= htmlspecialchars($v["ip"]) ?></td>
              <td><?= htmlspecialchars($v["pais"]) ?></td>
              <td><?= htmlspecialchars($v["region"]) ?></td>
              <td><?= htmlspecialchars($v["ciudad"]) ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- Botón flotante de exportar -->
  <form method="POST" class="export-float-form" title="Exportar registros">
    <button type="submit">
      <i class="fas fa-floppy-disk"></i>
    </button>
  </form>
</body>
</html>