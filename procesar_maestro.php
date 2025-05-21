<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    header("Location: index.php");
    exit();
}
include 'db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Recoger y sanitizar datos
    $codigo = $conn->real_escape_string(trim($_POST['codigo']));
    $descripcion = $conn->real_escape_string(trim($_POST['descripcion']));
    $num_parte = $conn->real_escape_string(trim($_POST['num_parte']));
    
    // Insertar en la tabla inventario (video inicialmente nulo)
    $stmt = $conn->prepare("INSERT INTO inventario (codigo, descripcion, num_parte) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $codigo, $descripcion, $num_parte);
    if (!$stmt->execute()) {
        die("Error al insertar en inventario: " . $stmt->error);
    }
    $inventario_id = $conn->insert_id;
    
    // Asegurarse de que los directorios existen
    $dirs = ['uploads/videos', 'uploads/images', 'uploads/documents'];
    foreach ($dirs as $dir) {
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
    }
    
    // Procesar video si se subió
    if (isset($_FILES['video']) && $_FILES['video']['error'] == 0) {
        $video_tmp = $_FILES['video']['tmp_name'];
        // Se añade timestamp para evitar colisiones de nombres
        $video_name = time() . "_" . basename($_FILES['video']['name']);
        $video_path = "uploads/videos/" . $video_name;
        if (move_uploaded_file($video_tmp, $video_path)) {
            $stmt_video = $conn->prepare("UPDATE inventario SET video = ? WHERE id = ?");
            $stmt_video->bind_param("si", $video_path, $inventario_id);
            $stmt_video->execute();
        } else {
            error_log("Error al mover el video: " . $_FILES['video']['error']);
        }
    }
    
    // Procesar imágenes (hasta 5)
    if (isset($_FILES['imagenes'])) {
        $total_images = count($_FILES['imagenes']['name']);
        $total_images = min($total_images, 5); // limitar a 5 imágenes
        for ($i = 0; $i < $total_images; $i++) {
            if ($_FILES['imagenes']['error'][$i] == 0) {
                $img_tmp = $_FILES['imagenes']['tmp_name'][$i];
                $img_name = time() . "_" . basename($_FILES['imagenes']['name'][$i]);
                $img_path = "uploads/images/" . $img_name;
                if (move_uploaded_file($img_tmp, $img_path)) {
                    $stmt_img = $conn->prepare("INSERT INTO imagenes (inventario_id, imagen_path) VALUES (?, ?)");
                    $stmt_img->bind_param("is", $inventario_id, $img_path);
                    $stmt_img->execute();
                } else {
                    error_log("Error al mover la imagen $i: " . $_FILES['imagenes']['error'][$i]);
                }
            }
        }
    }
    
    // Procesar documento (único)
    if (isset($_FILES['documentos']) && $_FILES['documentos']['error'] == 0) {
        $doc_tmp = $_FILES['documentos']['tmp_name'];
        $doc_name = time() . "_" . basename($_FILES['documentos']['name']);
        $doc_path = "uploads/documents/" . $doc_name;
        if (move_uploaded_file($doc_tmp, $doc_path)) {
            $ext = pathinfo($doc_name, PATHINFO_EXTENSION);
            $tipo = strtolower($ext);
            $stmt_doc = $conn->prepare("INSERT INTO documentos (inventario_id, documento_path, tipo) VALUES (?, ?, ?)");
            $stmt_doc->bind_param("iss", $inventario_id, $doc_path, $tipo);
            $stmt_doc->execute();
        } else {
            error_log("Error al mover el documento: " . $_FILES['documentos']['error']);
        }
    }
    
    header("Location: maestro_inventario.php?mensaje=" . urlencode("Datos guardados correctamente."));
    exit();
}
?>
