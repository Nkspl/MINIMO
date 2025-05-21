<?php
session_start();
include 'db.php';  // tu conexión a MySQL

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rut   = trim($_POST['rut']);
    $clave = $_POST['clave'];

    // 1) Prepara y ejecuta la consulta solo por rut
    $stmt = $conn->prepare("SELECT id, rut, clave, nombre, apellido FROM users WHERE rut = ?");
    if (!$stmt) {
        die("Error en prepare(): " . $conn->error);
    }
    $stmt->bind_param("s", $rut);
    $stmt->execute();
    $result = $stmt->get_result();

    // 2) Si encontramos exactamente un usuario...
    if ($result && $result->num_rows === 1) {
        $usuario = $result->fetch_assoc();

        // 3) Verifica la clave con password_verify
        if (password_verify($clave, $usuario['clave'])) {
            // 4) Desinfecta el array de sesión y guarda solo lo necesario
            unset($usuario['clave']);
            $_SESSION['usuario'] = $usuario;
            header("Location: home.php");
            exit();
        }
    }

    // Si llegaste aquí, rut o clave eran incorrectos
    header("Location: index.php?error=" . urlencode("RUT o clave incorrectos"));
    exit();
}
