<?php
/**
 * Configuración de la base de datos y constantes
 */

// Configuración de la base de datos
define('DB_HOST', 'localhost');
define('DB_NAME', 'greenmarket');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// Configuración de la aplicación
define('TAX_RATE', 0.08); // 8% de impuestos
define('FREE_SHIPPING_THRESHOLD', 25.00);
define('SHIPPING_COST', 3.99);

// Configuración de sesión
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 0); // Cambiar a 1 en producción con HTTPS

// Iniciar sesión
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Conexión a la base de datos
 */
function getDBConnection() {
    static $conn = null;
    
    if ($conn === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];
            
            $conn = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            error_log("Error de conexión a la base de datos: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'Error de conexión a la base de datos']);
            exit;
        }
    }
    
    return $conn;
}

/**
 * Respuesta JSON estándar
 */
function jsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

/**
 * Respuesta de error
 */
function errorResponse($message, $statusCode = 400) {
    jsonResponse(['error' => $message], $statusCode);
}

/**
 * Respuesta de éxito
 */
function successResponse($data = null, $message = null) {
    $response = ['success' => true];
    if ($message) {
        $response['message'] = $message;
    }
    if ($data !== null) {
        $response['data'] = $data;
    }
    jsonResponse($response);
}

/**
 * Verificar si el usuario está autenticado
 */
function requireAuth() {
    if (!isset($_SESSION['user_id'])) {
        errorResponse('No autenticado', 401);
    }
}

/**
 * Verificar si el usuario es administrador
 */
function requireAdmin() {
    requireAuth();
    if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
        errorResponse('Acceso denegado. Se requieren permisos de administrador', 403);
    }
}

/**
 * Obtener datos JSON del request
 */
function getJsonInput() {
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        errorResponse('JSON inválido: ' . json_last_error_msg());
    }
    
    return $data;
}

/**
 * Sanitizar entrada de texto
 */
function sanitize($input) {
    return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
}

/**
 * Validar email
 */
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

