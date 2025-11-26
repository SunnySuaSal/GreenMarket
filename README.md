# GreenMarket - Plataforma de Compras Locales Sustentables

Una aplicación web moderna para compras locales sustentables, desarrollada con HTML, CSS y JavaScript puro.

## 🌱 Características

- **Autenticación de usuarios**: Login, registro y acceso como invitado
- **Catálogo de productos**: Búsqueda, filtrado y ordenamiento de productos
- **Carrito de compras**: Gestión completa del carrito con cálculos automáticos
- **Sistema de pedidos**: Historial de compras y seguimiento de estado
- **Panel de administración**: Gestión de productos para administradores
- **Reportes de ventas**: Estadísticas y análisis de rendimiento
- **Diseño responsive**: Optimizado para dispositivos móviles y desktop
- **Accesibilidad**: Cumple con estándares de accesibilidad web

## 🚀 Instalación y Uso

### Requisitos
- Navegador web moderno (Chrome, Firefox, Safari, Edge)
- Servidor web local (opcional, para desarrollo)

### Instalación
1. Clona o descarga este repositorio
2. Abre `index.html` en tu navegador web
3. ¡Listo! La aplicación está funcionando

### Para desarrollo local
```bash
# Opción 1: Servidor Python
python -m http.server 8000

# Opción 2: Servidor Node.js
npx serve .

# Opción 3: Live Server (VS Code)
# Instala la extensión Live Server y haz clic derecho en index.html
```

## 👥 Roles de Usuario

### Invitado
- Explorar catálogo de productos
- Ver detalles de productos
- No puede realizar compras

### Usuario Registrado
- Todas las funciones de invitado
- Agregar productos al carrito
- Realizar pedidos
- Ver historial de pedidos

### Administrador
- Todas las funciones de usuario
- Panel de administración
- Gestión de productos (agregar, editar, eliminar)
- Reportes de ventas
- Estadísticas del sistema

## 🔐 Credenciales de Demo

- **Administrador**: `admin@greenmarket.com` (cualquier contraseña)
- **Usuario**: Cualquier email válido (cualquier contraseña)
- **Invitado**: Botón "Continuar como invitado"

## 📱 Funcionalidades

### Catálogo de Productos
- Búsqueda por nombre, descripción o vendedor
- Filtrado por categoría
- Ordenamiento por precio, valoración o nombre
- Vista de tarjetas con información completa

### Carrito de Compras
- Agregar/eliminar productos
- Modificar cantidades
- Cálculo automático de subtotal, envío e impuestos
- Envío gratis en compras superiores a $25
- Indicadores de confianza

### Sistema de Pedidos
- Confirmación de pedidos
- Historial completo
- Estados: Pendiente, Confirmado, Entregado
- Detalles de cada pedido

### Panel de Administración
- Estadísticas generales
- Gestión de productos
- Formulario para agregar/editar productos
- Eliminación de productos

### Reportes
- Ventas totales
- Pedidos completados
- Productos vendidos
- Gráficos de rendimiento

## 🎨 Diseño y UX

### Características de Diseño
- **Paleta de colores**: Verde sustentable con acentos modernos
- **Tipografía**: Inter (Google Fonts) para legibilidad óptima
- **Iconografía**: Lucide Icons para consistencia visual
- **Espaciado**: Sistema de espaciado consistente
- **Sombras**: Efectos sutiles para profundidad

### Responsive Design
- **Mobile First**: Diseño optimizado para móviles
- **Breakpoints**: 
  - Mobile: < 768px
  - Tablet: 768px - 1024px
  - Desktop: > 1024px
- **Grid System**: CSS Grid y Flexbox para layouts flexibles

### Accesibilidad
- **Navegación por teclado**: Soporte completo
- **Lectores de pantalla**: Etiquetas semánticas
- **Contraste**: Cumple WCAG 2.1 AA
- **Focus visible**: Indicadores claros de foco
- **Reduced motion**: Respeta preferencias del usuario

## 🛠️ Tecnologías Utilizadas

- **HTML5**: Estructura semántica y accesible
- **CSS3**: Variables CSS, Grid, Flexbox, animaciones
- **JavaScript ES6+**: Clases, módulos, async/await
- **Local Storage**: Persistencia de datos del usuario
- **Responsive Images**: Optimización automática
- **Progressive Enhancement**: Funciona sin JavaScript

## 📁 Estructura del Proyecto

```
GreenMarket Web App Mockups/
├── index.html              # Página principal
├── styles/
│   └── main.css            # Estilos principales
├── js/
│   └── app.js              # Lógica de la aplicación
└── README.md               # Documentación
```

## 🔧 Configuración

### Variables de Configuración
```javascript
const CONFIG = {
  TAX_RATE: 0.08,                    // 8% de impuestos
  FREE_SHIPPING_THRESHOLD: 25,       // Envío gratis desde $25
  SHIPPING_COST: 3.99,               // Costo de envío
  CART_STORAGE_KEY: 'greenmarket_cart',
  USER_STORAGE_KEY: 'greenmarket_user',
  ORDERS_STORAGE_KEY: 'greenmarket_orders'
};
```

### Personalización
- **Colores**: Modifica las variables CSS en `:root`
- **Productos**: Edita el array `MOCK_PRODUCTS` en `app.js`
- **Configuración**: Ajusta los valores en `CONFIG`

## 🚀 Despliegue

### Hosting Estático
- **Netlify**: Arrastra y suelta la carpeta
- **Vercel**: Conecta con GitHub
- **GitHub Pages**: Activa en configuración del repositorio
- **Firebase Hosting**: `firebase deploy`

### Servidor Web
- **Apache**: Copia archivos a htdocs
- **Nginx**: Configura root directory
- **IIS**: Publica en sitio web

## 🤝 Contribución

1. Fork el proyecto
2. Crea una rama para tu feature (`git checkout -b feature/AmazingFeature`)
3. Commit tus cambios (`git commit -m 'Add some AmazingFeature'`)
4. Push a la rama (`git push origin feature/AmazingFeature`)
5. Abre un Pull Request

## 📄 Licencia

Este proyecto está bajo la Licencia MIT. Ver el archivo `LICENSE` para más detalles.

## 🙏 Agradecimientos

- **Figma**: Diseño original disponible en [GreenMarket Web App Mockups](https://www.figma.com/design/OemZL9baeD1TUtTdPu5Yoi/GreenMarket-Web-App-Mockups)
- **Unsplash**: Imágenes de productos
- **Lucide**: Iconografía
- **Google Fonts**: Tipografía Inter

## 📞 Soporte

Si tienes preguntas o necesitas ayuda:

1. Revisa la documentación
2. Busca en los issues existentes
3. Crea un nuevo issue con detalles del problema
4. Incluye información del navegador y pasos para reproducir

---

**GreenMarket** - Conectando comunidades locales con productos sustentables 🌱