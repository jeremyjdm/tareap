<?php
session_start();

// Verificar si el usuario tiene acceso permitido (Root o Gerente)
if (!isset($_SESSION['perfil']) || !in_array($_SESSION['perfil'], ['Root', 'gerente'])) {
    header("Location: login.php");
    exit();
}

$servername = "localhost";
$username = "root";
$password = "root";
$dbname = "bdtienda";

$conn = new mysqli($servername, $username, $password, $dbname);

// Verificar la conexión
if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}

// Manejar adición de método de pago
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['agregar'])) {
    $tipo_pago = $_POST['tipo_pago'] ?? '';

    // Validar los datos antes de insertarlos
    if ($tipo_pago) {
        $stmt = $conn->prepare("INSERT INTO metodos_pago (tipo_pago) VALUES (?)");
        $stmt->bind_param("s", $tipo_pago);
        $stmt->execute();
        $stmt->close();
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }
}

// Manejar eliminación de método de pago
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['eliminar'])) {
    $id_metodo_pago = $_POST['id_metodo_pago'] ?? '';

    // Validar el ID del método de pago
    if ($id_metodo_pago) {
        $stmt = $conn->prepare("DELETE FROM metodos_pago WHERE id_metodo_pago = ?");
        $stmt->bind_param("i", $id_metodo_pago);
        $stmt->execute();
        $stmt->close();
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }
}

// Manejar edición de método de pago
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['editar'])) {
    $id_metodo_pago = $_POST['id_metodo_pago'];
    $tipo_pago = $_POST['tipo_pago'] ?? '';

    // Validar los datos antes de actualizar
    if ($id_metodo_pago && $tipo_pago) {
        $stmt = $conn->prepare("UPDATE metodos_pago SET tipo_pago = ? WHERE id_metodo_pago = ?");
        $stmt->bind_param("si", $tipo_pago, $id_metodo_pago);
        $stmt->execute();
        $stmt->close();
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }
}

// Obtener métodos de pago
$result = $conn->query("SELECT * FROM metodos_pago");
$metodos_pago = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
$conn->close();

$home_url = ($_SESSION['perfil'] === 'Root') ? "homer.php" : "homeg.php";
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Métodos de Pago</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f0f8ff; /* Fondo azul claro */
            margin: 20px;
        }
        h2 {
            color: #003366; /* Azul oscuro */
        }
        table, th, td {
            border: 1px solid #007bff; /* Bordes azules */
            border-collapse: collapse;
            padding: 8px;
        }
        table {
            width: 100%;
            margin-bottom: 16px;
        }
        th {
            background-color: #007bff; /* Fondo azul brillante para encabezados */
            color: white; /* Color de texto blanco */
        }
        tr:nth-child(even) {
            background-color: #f9f9f9; /* Filas alternas en gris claro */
        }
        form {
            display: flex;
            flex-direction: column;
            gap: 8px;
            max-width: 400px; /* Ampliado el ancho del formulario */
            margin-bottom: 16px;
        }
        button {
            background-color: #007bff; /* Color azul para botones */
            color: white;
            padding: 10px;
            border: none;
            cursor: pointer;
            border-radius: 5px; /* Bordes redondeados */
            transition: background-color 0.3s;
        }
        button:hover {
            background-color: #0056b3; /* Azul más oscuro al pasar el mouse */
        }
        /* Estilos del modal */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            justify-content: center;
            align-items: center;
        }
        .modal-content {
            background: white;
            padding: 20px;
            width: 400px; /* Ampliar el ancho del modal */
            border-radius: 8px; /* Bordes redondeados */
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1); /* Sombra suave */
        }
        input[type="text"] {
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
            width: 100%; /* Hacer que los inputs ocupen el 100% */
        }
    </style>
</head>
<body>

    <h2>Gestión de Métodos de Pago</h2>
    <table>
        <thead>
            <tr>
                <th>ID Método</th>
                <th>Tipo de Pago</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($metodos_pago)): ?>
                <tr>
                    <td colspan="3">No hay métodos de pago disponibles.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($metodos_pago as $metodo): ?>
                    <tr>
                        <td><?= $metodo['id_metodo_pago'] ?></td>
                        <td><?= htmlspecialchars($metodo['tipo_pago']) ?></td>
                        <td>
                            <form method="post" style="display:inline;">
                                <input type="hidden" name="id_metodo_pago" value="<?= $metodo['id_metodo_pago'] ?>">
                                <button type="submit" name="eliminar">Eliminar</button>
                            </form>
                            <button onclick="openEditModal(<?= $metodo['id_metodo_pago'] ?>, '<?= htmlspecialchars($metodo['tipo_pago']) ?>')">Editar</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <h3>Agregar Método de Pago</h3>
    <form method="post">
        <input type="text" name="tipo_pago" placeholder="Tipo de Pago" required>
        <button type="submit" name="agregar">Agregar Método de Pago</button>
    </form>

    <form action="<?= $home_url ?>" method="POST"> <!-- Acción para regresar -->
        <button type="submit">Regresar</button> 
    </form>

    <!-- Modal de Edición -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <h3>Editar Método de Pago</h3>
            <form id="editForm" method="post">
                <input type="hidden" name="id_metodo_pago" id="edit_id_metodo_pago">
                <input type="text" name="tipo_pago" id="edit_tipo_pago" placeholder="Tipo de Pago" required>
                <button type="submit" name="editar">Actualizar Método de Pago</button>
                <button type="button" onclick="closeEditModal()">Cerrar</button>
            </form>
        </div>
    </div>

    <script>
        function openEditModal(id, tipo_pago) {
            document.getElementById('edit_id_metodo_pago').value = id;
            document.getElementById('edit_tipo_pago').value = tipo_pago;

            document.getElementById('editModal').style.display = 'flex'; // Mostrar el modal
        }

        function closeEditModal() {
            document.getElementById('editModal').style.display = 'none'; // Ocultar el modal
        }
    </script>

</body>
</html>
