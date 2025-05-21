<?php
// includes/header.php
require_once __DIR__ . '/auth.php';
require_login();
?>
<header class="topbar">
  <button id="menu-btn" onclick="toggleSidebar()">☰</button>
  <span class="brand">MINIMO</span>
  <span class="user-info"><?=htmlspecialchars($_SESSION['user']['nombre'] . ' ' . $_SESSION['user']['apellido'])?></span>

 <!-- Nuevo botón de Cerrar Sesión -->
  <a href="logout.php" class="logout-btn" title="Cerrar sesión">
    <!-- Icono de logout (Heroicon "logout") -->
    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
        d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a2 2 0 11-4 0v-1m0-8V7a2 2 0 114 0v1" />
    </svg>
  </a>


</header>



<aside id="sidebar" class="sidebar collapsed">
  <ul>
    <li><a href="inventario.php">Inventario</a></li>
    <li class="submenu"><a href="#">Maestro Inventario</a>
      <ul>
        <li><a href="crear_codigo.php">Crear Código</a></li>
        <li><a href="revisar_codigo.php">Revisar Código</a></li>
      </ul>
    </li>
    <li><a href="comparar.php">Comparar</a></li>
    <li><a href="satisfaccion_inventario.php">Satisfacción Inventario</a></li>
    <li><a href="activos.php">Activos</a></li>
    <li><a href="usuarios.php">Configuración Usuarios</a></li>
  </ul>
</aside>

