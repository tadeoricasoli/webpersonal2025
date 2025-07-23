<?php
require __DIR__ . "/vendor/autoload.php";

use UAParser\Parser;

date_default_timezone_set("America/Argentina/Buenos_Aires");

function obtenerIP()
{
    if (!empty($_SERVER["HTTP_CLIENT_IP"])) {
        return $_SERVER["HTTP_CLIENT_IP"];
    }
    if (!empty($_SERVER["HTTP_X_FORWARDED_FOR"])) {
        return explode(",", $_SERVER["HTTP_X_FORWARDED_FOR"])[0];
    }
    return $_SERVER["REMOTE_ADDR"];
}

// Detectar tipo de dispositivo
function detectarTipoDispositivo($result)
{
    $ua = strtolower($_SERVER["HTTP_USER_AGENT"] ?? "");

    if (
        strpos($ua, "mobile") !== false ||
        strpos($ua, "iphone") !== false ||
        strpos($ua, "android") !== false
    ) {
        if (strpos($ua, "tablet") !== false || strpos($ua, "ipad") !== false) {
            return "Tablet";
        }
        return "Celular";
    }

    if (
        strpos($ua, "windows") !== false ||
        strpos($ua, "macintosh") !== false ||
        strpos($ua, "linux") !== false
    ) {
        if (
            strpos($ua, "laptop") !== false ||
            strpos($ua, "notebook") !== false
        ) {
            return "Notebook";
        }
        return "PC";
    }

    if ($result->device->family !== "Other") {
        return $result->device->family;
    }

    return "Desconocido";
}

// Registrar visita
$ip = obtenerIP();
$logFile = "visitas.log";
$now = time();
$ultimoRegistro = 0;

if (file_exists($logFile)) {
    $lineas = file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lineas as $linea) {
        $partes = explode("|", $linea);
        if (count($partes) >= 2) {
            $ipGuardada = trim($partes[1]);
            if ($ipGuardada === $ip) {
                $fechaHoraGuardada = trim($partes[0]);
                $dt = DateTime::createFromFormat("d/m/Y H:i:s", $fechaHoraGuardada);
                if ($dt) {
                    $timestamp = $dt->getTimestamp();
                    if ($timestamp > $ultimoRegistro) {
                        $ultimoRegistro = $timestamp;
                    }
                }
            }
        }
    }
}

if ($now - $ultimoRegistro > 1) {
    $fechaHora = date("d/m/Y H:i:s");

    // Geolocalización
    $datos = @file_get_contents("http://ip-api.com/json/$ip?fields=country,regionName,city");
    $datos_json = json_decode($datos, true);
    $pais = $datos_json["country"] ?? "Desconocido";
    $region = $datos_json["regionName"] ?? "";
    $ciudad = $datos_json["city"] ?? "";

    // Dispositivo y SO
    $parser = Parser::create();
    $result = $parser->parse($_SERVER["HTTP_USER_AGENT"] ?? "");

    $dispositivo = detectarTipoDispositivo($result);
    $so = $result->os->toString();

    // Guardar log
    $linea = "$fechaHora | $ip | $pais | $region | $ciudad | $dispositivo | $so" . PHP_EOL;
    file_put_contents($logFile, $linea, FILE_APPEND);
}

// Solo mostrar tabla si se accede por navegador
if (php_sapi_name() !== 'cli') {
    // Parámetros de paginación
    $porPagina = isset($_GET['porPagina']) ? (int)$_GET['porPagina'] : 10;
    $pagina = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;

    $lineas = file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $lineas = array_reverse($lineas);

    $totalRegistros = count($lineas);
    $totalPaginas = ceil($totalRegistros / $porPagina);
    $inicio = ($pagina - 1) * $porPagina;
    $lineasPaginadas = array_slice($lineas, $inicio, $porPagina);

    // Selector
    echo "<form method='GET' style='margin-bottom:10px'>";
    echo "Mostrar: <select name='porPagina' onchange='this.form.submit()'>";
    foreach ([10, 50, 100] as $opcion) {
        $selected = $porPagina === $opcion ? "selected" : "";
        echo "<option value='$opcion' $selected>$opcion</option>";
    }
    echo "</select> por página";
    echo "</form>";

    // Tabla
    echo "<table border='1' cellpadding='5' style='border-collapse:collapse; font-family:sans-serif; font-size:14px'>";
    echo "<tr><th>Fecha</th><th>IP</th><th>País</th><th>Región</th><th>Ciudad</th><th>Dispositivo</th><th>SO</th></tr>";

    foreach ($lineasPaginadas as $linea) {
        $partes = explode("|", $linea);
        echo "<tr>";
        foreach ($partes as $parte) {
            echo "<td>" . htmlspecialchars(trim($parte)) . "</td>";
        }
        echo "</tr>";
    }

    echo "</table>";

    // Navegación
    echo "<div style='margin-top:10px'>";
    for ($i = 1; $i <= $totalPaginas; $i++) {
        $style = $i == $pagina ? "font-weight:bold; text-decoration:underline;" : "";
        echo "<a href='?pagina=$i&porPagina=$porPagina' style='margin-right:5px; $style'>$i</a>";
    }
    echo "</div>";
}
?>