<?php
session_start();
if(!isset($_SESSION['usuario'])){
    header("Location: index.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Maestro Inventario - MINIMO</title>
    <link rel="stylesheet" href="css/style.css">
    <script>
    function validarVideo(input) {
        alert("Asegúrese de que el video tenga un máximo de 2 minutos.");
    }
    </script>
</head>
<body>
    <?php include 'header.php'; ?>
    <div class="content">
        <h2>Maestro Inventario</h2>
        <?php 
        if(isset($_GET['mensaje'])) { 
            echo '<p class="mensaje">'.htmlspecialchars($_GET['mensaje']).'</p>'; 
        } 
        ?>
        <form action="procesar_maestro.php" method="post" enctype="multipart/form-data">
            <label for="codigo">Código:</label>
            <input type="text" id="codigo" name="codigo" required>
            
            <label for="descripcion">Descripción:</label>
            <input type="text" id="descripcion" name="descripcion" required>
            
            <label for="num_parte">Número de Parte:</label>
            <input type="text" id="num_parte" name="num_parte" required>
            
            <label for="imagenes">Imágenes (hasta 5):</label>
            <input type="file" id="imagenes" name="imagenes[]" multiple accept="image/*">
            
            <label for="video">Video (máximo 2 minutos):</label>
            <input type="file" id="video" name="video" accept="video/*" onchange="validarVideo(this);">
            
            <label for="documentos">Documentos (PDF o Word):</label>
            <input type="file" id="documentos" name="documentos" accept=".pdf,.doc,.docx">
            
            <button type="submit">Guardar</button>
        </form>
    </div>
</body>
</html>
