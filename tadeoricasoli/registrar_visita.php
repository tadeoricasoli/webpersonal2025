<?php
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
                $fechaHoraGuardada = trim($partes[0]); // ej: "21/05/2025 14:03:12"
                $dt = DateTime::createFromFormat(
                    "d/m/Y H:i:s",
                    $fechaHoraGuardada
                );
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

// Solo registra si no se registró esta IP en los últimos 1 segundos
if ($now - $ultimoRegistro > 1) {
    $fechaHora = date("d/m/Y H:i:s");

    $datos = @file_get_contents(
        "http://ip-api.com/json/$ip?fields=country,regionName,city"
    );
    $datos_json = json_decode($datos, true);

    $pais = $datos_json["country"] ?? "Desconocido";
    $region = $datos_json["regionName"] ?? "";
    $ciudad = $datos_json["city"] ?? "";

    $linea = "$fechaHora | $ip | $pais | $region | $ciudad" . PHP_EOL;
    file_put_contents($logFile, $linea, FILE_APPEND);
}
?>