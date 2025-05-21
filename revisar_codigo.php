<?php
// revisar_codigo.php
require_once __DIR__ . '/includes/auth.php';
require_login();
require_once __DIR__ . '/includes/db.php';

$message = '';

// 1) Load grupo_articulos for the dropdown
$grupos = [];
$gRes = $conn->query("SELECT codigo, descripcion FROM grupo_articulos ORDER BY codigo");
while ($g = $gRes->fetch_assoc()) {
    $grupos[] = $g;
}

// 2) Handle UPDATE (images/video/docs + fields)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_id'])) {
    $id              = intval($_POST['update_id']);
    $parte           = $conn->real_escape_string($_POST['parte']);
    $descripcion     = $conn->real_escape_string($_POST['descripcion']);
    $codigo_oem      = $conn->real_escape_string($_POST['codigo_oem']);
    $grupo_codigo    = intval($_POST['grupo_codigo']);
    $rotativo        = $_POST['rotativo'];
    $kit             = $_POST['kit'];
    $condicion_habil = $_POST['condicion_habil'];
    $estado          = $_POST['estado'];

    // 2.1) Update maestro_inventario fields
    $upd = $conn->prepare("
      UPDATE maestro_inventario
         SET parte=?, descripcion=?, codigo_oem=?, grupo_codigo=?,
             rotativo=?, kit=?, condicion_habil=?, estado=?
       WHERE id=?
    ");
    $upd->bind_param(
      'sssissssi',
      $parte, $descripcion, $codigo_oem, $grupo_codigo,
      $rotativo, $kit, $condicion_habil, $estado, $id
    );
    $upd->execute();

    // 2.2) IMAGES
    //  - delete marked
    if (!empty($_POST['delete_image'])) {
        $delImg = $conn->prepare("DELETE FROM imagenes WHERE id=? AND maestro_id=?");
        foreach ($_POST['delete_image'] as $imgId) {
            $imgId = intval($imgId);
            $delImg->bind_param('ii', $imgId, $id);
            $delImg->execute();
        }
    }
    //  - upload up to 5
    $cntR = $conn->prepare("SELECT COUNT(*) AS cnt FROM imagenes WHERE maestro_id=?");
    $cntR->bind_param('i', $id);
    $cntR->execute();
    $cntR = $cntR->get_result()->fetch_assoc()['cnt'];
    $toUpload = max(0, 5 - $cntR);
    if ($toUpload > 0 && isset($_FILES['imagenes'])) {
        if (!is_dir('uploads/images')) mkdir('uploads/images', 0777, true);
        $imgs = $_FILES['imagenes'];
        for ($i=0; $i<count($imgs['name']) && $toUpload>0; $i++) {
            if ($imgs['error'][$i]===UPLOAD_ERR_OK) {
                $fn = time() . '_' . basename($imgs['name'][$i]);
                move_uploaded_file($imgs['tmp_name'][$i], "uploads/images/$fn");
                $ins = $conn->prepare("INSERT INTO imagenes(maestro_id,ruta_imagen) VALUES(?,?)");
                $ins->bind_param('is',$id,$fn);
                $ins->execute();
                $toUpload--;
            }
        }
    }

    // 2.3) VIDEO
    if (!empty($_POST['delete_video'])) {
        $conn->query("DELETE FROM videos WHERE maestro_id=$id");
    }
    if (isset($_FILES['video']) && $_FILES['video']['error']===UPLOAD_ERR_OK) {
        if (!is_dir('uploads/videos')) mkdir('uploads/videos', 0777, true);
        $vf = time() . '_' . basename($_FILES['video']['name']);
        move_uploaded_file($_FILES['video']['tmp_name'], "uploads/videos/$vf");
        $conn->query("DELETE FROM videos WHERE maestro_id=$id");
        $insV = $conn->prepare("INSERT INTO videos(maestro_id,ruta_video) VALUES(?,?)");
        $insV->bind_param('is',$id,$vf);
        $insV->execute();
    }

    // 2.4) DOCUMENTS
    //  - load current docs (so they show in the panel)
    $docStmt = $conn->prepare("SELECT id, ruta_documento, tipo FROM documentos WHERE maestro_id=?");
    $docStmt->bind_param('i', $id);
    $docStmt->execute();
    $docRes = $docStmt->get_result();

    //  - delete marked
    if (!empty($_POST['delete_doc'])) {
        $delDoc = $conn->prepare("
          DELETE FROM documentos 
           WHERE id=? AND maestro_id=?
        ");
        foreach ($_POST['delete_doc'] as $docId) {
            $docId = intval($docId);
            $delDoc->bind_param('ii', $docId, $id);
            $delDoc->execute();
        }
    }
    //  - upload new
    if (!empty($_FILES['documentos']['name'][0])) {
        if (!is_dir('uploads/documents')) mkdir('uploads/documents', 0777, true);
        $docs = $_FILES['documentos'];
        for ($i=0; $i<count($docs['name']); $i++) {
            if ($docs['error'][$i]===UPLOAD_ERR_OK) {
                $fn = time() . '_' . basename($docs['name'][$i]);
                move_uploaded_file($docs['tmp_name'][$i], "uploads/documents/$fn");
                $ext = pathinfo($fn,PATHINFO_EXTENSION);
                $insD = $conn->prepare("
                  INSERT INTO documentos(maestro_id,ruta_documento,tipo)
                  VALUES(?,?,?)
                ");
                $insD->bind_param('iss',$id,$fn,$ext);
                $insD->execute();
            }
        }
    }

    $message = 'Registro actualizado exitosamente.';
}

// 3) Handle SEARCH (GET)
$search  = $_GET['search']  ?? '';
$field   = in_array($_GET['field'] ?? 'parte',['parte','descripcion','codigo_oem'])
           ? $_GET['field'] : 'parte';
$showAll = isset($_GET['show_all']);
$doSearch= $showAll || trim($search)!=='' ;
if ($doSearch) {
    if ($showAll) {
        $stmt = $conn->prepare("SELECT * FROM maestro_inventario");
    } else {
        $stmt = $conn->prepare(
          "SELECT * FROM maestro_inventario WHERE $field LIKE ?"
        );
        $like = "%$search%";
        $stmt->bind_param('s',$like);
    }
    $stmt->execute();
    $res = $stmt->get_result();
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Revisar y Editar Códigos - MINIMO</title>
  <link rel="stylesheet" href="css/style.css">
  <script src="js/app.js" defer></script>
</head>
<body>
  <?php include __DIR__ . '/includes/header.php'; ?>
  <main class="content">
    <h2>Revisar y Editar Códigos</h2>
    <?php if ($message): ?>
      <p class="field-error"><?= $message ?></p>
    <?php endif; ?>

    <!-- Search form -->
    <form method="GET">
      <label>Buscar por:
        <select name="field">
          <option value="parte" <?= $field==='parte'?'selected':'' ?>>Parte</option>
          <option value="descripcion" <?= $field==='descripcion'?'selected':'' ?>>Descripción</option>
          <option value="codigo_oem" <?= $field==='codigo_oem'?'selected':'' ?>>Código OEM</option>
        </select>
      </label>
      <label>Término:
        <input type="text" name="search" value="<?= htmlspecialchars($search) ?>">
      </label>
      <button type="submit">Buscar</button>
      <button type="submit" name="show_all">Mostrar Todo</button>
    </form>

    <?php if ($doSearch): ?>
    <div class="table-responsive">
      <table>
        <thead>
          <tr>
            <th>ID</th><th>Parte</th><th>Descripción</th><th>OEM</th><th>Acción</th>
          </tr>
        </thead>
        <tbody>
        <?php while($row = $res->fetch_assoc()): ?>
          <tr>
            <td><?= $row['id'] ?></td>
            <td><?= htmlspecialchars($row['parte']) ?></td>
            <td><?= htmlspecialchars($row['descripcion']) ?></td>
            <td><?= htmlspecialchars($row['codigo_oem']) ?></td>
            <td>
              <button 
                onclick="document.getElementById('edit-<?= $row['id'] ?>').style.display='table-row'">
                Editar
              </button>
            </td>
          </tr>
          <!-- Edit panel -->
          <tr id="edit-<?= $row['id'] ?>" style="display:none;">
            <td colspan="5">
              <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="update_id" value="<?= $row['id'] ?>">

                <!-- Basic fields -->
                <label>Parte:
                  <input type="text" name="parte" 
                         value="<?= htmlspecialchars($row['parte']) ?>" required>
                </label>
                <label>Descripción:
                  <input type="text" name="descripcion" 
                         value="<?= htmlspecialchars($row['descripcion']) ?>" required>
                </label>
                <label>OEM:
                  <input type="text" name="codigo_oem" 
                         value="<?= htmlspecialchars($row['codigo_oem']) ?>">
                </label>
                <label>Grupo Código:
                  <select name="grupo_codigo" required>
                    <?php foreach($grupos as $g): ?>
                      <option value="<?= $g['codigo'] ?>"
                        <?= $g['codigo']==$row['grupo_codigo'] ? 'selected' : '' ?>>
                        <?= $g['codigo'] ?> – <?= htmlspecialchars($g['descripcion']) ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                </label>
                <label>Rotativo:
                  <select name="rotativo">
                    <option value="S" <?= $row['rotativo']==='S'?'selected':'' ?>>S</option>
                    <option value="N" <?= $row['rotativo']==='N'?'selected':'' ?>>N</option>
                  </select>
                </label>
                <label>Kit:
                  <select name="kit">
                    <option value="S" <?= $row['kit']==='S'?'selected':'' ?>>S</option>
                    <option value="N" <?= $row['kit']==='N'?'selected':'' ?>>N</option>
                  </select>
                </label>
                <label>Condición Habilitada:
                  <select name="condicion_habil">
                    <option value="S" <?= $row['condicion_habil']==='S'?'selected':'' ?>>S</option>
                    <option value="N" <?= $row['condicion_habil']==='N'?'selected':'' ?>>N</option>
                  </select>
                </label>
                <label>Estado:
                  <select name="estado">
                    <option value="ACTIVE" <?= $row['estado']==='ACTIVE'?'selected':'' ?>>ACTIVE</option>
                    <option value="PENDOBS"<?= $row['estado']==='PENDOBS'?'selected':'' ?>>PENDOBS</option>
                    <option value="PENDING"<?= $row['estado']==='PENDING'?'selected':'' ?>>PENDING</option>
                  </select>
                </label>

                <!-- Current images -->
                <?php
                  $imgSt = $conn->prepare("SELECT id,ruta_imagen FROM imagenes WHERE maestro_id=?");
                  $imgSt->bind_param('i',$row['id']);
                  $imgSt->execute();
                  $imgRs = $imgSt->get_result();
                ?>
                <fieldset>
                  <legend>Imágenes actuales (marcar para eliminar)</legend>
                  <?php while($img=$imgRs->fetch_assoc()): ?>
                    <div style="display:inline-block; text-align:center; margin:0.5rem;">
                      <img src="uploads/images/<?= htmlspecialchars($img['ruta_imagen']) ?>"
                           style="max-width:100px; display:block; margin-bottom:0.3rem;">
                      <label>
                        <input type="checkbox" name="delete_image[]" 
                               value="<?= $img['id'] ?>">
                        Eliminar
                      </label>
                    </div>
                  <?php endwhile; ?>
                </fieldset>

                <label>Agregar imágenes (hasta completar 5):
                  <input type="file" name="imagenes[]" accept="image/*" multiple>
                </label>

                <!-- Current video -->
                <?php
                  $vidSt = $conn->prepare(
                    "SELECT ruta_video FROM videos WHERE maestro_id=? LIMIT 1"
                  );
                  $vidSt->bind_param('i',$row['id']);
                  $vidSt->execute();
                  $vidRes = $vidSt->get_result()->fetch_assoc();
                ?>
                <?php if($vidRes): ?>
                  <fieldset>
                    <legend>Video actual</legend>
                    <video width="200" controls>
                      <source src="uploads/videos/<?= htmlspecialchars($vidRes['ruta_video']) ?>" 
                              type="video/mp4">
                    </video>
                    <label>
                      <input type="checkbox" name="delete_video" value="1">
                      Eliminar
                    </label>
                  </fieldset>
                <?php endif; ?>

                <label>Subir nuevo video (reemplaza):
                  <input type="file" name="video" accept="video/*">
                </label>

                <!-- Current documents -->
                <?php
                  $docSt = $conn->prepare(
                    "SELECT id,ruta_documento,tipo 
                       FROM documentos 
                      WHERE maestro_id=?"
                  );
                  $docSt->bind_param('i',$row['id']);
                  $docSt->execute();
                  $docRs = $docSt->get_result();
                ?>
                <fieldset>
                  <legend>Documentos actuales (marcar para eliminar)</legend>
                  <?php while($doc=$docRs->fetch_assoc()): ?>
                    <div style="margin:0.5rem 0;">
                      <a href="uploads/documents/<?=htmlspecialchars($doc['ruta_documento'])?>" 
                         target="_blank">
                        <?= htmlspecialchars($doc['ruta_documento']) ?>
                      </a>
                      <label style="margin-left:1rem;">
                        <input type="checkbox" name="delete_doc[]" 
                               value="<?= $doc['id'] ?>">
                        Eliminar
                      </label>
                    </div>
                  <?php endwhile; ?>
                </fieldset>

                <label>Agregar documentos (PDF/Word):
                  <input type="file" name="documentos[]" accept=".pdf,.doc,.docx" multiple>
                </label>

                <div style="margin-top:1rem;">
                  <button type="submit">Guardar Cambios</button>
                  <button type="button" 
                          onclick="document.getElementById('edit-<?= $row['id'] ?>').style.display='none'">
                    Cancelar
                  </button>
                </div>
              </form>
            </td>
          </tr>
        <?php endwhile; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>

  </main>
</body>
</html>
