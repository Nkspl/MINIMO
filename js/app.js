// js/app.js

// Valida formato de RUT en el login
function validarLogin() {
  const rut = document.getElementById('rut').value;
  const regex = /^[0-9]+-[0-9Kk]$/;
  const errorEl = document.getElementById('rut-error');
  if (!regex.test(rut)) {
    errorEl.innerText = 'Formato de RUT inválido';
    return false;
  }
  errorEl.innerText = '';
  return true;
}

// Alterna visibilidad del sidebar
function toggleSidebar() {
  document.getElementById('sidebar').classList.toggle('collapsed');
}

// Placeholder: función para cargar media en modal
function loadMedia(maestroId) {
  // Aquí iría AJAX para traer imágenes/videos/documentos
  alert('Cargar media para ítem ' + maestroId);
}



