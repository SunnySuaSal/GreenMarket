<?php
/**
 * API de Carrito de Compras
 * Endpoints: get, add, update, remove, clear
 */

require_once 'config.php';

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? 'get';

try {
    requireAuth();
    $db = getDBConnection();
    $userId = $_SESSION['user_id'];
    
    switch ($action) {
        case 'get':
            if ($method !== 'GET') {
                errorResponse('Método no permitido', 405);
            }
            
            // Obtener items del carrito con información del producto
            $stmt = $db->prepare("SELECT c.id, c.quantity, 
                                          p.id as product_id, p.name, p.price, p.image_url, 
                                          p.stock, p.seller, p.category_id,
                                          cat.name as category
                                   FROM cart c
                                   INNER JOIN products p ON c.product_id = p.id
                                   INNER JOIN categories cat ON p.category_id = cat.id
                                   WHERE c.user_id = ?");
            $stmt->execute([$userId]);
            $items = $stmt->fetchAll();
            
            // Formatear items
            $cart = [];
            foreach ($items as $item) {
                $cart[] = [
                    'id' => $item['product_id'],
                    'name' => $item['name'],
                    'price' => (float)$item['price'],
                    'image' => $item['image_url'],
                    'stock' => (int)$item['stock'],
                    'seller' => $item['seller'],
                    'category' => $item['category'],
                    'quantity' => (int)$item['quantity']
                ];
            }
            
            successResponse($cart);
            break;
            
        case 'add':
            if ($method !== 'POST') {
                errorResponse('Método no permitido', 405);
            }
            
            $data = getJsonInput();
            $productId = intval($data['productId'] ?? 0);
            $quantity = intval($data['quantity'] ?? 1);
            
            if (!$productId || $quantity < 1) {
                errorResponse('Producto y cantidad válidos son requeridos');
            }
            
            // Verificar que el producto existe y tiene stock
            $stmt = $db->prepare("SELECT id, stock FROM products WHERE id = ?");
            $stmt->execute([$productId]);
            $product = $stmt->fetch();
            
            if (!$product) {
                errorResponse('Producto no encontrado', 404);
            }
            
            // Verificar si ya existe en el carrito
            $stmt = $db->prepare("SELECT id, quantity FROM cart WHERE user_id = ? AND product_id = ?");
            $stmt->execute([$userId, $productId]);
            $existing = $stmt->fetch();
            
            if ($existing) {
                $newQuantity = $existing['quantity'] + $quantity;
                
                // Verificar stock disponible
                if ($newQuantity > $product['stock']) {
                    errorResponse('No hay suficiente stock disponible');
                }
                
                // Actualizar cantidad
                $stmt = $db->prepare("UPDATE cart SET quantity = ? WHERE id = ?");
                $stmt->execute([$newQuantity, $existing['id']]);
            } else {
                // Verificar stock disponible
                if ($quantity > $product['stock']) {
                    errorResponse('No hay suficiente stock disponible');
                }
                
                // Agregar al carrito
                $stmt = $db->prepare("INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, ?)");
                $stmt->execute([$userId, $productId, $quantity]);
            }
            
            successResponse(null, 'Producto agregado al carrito');
            break;
            
        case 'update':
            if ($method !== 'PUT' && $method !== 'POST') {
                errorResponse('Método no permitido', 405);
            }
            
            $data = getJsonInput();
            $productId = intval($data['productId'] ?? 0);
            $quantity = intval($data['quantity'] ?? 0);
            
            if (!$productId) {
                errorResponse('ID de producto requerido');
            }
            
            if ($quantity < 0) {
                errorResponse('Cantidad inválida');
            }
            
            // Si la cantidad es 0, eliminar del carrito
            if ($quantity === 0) {
                $stmt = $db->prepare("DELETE FROM cart WHERE user_id = ? AND product_id = ?");
                $stmt->execute([$userId, $productId]);
                successResponse(null, 'Producto eliminado del carrito');
                break;
            }
            
            // Verificar stock disponible
            $stmt = $db->prepare("SELECT stock FROM products WHERE id = ?");
            $stmt->execute([$productId]);
            $product = $stmt->fetch();
            
            if (!$product) {
                errorResponse('Producto no encontrado', 404);
            }
            
            if ($quantity > $product['stock']) {
                errorResponse('No hay suficiente stock disponible');
            }
            
            // Verificar que existe en el carrito
            $stmt = $db->prepare("SELECT id FROM cart WHERE user_id = ? AND product_id = ?");
            $stmt->execute([$userId, $productId]);
            if (!$stmt->fetch()) {
                errorResponse('Producto no está en el carrito', 404);
            }
            
            // Actualizar cantidad
            $stmt = $db->prepare("UPDATE cart SET quantity = ? WHERE user_id = ? AND product_id = ?");
            $stmt->execute([$quantity, $userId, $productId]);
            
            successResponse(null, 'Cantidad actualizada');
            break;
            
        case 'remove':
            if ($method !== 'DELETE' && $method !== 'POST') {
                errorResponse('Método no permitido', 405);
            }
            
            $productId = intval($_GET['productId'] ?? ($_POST['productId'] ?? 0));
            
            if (!$productId) {
                errorResponse('ID de producto requerido');
            }
            
            $stmt = $db->prepare("DELETE FROM cart WHERE user_id = ? AND product_id = ?");
            $stmt->execute([$userId, $productId]);
            
            successResponse(null, 'Producto eliminado del carrito');
            break;
            
        case 'clear':
            if ($method !== 'DELETE' && $method !== 'POST') {
                errorResponse('Método no permitido', 405);
            }
            
            $stmt = $db->prepare("DELETE FROM cart WHERE user_id = ?");
            $stmt->execute([$userId]);
            
            successResponse(null, 'Carrito vaciado');
            break;
            
        default:
            errorResponse('Acción no válida', 404);
    }
    
} catch (PDOException $e) {
    error_log("Error en cart.php: " . $e->getMessage());
    errorResponse('Error del servidor', 500);
} catch (Exception $e) {
    error_log("Error en cart.php: " . $e->getMessage());
    errorResponse('Error del servidor', 500);
}

