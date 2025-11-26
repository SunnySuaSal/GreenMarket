# 🏗️ Arquitectura Técnica - GreenMarket

## 📋 Índice
1. [Arquitectura General](#arquitectura-general)
2. [Frontend](#frontend)
3. [Backend](#backend)
4. [Base de Datos](#base-de-datos)
5. [Flujos de Datos](#flujos-de-datos)
6. [Seguridad](#seguridad)
7. [API REST](#api-rest)

---

## 🏛️ Arquitectura General

### Patrón Arquitectónico
**Cliente-Servidor con API REST**

```
┌─────────────┐         HTTP/JSON        ┌─────────────┐
│   Cliente   │ ◄──────────────────────► │   Servidor  │
│  (Frontend) │                          │  (Backend)  │
│             │                          │             │
│  JavaScript │                          │    PHP      │
│   Vanilla   │                          │   + MySQL   │
└─────────────┘                          └─────────────┘
```

### Stack Tecnológico

**Frontend:**
- HTML5 (Semántico)
- CSS3 (Variables, Grid, Flexbox)
- JavaScript ES6+ (Vanilla, sin frameworks)
- Fetch API para comunicación HTTP
- LocalStorage (solo para estado temporal)

**Backend:**
- PHP 8.5+ (Server-side)
- MySQL 9.5+ (Base de datos relacional)
- PDO (PHP Data Objects) para acceso a BD
- Sesiones PHP para autenticación

**Protocolo:**
- HTTP/HTTPS
- JSON para intercambio de datos
- RESTful API

---

## 🎨 Frontend

### Estructura del Código

#### 1. **Clase AppState (Estado Global)**
```javascript
class AppState {
  constructor() {
    this.currentScreen = 'login';
    this.userRole = 'guest';
    this.cart = [];
    this.orders = [];
    this.products = [];
    this.currentUser = null;
  }
}
```

**Responsabilidades:**
- Gestión del estado de la aplicación (SPA - Single Page Application)
- Navegación entre pantallas sin recargar la página
- Coordinación entre componentes
- Comunicación con el backend

#### 2. **Función API Helper**
```javascript
async function apiCall(endpoint, method = 'GET', data = null) {
  const options = {
    method,
    headers: { 'Content-Type': 'application/json' },
    credentials: 'include' // Para mantener sesiones PHP
  };
  
  if (data) options.body = JSON.stringify(data);
  
  const response = await fetch(`${CONFIG.API_BASE_URL}/${endpoint}`, options);
  return await response.json();
}
```

**Características:**
- Abstracción de Fetch API
- Manejo centralizado de errores
- Soporte para métodos HTTP (GET, POST, PUT, DELETE)
- Envío automático de credenciales (cookies de sesión)

#### 3. **Renderizado Dinámico**

**Patrón: Virtual DOM Manual**
- No usa frameworks (React, Vue, etc.)
- Manipulación directa del DOM
- Templates como strings de JavaScript
- Actualización selectiva de elementos

**Ejemplo:**
```javascript
createProductCard(product) {
  return `
    <div class="product-card">
      <h3>${product.name}</h3>
      <p>$${product.price.toFixed(2)}</p>
      <button onclick="app.addToCart(${product.id})">
        Agregar al Carrito
      </button>
    </div>
  `;
}
```

#### 4. **Gestión de Estado**

**Estado Local vs Remoto:**
- **Local (Frontend):** Estado de UI, pantalla actual, filtros activos
- **Remoto (Backend):** Productos, carrito, pedidos, usuarios

**Sincronización:**
- Cada acción modifica el estado local inmediatamente
- Luego sincroniza con el backend
- En caso de error, revierte el estado local

#### 5. **Manejo de Autenticación**

**Flujo:**
1. Usuario envía credenciales → `POST /api/auth.php?action=login`
2. Backend valida y crea sesión PHP
3. Backend retorna datos del usuario (sin contraseña)
4. Frontend guarda datos en `this.currentUser`
5. Cookie `PHPSESSID` se envía automáticamente en requests subsecuentes

**Roles:**
- `guest`: Solo lectura
- `user`: Compra y pedidos
- `admin`: Gestión completa

---

## ⚙️ Backend

### Arquitectura del Backend

#### 1. **Estructura de Archivos**
```
api/
├── config.php      # Configuración y utilidades
├── auth.php        # Autenticación
├── products.php    # CRUD de productos
├── cart.php        # Gestión de carrito
├── orders.php      # Gestión de pedidos
└── reports.php     # Reportes (admin)
```

#### 2. **config.php - Núcleo del Backend**

**Funciones Principales:**

```php
// Conexión a BD con Singleton Pattern
function getDBConnection() {
    static $conn = null;
    if ($conn === null) {
        $conn = new PDO($dsn, $user, $pass, $options);
    }
    return $conn;
}

// Respuestas JSON estandarizadas
function jsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

// Middleware de autenticación
function requireAuth() {
    if (!isset($_SESSION['user_id'])) {
        errorResponse('No autenticado', 401);
    }
}

// Middleware de autorización
function requireAdmin() {
    requireAuth();
    if ($_SESSION['user_role'] !== 'admin') {
        errorResponse('Acceso denegado', 403);
    }
}
```

**Patrones de Diseño:**
- **Singleton:** Conexión a BD (una sola instancia)
- **Middleware:** Verificación de permisos
- **Factory:** Creación de respuestas estandarizadas

#### 3. **Sistema de Autenticación**

**Tecnología:** Sesiones PHP nativas

**Flujo de Login:**
```php
// 1. Recibir credenciales
$email = $data['email'];
$password = $data['password'];

// 2. Buscar usuario en BD
$stmt = $db->prepare("SELECT * FROM users WHERE email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch();

// 3. Verificar contraseña (bcrypt)
if (password_verify($password, $user['password'])) {
    // 4. Crear sesión
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_role'] = $user['role'];
    // ...
}
```

**Seguridad:**
- Contraseñas hasheadas con `password_hash()` (bcrypt)
- Verificación con `password_verify()`
- Sesiones con cookies HttpOnly
- Validación de entrada (sanitización)

#### 4. **Endpoints REST**

**Convenciones:**
- `GET`: Lectura de datos
- `POST`: Creación de recursos
- `PUT`: Actualización completa
- `DELETE`: Eliminación

**Estructura de URLs:**
```
/api/{recurso}.php?action={accion}&{parametros}
```

**Ejemplos:**
- `GET /api/products.php?action=list&search=manzana`
- `POST /api/products.php?action=create`
- `GET /api/orders.php?action=list`

#### 5. **Manejo de Transacciones**

**Ejemplo en orders.php:**
```php
$db->beginTransaction();
try {
    // Crear pedido
    $stmt = $db->prepare("INSERT INTO orders ...");
    $stmt->execute([...]);
    
    // Crear items
    foreach ($items as $item) {
        $stmt = $db->prepare("INSERT INTO order_items ...");
        $stmt->execute([...]);
        
        // Actualizar stock
        $stmt = $db->prepare("UPDATE products SET stock = stock - ? ...");
        $stmt->execute([...]);
    }
    
    // Vaciar carrito
    $stmt = $db->prepare("DELETE FROM cart ...");
    $stmt->execute([...]);
    
    $db->commit();
} catch (Exception $e) {
    $db->rollBack();
    throw $e;
}
```

**Garantías ACID:**
- **Atomicidad:** Todo o nada
- **Consistencia:** Stock siempre correcto
- **Aislamiento:** Transacciones concurrentes no interfieren
- **Durabilidad:** Cambios persistentes

---

## 🗄️ Base de Datos

### Modelo Relacional

#### **Diagrama ER Simplificado:**

```
users (1) ──< (N) orders
users (1) ──< (N) cart
orders (1) ──< (N) order_items
products (1) ──< (N) order_items
products (1) ──< (N) cart
categories (1) ──< (N) products
```

#### **Tablas Principales:**

**1. users**
```sql
- id (PK, AUTO_INCREMENT)
- name (VARCHAR 255)
- email (VARCHAR 255, UNIQUE)
- password (VARCHAR 255, bcrypt hash)
- role (ENUM: 'user', 'admin')
- created_at, updated_at (TIMESTAMP)
```

**2. products**
```sql
- id (PK, AUTO_INCREMENT)
- name (VARCHAR 255)
- description (TEXT)
- price (DECIMAL 10,2)
- category_id (FK → categories.id)
- seller (VARCHAR 255)
- stock (INT)
- image_url (VARCHAR 500)
- rating (DECIMAL 3,2)
- reviews_count (INT)
```

**3. orders**
```sql
- id (PK, AUTO_INCREMENT)
- user_id (FK → users.id, CASCADE DELETE)
- total (DECIMAL 10,2)
- subtotal (DECIMAL 10,2)
- shipping (DECIMAL 10,2)
- tax (DECIMAL 10,2)
- status (ENUM: 'pending', 'confirmed', 'delivered', 'cancelled')
```

**4. order_items**
```sql
- id (PK, AUTO_INCREMENT)
- order_id (FK → orders.id, CASCADE DELETE)
- product_id (FK → products.id, RESTRICT)
- quantity (INT)
- price (DECIMAL 10,2) -- Precio al momento de la compra
- subtotal (DECIMAL 10,2)
```

**5. cart**
```sql
- id (PK, AUTO_INCREMENT)
- user_id (FK → users.id, CASCADE DELETE)
- product_id (FK → products.id, CASCADE DELETE)
- quantity (INT)
- UNIQUE KEY (user_id, product_id)
```

### Índices y Optimización

**Índices Creados:**
```sql
-- Búsquedas rápidas
INDEX idx_email ON users(email)
INDEX idx_category ON products(category_id)
INDEX idx_user ON orders(user_id)
INDEX idx_status ON orders(status)

-- Unicidad
UNIQUE KEY unique_user_product ON cart(user_id, product_id)
```

**Optimizaciones:**
- Índices en claves foráneas
- Índices en campos de búsqueda frecuente
- Constraints para integridad referencial

---

## 🔄 Flujos de Datos

### 1. **Flujo de Login**

```
Usuario → Frontend → POST /api/auth.php?action=login
                    ↓
                 Backend valida credenciales
                    ↓
                 Crea sesión PHP
                    ↓
                 Retorna JSON con datos usuario
                    ↓
Frontend → Actualiza estado → Navega a catálogo
```

### 2. **Flujo de Compra**

```
Usuario agrega producto → Frontend → POST /api/cart.php?action=add
                                    ↓
                                 Backend valida stock
                                    ↓
                                 Inserta en tabla cart
                                    ↓
Frontend ← Retorna éxito ← Actualiza UI
                                    ↓
Usuario finaliza compra → POST /api/orders.php?action=create
                                    ↓
                                 Backend inicia TRANSACCIÓN
                                    ↓
                                 1. Crea order
                                 2. Crea order_items
                                 3. Actualiza stock (products)
                                 4. Elimina cart
                                    ↓
                                 COMMIT transacción
                                    ↓
Frontend ← Retorna order completo ← Navega a "Mis Pedidos"
```

### 3. **Flujo de Búsqueda de Productos**

```
Usuario escribe → Frontend (debounce 300ms)
                    ↓
                 GET /api/products.php?action=list&search=term&category=X&sort=Y
                    ↓
                 Backend ejecuta query SQL con LIKE
                    ↓
                 Retorna array JSON de productos
                    ↓
Frontend → Renderiza productos → Actualiza DOM
```

### 4. **Flujo de Gestión Admin**

```
Admin crea producto → Frontend → POST /api/products.php?action=create
                                  ↓
                               Backend valida permisos (requireAdmin)
                                  ↓
                               Valida datos de entrada
                                  ↓
                               INSERT INTO products
                                  ↓
Frontend ← Retorna producto creado ← Actualiza lista admin
```

---

## 🔒 Seguridad

### Frontend

**1. Validación de Entrada:**
- Validación HTML5 (required, type, min, max)
- Validación JavaScript antes de enviar
- Sanitización de output (escapado de HTML)

**2. Manejo de Errores:**
```javascript
try {
  const result = await apiCall('endpoint');
  // Procesar éxito
} catch (error) {
  alert(error.message); // Mostrar error al usuario
}
```

**3. Protección XSS:**
- No usar `innerHTML` con datos del usuario
- Escapar caracteres especiales en templates
- Usar `textContent` cuando sea posible

### Backend

**1. SQL Injection Prevention:**
```php
// ❌ VULNERABLE
$query = "SELECT * FROM users WHERE email = '$email'";

// ✅ SEGURO (Prepared Statements)
$stmt = $db->prepare("SELECT * FROM users WHERE email = ?");
$stmt->execute([$email]);
```

**2. Autenticación:**
- Contraseñas hasheadas (bcrypt, cost factor 10)
- Sesiones seguras (HttpOnly cookies)
- Verificación de permisos en cada endpoint

**3. Validación de Entrada:**
```php
// Sanitización
$name = sanitize($data['name']); // strip_tags, trim, htmlspecialchars

// Validación
if (!validateEmail($email)) {
    errorResponse('Email inválido');
}

// Type casting
$price = floatval($data['price']);
$stock = intval($data['stock']);
```

**4. CORS:**
```php
header('Access-Control-Allow-Origin: *'); // Desarrollo
// En producción: dominio específico
```

**5. Headers de Seguridad:**
```apache
X-Content-Type-Options: nosniff
X-Frame-Options: SAMEORIGIN
X-XSS-Protection: 1; mode=block
```

---

## 🌐 API REST

### Especificación de Endpoints

#### **Autenticación**

| Método | Endpoint | Descripción | Auth |
|--------|----------|-------------|------|
| POST | `/api/auth.php?action=login` | Iniciar sesión | No |
| POST | `/api/auth.php?action=register` | Registrar usuario | No |
| POST | `/api/auth.php?action=logout` | Cerrar sesión | Sí |
| GET | `/api/auth.php?action=check` | Verificar sesión | No |

#### **Productos**

| Método | Endpoint | Descripción | Auth |
|--------|----------|-------------|------|
| GET | `/api/products.php?action=list` | Listar productos | No |
| GET | `/api/products.php?action=get&id=X` | Obtener producto | No |
| POST | `/api/products.php?action=create` | Crear producto | Admin |
| POST | `/api/products.php?action=update` | Actualizar producto | Admin |
| POST | `/api/products.php?action=delete&id=X` | Eliminar producto | Admin |
| GET | `/api/products.php?action=categories` | Listar categorías | No |

**Query Parameters (list):**
- `search`: Término de búsqueda
- `category`: Filtrar por categoría
- `sort`: Ordenar (name, price-low, price-high, rating)

#### **Carrito**

| Método | Endpoint | Descripción | Auth |
|--------|----------|-------------|------|
| GET | `/api/cart.php?action=get` | Obtener carrito | User |
| POST | `/api/cart.php?action=add` | Agregar producto | User |
| POST | `/api/cart.php?action=update` | Actualizar cantidad | User |
| POST | `/api/cart.php?action=remove&productId=X` | Eliminar producto | User |
| POST | `/api/cart.php?action=clear` | Vaciar carrito | User |

#### **Pedidos**

| Método | Endpoint | Descripción | Auth |
|--------|----------|-------------|------|
| GET | `/api/orders.php?action=list` | Listar pedidos | User/Admin |
| GET | `/api/orders.php?action=get&id=X` | Obtener pedido | User/Admin |
| POST | `/api/orders.php?action=create` | Crear pedido | User |
| POST | `/api/orders.php?action=update` | Actualizar estado | Admin |

#### **Reportes**

| Método | Endpoint | Descripción | Auth |
|--------|----------|-------------|------|
| GET | `/api/reports.php?action=stats` | Estadísticas generales | Admin |
| GET | `/api/reports.php?action=sales` | Ventas por mes | Admin |
| GET | `/api/reports.php?action=top-products` | Productos más vendidos | Admin |

### Formato de Respuestas

**Éxito:**
```json
{
  "success": true,
  "data": { ... },
  "message": "Operación exitosa"
}
```

**Error:**
```json
{
  "error": "Mensaje de error descriptivo"
}
```

**Códigos HTTP:**
- `200`: Éxito
- `400`: Error de solicitud (validación)
- `401`: No autenticado
- `403`: Acceso denegado (permisos)
- `404`: Recurso no encontrado
- `500`: Error del servidor

---

## 📊 Rendimiento

### Optimizaciones Frontend

1. **Debounce en búsqueda:** 300ms para evitar requests excesivos
2. **Lazy loading de imágenes:** `loading="lazy"` en `<img>`
3. **Renderizado selectivo:** Solo actualiza elementos modificados
4. **Caché de productos:** Estado en memoria durante la sesión

### Optimizaciones Backend

1. **Prepared Statements:** Reutilización de queries
2. **Índices en BD:** Búsquedas rápidas
3. **Transacciones:** Operaciones atómicas eficientes
4. **Conexión Singleton:** Una sola conexión por request

### Escalabilidad

**Limitaciones actuales:**
- Servidor PHP integrado (solo desarrollo)
- Sin caché de queries
- Sin CDN para assets estáticos

**Mejoras futuras:**
- Servidor web dedicado (Apache/Nginx)
- Redis para sesiones y caché
- CDN para imágenes
- Load balancer para múltiples instancias

---

## 🧪 Testing y Debugging

### Frontend

**Herramientas:**
- Console.log para debugging
- DevTools Network tab para ver requests
- DevTools Application tab para ver sesiones/cookies

**Funciones de debug:**
```javascript
window.debugApp(); // Estado completo
window.debugCart(); // Estado del carrito
```

### Backend

**Logging:**
```php
error_log("Error: " . $e->getMessage()); // Logs en error_log de PHP
```

**Verificación:**
- Probar endpoints con Postman/cURL
- Verificar respuestas JSON
- Revisar logs de PHP y MySQL

---

## 📝 Conclusión

### Fortalezas

✅ **Arquitectura clara:** Separación frontend/backend  
✅ **Seguridad:** Prepared statements, bcrypt, validación  
✅ **Escalable:** Fácil agregar features  
✅ **Mantenible:** Código organizado y documentado  
✅ **RESTful:** API estándar y predecible  

### Áreas de Mejora

🔧 **Caché:** Implementar Redis  
🔧 **Validación:** Librerías de validación más robustas  
🔧 **Testing:** Unit tests y integration tests  
🔧 **Documentación:** Swagger/OpenAPI para API  
🔧 **Monitoreo:** Logs estructurados y métricas  

---

**Preparado para exposición técnica** 🎯

