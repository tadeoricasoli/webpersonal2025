<?php
require __DIR__ . "/vendor/autoload.php";

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;

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

$archivo = "visitas.log";
$visitas = [];

if (file_exists($archivo)) {
    $lineas = file($archivo, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lineas as $linea) {
        $partes = array_map("trim", explode("|", $linea));
        if (count($partes) >= 8) {
            $visitas[] = [
                "fecha" => $partes[0],
                "ip" => $partes[1],
                "pais" => $partes[2] ?? "",
                "region" => $partes[3] ?? "",
                "ciudad" => $partes[4] ?? "",
                "dispositivo" => $partes[5] ?? "",
                "so" => $partes[6] ?? "",
                "source" => $partes[7] ?? "",
            ];
        }
    }

    usort($visitas, function ($a, $b) {
        return DateTime::createFromFormat("d/m/Y H:i:s", $b["fecha"]) <=>
            DateTime::createFromFormat("d/m/Y H:i:s", $a["fecha"]);
    });
} else {
    echo "No se encontró el archivo de visitas.";
    exit();
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle("Visitas");

    $headers = ["Date", "IP", "Country", "Region", "City", "Device", "OS", "Source"];
    $sheet->fromArray($headers, null, "A1");

    $fila = 2;
    foreach ($visitas as $v) {
        $sheet->setCellValue("A$fila", $v["fecha"]);
        $sheet->setCellValue("B$fila", $v["ip"]);
        $sheet->setCellValue("C$fila", $v["pais"]);
        $sheet->setCellValue("D$fila", $v["region"]);
        $sheet->setCellValue("E$fila", $v["ciudad"]);
        $sheet->setCellValue("F$fila", $v["dispositivo"]);
        $sheet->setCellValue("G$fila", $v["so"]);
        $sheet->setCellValue("H$fila", $v["source"]);
        $fila++;
    }

    $lastRow = $fila - 1;

    $sheet->getStyle("A1:H1")->applyFromArray([
        "font" => ["bold" => true, "color" => ["rgb" => "FFFFFF"]],
        "alignment" => ["horizontal" => Alignment::HORIZONTAL_CENTER],
        "fill" => [
            "fillType" => Fill::FILL_SOLID,
            "startColor" => ["rgb" => "34363B"],
        ],
    ]);

    $sheet->getStyle("A2:H$lastRow")->applyFromArray([
        "alignment" => ["horizontal" => Alignment::HORIZONTAL_CENTER],
    ]);

    foreach (range("A", "H") as $col) {
        $sheet->getColumnDimension($col)->setAutoSize(true);
    }

    header(
        "Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet"
    );
    header('Content-Disposition: attachment; filename="exportar_visitas.xlsx"');
    header("Cache-Control: max-age=0");

    $writer = new Xlsx($spreadsheet);
    $writer->save("php://output");
    exit();
}
?>
<!DOCTYPE HTML>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Exportar visitas</title>
  <link rel="icon" href="images/tpr_prada_ico.ico" type="image/x-icon" />
  <link rel="stylesheet" href="assets/css/main.css" />
  <noscript><link rel="stylesheet" href="assets/css/noscript.css" /></noscript>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
  <style>
    body { background-color: #242629; }
    .inner { max-width: 90%; margin: auto; padding: 4em 1em; background-color: #242629; }
    h1 { text-align: center; color: #ffffff; font-size: 2em; text-transform: uppercase; margin-bottom: 0.5em; }
    .selector-form {
      text-align: right;
      margin-bottom: 1em;
      padding-right: 20px;
    }
    .selector-form form {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      color: #fff;
      font-size: 0.9em;
    }
    .selector-form select {
      padding: 4px;
      border-radius: 4px;
      background: #2d2f33;
      color: #fff;
      border: 1px solid #4CAF50;
      width: 60px;
      text-align: center;
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
      text-align: center;
      vertical-align: middle;
      padding: 0.75em;
    }
    th {
      background-color: #2d2f33;
      color: #fff;
      font-weight: 300;
      font-size: 0.9em;
      border-bottom: 2px solid #36383c;
    }
    td {
      color: #a0a0a1;
      font-size: 0.9em;
      border-bottom: 1px solid #36383c;
    }
    tr:nth-child(even) { background-color: #2d2f33; }
    tr:hover { background-color: #404247; }
    .filters input[type="text"],
    .filters input[type="date"] {
      width: 90%;
      padding: 4px;
      border-radius: 4px;
      border: 1px solid #4CAF50;
      background-color: #2d2f33;
      color: #fff;
      font-size: 0.85em;
    }
    .filter-date-container {
      display: flex;
      align-items: center;
      gap: 6px;
      justify-content: center;
    }
    #clear-filters {
      background: #4CAF50;
      border: none;
      color: #fff;
      width: 32px;
      height: 32px;
      font-size: 1.2em;
      border-radius: 4px;
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 0;
      transition: background-color 0.3s ease;
    }
    #clear-filters:hover { background-color: #45a049; }
    .export-float-form {
      position: fixed;
      bottom: 20px;
      right: 20px;
      z-index: 1000;
    }
    .export-float-form button {
      background-color: transparent;
      color: #4CAF50;
      border: 2px solid #4CAF50;
      width: 40px;
      height: 40px;
      font-size: 1.1em;
      border-radius: 0.3em;
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 0;
    }
    .export-float-form button:hover {
      background-color: #4CAF50;
      color: white;
    }
    .pagination {
      text-align: center;
      margin-top: 20px;
      user-select: none;
    }
    .pagination a {
      display: inline-block;
      padding: 5px 12px;
      margin: 0 2px;
      background-color: #2d2f33;
      color: #ccc;
      text-decoration: none;
      border-radius: 4px;
      cursor: pointer;
      transition: background-color 0.3s;
    }
    .pagination a:hover:not(.activo) {
      background-color: #3a3c40;
    }
    .pagination a.activo {
      background-color: #4CAF50;
      color: #fff;
      font-weight: bold;
      cursor: default;
    }
    .puntos {
      display: inline-block;
      padding: 5px 10px;
      color: #666;
      user-select: none;
    }
    thead th {
      text-align: center !important;
      vertical-align: middle !important;
    }
  </style>
</head>
<body>
  <div id="wrapper">
    <div class="inner">
      <h1><i class="fas fa-eye"></i> Visitor Log</h1>

      <div class="selector-form">
        <form method="GET" id="form-porPagina">
          <label for="porPagina">Results per page:</label>
          <select name="porPagina" id="porPagina">
            <option value="10" <?= isset($_GET["porPagina"]) &&
            $_GET["porPagina"] == 10
                ? "selected"
                : "" ?>>10</option>
            <option value="50" <?= isset($_GET["porPagina"]) &&
            $_GET["porPagina"] == 50
                ? "selected"
                : "" ?>>50</option>
            <option value="100" <?= isset($_GET["porPagina"]) &&
            $_GET["porPagina"] == 100
                ? "selected"
                : "" ?>>100</option>
          </select>
        </form>
      </div>

      <div class="table-wrapper">
        <table>
          <thead>
            <tr>
              <th>Date</th>
              <th>IP</th>
              <th>Country</th>
              <th>Region</th>
              <th>City</th>
              <th>Device</th>
              <th>OS</th>
              <th>Source</th>
            </tr>
            <tr class="filters">
              <th>
                <div class="filter-date-container">
                  <button id="clear-filters" title="Clear filters" type="button">
                    <i class="fas fa-eraser"></i>
                  </button>
                  <input type="date" id="filter-date" />
                </div>
              </th>
              <th><input type="text" id="filter-ip" placeholder="Filter IP" /></th>
              <th><input type="text" id="filter-country" placeholder="Filter Country" /></th>
              <th><input type="text" id="filter-region" placeholder="Filter Region" /></th>
              <th><input type="text" id="filter-city" placeholder="Filter City" /></th>
              <th><input type="text" id="filter-device" placeholder="Filter Device" /></th>
              <th><input type="text" id="filter-os" placeholder="Filter OS" /></th>
              <th><input type="text" id="filter-source" placeholder="Filter Source" /></th>
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
              <td><?= htmlspecialchars($v["dispositivo"]) ?></td>
              <td><?= htmlspecialchars($v["so"]) ?></td>
              <td><?= htmlspecialchars($v["source"]) ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>

      <div class="pagination" id="pagination"></div>
    </div>
  </div>

  <form method="POST" class="export-float-form" title="Export records">
    <button type="submit">
      <i class="fas fa-floppy-disk"></i>
    </button>
  </form>

<script>
  const rowsPerPageSelect = document.getElementById('porPagina');
  const tbody = document.querySelector("tbody");
  const pagination = document.getElementById("pagination");
  const filterInputs = document.querySelectorAll(".filters input");
  let currentPage = 1;

  function formatoFechaParaFiltro(fechaCompleta) {
    if (!fechaCompleta) return "";
    const partes = fechaCompleta.split(" ");
    if (partes.length < 1) return "";
    const fecha = partes[0].split("/");
    if (fecha.length !== 3) return "";
    const dia = fecha[0].padStart(2, "0");
    const mes = fecha[1].padStart(2, "0");
    const anio = fecha[2];
    return `${anio}-${mes}-${dia}`;
  }

  function filtrarYPaginar() {
    const inputDate = document.getElementById("filter-date").value;
    const inputIp = document.getElementById("filter-ip").value.toLowerCase();
    const inputCountry = document.getElementById("filter-country").value.toLowerCase();
    const inputRegion = document.getElementById("filter-region").value.toLowerCase();
    const inputCity = document.getElementById("filter-city").value.toLowerCase();
    const inputDevice = document.getElementById("filter-device").value.toLowerCase();
    const inputOs = document.getElementById("filter-os").value.toLowerCase();
    const inputSource = document.getElementById("filter-source").value.toLowerCase();

    const rows = Array.from(tbody.querySelectorAll("tr"));

    let filteredRows = rows.filter(row => {
      const cells = row.querySelectorAll("td");
      const fechaTexto = cells[0].textContent.trim();
      const ip = cells[1].textContent.toLowerCase();
      const country = cells[2].textContent.toLowerCase();
      const region = cells[3].textContent.toLowerCase();
      const city = cells[4].textContent.toLowerCase();
      const device = cells[5].textContent.toLowerCase();
      const os = cells[6].textContent.toLowerCase();
      const source = cells[7].textContent.toLowerCase();

      const fechaFormateada = formatoFechaParaFiltro(fechaTexto);

      return (
  (!inputDate || fechaFormateada === inputDate) &&
  (!inputIp || ip.includes(inputIp)) &&
  (!inputCountry || country.includes(inputCountry)) &&
  (!inputRegion || region.includes(inputRegion)) &&
  (!inputCity || city.includes(inputCity)) &&
  (!inputDevice || device.includes(inputDevice)) &&
  (!inputOs || os.includes(inputOs)) &&
  (!inputSource || source.includes(inputSource))
);
    });

    rows.forEach(r => r.style.display = "none");

    const perPage = parseInt(rowsPerPageSelect.value);
    const totalPages = Math.max(1, Math.ceil(filteredRows.length / perPage));

    if (currentPage > totalPages) currentPage = 1;

    const start = (currentPage - 1) * perPage;
    const end = start + perPage;

    const paginatedRows = filteredRows.slice(start, end);

    paginatedRows.forEach(r => r.style.display = "");

    // Mostrar info registros
    const totalRegistros = rows.length;
    const filtrados = filteredRows.length;
    const desde = filtrados === 0 ? 0 : start + 1;
    const hasta = start + paginatedRows.length;

    let infoText = `Showing ${desde} to ${hasta} (total records ${totalRegistros})`;

    const selectorForm = document.querySelector('.selector-form');
    if (selectorForm) {
      let infoElem = document.getElementById("registros-info");
      if (!infoElem) {
        infoElem = document.createElement("div");
        infoElem.id = "registros-info";
        infoElem.style.color = "#ccc";
        infoElem.style.marginTop = "5px";
        infoElem.style.fontSize = "0.9em";
        infoElem.style.fontStyle = "italic";
        selectorForm.appendChild(infoElem);
      }
      infoElem.textContent = infoText;
    }

    pagination.innerHTML = "";

    function crearEnlacePagina(i) {
      const a = document.createElement("a");
      a.textContent = i;
      if (i === currentPage) {
        a.classList.add("activo");
        a.style.pointerEvents = "none";
      } else {
        a.href = "#";
        a.addEventListener("click", e => {
          e.preventDefault();
          currentPage = i;
          filtrarYPaginar();
          window.scrollTo({ top: 0, behavior: 'smooth' });
        });
      }
      pagination.appendChild(a);
    }

    const delta = 2;
    let paginas = [];

    for (let i = 1; i <= totalPages; i++) {
      if (i === 1 || i === totalPages || (i >= currentPage - delta && i <= currentPage + delta)) {
        paginas.push(i);
      }
    }

    let ultima = 0;
    paginas.forEach(pagina => {
      if (pagina - ultima > 1) {
        if (pagina - ultima === 2) {
          crearEnlacePagina(ultima + 1);
        } else {
          const span = document.createElement("span");
          span.textContent = "...";
          span.classList.add("puntos");
          pagination.appendChild(span);
        }
      }
      crearEnlacePagina(pagina);
      ultima = pagina;
    });
  }

  rowsPerPageSelect.addEventListener("change", () => {
    currentPage = 1;
    filtrarYPaginar();
  });

  filterInputs.forEach(input => {
    input.addEventListener("input", () => {
      currentPage = 1;
      filtrarYPaginar();
    });
  });

  document.getElementById("clear-filters").addEventListener("click", () => {
    filterInputs.forEach(input => {
      input.value = "";
    });
    currentPage = 1;
    filtrarYPaginar();
  });

  // Mantener select en URL
  window.addEventListener("load", () => {
    const urlParams = new URLSearchParams(window.location.search);
    const porPagina = urlParams.get("porPagina");
    if (porPagina) {
      rowsPerPageSelect.value = porPagina;
    }
    filtrarYPaginar();
  });

  // Cuando se cambia select, enviar GET para guardar preferencia
  rowsPerPageSelect.addEventListener("change", () => {
    const form = document.getElementById("form-porPagina");
    form.submit();
  });
</script>
</body>
</html>
