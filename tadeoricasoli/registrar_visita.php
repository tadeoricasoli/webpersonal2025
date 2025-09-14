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
    return $result->device->family !== "Other"
        ? $result->device->family
        : "Desconocido";
}

// Bloquear bots
$ua = $_SERVER["HTTP_USER_AGENT"] ?? "";
$uaSospechoso = preg_match(
    "/bot|spider|crawler|scraper|ping|preview|curl|wget|Claude|GPT|OpenAI|BingPreview|LLaMA|Anthropic/i",
    $ua
);
if ($uaSospechoso) {
    http_response_code(403);
    exit();
}

$ip = obtenerIP();
$logFile = __DIR__ . "/../logs/visitas.log";
$now = time();
$ultimoRegistro = 0;

if (file_exists($logFile)) {
    $lineas = file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lineas as $linea) {
        $partes = explode("|", $linea);
        if (count($partes) >= 2 && trim($partes[1]) === $ip) {
            $dt = DateTime::createFromFormat("d/m/Y H:i:s", trim($partes[0]));
            if ($dt && $dt->getTimestamp() > $ultimoRegistro) {
                $ultimoRegistro = $dt->getTimestamp();
            }
        }
    }
}

if ($now - $ultimoRegistro > 1) {
    $fechaHora = date("d/m/Y H:i:s");
    $datos = @file_get_contents(
        "http://ip-api.com/json/$ip?fields=country,regionName,city"
    );
    $datos_json = json_decode($datos, true);
    $pais = $datos_json["country"] ?? "Desconocido";
    $region = $datos_json["regionName"] ?? "";
    $ciudad = $datos_json["city"] ?? "";

    $parser = Parser::create();
    $result = $parser->parse($ua);

    $dispositivo = detectarTipoDispositivo($result);
    $so = $result->os->toString();
    $source = $result->ua->toString();

    $linea =
        "$fechaHora | $ip | $pais | $region | $ciudad | $dispositivo | $so | $source" .
        PHP_EOL;
    file_put_contents($logFile, $linea, FILE_APPEND);
}

// Fin del script, no mostrar nada
exit();
?>
