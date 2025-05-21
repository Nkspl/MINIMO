<?php
// usuarios.php
require_once 'includes/auth.php'; require_login();
require_once 'includes/db.php';

// Asegurar tabla de permisos
$conn->query("CREATE TABLE IF NOT EXISTS user_permissions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  permission VARCHAR(50) NOT NULL,
  FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$roles = ['ADMIN','STANDARD'];
$modules = ['inventario','comparar','mi_usuario'];

// Procesar formularios
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (isset($_POST['create'])) {
    // Crear usuario
    $rut   = trim($_POST['rut']);
    $hash  = password_hash($_POST['clave'], PASSWORD_DEFAULT);
    $nom   = $conn->real_escape_string($_POST['nombre']);
    $ape   = $conn->real_escape_string($_POST['apellido']);
    $rol   = in_array($_POST['rol'],$roles)?$_POST['rol']:'STANDARD';
    $st = $conn->prepare("INSERT INTO users(rut,clave,nombre,apellido,rol) VALUES(?,?,?,?,?)");
    $st->bind_param('sssss',$rut,$hash,$nom,$ape,$rol);
    $st->execute();
    $uid = $st->insert_id;
    // Permisos
    if ($rol === 'ADMIN') {
      foreach ($modules as $p) {
        $conn->query("INSERT INTO user_permissions(user_id,permission) VALUES($uid,'$p')");
      }
    } else {
      foreach (['inventario','comparar','mi_usuario'] as $p) {
        $conn->query("INSERT INTO user_permissions(user_id,permission) VALUES($uid,'$p')");
      }
    }
  } elseif (isset($_POST['update'])) {
    // Actualizar usuario
    $uid = intval($_POST['user_id']);
    $nom = $conn->real_escape_string($_POST['nombre']);
    $ape = $conn->real_escape_string($_POST['apellido']);
    $rol = in_array($_POST['rol'],$roles)?$_POST['rol']:'STANDARD';
    $conn->query("UPDATE users SET nombre='$nom',apellido='$ape',rol='$rol' WHERE id=$uid");
    $conn->query("DELETE FROM user_permissions WHERE user_id=$uid");
    foreach ($modules as $perm) {
      if (!empty($_POST['perm'][$perm])) {
        $conn->query("INSERT INTO user_permissions(user_id,permission) VALUES($uid,'$perm')");
      }
    }
  }
}

// Obtener usuarios
$users = $conn->query("SELECT * FROM users");
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Configuración Usuarios</title>
  <link rel="stylesheet" href="css/style.css">
  <script src="js/app.js" defer></script>
</head>
<body>
  <?php include 'includes/header.php'; ?>
  <main class="content">
    <h2>Usuarios</h2>

    <section>
      <h3>Crear Usuario</h3>
      <form method="POST">
        <input type="hidden" name="create" value="1">
        <label>RUT:<input name="rut" required></label>
        <label>Nombre:<input name="nombre" required></label>
        <label>Apellido:<input name="apellido" required></label>
        <label>Clave:<input type="password" name="clave" required></label>
        <label>Rol:
          <select name="rol">
            <?php foreach ($roles as $r): ?><option value="<?=$r?>"><?=$r?></option><?php endforeach; ?>
          </select>
        </label>
        <button type="submit">Crear Usuario</button>
      </form>
    </section>

    <section>
      <h3>Lista de Usuarios</h3>
      <div class="table-responsive">
        <table>
          <thead><tr><th>ID</th><th>RUT</th><th>Nombre</th><th>Apellido</th><th>Rol</th><th>Acciones</th></tr></thead>
          <tbody>
          <?php while ($u = $users->fetch_assoc()): ?>
            <tr>
              <td><?=$u['id']?></td>
              <td><?=htmlspecialchars($u['rut'])?></td>
              <td><?=htmlspecialchars($u['nombre'])?></td>
              <td><?=htmlspecialchars($u['apellido'])?></td>
              <td><?=$u['rol']?></td>
              <td><button onclick="document.getElementById('edit-<?=$u['id']?>').style.display='block'">Editar</button></td>
            </tr>
            <tr id="edit-<?=$u['id']?>" style="display:none;">
              <td colspan="6">
                <form method="POST">
                  <input type="hidden" name="update" value="1">
                  <input type="hidden" name="user_id" value="<?=$u['id']?>">
                  <label>Nombre:<input name="nombre" value="<?=htmlspecialchars($u['nombre'])?>"></label>
                  <label>Apellido:<input name="apellido" value="<?=htmlspecialchars($u['apellido'])?>"></label>
                  <label>Rol:
                    <select name="rol">
                      <?php foreach ($roles as $r): ?>
                        <option value="<?=$r?>"<?=$u['rol']==$r?' selected':''?>><?=$r?></option>
                      <?php endforeach; ?>
                    </select>
                  </label>
                  <fieldset>
                    <legend>Permisos</legend>
                    <?php
                    $perms = array_column(
                      $conn->query("SELECT permission FROM user_permissions WHERE user_id={$u['id']}")->fetch_all(MYSQLI_ASSOC),
                      'permission'
                    );
                    foreach ($modules as $mod): ?>
                      <label><input type="checkbox" name="perm[<?=$mod?>]" value="1"<?=in_array($mod,$perms)?' checked':''?>> <?=$mod?></label>
                    <?php endforeach; ?>
                  </fieldset>
                  <button type="submit">Guardar Cambios</button>
                  <button type="button" onclick="this.closest('tr').style.display='none'">Cancelar</button>
                </form>
              </td>
            </tr>
          <?php endwhile; ?>
          </tbody>
        </table>
      </div>
    </section>

  </main>
</body>
</html>
