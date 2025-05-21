
<?php
// includes/db.php
$servername = "localhost";
$username = "root";
$password = "Hola.,123";
$dbname = "minimoo_db";

// Crear conexión
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}
// Charset
$conn->set_charset("utf8mb4");
?>
