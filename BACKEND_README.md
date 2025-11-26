# Backend GreenMarket - PHP y MySQL

Backend completo para la aplicación GreenMarket desarrollado en PHP con MySQL.

## 📋 Requisitos

- PHP 7.4 o superior
- MySQL 5.7 o superior (o MariaDB 10.2+)
- Servidor web (Apache, Nginx, o servidor PHP integrado)
- Extensiones PHP requeridas:
  - PDO
  - PDO_MySQL
  - JSON
  - Session

## 🚀 Instalación

### 1. Configurar la Base de Datos

1. Crea una base de datos MySQL:
```sql
CREATE DATABASE greenmarket CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

2. Importa el esquema de la base de datos:
```bash
mysql -u root -p greenmarket < database.sql
```

O ejecuta el archivo `database.sql` desde tu cliente MySQL.

### 2. Configurar la Conexión

Edita el archivo `api/config.php` y actualiza las credenciales de la base de datos:

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'greenmarket');
define('DB_USER', 'tu_usuario');
define('DB_PASS', 'tu_contraseña');
```

### 3. Configurar el Servidor Web

#### Opción A: Servidor PHP Integrado (Desarrollo)
```bash
php -S localhost:8000
```

#### Opción B: Apache
Asegúrate de que el módulo `mod_rewrite` esté habilitado y que el archivo `.htaccess` esté configurado.

#### Opción C: Nginx
Configura las rutas para que apunten a la carpeta del proyecto.

## 📁 Estructura del Backend

```
api/
├── config.php      # Configuración y funciones auxiliares
├── auth.php        # Autenticación (login, registro, logout)
├── products.php    # CRUD de productos
├── cart.php        # Gestión del carrito
├── orders.php      # Gestión de pedidos
└── reports.php     # Reportes y estadísticas
```

## 🔌 Endpoints de la API

### Autenticación (`auth.php`)

- **POST** `auth.php?action=login` - Iniciar sesión
  ```json
  {
    "email": "usuario@example.com",
    "password": "contraseña"
  }
  ```

- **POST** `auth.php?action=register` - Registrar usuario
  ```json
  {
    "name": "Nombre Completo",
    "email": "usuario@example.com",
    "password": "contraseña",
    "confirmPassword": "contraseña"
  }
  ```

- **POST** `auth.php?action=logout` - Cerrar sesión

- **GET** `auth.php?action=check` - Verificar sesión activa

### Productos (`products.php`)

- **GET** `products.php?action=list&search=term&category=cat&sort=name` - Listar productos
- **GET** `products.php?action=get&id=1` - Obtener producto por ID
- **POST** `products.php?action=create` - Crear producto (requiere admin)
- **POST** `products.php?action=update` - Actualizar producto (requiere admin)
- **POST** `products.php?action=delete&id=1` - Eliminar producto (requiere admin)
- **GET** `products.php?action=categories` - Listar categorías

### Carrito (`cart.php`)

- **GET** `cart.php?action=get` - Obtener carrito del usuario
- **POST** `cart.php?action=add` - Agregar producto al carrito
  ```json
  {
    "productId": 1,
    "quantity": 1
  }
  ```
- **POST** `cart.php?action=update` - Actualizar cantidad
  ```json
  {
    "productId": 1,
    "quantity": 2
  }
  ```
- **POST** `cart.php?action=remove&productId=1` - Eliminar producto del carrito
- **POST** `cart.php?action=clear` - Vaciar carrito

### Pedidos (`orders.php`)

- **GET** `orders.php?action=list` - Listar pedidos del usuario (o todos si es admin)
- **GET** `orders.php?action=get&id=1` - Obtener pedido por ID
- **POST** `orders.php?action=create` - Crear pedido desde el carrito
- **POST** `orders.php?action=update` - Actualizar estado del pedido (requiere admin)
  ```json
  {
    "id": 1,
    "status": "confirmed"
  }
  ```

### Reportes (`reports.php`)

- **GET** `reports.php?action=stats` - Estadísticas generales (requiere admin)
- **GET** `reports.php?action=sales` - Ventas por mes (requiere admin)
- **GET** `reports.php?action=top-products&limit=10` - Productos más vendidos (requiere admin)

## 🔐 Autenticación y Sesiones

El backend utiliza sesiones PHP para mantener la autenticación. Las sesiones se manejan automáticamente mediante cookies.

### Usuario Administrador por Defecto

- **Email**: `admin@greenmarket.com`
- **Contraseña**: `admin123`

⚠️ **IMPORTANTE**: Cambia la contraseña del administrador en producción.

## 🗄️ Estructura de la Base de Datos

### Tablas Principales

- **users**: Usuarios del sistema
- **categories**: Categorías de productos
- **products**: Productos disponibles
- **orders**: Pedidos realizados
- **order_items**: Items de cada pedido
- **cart**: Carrito de compras (temporal)

## 🔒 Seguridad

- Las contraseñas se almacenan con hash bcrypt
- Validación de entrada en todos los endpoints
- Protección contra SQL injection mediante prepared statements
- Verificación de permisos según rol de usuario
- Sanitización de datos de entrada

## 📝 Notas de Desarrollo

### CORS

Los endpoints incluyen headers CORS básicos. En producción, configura los orígenes permitidos según tus necesidades.

### Manejo de Errores

Todos los endpoints retornan respuestas JSON consistentes:
- **Éxito**: `{ "success": true, "data": {...}, "message": "..." }`
- **Error**: `{ "error": "mensaje de error" }`

### Códigos de Estado HTTP

- `200`: Éxito
- `400`: Error de solicitud
- `401`: No autenticado
- `403`: Acceso denegado
- `404`: No encontrado
- `500`: Error del servidor

## 🧪 Testing

Para probar los endpoints, puedes usar:

- **Postman**: Importa los endpoints y prueba las peticiones
- **cURL**: Desde la línea de comandos
- **Frontend**: La aplicación frontend ya está configurada para usar estos endpoints

## 🚀 Despliegue

### Producción

1. Cambia las credenciales de la base de datos en `config.php`
2. Configura `DB_CHARSET` y otras constantes según tu entorno
3. Habilita HTTPS y configura `session.cookie_secure = 1`
4. Configura permisos de archivos apropiados
5. Desactiva el display de errores PHP en producción
6. Configura un sistema de logs apropiado

### Variables de Entorno (Recomendado)

Para mayor seguridad, considera usar variables de entorno en lugar de hardcodear credenciales:

```php
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '');
```

## 📞 Soporte

Si encuentras problemas:

1. Verifica los logs de PHP (`error_log`)
2. Verifica la conexión a la base de datos
3. Asegúrate de que todas las extensiones PHP requeridas estén instaladas
4. Verifica los permisos de archivos y directorios

## 📄 Licencia

MIT License - Ver archivo LICENSE para más detalles.

