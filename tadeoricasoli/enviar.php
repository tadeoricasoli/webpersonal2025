<?php
session_start();

$name = trim($_POST["name"] ?? "");
$email = trim($_POST["email"] ?? "");
$message = trim($_POST["message"] ?? "");

$errors = [];

if (empty($name)) {
    $errors["name"] = "Please enter your name.";
}

if (empty($email)) {
    $errors["email"] = "Please enter your email.";
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors["email"] = "Please enter a valid email address.";
}

if (empty($message)) {
    $errors["message"] = "Please enter your message.";
}

$_SESSION["form_values"] = [
    "name" => $name,
    "email" => $email,
    "message" => $message,
];

if (!empty($errors)) {
    $_SESSION["form_errors"] = $errors;
    header("Location: index.php#footer");
    exit();
}

// Enviar correo
$to = "tadeoricasoli@outlook.com";
$subject = "Nuevo mensaje de contacto";
$body = "Nombre: $name\nEmail: $email\nMensaje:\n$message";
$headers = "From: $email";

mail($to, $subject, $body, $headers);

// Limpieza y éxito
unset($_SESSION["form_values"]);
$_SESSION["form_message"] =
    "Thank you! Your message has been sent successfully.";
header("Location: index.php#footer");
exit();