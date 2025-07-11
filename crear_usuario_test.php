<?php
// crear_usuario_test.php
// Este script crea un usuario de prueba en la base de datos

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/rut_validator.php';

// Datos del usuario de prueba
$rut      = limpiar_rut('12345678-9');
$pass     = 'clave123';
$hash     = password_hash($pass, PASSWORD_DEFAULT);
$nombre   = 'Usuario';
$apellido = 'Prueba';
$rol      = 'STANDARD';

$stmt = $conn->prepare(
    "INSERT INTO users (rut, clave, nombre, apellido, rol) VALUES (?, ?, ?, ?, ?)"
);
$stmt->bind_param('sssss', $rut, $hash, $nombre, $apellido, $rol);

if ($stmt->execute()) {
    echo "Usuario de prueba creado con ID: " . $stmt->insert_id . "\n";
} else {
    echo "Error al crear usuario: " . $stmt->error . "\n";
}

$stmt->close();
$conn->close();
?>
