<?php
/**
 * API de Productos
 * Endpoints: list, get, create, update, delete
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
$action = $_GET['action'] ?? 'list';

try {
    $db = getDBConnection();
    
    switch ($action) {
        case 'list':
            if ($method !== 'GET') {
                errorResponse('Método no permitido', 405);
            }
            
            // Parámetros de búsqueda y filtrado
            $search = $_GET['search'] ?? '';
            $category = $_GET['category'] ?? '';
            $sort = $_GET['sort'] ?? 'name';
            
            // Construir query
            $query = "SELECT p.*, c.name as category_name 
                      FROM products p 
                      INNER JOIN categories c ON p.category_id = c.id 
                      WHERE 1=1";
            $params = [];
            
            // Búsqueda
            if (!empty($search)) {
                $query .= " AND (p.name LIKE ? OR p.description LIKE ? OR p.seller LIKE ?)";
                $searchTerm = "%$search%";
                $params[] = $searchTerm;
                $params[] = $searchTerm;
                $params[] = $searchTerm;
            }
            
            // Filtro por categoría
            if (!empty($category) && $category !== 'all') {
                $query .= " AND c.name = ?";
                $params[] = $category;
            }
            
            // Ordenamiento
            $sortMap = [
                'name' => 'p.name ASC',
                'price-low' => 'p.price ASC',
                'price-high' => 'p.price DESC',
                'rating' => 'p.rating DESC'
            ];
            $orderBy = $sortMap[$sort] ?? 'p.name ASC';
            $query .= " ORDER BY $orderBy";
            
            $stmt = $db->prepare($query);
            $stmt->execute($params);
            $products = $stmt->fetchAll();
            
            // Formatear productos
            foreach ($products as &$product) {
                $product['id'] = (int)$product['id'];
                $product['price'] = (float)$product['price'];
                $product['stock'] = (int)$product['stock'];
                $product['rating'] = (float)$product['rating'];
                $product['reviews'] = (int)$product['reviews_count'];
                $product['category'] = $product['category_name'];
                $product['image'] = $product['image_url'];
                unset($product['category_id'], $product['category_name'], $product['image_url'], $product['reviews_count']);
            }
            
            successResponse($products);
            break;
            
        case 'get':
            if ($method !== 'GET') {
                errorResponse('Método no permitido', 405);
            }
            
            $id = $_GET['id'] ?? 0;
            if (!$id) {
                errorResponse('ID de producto requerido');
            }
            
            $stmt = $db->prepare("SELECT p.*, c.name as category_name 
                                  FROM products p 
                                  INNER JOIN categories c ON p.category_id = c.id 
                                  WHERE p.id = ?");
            $stmt->execute([$id]);
            $product = $stmt->fetch();
            
            if (!$product) {
                errorResponse('Producto no encontrado', 404);
            }
            
            // Formatear producto
            $product['id'] = (int)$product['id'];
            $product['price'] = (float)$product['price'];
            $product['stock'] = (int)$product['stock'];
            $product['rating'] = (float)$product['rating'];
            $product['reviews'] = (int)$product['reviews_count'];
            $product['category'] = $product['category_name'];
            $product['image'] = $product['image_url'];
            unset($product['category_id'], $product['category_name'], $product['image_url'], $product['reviews_count']);
            
            successResponse($product);
            break;
            
        case 'create':
            requireAdmin();
            
            if ($method !== 'POST') {
                errorResponse('Método no permitido', 405);
            }
            
            $data = getJsonInput();
            
            $name = sanitize($data['name'] ?? '');
            $description = sanitize($data['description'] ?? '');
            $price = floatval($data['price'] ?? 0);
            $category = sanitize($data['category'] ?? '');
            $seller = sanitize($data['seller'] ?? '');
            $stock = intval($data['stock'] ?? 0);
            $image = $data['image'] ?? '';
            
            // Validaciones
            if (empty($name) || empty($description) || $price <= 0 || empty($category) || empty($seller) || $stock < 0) {
                errorResponse('Todos los campos son requeridos y válidos');
            }
            
            // Obtener ID de categoría
            $stmt = $db->prepare("SELECT id FROM categories WHERE name = ?");
            $stmt->execute([$category]);
            $categoryData = $stmt->fetch();
            
            if (!$categoryData) {
                errorResponse('Categoría no válida');
            }
            
            $categoryId = $categoryData['id'];
            
            // Insertar producto
            $stmt = $db->prepare("INSERT INTO products (name, description, price, category_id, seller, stock, image_url, rating, reviews_count) 
                                  VALUES (?, ?, ?, ?, ?, ?, ?, 4.5, 0)");
            $stmt->execute([$name, $description, $price, $categoryId, $seller, $stock, $image]);
            
            $productId = $db->lastInsertId();
            
            // Obtener producto creado
            $stmt = $db->prepare("SELECT p.*, c.name as category_name 
                                  FROM products p 
                                  INNER JOIN categories c ON p.category_id = c.id 
                                  WHERE p.id = ?");
            $stmt->execute([$productId]);
            $product = $stmt->fetch();
            
            // Formatear producto
            $product['id'] = (int)$product['id'];
            $product['price'] = (float)$product['price'];
            $product['stock'] = (int)$product['stock'];
            $product['rating'] = (float)$product['rating'];
            $product['reviews'] = (int)$product['reviews_count'];
            $product['category'] = $product['category_name'];
            $product['image'] = $product['image_url'];
            unset($product['category_id'], $product['category_name'], $product['image_url'], $product['reviews_count']);
            
            successResponse($product, 'Producto creado exitosamente');
            break;
            
        case 'update':
            requireAdmin();
            
            if ($method !== 'PUT' && $method !== 'POST') {
                errorResponse('Método no permitido', 405);
            }
            
            $data = getJsonInput();
            $id = intval($data['id'] ?? 0);
            
            if (!$id) {
                errorResponse('ID de producto requerido');
            }
            
            // Verificar que el producto existe
            $stmt = $db->prepare("SELECT id FROM products WHERE id = ?");
            $stmt->execute([$id]);
            if (!$stmt->fetch()) {
                errorResponse('Producto no encontrado', 404);
            }
            
            $name = sanitize($data['name'] ?? '');
            $description = sanitize($data['description'] ?? '');
            $price = floatval($data['price'] ?? 0);
            $category = sanitize($data['category'] ?? '');
            $seller = sanitize($data['seller'] ?? '');
            $stock = intval($data['stock'] ?? 0);
            $image = $data['image'] ?? '';
            
            // Validaciones
            if (empty($name) || empty($description) || $price <= 0 || empty($category) || empty($seller) || $stock < 0) {
                errorResponse('Todos los campos son requeridos y válidos');
            }
            
            // Obtener ID de categoría
            $stmt = $db->prepare("SELECT id FROM categories WHERE name = ?");
            $stmt->execute([$category]);
            $categoryData = $stmt->fetch();
            
            if (!$categoryData) {
                errorResponse('Categoría no válida');
            }
            
            $categoryId = $categoryData['id'];
            
            // Actualizar producto
            $stmt = $db->prepare("UPDATE products 
                                  SET name = ?, description = ?, price = ?, category_id = ?, seller = ?, stock = ?, image_url = ? 
                                  WHERE id = ?");
            $stmt->execute([$name, $description, $price, $categoryId, $seller, $stock, $image, $id]);
            
            // Obtener producto actualizado
            $stmt = $db->prepare("SELECT p.*, c.name as category_name 
                                  FROM products p 
                                  INNER JOIN categories c ON p.category_id = c.id 
                                  WHERE p.id = ?");
            $stmt->execute([$id]);
            $product = $stmt->fetch();
            
            // Formatear producto
            $product['id'] = (int)$product['id'];
            $product['price'] = (float)$product['price'];
            $product['stock'] = (int)$product['stock'];
            $product['rating'] = (float)$product['rating'];
            $product['reviews'] = (int)$product['reviews_count'];
            $product['category'] = $product['category_name'];
            $product['image'] = $product['image_url'];
            unset($product['category_id'], $product['category_name'], $product['image_url'], $product['reviews_count']);
            
            successResponse($product, 'Producto actualizado exitosamente');
            break;
            
        case 'delete':
            requireAdmin();
            
            if ($method !== 'DELETE' && $method !== 'POST') {
                errorResponse('Método no permitido', 405);
            }
            
            $id = intval($_GET['id'] ?? ($_POST['id'] ?? 0));
            
            if (!$id) {
                errorResponse('ID de producto requerido');
            }
            
            // Verificar que el producto existe
            $stmt = $db->prepare("SELECT id FROM products WHERE id = ?");
            $stmt->execute([$id]);
            if (!$stmt->fetch()) {
                errorResponse('Producto no encontrado', 404);
            }
            
            // Eliminar producto
            $stmt = $db->prepare("DELETE FROM products WHERE id = ?");
            $stmt->execute([$id]);
            
            successResponse(null, 'Producto eliminado exitosamente');
            break;
            
        case 'categories':
            if ($method !== 'GET') {
                errorResponse('Método no permitido', 405);
            }
            
            $stmt = $db->prepare("SELECT name FROM categories ORDER BY name");
            $stmt->execute();
            $categories = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            successResponse($categories);
            break;
            
        default:
            errorResponse('Acción no válida', 404);
    }
    
} catch (PDOException $e) {
    error_log("Error en products.php: " . $e->getMessage());
    errorResponse('Error del servidor', 500);
} catch (Exception $e) {
    error_log("Error en products.php: " . $e->getMessage());
    errorResponse('Error del servidor', 500);
}

