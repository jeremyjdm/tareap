<?php
session_start();

// Verificar si el usuario tiene acceso permitido (Root o Gerente)
if (!isset($_SESSION['perfil']) || !in_array($_SESSION['perfil'], ['Root', 'gerente'])) {
    header("Location: login.php");
    exit();
}

$servername = "localhost";
$username = "root";
$password = "root1";
$dbname = "bdtienda";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}

// Manejar adición de usuario
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['agregar'])) {
    $nombre_usuario = $_POST['nombre_usuario'] ?? '';
    $contrasena = $_POST['contrasena'] ?? '';
    $perfil = $_POST['perfil'] ?? '';

    // Validar los datos antes de insertarlos
    if ($nombre_usuario && $contrasena && $perfil) {
        $stmt = $conn->prepare("INSERT INTO usuarios (nombre_usuario, contrasena, perfil) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $nombre_usuario, $contrasena, $perfil);
        $stmt->execute();
        $stmt->close();
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }
}

// Manejar eliminación de usuario
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['eliminar'])) {
    $id_usuario = $_POST['id_usuario'] ?? '';
    
    // Validar el ID del usuario
    if ($id_usuario) {
        $stmt = $conn->prepare("DELETE FROM usuarios WHERE id_usuario = ?");
        $stmt->bind_param("i", $id_usuario);
        $stmt->execute();
        $stmt->close();
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }
}

// Manejar edición de usuario
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['editar'])) {
    $id_usuario = $_POST['id_usuario'] ?? '';
    $nombre_usuario = $_POST['nombre_usuario'] ?? '';
    $contrasena = $_POST['contrasena'] ?? '';
    $perfil = $_POST['perfil'] ?? '';

    // Validar los datos antes de actualizar
    if ($id_usuario && $nombre_usuario && $contrasena && $perfil) {
        $stmt = $conn->prepare("UPDATE usuarios SET nombre_usuario = ?, contrasena = ?, perfil = ? WHERE id_usuario = ?");
        $stmt->bind_param("sssi", $nombre_usuario, $contrasena, $perfil, $id_usuario);
        $stmt->execute();
        $stmt->close();
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }
}

// Obtener usuarios
$result = $conn->query("SELECT * FROM usuarios");
$usuarios = $result->fetch_all(MYSQLI_ASSOC);
$conn->close();

$home_url = ($_SESSION['perfil'] === 'Root') ? "homer.php" : "homeg.php";
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Usuarios</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background-color: #e9f0f5; /* Color de fondo suave */
            color: #333;
            margin: 20px;
        }
        h2 {
            color: #007BFF; /* Título en azul */
        }
        table, th, td {
            border: 1px solid #ddd;
            border-collapse: collapse;
            padding: 10px;
        }
        table {
            width: 100%;
            margin-bottom: 16px;
            background-color: #fff; /* Color de fondo de la tabla */
        }
        th {
            background-color: #007BFF; /* Cabecera en azul */
            color: white;
            text-align: left;
        }
        tr:hover {
            background-color: #f1f1f1; /* Color de hover */
        }
        form {
            display: flex;
            flex-direction: column;
            gap: 8px;
            max-width: 400px;
            margin-bottom: 20px;
        }
        input, select, button {
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
            font-size: 16px;
        }
        button {
            background-color: #007BFF; /* Botones en azul */
            color: white;
            border: none;
            cursor: pointer;
        }
        button:hover {
            background-color: #0056b3; /* Color de hover en botones */
        }
        #editForm {
            display: none;
            background-color: #fff; /* Color de fondo del formulario de edición */
            padding: 20px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }
    </style>
</head>
<body>

    <h2>Gestión de Usuarios</h2>
    <table>
        <thead>
            <tr>
                <th>ID Usuario</th>
                <th>Nombre Usuario</th>
                <th>Contraseña</th>
                <th>Perfil</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($usuarios)): ?>
                <?php foreach ($usuarios as $usuario): ?>
                    <tr>
                        <td><?= $usuario['id_usuario'] ?></td>
                        <td><?= htmlspecialchars($usuario['nombre_usuario']) ?></td>
                        <td><?= htmlspecialchars($usuario['contrasena']) ?></td>
                        <td><?= htmlspecialchars($usuario['perfil']) ?></td>
                        <td>
                            <form method="post" style="display:inline;">
                                <input type="hidden" name="id_usuario" value="<?= $usuario['id_usuario'] ?>">
                                <button type="submit" name="eliminar">Eliminar</button>
                            </form>
                            <button onclick="editUser(<?= $usuario['id_usuario'] ?>, '<?= htmlspecialchars($usuario['nombre_usuario']) ?>', '<?= htmlspecialchars($usuario['contrasena']) ?>', '<?= htmlspecialchars($usuario['perfil']) ?>')">Editar</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="5">No hay usuarios registrados.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <h3>Agregar Usuario</h3>
    <form method="post">
        <input type="text" name="nombre_usuario" placeholder="Nombre Usuario" required>
        <input type="password" name="contrasena" placeholder="Contraseña" required>
        <select name="perfil" required>
            <option value="" disabled selected>Selecciona un perfil</option>
            <option value="Root">Root</option>
            <option value="Empleado">Empleado</option>
            <option value="Gerente">Gerente</option>
            <option value="Secretaria">Secretaria</option>
        </select>
        <button type="submit" name="agregar">Agregar Usuario</button>
    </form>

    <form action="<?= $home_url ?>" method="POST"> <!-- Acción para regresar -->
        <button type="submit">Regresar</button> 
    </form>

    <div id="editForm">
        <h3>Editar Usuario</h3>
        <form method="post" id="updateForm">
            <input type="hidden" name="id_usuario" id="edit_id_usuario">
            <input type="text" name="nombre_usuario" id="edit_nombre_usuario" placeholder="Nombre Usuario" required>
            <input type="password" name="contrasena" id="edit_contrasena" placeholder="Contraseña" required>
            <select name="perfil" id="edit_perfil" required>
                <option value="Root">Root</option>
                <option value="Empleado">Empleado</option>
                <option value="Gerente">Gerente</option>
                <option value="Secretaria">Secretaria</option>
            </select>
            <button type="submit" name="editar">Actualizar Usuario</button>
            <button type="button" onclick="document.getElementById('editForm').style.display='none';">Cancelar</button>
        </form>
    </div>

    <script>
        function editUser(id, nombre, contrasena, perfil) {
            document.getElementById('edit_id_usuario').value = id;
            document.getElementById('edit_nombre_usuario').value = nombre;
            document.getElementById('edit_contrasena').value = contrasena;
            document.getElementById('edit_perfil').value = perfil;
            document.getElementById('editForm').style.display = 'block';
        }
    </script>

</body>
</html>
