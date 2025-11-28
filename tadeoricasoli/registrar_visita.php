
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
        if (strpos($ua, "laptop") !== false || strpos($ua, "notebook") !== false) {
            return "Notebook";
        }
        return "PC";
    }
    return $result->device->family !== "Other" ? $result->device->family : "Desconocido";
}

// Bloqueo básico de bots
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
$logFile = __DIR__ . '/visitas.log';

$now = time();
$ultimoRegistro = 0;

// Leer último registro para esta IP
if (file_exists($logFile)) {
    $lineas = @file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($lineas !== false) {
        foreach ($lineas as $linea) {
            $partes = explode("|", $linea);
            if (count($partes) >= 2 && trim($partes[1]) === $ip) {
                $dt = DateTime::createFromFormat("d/m/Y H:i:s", trim($partes[0]));
                if ($dt) {
                    $ts = $dt->getTimestamp();
                    if ($ts > $ultimoRegistro) {
                        $ultimoRegistro = $ts;
                    }
                }
            }
        }
    }
}

// ⚠️ Salvaguarda: si el último registro quedó en el futuro respecto al reloj actual, lo ignoramos
if ($ultimoRegistro > $now) {
    error_log("[visitas] Ajuste: ultimoRegistro futuro (".$ultimoRegistro.") > now (".$now."). Se resetea a 0.");
    $ultimoRegistro = 0;
}

// Log de depuración
error_log("[visitas] NOW: $now | ULTIMO: $ultimoRegistro | DIF: " . ($now - $ultimoRegistro));

// Condición: permitir si no hay registros previos, o si pasó > 1 segundo
if ($ultimoRegistro === 0 || ($now - $ultimoRegistro) > 1) {
    $fechaHora = date("d/m/Y H:i:s");

    // Obtener datos de IP con tolerancia a fallos
    $pais = "Desconocido";
    $region = "";
    $ciudad = "";
    $endpoint = "http://ip-api.com/json/$ip?fields=country,regionName,city";

    $datos = @file_get_contents($endpoint);
    if ($datos !== false) {
        $datos_json = json_decode($datos, true);
        if (is_array($datos_json)) {
            $pais   = $datos_json["country"]    ?? $pais;
            $region = $datos_json["regionName"] ?? $region;
            $ciudad = $datos_json["city"]       ?? $ciudad;
        } else {
            error_log("[visitas] ip-api json_decode falló para IP $ip. Respuesta: " . substr($datos, 0, 200));
        }
    } else {
        error_log("[visitas] ip-api no respondió para IP $ip (endpoint: $endpoint)");
    }

    // UA Parser
    try {
        $parser = Parser::create();
        $result = $parser->parse($ua);
    } catch (Throwable $e) {
        error_log("[visitas] UAParser error: " . $e->getMessage());
        // Fallback mínimo
        $result = (object)[
            "device" => (object)["family" => "Desconocido"],
            "os"     => (object)["toString" => fn() => "Desconocido"],
            "ua"     => (object)["toString" => fn() => substr($ua, 0, 64)]
        ];
    }

    $dispositivo = detectarTipoDispositivo($result);
    // toString puede no existir en el fallback, se cubre con ternario
    $so      = method_exists($result->os, 'toString') ? $result->os->toString() : "Desconocido";
    $source  = method_exists($result->ua, 'toString') ? $result->ua->toString() : substr($ua, 0, 64);

    $linea = "$fechaHora | $ip | $pais | $region | $ciudad | $dispositivo | $so | $source" . PHP_EOL;

    // Escribir
    $ok = @file_put_contents($logFile, $linea, FILE_APPEND);
    if ($ok === false) {
        error_log("[visitas] ERROR: no se pudo escribir en $logFile");
    } else {
        error_log("[visitas] OK: línea escrita para IP $ip en $logFile");
    }
}
// Fin
