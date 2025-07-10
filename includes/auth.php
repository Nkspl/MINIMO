<?php
// includes/auth.php
session_start();
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/rut_validator.php';

/**
 * Intenta autenticar al usuario.
 */
function login(string $rut, string $clave): bool {
    $rut = limpiar_rut($rut);
    if (!validar_rut($rut)) {
        return false;
    }
    global $conn;
    $stmt = $conn->prepare("SELECT id, rut, nombre, apellido, clave, rol FROM users WHERE rut = ?");
    $stmt->bind_param('s', $rut);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res->num_rows !== 1) return false;
    $user = $res->fetch_assoc();
    if (!password_verify($clave, $user['clave'])) return false;
    unset($user['clave']);
    $_SESSION['user'] = $user;
    return true;
}

/**
 * Verifica que haya sesión activa.
 */
function require_login(): void {
    if (empty($_SESSION['user'])) {
        header('Location: index.php');
        exit();
    }
}
?>