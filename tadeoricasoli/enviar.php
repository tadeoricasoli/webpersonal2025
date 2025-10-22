
<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require "vendor/autoload.php";

ini_set("display_errors", 1);
ini_set("display_startup_errors", 1);
error_reporting(E_ALL);

if (
    empty($_POST["nombre"]) ||
    empty($_POST["email"]) ||
    empty($_POST["mensaje"])
) {
    http_response_code(400);
    echo "Missing data.";
    exit();
}

$nombre = htmlspecialchars($_POST["nombre"]);
$email = htmlspecialchars($_POST["email"]);
$mensaje = htmlspecialchars($_POST["mensaje"]);

$mail = new PHPMailer(true);
try {
    // Configuración SMTP
    $mail->isSMTP();
    $mail->Host = "smtp.gmail.com";
    $mail->SMTPAuth = true;
    $mail->Username = "ricasolit@gmail.com"; // tu correo
    $mail->Password = "###################"; // clave de aplicación de Gmail
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
    $mail->Port = 465;


    // Emisor y receptor
    $mail->setFrom("ricasolit@gmail.com", "tadeoricasoli.com");
    $mail->addAddress("ricasolit@gmail.com", "Tadeo");
    $mail->addReplyTo($email, $nombre);

    // Contenido del mensaje
    $mail->isHTML(true);
    $mail->Subject = "You've got a new message";
    $mail->Body =
        "
        <p><strong>Name:</strong> {$nombre}</p>
        <p><strong>Email:</strong> {$email}</p>
        <p><strong>Message:</strong><br />" .
        nl2br($mensaje) .
        "</p>
    ";

    $mail->send();

    // Mostrar SweetAlert2
    echo <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Message sent</title>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <link href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,600" rel="stylesheet" />
  <style>
    body {
      background-color: #242629;
      font-family: "Source Sans Pro", Helvetica, sans-serif;
    }
    .swal2-popup {
      background: #34363b !important;
      color: #a0a0a1 !important;
      font-family: "Source Sans Pro", Helvetica, sans-serif !important;
      font-size: 1.2em !important;
      border: 1px solid #36383c;
      letter-spacing: 0.03em;
      line-height: 1.75;
      padding: 2em 1.5em;
    }
    .swal2-title {
      font-size: 1.6em !important;
      font-weight: 600 !important;
      color: #ffffff !important;
      margin-bottom: 0.5em;
    }
    .swal2-html-container {
      font-size: 1.2em !important;
      font-weight: 300;
    }
    .swal2-confirm {
      background-color: #34a58e !important;
      color: #ffffff !important;
      font-size: 1em !important;
      font-weight: 500;
      text-transform: uppercase;
      padding: 0.8em 2.5em;
      border-radius: 0.3em;
    }
    .swal2-icon {
      transform: scale(1.5);
      margin: 1em auto !important;
    }
  </style>
</head>
<body>
  <script>
    Swal.fire({
      icon: 'success',
      title: 'Message Sent',
      text: 'Thank you, I will reply soon!',
      background: '#34363b',
      color: '#a0a0a1',
      confirmButtonColor: '#34a58e',
      confirmButtonText: 'Go Back'
    }).then(() => {
      window.location.href = 'index.php';
    });
  </script>
</body>
</html>
HTML;
    exit();
} catch (Exception $e) {
    http_response_code(500);
    echo "Error sending message: {$mail->ErrorInfo}";
}
