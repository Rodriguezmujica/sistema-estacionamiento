<?php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/../config/conexion.php';

// Solo los administradores pueden gestionar usuarios
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Acceso denegado']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {
        case 'GET':
            // Obtener todos los usuarios (sin la contraseña)
            //  Ocultar al super admin de la lista
            $sql = "SELECT id, usuario, rol FROM usuarios WHERE usuario != 'evelyn_dev' ORDER BY usuario ASC";
            $result = $conn->query($sql);
            $usuarios = [];
            while ($row = $result->fetch_assoc()) {
                $usuarios[] = $row;
            }
            echo json_encode(['success' => true, 'data' => $usuarios]);
            break;

        case 'POST':
            // Crear o actualizar un usuario
            $data = json_decode(file_get_contents('php://input'), true);

            // 🐛 DEBUG temporal
            error_log("DEBUG - Datos recibidos: " . json_encode($data));

            if (!isset($data['usuario'], $data['rol'])) {
                throw new Exception("Usuario y rol son requeridos.");
            }

            $id = isset($data['id']) && $data['id'] !== '' && $data['id'] !== null ? intval($data['id']) : null;
            $usuario = $conn->real_escape_string($data['usuario']);
            $rol = $conn->real_escape_string($data['rol']);
            // 🔧 CORRECCIÓN: Convertir 'operador' a 'cajero' para que coincida con la BD
            if ($rol === 'operador') { $rol = 'cajero'; }

            $password = isset($data['password']) && !empty($data['password']) ? $data['password'] : null;
            
            // 🐛 DEBUG temporal
            error_log("DEBUG - ID procesado: " . ($id ?? 'NULL') . " | Usuario: $usuario | Tiene password: " . ($password ? 'SI' : 'NO'));

            // 🔧 CORRECCIÓN: Verificar si el nombre de usuario ya existe en OTRO usuario
            if ($id) {
                // Modo edición: excluir el usuario actual de la búsqueda
                $checkSql = "SELECT id FROM usuarios WHERE usuario = ? AND id != ?";
                $checkStmt = $conn->prepare($checkSql);
                $checkStmt->bind_param("si", $usuario, $id);
            } else {
                // Modo creación: verificar si el nombre existe
                $checkSql = "SELECT id FROM usuarios WHERE usuario = ?";
                $checkStmt = $conn->prepare($checkSql);
                $checkStmt->bind_param("s", $usuario);
            }
            
            $checkStmt->execute();
            if ($checkStmt->get_result()->num_rows > 0) {
                throw new Exception("El nombre de usuario '$usuario' ya existe.");
            }
            $checkStmt->close();

            if ($id) {
                // Actualizar
                if ($password) {
                    $hash = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $conn->prepare("UPDATE usuarios SET usuario = ?, rol = ?, password_hash = ? WHERE id = ?");
                    $stmt->bind_param("sssi", $usuario, $rol, $hash, $id);
                } else {
                    $stmt = $conn->prepare("UPDATE usuarios SET usuario = ?, rol = ? WHERE id = ?"); // Sin cambio de clave
                    $stmt->bind_param("ssi", $usuario, $rol, $id);
                }
                $message = "Usuario actualizado correctamente.";
            } else {
                // Crear
                if (!$password) {
                    throw new Exception("La contraseña es requerida para nuevos usuarios.");
                }
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("INSERT INTO usuarios (usuario, password_hash, rol) VALUES (?, ?, ?)");
                $stmt->bind_param("sss", $usuario, $hash, $rol);
                $message = "Usuario creado correctamente.";
            }

            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => $message]);
            } else { throw new Exception("Error al ejecutar la consulta: " . $stmt->error); }
            $stmt->close();
            break;

        case 'DELETE':
            // Eliminar un usuario
            $id = $_GET['id'] ?? null;
            if (!$id) {
                throw new Exception("No se proporcionó ID para eliminar.");
            }

            $id = intval($id);

            // No permitir eliminar al usuario actual
            if (isset($_SESSION['id_usuario']) && $_SESSION['id_usuario'] == $id) {
                throw new Exception("No puedes eliminar tu propio usuario.");
            }

            // No permitir eliminar el último administrador
            $res = $conn->query("SELECT COUNT(*) as total_admins FROM usuarios WHERE rol = 'admin'");
            $total_admins = $res->fetch_assoc()['total_admins'];
            
            $res_user = $conn->query("SELECT rol FROM usuarios WHERE id = $id");
            $rol_user = $res_user->fetch_assoc()['rol'];

            // 🤫 Proteger al super admin para que no sea eliminado
            $res_super = $conn->query("SELECT usuario FROM usuarios WHERE id = $id");
            if ($res_super->fetch_assoc()['usuario'] === 'evelyn_dev') {
                throw new Exception("Este usuario no puede ser eliminado.");
            }

            if ($rol_user === 'admin' && $total_admins <= 1) {
                throw new Exception("No se puede eliminar al último administrador del sistema.");
            }

            $stmt = $conn->prepare("DELETE FROM usuarios WHERE id = ?");
            $stmt->bind_param("i", $id);

            if ($stmt->execute()) {
                if ($stmt->affected_rows > 0) {
                    echo json_encode(['success' => true, 'message' => 'Usuario eliminado correctamente.']);
                } else {
                    throw new Exception("No se encontró el usuario con ID $id para eliminar.");
                }
            } else {
                throw new Exception("Error al eliminar: " . $stmt->error);
            }
            $stmt->close();
            break;

        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Método no permitido']);
            break;
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

$conn->close();