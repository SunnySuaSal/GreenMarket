# 🚀 Guía de Inicio Rápido - GreenMarket

## 📋 Paso 1: Configurar la Base de Datos

### Opción A: Usando MySQL desde la línea de comandos

```bash
# 1. Accede a MySQL
mysql -u root -p

# 2. Crea la base de datos (si no existe)
CREATE DATABASE IF NOT EXISTS greenmarket CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

# 3. Sal de MySQL
exit;

# 4. Importa el esquema y datos iniciales
mysql -u root -p greenmarket < database.sql
```

### Opción B: Usando phpMyAdmin o un cliente gráfico

1. Abre phpMyAdmin o tu cliente MySQL favorito
2. Crea una nueva base de datos llamada `greenmarket`
3. Selecciona la base de datos
4. Ve a la pestaña "Importar"
5. Selecciona el archivo `database.sql`
6. Haz clic en "Ejecutar"

## ⚙️ Paso 2: Configurar las Credenciales

Edita el archivo `api/config.php` y actualiza las credenciales de tu base de datos:

```php
// Línea 8-11 aproximadamente
define('DB_HOST', 'localhost');        // Cambia si tu MySQL está en otro servidor
define('DB_NAME', 'greenmarket');      // Nombre de tu base de datos
define('DB_USER', 'root');              // Tu usuario de MySQL
define('DB_PASS', '');                  // Tu contraseña de MySQL (déjala vacía si no tienes)
```

## 🖥️ Paso 3: Iniciar el Servidor

### Opción A: Script de Inicio Rápido (Más fácil)

**En macOS/Linux:**
```bash
./iniciar.sh
```

El script verificará que todo esté configurado y te mostrará los usuarios disponibles.

### Opción B: Servidor PHP Integrado (Manual)

```bash
# Navega a la carpeta del proyecto
cd "/Users/sunnysaldana/Downloads/GreenMarket Web App Mockups"

# Inicia el servidor
php -S localhost:8000
```

### Opción B: Usando XAMPP/MAMP/WAMP

1. Copia la carpeta del proyecto a `htdocs` (XAMPP) o `htdocs` (MAMP)
2. Inicia Apache y MySQL desde el panel de control
3. Accede a: `http://localhost/GreenMarket Web App Mockups`

### Opción C: Usando un servidor web existente

1. Configura un virtual host apuntando a la carpeta del proyecto
2. Asegúrate de que PHP esté habilitado
3. Accede a través de tu dominio configurado

## 🌐 Paso 4: Abrir la Aplicación

Abre tu navegador y ve a:
```
http://localhost:8000
```

## 👥 Paso 5: Usuarios Disponibles

### 🔑 Usuario Administrador (Ya creado)

- **Email**: `admin@greenmarket.com`
- **Contraseña**: `admin123`
- **Permisos**: 
  - ✅ Ver panel de administración
  - ✅ Crear, editar y eliminar productos
  - ✅ Ver reportes de ventas
  - ✅ Gestionar pedidos

### 👥 Usuarios de Ejemplo (Opcional)

Para tener más usuarios de prueba, ejecuta el archivo adicional:

```bash
mysql -u root -p greenmarket < usuarios_ejemplo.sql
```

Esto creará 5 usuarios adicionales, todos con la contraseña `password123`:

1. **María González** - `maria@example.com`
2. **Juan Pérez** - `juan@example.com`
3. **Ana Martínez** - `ana@example.com`
4. **Carlos Rodríguez** - `carlos@example.com`
5. **Laura Sánchez** - `laura@example.com`

### 👤 Crear Usuario Normal

1. En la pantalla de login, haz clic en la pestaña **"Registrarse"**
2. Completa el formulario:
   - Nombre completo
   - Email (debe ser único)
   - Contraseña (mínimo 8 caracteres)
   - Confirmar contraseña
3. Haz clic en **"Crear Cuenta"**
4. Serás redirigido automáticamente al catálogo

**Ejemplo de usuario normal:**
- Email: `usuario@example.com`
- Contraseña: `password123`

### 🎭 Modo Invitado

1. En la pantalla de login, haz clic en **"Continuar como invitado"**
2. Podrás:
   - ✅ Ver el catálogo de productos
   - ✅ Buscar y filtrar productos
   - ❌ NO podrás agregar al carrito
   - ❌ NO podrás hacer pedidos

## 🧪 Paso 6: Probar la Aplicación

### Como Administrador:

1. **Login**: Usa `admin@greenmarket.com` / `admin123`
2. **Panel Admin**: 
   - Ve a "Admin Panel" en el menú
   - Verás estadísticas: Total productos, productos en stock, categorías
   - Haz clic en "Agregar Producto" para crear nuevos productos
   - Puedes editar o eliminar productos existentes
3. **Reportes**:
   - Ve a "Reportes" en el menú
   - Verás estadísticas de ventas, pedidos completados, productos vendidos

### Como Usuario Normal:

1. **Registro o Login**: Crea una cuenta nueva o inicia sesión
2. **Catálogo**:
   - Explora los productos disponibles
   - Usa la búsqueda para encontrar productos
   - Filtra por categoría (Verduras, Frutas, Panadería)
   - Ordena por precio o valoración
3. **Carrito**:
   - Haz clic en "Agregar al Carrito" en cualquier producto
   - Ve a "Carrito" en el menú para ver tus productos
   - Ajusta las cantidades
   - Verás el resumen con subtotal, envío e impuestos
4. **Pedidos**:
   - Haz clic en "Finalizar Compra" en el carrito
   - Ve a "Mis Pedidos" para ver tu historial
   - Verás el estado de cada pedido (Pendiente, Confirmado, Entregado)

### Como Invitado:

1. Haz clic en "Continuar como invitado"
2. Explora el catálogo
3. Nota que los botones de "Agregar al Carrito" están deshabilitados
4. Para comprar, necesitarás crear una cuenta

## 🔍 Verificar que Todo Funciona

### 1. Verificar Base de Datos

```bash
mysql -u root -p greenmarket -e "SELECT COUNT(*) as total_usuarios FROM users;"
mysql -u root -p greenmarket -e "SELECT COUNT(*) as total_productos FROM products;"
```

Deberías ver:
- Al menos 1 usuario (el admin)
- 4 productos de ejemplo

### 2. Verificar API

Abre en tu navegador:
```
http://localhost:8000/api/products.php?action=list
```

Deberías ver un JSON con los productos.

### 3. Verificar Sesiones

1. Inicia sesión como admin
2. Abre las herramientas de desarrollador (F12)
3. Ve a la pestaña "Application" > "Cookies"
4. Deberías ver una cookie `PHPSESSID`

## 🐛 Solución de Problemas

### Error: "Error de conexión a la base de datos"

**Solución:**
1. Verifica que MySQL esté corriendo
2. Revisa las credenciales en `api/config.php`
3. Asegúrate de que la base de datos `greenmarket` existe

```bash
# Verificar que MySQL está corriendo
mysql -u root -p -e "SHOW DATABASES;"
```

### Error: "No autenticado" al hacer login

**Solución:**
1. Verifica que las sesiones PHP estén habilitadas
2. Asegúrate de que el servidor esté configurado correctamente
3. Revisa los logs de PHP

### Los productos no se cargan

**Solución:**
1. Verifica que la base de datos tenga productos:
```sql
SELECT * FROM products;
```
2. Revisa la consola del navegador (F12) para ver errores
3. Verifica que la URL de la API sea correcta en `js/app.js`

### No puedo agregar productos al carrito

**Solución:**
1. Asegúrate de estar logueado como usuario (no como invitado)
2. Verifica que el producto tenga stock disponible
3. Revisa la consola del navegador para errores

## 📊 Datos de Ejemplo Incluidos

El archivo `database.sql` incluye:

- **1 Usuario Admin**: `admin@greenmarket.com`
- **3 Categorías**: Verduras, Frutas, Panadería
- **4 Productos de ejemplo**:
  - Tomates Orgánicos ($3.99)
  - Manzanas Locales ($2.50)
  - Pan Artesanal ($4.25)
  - Verduras Mixtas ($5.75)

## 🔐 Seguridad en Producción

Antes de desplegar en producción:

1. **Cambia la contraseña del admin**:
```sql
UPDATE users SET password = '$2y$10$nuevo_hash_aqui' WHERE email = 'admin@greenmarket.com';
```

2. **Actualiza `api/config.php`**:
   - Cambia `session.cookie_secure` a `1` (requiere HTTPS)
   - Configura CORS para tu dominio específico
   - Desactiva el display de errores

3. **Configura permisos de archivos**:
```bash
chmod 644 api/*.php
chmod 600 api/config.php  # Si contiene información sensible
```

## 📝 Notas Importantes

- El servidor PHP integrado es solo para desarrollo
- Para producción, usa Apache o Nginx con PHP-FPM
- Las sesiones se guardan en el servidor, no en el navegador
- El carrito se guarda en la base de datos (tabla `cart`)
- Los pedidos se guardan permanentemente en la base de datos

## 🎉 ¡Listo!

Ya tienes todo configurado. Puedes empezar a usar GreenMarket con diferentes usuarios y roles.

**Flujo recomendado para probar:**
1. Inicia como **invitado** → Explora el catálogo
2. **Regístrate** como usuario nuevo → Agrega productos al carrito
3. **Haz un pedido** → Ve tu historial
4. **Inicia sesión como admin** → Gestiona productos y ve reportes

¡Disfruta usando GreenMarket! 🌱

