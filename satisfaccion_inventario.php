<?php
// satisfaccion_inventario.php
require_once __DIR__ . '/includes/auth.php';
require_login();
require_once __DIR__ . '/includes/db.php';

// 1) Campos de filtro
$filters = [
    'ot',
    'activo_solicitado',
    'fecha_solicitud',
    'requerido_por',
    'parte_maestro',
    'cantidad_solicitada',
    'almacen_origen',
    'id_vale',
    'fecha',
    'transaccion',
    'asset_destino',
    'almacen_destino',
    'cantidad_entregada',
    'costo_unitario',
    'costo_total_entregado',
    'despachado_por',
    'diferencia_cant'
];

// 2) Construcción dinámica del WHERE … LIKE ?
$conditions = [];
$params     = [];
$types      = '';

if (!isset($_GET['show_all'])) {
    foreach ($filters as $f) {
        if (!empty($_GET[$f])) {
            $conditions[] = "$f LIKE ?";
            $params[]     = '%' . trim($_GET[$f]) . '%';
            $types       .= 's';
        }
    }
}

// 3) Montar la consulta
$sql = 'SELECT * FROM satisfaccion_inventario';
if ($conditions) {
    $sql .= ' WHERE ' . implode(' AND ', $conditions);
}
$sql .= ' ORDER BY ot DESC';

$stmt = $conn->prepare($sql);
if ($conditions) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$res = $stmt->get_result();

// 4) Volcar resultados
$rows = [];
while ($r = $res->fetch_assoc()) {
    $rows[] = $r;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Satisfacción de Inventario – MINIMO</title>
  <link rel="stylesheet" href="css/style.css">
  <link rel="stylesheet" href="css/satisfaccion.css">
  <script src="js/app.js" defer></script>
</head>
<body>
  <?php include __DIR__ . '/includes/header.php'; ?>
  <main class="content">
    <h2>Satisfacción de Inventario</h2>

    <!-- Filtro en una sola línea -->
    <form method="GET" class="sat-filter">
      <?php foreach ($filters as $f): ?>
        <label>
          <?= ucfirst(str_replace('_',' ',$f)) ?>:
          <input
            type="text"
            name="<?= htmlspecialchars($f) ?>"
            value="<?= htmlspecialchars($_GET[$f] ?? '') ?>"
          >
        </label>
      <?php endforeach; ?>
      <button type="submit">Filtrar</button>
      <button type="submit" name="show_all">Mostrar Todo</button>
    </form>

    <!-- Línea separadora -->
    <div class="sat-separator"></div>

    <!-- Contenedor con scroll horizontal -->
    <div class="sat-table-container">
      <table class="sat-table">
        <thead>
          <tr>
            <?php foreach ($filters as $f): ?>
              <th><?= ucfirst(str_replace('_',' ',$f)) ?></th>
            <?php endforeach; ?>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($rows)): ?>
            <tr>
              <td colspan="<?= count($filters) ?>">No se encontraron registros.</td>
            </tr>
          <?php else: ?>
            <?php foreach ($rows as $row): ?>
              <tr>
                <?php foreach ($filters as $f): ?>
                  <td>
                    <?php
                      $val = $row[$f];
                      if (in_array($f, ['costo_unitario','costo_total_entregado'])) {
                        echo number_format($val, 2);
                      } else {
                        echo htmlspecialchars($val);
                      }
                    ?>
                  </td>
                <?php endforeach; ?>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

  </main>
</body>
</html>
