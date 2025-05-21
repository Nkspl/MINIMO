<?php
// crear_codigo.php
require_once __DIR__ . '/includes/auth.php';
require_login();
require_once __DIR__ . '/includes/db.php';

$message = '';

// 1) Obtengo todos los grupos para el <select>
$grupos = $conn->query("SELECT codigo, descripcion FROM grupo_articulos ORDER BY codigo");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 2) Recoger y sanitizar datos
    $parte       = $conn->real_escape_string($_POST['parte']);
    $descripcion = $conn->real_escape_string($_POST['descripcion']);
    $oem         = $conn->real_escape_string($_POST['codigo_oem']);
    $grupo       = intval($_POST['grupo_codigo']);
    $rotativo    = $_POST['rotativo'];
    $kit         = $_POST['kit'];
    $cond_habil  = $_POST['condicion_habil'];
    $estado      = $_POST['estado'];

    // 3) Verifico que el grupo exista
    $chk = $conn->prepare("SELECT 1 FROM grupo_articulos WHERE codigo = ?");
    $chk->bind_param('i', $grupo);
    $chk->execute();
    $resChk = $chk->get_result();
    if ($resChk->num_rows === 0) {
        $message = "Error: el Grupo Código <strong>$grupo</strong> no existe.";
    } else {
        // 4) Inserto en maestro_inventario
        $stmt = $conn->prepare("
            INSERT INTO maestro_inventario
              (parte, descripcion, codigo_oem, grupo_codigo, rotativo, kit, condicion_habil, estado)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param(
            'sssissss',
            $parte,
            $descripcion,
            $oem,
            $grupo,
            $rotativo,
            $kit,
            $cond_habil,
            $estado
        );
        if (!$stmt->execute()) {
            die('Error al crear código: ' . $stmt->error);
        }
        $mid = $stmt->insert_id;

        // (Aquí iría la subida de imágenes, video y documentos, igual que tenías)
        // …

        $message = 'Código creado exitosamente.';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Crear Código - MINIMO</title>
  <link rel="stylesheet" href="css/style.css">
  <script src="js/app.js" defer></script>
</head>
<body>
  <?php include __DIR__ . '/includes/header.php'; ?>
  <main class="content">
    <h2>Crear Código</h2>
    <?php if ($message): ?>
      <p class="field-error"><?= $message ?></p>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data">
      <label>Parte (código):
        <input type="text" name="parte" required>
      </label>

      <label>Descripción:
        <input type="text" name="descripcion" required>
      </label>

      <label>Código OEM:
        <input type="text" name="codigo_oem">
      </label>

      <label>Grupo Código:
        <select name="grupo_codigo" required>
          <option value="">-- Selecciona un grupo --</option>
          <?php while ($g = $grupos->fetch_assoc()): ?>
            <option value="<?= $g['codigo'] ?>">
              <?= $g['codigo'] ?> – <?= htmlspecialchars($g['descripcion']) ?>
            </option>
          <?php endwhile; ?>
        </select>
      </label>

      <label>Rotativo:
        <select name="rotativo">
          <option value="S">S</option>
          <option value="N" selected>N</option>
        </select>
      </label>

      <label>Kit:
        <select name="kit">
          <option value="S">S</option>
          <option value="N" selected>N</option>
        </select>
      </label>

      <label>Condición Habilitada:
        <select name="condicion_habil">
          <option value="S">S</option>
          <option value="N" selected>N</option>
        </select>
      </label>

      <label>Estado:
        <select name="estado">
          <option value="ACTIVE" selected>ACTIVE</option>
          <option value="PENDOBS">PENDOBS</option>
          <option value="PENDING">PENDING</option>
        </select>
      </label>

      <label>Imágenes (hasta 5):
        <input type="file" name="imagenes[]" accept="image/*" multiple>
      </label>

      <label>Video (≤2 min):
        <input type="file" name="video" accept="video/*">
      </label>

      <label>Documentos (PDF/Word):
        <input type="file" name="documentos" accept=".pdf,.doc,.docx">
      </label>

      <button type="submit">Crear</button>
    </form>
  </main>
</body>
</html>
