<?php
// activos.php
require_once __DIR__ . '/includes/auth.php';
require_login();
require_once __DIR__ . '/includes/db.php';

// Defino los campos por los que se puede filtrar
$afields = ['activo','descripcion','alias','ano','fabricante','modelo','planta'];

$conditions = [];
$params     = [];
$types      = '';

// 1) Construyo dinámicamente el WHERE
foreach ($afields as $field) {
    if (!empty($_GET[$field])) {
        $conditions[] = "$field LIKE ?";
        $params[]     = '%' . trim($_GET[$field]) . '%';
        $types       .= 's';
    }
}

// 2) Preparo la consulta
$sql = 'SELECT * FROM activos';
if ($conditions) {
    // concateno con AND para que todos los filtros apliquen juntos
    $sql .= ' WHERE ' . implode(' AND ', $conditions);
}

$stmt = $conn->prepare($sql);
if ($conditions) {
    // uso unpacking para bindear todos los parámetros
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Activos - MINIMO</title>
  <link rel="stylesheet" href="css/style.css">
  <script src="js/app.js" defer></script>
</head>
<body>
  <?php include __DIR__ . '/includes/header.php'; ?>
  <main class="content">
    <h2>Activos</h2>

    <form method="GET">
      <?php foreach ($afields as $field): ?>
        <label>
          <?= ucfirst($field) ?>:
          <input
            type="text"
            name="<?= htmlspecialchars($field)?>"
            value="<?= htmlspecialchars($_GET[$field] ?? '') ?>"
          >
        </label>
      <?php endforeach; ?>

      <button type="submit">Buscar</button>
      <button type="submit" name="show_all">Mostrar Todo</button>
    </form>

    <div class="table-responsive">
      <table>
        <thead>
          <tr>
            <th>Activo</th>
            <th>Descripción</th>
            <th>Alias</th>
            <th>Año</th>
            <th>Fabricante</th>
            <th>Modelo</th>
            <th>Planta</th>
          </tr>
        </thead>
        <tbody>
          <?php while ($row = $result->fetch_assoc()): ?>
            <tr>
              <td><?= htmlspecialchars($row['activo']) ?></td>
              <td><?= htmlspecialchars($row['descripcion']) ?></td>
              <td><?= htmlspecialchars($row['alias']) ?></td>
              <td><?= htmlspecialchars($row['ano']) ?></td>
              <td><?= htmlspecialchars($row['fabricante']) ?></td>
              <td><?= htmlspecialchars($row['modelo']) ?></td>
              <td><?= htmlspecialchars($row['planta']) ?></td>
            </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    </div>
  </main>
</body>
</html>
