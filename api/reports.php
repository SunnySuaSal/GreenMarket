<?php
/**
 * API de Reportes
 * Endpoints: stats, sales
 */

require_once 'config.php';

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? 'stats';

try {
    requireAdmin();
    $db = getDBConnection();
    
    switch ($action) {
        case 'stats':
            if ($method !== 'GET') {
                errorResponse('Método no permitido', 405);
            }
            
            // Ventas totales
            $stmt = $db->prepare("SELECT COALESCE(SUM(total), 0) as total_sales FROM orders WHERE status != 'cancelled'");
            $stmt->execute();
            $totalSales = (float)$stmt->fetch()['total_sales'];
            
            // Pedidos completados
            $stmt = $db->prepare("SELECT COUNT(*) as count FROM orders WHERE status = 'delivered'");
            $stmt->execute();
            $completedOrders = (int)$stmt->fetch()['count'];
            
            // Productos vendidos
            $stmt = $db->prepare("SELECT COALESCE(SUM(quantity), 0) as total 
                                  FROM order_items oi
                                  INNER JOIN orders o ON oi.order_id = o.id
                                  WHERE o.status != 'cancelled'");
            $stmt->execute();
            $productsSold = (int)$stmt->fetch()['total'];
            
            // Total de productos
            $stmt = $db->prepare("SELECT COUNT(*) as count FROM products");
            $stmt->execute();
            $totalProducts = (int)$stmt->fetch()['count'];
            
            // Productos en stock
            $stmt = $db->prepare("SELECT COUNT(*) as count FROM products WHERE stock > 0");
            $stmt->execute();
            $productsInStock = (int)$stmt->fetch()['count'];
            
            // Total de categorías
            $stmt = $db->prepare("SELECT COUNT(*) as count FROM categories");
            $stmt->execute();
            $totalCategories = (int)$stmt->fetch()['count'];
            
            // Pedidos pendientes
            $stmt = $db->prepare("SELECT COUNT(*) as count FROM orders WHERE status = 'pending'");
            $stmt->execute();
            $pendingOrders = (int)$stmt->fetch()['count'];
            
            successResponse([
                'totalSales' => $totalSales,
                'completedOrders' => $completedOrders,
                'productsSold' => $productsSold,
                'totalProducts' => $totalProducts,
                'productsInStock' => $productsInStock,
                'totalCategories' => $totalCategories,
                'pendingOrders' => $pendingOrders
            ]);
            break;
            
        case 'sales':
            if ($method !== 'GET') {
                errorResponse('Método no permitido', 405);
            }
            
            $period = $_GET['period'] ?? 'month'; // month, week, year
            
            // Ventas por mes (últimos 12 meses)
            $query = "SELECT 
                        DATE_FORMAT(created_at, '%Y-%m') as month,
                        COUNT(*) as orders_count,
                        COALESCE(SUM(total), 0) as total_sales
                      FROM orders 
                      WHERE status != 'cancelled'
                      GROUP BY DATE_FORMAT(created_at, '%Y-%m')
                      ORDER BY month DESC
                      LIMIT 12";
            
            $stmt = $db->prepare($query);
            $stmt->execute();
            $sales = $stmt->fetchAll();
            
            // Formatear datos
            foreach ($sales as &$sale) {
                $sale['orders_count'] = (int)$sale['orders_count'];
                $sale['total_sales'] = (float)$sale['total_sales'];
            }
            
            successResponse($sales);
            break;
            
        case 'top-products':
            if ($method !== 'GET') {
                errorResponse('Método no permitido', 405);
            }
            
            $limit = intval($_GET['limit'] ?? 10);
            
            // Productos más vendidos
            $stmt = $db->prepare("SELECT 
                                    p.id,
                                    p.name,
                                    p.price,
                                    COALESCE(SUM(oi.quantity), 0) as total_sold,
                                    COALESCE(SUM(oi.subtotal), 0) as total_revenue
                                  FROM products p
                                  LEFT JOIN order_items oi ON p.id = oi.product_id
                                  LEFT JOIN orders o ON oi.order_id = o.id AND o.status != 'cancelled'
                                  GROUP BY p.id, p.name, p.price
                                  ORDER BY total_sold DESC
                                  LIMIT ?");
            $stmt->execute([$limit]);
            $topProducts = $stmt->fetchAll();
            
            // Formatear datos
            foreach ($topProducts as &$product) {
                $product['id'] = (int)$product['id'];
                $product['price'] = (float)$product['price'];
                $product['total_sold'] = (int)$product['total_sold'];
                $product['total_revenue'] = (float)$product['total_revenue'];
            }
            
            successResponse($topProducts);
            break;
            
        default:
            errorResponse('Acción no válida', 404);
    }
    
} catch (PDOException $e) {
    error_log("Error en reports.php: " . $e->getMessage());
    errorResponse('Error del servidor', 500);
} catch (Exception $e) {
    error_log("Error en reports.php: " . $e->getMessage());
    errorResponse('Error del servidor', 500);
}

