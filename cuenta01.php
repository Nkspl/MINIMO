<?php
include 'db.php';  // tu conexión
require_once 'includes/rut_validator.php';

// Datos del nuevo usuario
$rut      = limpiar_rut('24471968-6');
$pass     = '1234';
$hash     = password_hash($pass, PASSWORD_DEFAULT);
$nombre   = 'Nickens';
$apellido = 'Pierre Louis';

$stmt = $conn->prepare("
  INSERT INTO users (rut, clave, nombre, apellido)
  VALUES (?, ?, ?, ?)
");
$stmt->bind_param("ssss", $rut, $hash, $nombre, $apellido);

if ($stmt->execute()) {
    echo "Usuario creado con ID: " . $stmt->insert_id;
} else {
    echo "Error: " . $stmt->error;
}

$stmt->close();
$conn->close();
?>
