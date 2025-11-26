<?php
/**
 * API de Pedidos
 * Endpoints: list, get, create, update
 */

require_once 'config.php';

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? 'list';

try {
    $db = getDBConnection();
    
    switch ($action) {
        case 'list':
            if ($method !== 'GET') {
                errorResponse('Método no permitido', 405);
            }
            
            // Si es admin, puede ver todos los pedidos, si no, solo los suyos
            if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin') {
                $stmt = $db->prepare("SELECT o.*, u.name as user_name, u.email as user_email 
                                      FROM orders o 
                                      INNER JOIN users u ON o.user_id = u.id 
                                      ORDER BY o.created_at DESC");
                $stmt->execute();
            } else {
                requireAuth();
                $userId = $_SESSION['user_id'];
                $stmt = $db->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC");
                $stmt->execute([$userId]);
            }
            
            $orders = $stmt->fetchAll();
            
            // Obtener items de cada pedido
            foreach ($orders as &$order) {
                $stmt = $db->prepare("SELECT oi.*, p.name, p.image_url, p.seller, cat.name as category
                                       FROM order_items oi
                                       INNER JOIN products p ON oi.product_id = p.id
                                       INNER JOIN categories cat ON p.category_id = cat.id
                                       WHERE oi.order_id = ?");
                $stmt->execute([$order['id']]);
                $items = $stmt->fetchAll();
                
                // Formatear items
                $order['items'] = [];
                foreach ($items as $item) {
                    $order['items'][] = [
                        'id' => (int)$item['product_id'],
                        'name' => $item['name'],
                        'price' => (float)$item['price'],
                        'quantity' => (int)$item['quantity'],
                        'image' => $item['image_url'],
                        'seller' => $item['seller'],
                        'category' => $item['category']
                    ];
                }
                
                // Formatear pedido
                $order['id'] = (int)$order['id'];
                $order['total'] = (float)$order['total'];
                $order['subtotal'] = (float)$order['subtotal'];
                $order['shipping'] = (float)$order['shipping'];
                $order['tax'] = (float)$order['tax'];
                $order['date'] = date('d/m/Y', strtotime($order['created_at']));
            }
            
            successResponse($orders);
            break;
            
        case 'get':
            if ($method !== 'GET') {
                errorResponse('Método no permitido', 405);
            }
            
            $id = intval($_GET['id'] ?? 0);
            if (!$id) {
                errorResponse('ID de pedido requerido');
            }
            
            // Verificar permisos
            if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin') {
                $stmt = $db->prepare("SELECT o.*, u.name as user_name, u.email as user_email 
                                      FROM orders o 
                                      INNER JOIN users u ON o.user_id = u.id 
                                      WHERE o.id = ?");
                $stmt->execute([$id]);
            } else {
                requireAuth();
                $userId = $_SESSION['user_id'];
                $stmt = $db->prepare("SELECT * FROM orders WHERE id = ? AND user_id = ?");
                $stmt->execute([$id, $userId]);
            }
            
            $order = $stmt->fetch();
            
            if (!$order) {
                errorResponse('Pedido no encontrado', 404);
            }
            
            // Obtener items del pedido
            $stmt = $db->prepare("SELECT oi.*, p.name, p.image_url, p.seller, cat.name as category
                                   FROM order_items oi
                                   INNER JOIN products p ON oi.product_id = p.id
                                   INNER JOIN categories cat ON p.category_id = cat.id
                                   WHERE oi.order_id = ?");
            $stmt->execute([$order['id']]);
            $items = $stmt->fetchAll();
            
            // Formatear items
            $order['items'] = [];
            foreach ($items as $item) {
                $order['items'][] = [
                    'id' => (int)$item['product_id'],
                    'name' => $item['name'],
                    'price' => (float)$item['price'],
                    'quantity' => (int)$item['quantity'],
                    'image' => $item['image_url'],
                    'seller' => $item['seller'],
                    'category' => $item['category']
                ];
            }
            
            // Formatear pedido
            $order['id'] = (int)$order['id'];
            $order['total'] = (float)$order['total'];
            $order['subtotal'] = (float)$order['subtotal'];
            $order['shipping'] = (float)$order['shipping'];
            $order['tax'] = (float)$order['tax'];
            $order['date'] = date('d/m/Y', strtotime($order['created_at']));
            
            successResponse($order);
            break;
            
        case 'create':
            requireAuth();
            
            if ($method !== 'POST') {
                errorResponse('Método no permitido', 405);
            }
            
            $userId = $_SESSION['user_id'];
            
            // Obtener items del carrito
            $stmt = $db->prepare("SELECT c.*, p.price, p.stock, p.name
                                   FROM cart c
                                   INNER JOIN products p ON c.product_id = p.id
                                   WHERE c.user_id = ?");
            $stmt->execute([$userId]);
            $cartItems = $stmt->fetchAll();
            
            if (empty($cartItems)) {
                errorResponse('El carrito está vacío');
            }
            
            // Verificar stock y calcular totales
            $subtotal = 0;
            $items = [];
            
            foreach ($cartItems as $item) {
                if ($item['quantity'] > $item['stock']) {
                    errorResponse("No hay suficiente stock para: {$item['name']}");
                }
                
                $itemSubtotal = $item['price'] * $item['quantity'];
                $subtotal += $itemSubtotal;
                
                $items[] = [
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'price' => $item['price'],
                    'subtotal' => $itemSubtotal
                ];
            }
            
            // Calcular envío e impuestos
            $shipping = $subtotal >= FREE_SHIPPING_THRESHOLD ? 0 : SHIPPING_COST;
            $tax = $subtotal * TAX_RATE;
            $total = $subtotal + $shipping + $tax;
            
            // Iniciar transacción
            $db->beginTransaction();
            
            try {
                // Crear pedido
                $stmt = $db->prepare("INSERT INTO orders (user_id, total, subtotal, shipping, tax, status) 
                                      VALUES (?, ?, ?, ?, ?, 'pending')");
                $stmt->execute([$userId, $total, $subtotal, $shipping, $tax]);
                $orderId = $db->lastInsertId();
                
                // Crear items del pedido y actualizar stock
                foreach ($items as $item) {
                    // Insertar item
                    $stmt = $db->prepare("INSERT INTO order_items (order_id, product_id, quantity, price, subtotal) 
                                          VALUES (?, ?, ?, ?, ?)");
                    $stmt->execute([
                        $orderId,
                        $item['product_id'],
                        $item['quantity'],
                        $item['price'],
                        $item['subtotal']
                    ]);
                    
                    // Actualizar stock
                    $stmt = $db->prepare("UPDATE products SET stock = stock - ? WHERE id = ?");
                    $stmt->execute([$item['quantity'], $item['product_id']]);
                }
                
                // Vaciar carrito
                $stmt = $db->prepare("DELETE FROM cart WHERE user_id = ?");
                $stmt->execute([$userId]);
                
                // Confirmar transacción
                $db->commit();
                
                // Obtener pedido creado
                $stmt = $db->prepare("SELECT * FROM orders WHERE id = ?");
                $stmt->execute([$orderId]);
                $order = $stmt->fetch();
                
                // Obtener items del pedido
                $stmt = $db->prepare("SELECT oi.*, p.name, p.image_url, p.seller, cat.name as category
                                       FROM order_items oi
                                       INNER JOIN products p ON oi.product_id = p.id
                                       INNER JOIN categories cat ON p.category_id = cat.id
                                       WHERE oi.order_id = ?");
                $stmt->execute([$orderId]);
                $orderItems = $stmt->fetchAll();
                
                // Formatear items
                $order['items'] = [];
                foreach ($orderItems as $item) {
                    $order['items'][] = [
                        'id' => (int)$item['product_id'],
                        'name' => $item['name'],
                        'price' => (float)$item['price'],
                        'quantity' => (int)$item['quantity'],
                        'image' => $item['image_url'],
                        'seller' => $item['seller'],
                        'category' => $item['category']
                    ];
                }
                
                // Formatear pedido
                $order['id'] = (int)$order['id'];
                $order['total'] = (float)$order['total'];
                $order['subtotal'] = (float)$order['subtotal'];
                $order['shipping'] = (float)$order['shipping'];
                $order['tax'] = (float)$order['tax'];
                $order['date'] = date('d/m/Y', strtotime($order['created_at']));
                
                successResponse($order, 'Pedido creado exitosamente');
                
            } catch (Exception $e) {
                $db->rollBack();
                throw $e;
            }
            break;
            
        case 'update':
            requireAdmin();
            
            if ($method !== 'PUT' && $method !== 'POST') {
                errorResponse('Método no permitido', 405);
            }
            
            $data = getJsonInput();
            $id = intval($data['id'] ?? 0);
            $status = $data['status'] ?? '';
            
            if (!$id) {
                errorResponse('ID de pedido requerido');
            }
            
            $validStatuses = ['pending', 'confirmed', 'delivered', 'cancelled'];
            if (!in_array($status, $validStatuses)) {
                errorResponse('Estado inválido');
            }
            
            // Verificar que el pedido existe
            $stmt = $db->prepare("SELECT id FROM orders WHERE id = ?");
            $stmt->execute([$id]);
            if (!$stmt->fetch()) {
                errorResponse('Pedido no encontrado', 404);
            }
            
            // Actualizar estado
            $stmt = $db->prepare("UPDATE orders SET status = ? WHERE id = ?");
            $stmt->execute([$status, $id]);
            
            successResponse(null, 'Estado del pedido actualizado');
            break;
            
        default:
            errorResponse('Acción no válida', 404);
    }
    
} catch (PDOException $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    error_log("Error en orders.php: " . $e->getMessage());
    errorResponse('Error del servidor', 500);
} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    error_log("Error en orders.php: " . $e->getMessage());
    errorResponse('Error del servidor', 500);
}

