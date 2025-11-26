<?php
/**
 * API de Autenticación
 * Endpoints: login, register, logout, check
 */

require_once 'config.php';

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

try {
    $db = getDBConnection();
    
    switch ($action) {
        case 'login':
            if ($method !== 'POST') {
                errorResponse('Método no permitido', 405);
            }
            
            $data = getJsonInput();
            $email = $data['email'] ?? '';
            $password = $data['password'] ?? '';
            
            if (empty($email) || empty($password)) {
                errorResponse('Email y contraseña son requeridos');
            }
            
            if (!validateEmail($email)) {
                errorResponse('Email inválido');
            }
            
            // Buscar usuario
            $stmt = $db->prepare("SELECT id, name, email, password, role FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            
            if (!$user) {
                errorResponse('Credenciales inválidas', 401);
            }
            
            // Verificar contraseña
            if (!password_verify($password, $user['password'])) {
                errorResponse('Credenciales inválidas', 401);
            }
            
            // Crear sesión
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_role'] = $user['role'];
            
            // Retornar datos del usuario (sin contraseña)
            unset($user['password']);
            successResponse($user, 'Login exitoso');
            break;
            
        case 'register':
            if ($method !== 'POST') {
                errorResponse('Método no permitido', 405);
            }
            
            $data = getJsonInput();
            $name = sanitize($data['name'] ?? '');
            $email = $data['email'] ?? '';
            $password = $data['password'] ?? '';
            $confirmPassword = $data['confirmPassword'] ?? '';
            
            // Validaciones
            if (empty($name) || empty($email) || empty($password)) {
                errorResponse('Todos los campos son requeridos');
            }
            
            if (!validateEmail($email)) {
                errorResponse('Email inválido');
            }
            
            if (strlen($password) < 8) {
                errorResponse('La contraseña debe tener al menos 8 caracteres');
            }
            
            if ($password !== $confirmPassword) {
                errorResponse('Las contraseñas no coinciden');
            }
            
            // Verificar si el email ya existe
            $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                errorResponse('El email ya está registrado');
            }
            
            // Crear usuario
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $db->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, 'user')");
            $stmt->execute([$name, $email, $hashedPassword]);
            
            $userId = $db->lastInsertId();
            
            // Crear sesión
            $_SESSION['user_id'] = $userId;
            $_SESSION['user_email'] = $email;
            $_SESSION['user_name'] = $name;
            $_SESSION['user_role'] = 'user';
            
            successResponse([
                'id' => $userId,
                'name' => $name,
                'email' => $email,
                'role' => 'user'
            ], 'Registro exitoso');
            break;
            
        case 'logout':
            if ($method !== 'POST') {
                errorResponse('Método no permitido', 405);
            }
            
            session_destroy();
            successResponse(null, 'Logout exitoso');
            break;
            
        case 'check':
            if ($method !== 'GET') {
                errorResponse('Método no permitido', 405);
            }
            
            if (isset($_SESSION['user_id'])) {
                $stmt = $db->prepare("SELECT id, name, email, role FROM users WHERE id = ?");
                $stmt->execute([$_SESSION['user_id']]);
                $user = $stmt->fetch();
                
                if ($user) {
                    successResponse($user);
                }
            }
            
            jsonResponse(['authenticated' => false], 200);
            break;
            
        default:
            errorResponse('Acción no válida', 404);
    }
    
} catch (PDOException $e) {
    error_log("Error en auth.php: " . $e->getMessage());
    errorResponse('Error del servidor', 500);
} catch (Exception $e) {
    error_log("Error en auth.php: " . $e->getMessage());
    errorResponse('Error del servidor', 500);
}

