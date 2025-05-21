<?php
// inventario.php
require_once __DIR__ . '/includes/auth.php';
require_login();
require_once __DIR__ . '/includes/db.php';

$cod   = $_GET['cod']   ?? '';
$desc  = $_GET['desc']  ?? '';
$oem   = $_GET['oem']   ?? '';
$doSearch = isset($_GET['search']);
$data  = null;
$loc   = null;
$images = [];

if ($doSearch) {
    // Busco en la vista inventario_completo
    $sql = "SELECT * FROM inventario_completo WHERE codigo LIKE ? OR descripcion LIKE ? OR oem LIKE ?";
    $stmt = $conn->prepare($sql);
    $pat  = '%' . ($cod ?: $desc ?: $oem) . '%';
    $stmt->bind_param('sss', $pat, $pat, $pat);
    $stmt->execute();
    $data = $stmt->get_result()->fetch_assoc();
    if ($data) {
        // Ubicación y documento
        $locStmt = $conn->prepare(
          "SELECT bodega, estante, cantidad,
                  (SELECT GROUP_CONCAT(ruta_documento SEPARATOR ', ')
                     FROM documentos WHERE maestro_id=?) AS docs
             FROM inventario WHERE maestro_id=? LIMIT 1"
        );
        $locStmt->bind_param('ii', $data['maestro_id'], $data['maestro_id']);
        $locStmt->execute();
        $loc = $locStmt->get_result()->fetch_assoc();
        // Imágenes asociadas
        $imgStmt = $conn->prepare("SELECT ruta_imagen FROM imagenes WHERE maestro_id=?");
        $imgStmt->bind_param('i', $data['maestro_id']);
        $imgStmt->execute();
        $imgsRes = $imgStmt->get_result();
        while ($row = $imgsRes->fetch_assoc()) {
            $images[] = $row['ruta_imagen'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Inventario - MINIMO</title>
  <link rel="stylesheet" href="css/style.css">
  <script src="js/app.js" defer></script>
</head>
<body>
  <?php include __DIR__ . '/includes/header.php'; ?>

  <div class="inv-container">
    <h1 class="inv-title">Inventario</h1>

    <form class="inv-search" method="GET">
      <label for="cod">Código:</label>
      <input id="cod" name="cod"   value="<?= htmlspecialchars($cod) ?>">
      <label for="desc">Descripción:</label>
      <input id="desc" name="desc"  value="<?= htmlspecialchars($desc) ?>">
      <label for="oem">OEM:</label>
      <input id="oem" name="oem"   value="<?= htmlspecialchars($oem) ?>">
      <button type="submit" name="search">🔍</button>
    </form>

    <?php if ($doSearch && $data): ?>
    <div class="inv-body">
      <div class="inv-carousel">
        <button id="prev" onclick="prevImage()">‹</button>
        <img id="carousel-img" src="<?= 'uploads/images/' . ($images[0] ?? 'placeholder.png') ?>" alt="">
        <button id="next" onclick="nextImage()">›</button>
      </div>

      <div class="inv-details">
      <div class="label">Código:</div>
  <div class="value" title="<?=htmlspecialchars($data['codigo'])?>">
    <?=htmlspecialchars($data['codigo'])?>
  </div>

  <div class="label">Descripción:</div>
  <div class="value" title="<?=htmlspecialchars($data['descripcion'])?>">
    <?=htmlspecialchars($data['descripcion'])?>
  </div>

  <div class="label">OEM:</div>
  <div class="value" title="<?=htmlspecialchars($data['oem'])?>">
    <?=htmlspecialchars($data['oem'])?>
  </div>

  <div class="label">Condición:</div>
  <div class="value"><?=htmlspecialchars($data['condicion'])?></div>

  <div class="label">Costo Promedio:</div>
  <div class="value"><?=number_format($data['costo_promedio'],2)?></div>

  <div class="label">Costo Última Compra:</div>
  <div class="value"><?=number_format($data['costo_ultima_compra'],2)?></div>

  <div class="label">Costo Total del Stock:</div>
  <div class="value"><?=number_format($data['costo_total'],2)?></div>

 </div>
  </div>


    <div class="inv-location">
      <h2>UBICACIÓN</h2>
      <table>
        <thead>
          <tr>
            <th>BODEGA</th>
            <th>ESTANTE</th>
            <th>CANTIDAD</th>
            <th>DOCUMENTO</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td><?= htmlspecialchars($loc['bodega'] ?? '') ?></td>
            <td><?= htmlspecialchars($loc['estante'] ?? '') ?></td>
            <td><?= htmlspecialchars($loc['cantidad'] ?? '') ?></td>
<td>
  <?php 
    // 1) Separa cada ruta si vienen concatenadas
    $docs = explode(',', $loc['docs'] ?? '');
    foreach ($docs as $doc):
      $doc = trim($doc);
      if ($doc === '') continue;
      // 2) Construye la ruta relativa correcta
      $path = "uploads/documents/{$doc}";
      // 3) Comprueba que el archivo exista antes de mostrar
      if (file_exists(__DIR__ . "/{$path}")):
  ?>
      <!-- 4) Enlace al documento, abre en nueva pestaña y fuerza descarga -->
      <a 
        href="<?= htmlspecialchars($path) ?>" 
        target="_blank" 
       
        style="text-decoration:none; color:#000;"
      >
        <?= htmlspecialchars($doc) ?>
      </a><br>
  <?php 
      else:
        echo htmlspecialchars($doc) . ' (no encontrado)<br>';
      endif;
    endforeach;
  ?>
</td>

          </tr>
        </tbody>
      </table>
    </div>
    <?php endif; ?>
  </div>

  <script>
    // Carrusel
    const imgs = <?= json_encode($images ?: ['placeholder.png']) ?>;
    let idx = 0;
    function showImg() {
      document.getElementById('carousel-img').src = 'uploads/images/' + imgs[idx];
    }
    function nextImage(){ idx = (idx + 1) % imgs.length; showImg(); }
    function prevImage(){ idx = (idx - 1 + imgs.length) % imgs.length; showImg(); }
    setInterval(nextImage, 5000);
  </script>
</body>
</html>
