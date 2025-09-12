<?php
require __DIR__ . "/vendor/autoload.php";
use UAParser\Parser;

$usuario_valido = "###########";
$contrasena_valida = "###########";

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


date_default_timezone_set("America/Argentina/Buenos_Aires");

function obtenerIP() {
    if (!empty($_SERVER["HTTP_CLIENT_IP"])) return $_SERVER["HTTP_CLIENT_IP"];
    if (!empty($_SERVER["HTTP_X_FORWARDED_FOR"])) return explode(",", $_SERVER["HTTP_X_FORWARDED_FOR"])[0];
    return $_SERVER["REMOTE_ADDR"];
}

function detectarTipoDispositivo($result) {
    $ua = strtolower($_SERVER["HTTP_USER_AGENT"] ?? "");
    if (strpos($ua, "mobile") !== false || strpos($ua, "iphone") !== false || strpos($ua, "android") !== false) {
        if (strpos($ua, "tablet") !== false || strpos($ua, "ipad") !== false) return "Tablet";
        return "Celular";
    }
    if (strpos($ua, "windows") !== false || strpos($ua, "macintosh") !== false || strpos($ua, "linux") !== false) {
        if (strpos($ua, "laptop") !== false || strpos($ua, "notebook") !== false) return "Notebook";
        return "PC";
    }
    return $result->device->family !== "Other" ? $result->device->family : "Desconocido";
}

// Bloquear bots directamente desde PHP por User-Agent
$ua = $_SERVER["HTTP_USER_AGENT"] ?? "";
$uaSospechoso = preg_match("/bot|spider|crawler|scraper|ping|preview|curl|wget|Claude|GPT|OpenAI|BingPreview|LLaMA|Anthropic/i", $ua);
if ($uaSospechoso) {
    http_response_code(403);
    exit("Acceso denegado.");
}

$ip = obtenerIP();
$logFile = "visitas.log";
$now = time();
$ultimoRegistro = 0;

if (file_exists($logFile)) {
    $lineas = file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lineas as $linea) {
        $partes = explode("|", $linea);
        if (count($partes) >= 2 && trim($partes[1]) === $ip) {
            $dt = DateTime::createFromFormat("d/m/Y H:i:s", trim($partes[0]));
            if ($dt && $dt->getTimestamp() > $ultimoRegistro) $ultimoRegistro = $dt->getTimestamp();
        }
    }
}

if ($now - $ultimoRegistro > 1) {
    $fechaHora = date("d/m/Y H:i:s");
    $datos = @file_get_contents("http://ip-api.com/json/$ip?fields=country,regionName,city");
    $datos_json = json_decode($datos, true);
    $pais = $datos_json["country"] ?? "Desconocido";
    $region = $datos_json["regionName"] ?? "";
    $ciudad = $datos_json["city"] ?? "";

    $parser = Parser::create();
    $result = $parser->parse($ua);

    $dispositivo = detectarTipoDispositivo($result);
    $so = $result->os->toString();
    $source = $result->ua->toString();

    $linea = "$fechaHora | $ip | $pais | $region | $ciudad | $dispositivo | $so | $source" . PHP_EOL;
    file_put_contents($logFile, $linea, FILE_APPEND);
}

// Solo mostrar tabla si se accede por navegador
if (php_sapi_name() !== 'cli') {
    $porPagina = isset($_GET['porPagina']) ? (int)$_GET['porPagina'] : 10;
    $pagina = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;

    $lineas = file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $lineas = array_reverse($lineas);

    $totalRegistros = count($lineas);
    $totalPaginas = ceil($totalRegistros / $porPagina);
    $inicio = ($pagina - 1) * $porPagina;
    $lineasPaginadas = array_slice($lineas, $inicio, $porPagina);

    echo "<form method='GET' style='margin-bottom:10px'>";
    echo "Mostrar: <select name='porPagina' onchange='this.form.submit()'>";
    foreach ([10, 50, 100] as $opcion) {
        $selected = $porPagina === $opcion ? "selected" : "";
        echo "<option value='$opcion' $selected>$opcion</option>";
    }
    echo "</select> por página";
    echo "</form>";

    // Tabla con nueva columna "Source"
    echo "<table border='1' cellpadding='5' style='border-collapse:collapse; font-family:sans-serif; font-size:14px'>";
    echo "<tr><th>Fecha</th><th>IP</th><th>País</th><th>Región</th><th>Ciudad</th><th>Dispositivo</th><th>SO</th><th>Source</th>";

    foreach ($lineasPaginadas as $linea) {
        $partes = explode("|", $linea);
        echo "<tr>";
        foreach ($partes as $parte) {
            echo "<td>" . htmlspecialchars(trim($parte)) . "</td>";
        }
        echo "</tr>";
    }

    echo "</table>";

    echo "<div style='margin-top:10px'>";
    for ($i = 1; $i <= $totalPaginas; $i++) {
        $style = $i == $pagina ? "font-weight:bold; text-decoration:underline;" : "";
        echo "<a href='?pagina=$i&porPagina=$porPagina' style='margin-right:5px; $style'>$i</a>";
    }
    echo "</div>";
}
?>
