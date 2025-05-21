<?php
// home.php
require_once 'includes/auth.php';
require_login();
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Home - MINIMO</title>
  <link rel="stylesheet" href="css/style.css">
  <script src="js/app.js" defer></script>
</head>
<body>
  <?php include 'includes/header.php'; ?>
  <main class="content">
    <h2>Bienvenido, <?=htmlspecialchars($_SESSION['user']['nombre'])?>!</h2>
    <p>Selecciona una opción del menú.</p>
  </main>
</body>
</html>
