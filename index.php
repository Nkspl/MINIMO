<?php
// index.php
require_once 'includes/auth.php';
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rut   = trim($_POST['rut']);
    $clave = $_POST['clave'];
    if (login($rut, $clave)) {
        header('Location: home.php');
        exit();
    }
    $error = 'RUT o clave inválidos';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Login - MINIMO</title>
  <link rel="stylesheet" href="css/style.css">
  <script src="js/app.js" defer></script>
</head>
<body class="login-page">
  <form class="login-form" onsubmit="return validarLogin()" method="post">
    <h1>MINIMO</h1>
    <?php if ($error): ?><p class="field-error"><?=htmlspecialchars($error)?></p><?php endif; ?>
    <label for="rut">RUT:</label>
    <input type="text" id="rut" name="rut" placeholder="12345678-5" required>
    <span id="rut-error" class="field-error"></span>
    <label for="clave">Clave:</label>
    <input type="password" id="clave" name="clave" required>
    <button type="submit">Iniciar Sesión</button>
  </form>
</body>
</html>
