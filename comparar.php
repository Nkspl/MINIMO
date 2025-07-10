<?php
// comparar.php
require_once __DIR__ . '/includes/auth.php';
require_login();
require_once __DIR__ . '/includes/db.php';

$a1 = trim($_GET['a1'] ?? '');
$a2 = trim($_GET['a2'] ?? '');

function fetchItem($conn, $term) {
    if ($term === '') return null;
    $stmt = $conn->prepare("
      SELECT * FROM maestro_inventario
       WHERE parte LIKE ? OR descripcion LIKE ? OR codigo_oem LIKE ?
       LIMIT 1
    ");
    $like = "%{$term}%";
    $stmt->bind_param('sss',$like,$like,$like);
    $stmt->execute();
    $mi = $stmt->get_result()->fetch_assoc();
    if (!$mi) return null;
    $id = $mi['id'];
    // imágenes
    $imgs = [];
    $st = $conn->prepare("SELECT ruta_imagen FROM imagenes WHERE maestro_id=?");
    $st->bind_param('i',$id); $st->execute();
    foreach ($st->get_result()->fetch_all(MYSQLI_ASSOC) as $r) {
      $imgs[] = $r['ruta_imagen'];
    }
    // documentos
    $docs = [];
    $st = $conn->prepare("SELECT ruta_documento FROM documentos WHERE maestro_id=?");
    $st->bind_param('i',$id); $st->execute();
    foreach ($st->get_result()->fetch_all(MYSQLI_ASSOC) as $r) {
      $docs[] = $r['ruta_documento'];
    }
    return ['data'=>$mi,'images'=>$imgs,'docs'=>$docs];
}

$item1 = fetchItem($conn,$a1);
$item2 = fetchItem($conn,$a2);
$doSearch = isset($_GET['a1']) || isset($_GET['a2']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Comparar Ítems - MINIMO</title>
  <link rel="stylesheet" href="css/style.css">
   <link rel="stylesheet" href="css/comparar.css">
  <script src="js/app.js" defer></script>


  <script>
    let imgs1 = <?=json_encode($item1['images'] ?? ['placeholder.png'])?>,
        imgs2 = <?=json_encode($item2['images'] ?? ['placeholder.png'])?>,
        i1=0,i2=0;
    function show1(){document.getElementById('img1').src='uploads/images/'+imgs1[i1]}
    function show2(){document.getElementById('img2').src='uploads/images/'+imgs2[i2]}
    function nxt1(){i1=(i1+1)%imgs1.length;show1()}
    function prv1(){i1=(i1-1+imgs1.length)%imgs1.length;show1()}
    function nxt2(){i2=(i2+1)%imgs2.length;show2()}
    function prv2(){i2=(i2-1+imgs2.length)%imgs2.length;show2()}
    window.addEventListener('DOMContentLoaded',()=>{
      show1();show2();
      setInterval(nxt1,5000);
      setInterval(nxt2,5000);
    });
  </script>
</head>
<body>
  <?php include __DIR__.'/includes/header.php'; ?>


   <h1 class="comp-title">Comparar</h1>

  <form class="comp-search" method="GET">
    <label for="a1">Codigo 1:</label>
    <input id="a1" name="a1" value="<?=htmlspecialchars($a1)?>">
    <label for="a2">Codigo 2:</label>
    <input id="a2" name="a2" value="<?=htmlspecialchars($a2)?>">
    <button type="submit">🔍</button>
  </form>

  <?php if($doSearch): ?>
  <div class="comp-container">

    <div class="comp-column">
      <?php if($item1): ?>
        <div class="inv-carousel">
          <button id="prev1" onclick="prv1()">‹</button>
          <img id="img1" src="" alt="">
          <button id="next1" onclick="nxt1()">›</button>
        </div>
        <div class="inv-details">
          <?php foreach([
            'Código'=>$item1['data']['parte'],
            'Descripción'=>$item1['data']['descripcion'],
            'OEM'=>$item1['data']['codigo_oem'],
            'Grupo'=>$item1['data']['grupo_codigo'],
            'Rotativo'=>$item1['data']['rotativo'],
            'Kit'=>$item1['data']['kit'],
            'Condición'=>$item1['data']['condicion_habil'],
            'Estado'=>$item1['data']['estado']
          ] as $l=>$v): ?>
            <div class="label"><?=$l?>:</div>
            <div class="value" title="<?=htmlspecialchars($v)?>"><?=htmlspecialchars($v)?></div>
          <?php endforeach; ?>
          <div class="label">Documento:</div>
          <div class="value">
            <?php foreach($item1['docs'] as $doc): ?>
              <a href="uploads/documents/<?=htmlspecialchars($doc)?>" target="_blank">
                <?=htmlspecialchars($doc)?>
              </a><br>
            <?php endforeach; ?>
          </div>
        </div>
      <?php else: ?>
        <p>No se encontró Ítem 1.</p>
      <?php endif; ?>
    </div>

    <div class="comp-column">
      <?php if($item2): ?>
        <div class="inv-carousel">
          <button id="prev2" onclick="prv2()">‹</button>
          <img id="img2" src="" alt="">
          <button id="next2" onclick="nxt2()">›</button>
        </div>
        <div class="inv-details">
          <?php foreach([
            'Código'=>$item2['data']['parte'],
            'Descripción'=>$item2['data']['descripcion'],
            'OEM'=>$item2['data']['codigo_oem'],
            'Grupo'=>$item2['data']['grupo_codigo'],
            'Rotativo'=>$item2['data']['rotativo'],
            'Kit'=>$item2['data']['kit'],
            'Condición'=>$item2['data']['condicion_habil'],
            'Estado'=>$item2['data']['estado']
          ] as $l=>$v): ?>
            <div class="label"><?=$l?>:</div>
            <div class="value" title="<?=htmlspecialchars($v)?>"><?=htmlspecialchars($v)?></div>
          <?php endforeach; ?>
          <div class="label">Documento:</div>
          <div class="value">
            <?php foreach($item2['docs'] as $doc): ?>
              <a href="uploads/documents/<?=htmlspecialchars($doc)?>" target="_blank">
                <?=htmlspecialchars($doc)?>
              </a><br>
            <?php endforeach; ?>
          </div>
        </div>
      <?php else: ?>
        <p>No se encontró Ítem 2.</p>
      <?php endif; ?>
    </div>

  </div>
  <?php endif; ?>
</body>
</html>
