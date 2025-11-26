// ===== CONFIGURACIÓN Y CONSTANTES =====
const CONFIG = {
  TAX_RATE: 0.08,
  FREE_SHIPPING_THRESHOLD: 25,
  SHIPPING_COST: 3.99,
  API_BASE_URL: 'api'
};

// ===== API HELPER FUNCTIONS =====
async function apiCall(endpoint, method = 'GET', data = null) {
  const options = {
    method,
    headers: {
      'Content-Type': 'application/json'
    },
    credentials: 'include' // Para mantener las sesiones PHP
  };
  
  if (data && (method === 'POST' || method === 'PUT')) {
    options.body = JSON.stringify(data);
  }
  
  try {
    const response = await fetch(`${CONFIG.API_BASE_URL}/${endpoint}`, options);
    const result = await response.json();
    
    if (!response.ok) {
      throw new Error(result.error || 'Error en la petición');
    }
    
    return result;
  } catch (error) {
    console.error('API Error:', error);
    throw error;
  }
}

// ===== ESTADO GLOBAL DE LA APLICACIÓN =====
class AppState {
  constructor() {
    this.currentScreen = 'login';
    this.userRole = 'guest';
    this.cart = [];
    this.orders = [];
    this.products = [];
    this.currentUser = null;
    this.editingProductId = null;
    this.init();
  }

  async init() {
    // Verificar si hay sesión activa
    try {
      const result = await apiCall('auth.php?action=check');
      if (result.data && result.data.authenticated !== false) {
        this.currentUser = result.data;
        this.userRole = result.data.role || 'user';
        await this.loadCart();
        this.updateNavigation();
      }
    } catch (error) {
      console.log('No hay sesión activa');
    }
  }

  // Gestión del carrito
  async loadCart() {
    if (this.userRole === 'guest') {
      this.cart = [];
      return;
    }
    try {
      const result = await apiCall('cart.php?action=get');
      this.cart = result.data || [];
      this.updateCartUI();
    } catch (error) {
      console.error('Error loading cart:', error);
      this.cart = [];
    }
  }

  // Gestión de usuarios
  async checkAuth() {
    try {
      const result = await apiCall('auth.php?action=check');
      if (result.data && result.data.authenticated !== false) {
        this.currentUser = result.data;
        this.userRole = result.data.role || 'user';
        return true;
      }
      return false;
    } catch (error) {
      return false;
    }
  }

  // Métodos del carrito
  async addToCart(productId) {
    if (this.userRole !== 'user') {
      alert('Debes iniciar sesión para agregar productos al carrito');
      return;
    }
    
    try {
      await apiCall('cart.php?action=add', 'POST', { productId, quantity: 1 });
      await this.loadCart();
      
      if (this.currentScreen === 'cart') {
        await this.renderCart();
      }
    } catch (error) {
      alert(error.message || 'Error al agregar producto al carrito');
    }
  }

  async updateCartQuantity(productId, quantity) {
    if (this.userRole !== 'user') return;
    
    try {
      await apiCall('cart.php?action=update', 'POST', { productId, quantity });
      await this.loadCart();
      
      if (this.currentScreen === 'cart') {
        await this.renderCart();
      }
    } catch (error) {
      alert(error.message || 'Error al actualizar el carrito');
    }
  }

  async clearCart() {
    try {
      await apiCall('cart.php?action=clear', 'POST');
      this.cart = [];
      this.updateCartUI();
    } catch (error) {
      console.error('Error clearing cart:', error);
    }
  }

  // Métodos de autenticación
  async login(email, password) {
    try {
      const result = await apiCall('auth.php?action=login', 'POST', { email, password });
      this.currentUser = result.data;
      this.userRole = result.data.role || 'user';
      await this.loadCart();
      this.navigateTo('catalog');
    } catch (error) {
      alert(error.message || 'Error al iniciar sesión');
      throw error;
    }
  }

  async register(name, email, password, confirmPassword) {
    try {
      const result = await apiCall('auth.php?action=register', 'POST', { 
        name, email, password, confirmPassword 
      });
      this.currentUser = result.data;
      this.userRole = 'user';
      await this.loadCart();
      this.navigateTo('catalog');
    } catch (error) {
      alert(error.message || 'Error al registrar usuario');
      throw error;
    }
  }

  async logout() {
    try {
      await apiCall('auth.php?action=logout', 'POST');
    } catch (error) {
      console.error('Error en logout:', error);
    }
    this.userRole = 'guest';
    this.currentUser = null;
    this.cart = [];
    this.updateCartUI();
    this.navigateTo('login');
  }

  loginAsGuest() {
    this.userRole = 'guest';
    this.currentUser = null;
    this.cart = [];
    this.navigateTo('catalog');
  }

  // Navegación
  navigateTo(screen) {
    this.currentScreen = screen;
    this.renderScreen();
    this.updateNavigation();
  }

  // Métodos de pedidos
  async placeOrder() {
    if (this.cart.length === 0) {
      alert('El carrito está vacío');
      return;
    }

    try {
      const result = await apiCall('orders.php?action=create', 'POST');
      this.cart = [];
      this.updateCartUI();
      this.navigateTo('orders');
      await this.loadOrders();
    } catch (error) {
      alert(error.message || 'Error al crear el pedido');
    }
  }

  async loadOrders() {
    if (this.userRole === 'guest') {
      this.orders = [];
      return;
    }
    try {
      const result = await apiCall('orders.php?action=list');
      this.orders = result.data || [];
    } catch (error) {
      console.error('Error loading orders:', error);
      this.orders = [];
    }
  }

  // Utilidades
  generateId() {
    return Math.random().toString(36).substr(2, 9);
  }

  calculateCartTotal() {
    const subtotal = this.cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
    const shipping = subtotal > CONFIG.FREE_SHIPPING_THRESHOLD ? 0 : CONFIG.SHIPPING_COST;
    const tax = subtotal * CONFIG.TAX_RATE;
    return subtotal + shipping + tax;
  }

  // Métodos de UI
  updateCartUI() {
    const cartBadge = document.getElementById('cart-badge');
    if (cartBadge) {
      const totalItems = this.cart.reduce((sum, item) => sum + item.quantity, 0);
      if (totalItems > 0) {
        cartBadge.textContent = totalItems;
        cartBadge.classList.remove('hidden');
      } else {
        cartBadge.classList.add('hidden');
      }
    }
  }

  updateNavigation() {
    const navigation = document.getElementById('navigation');
    if (this.userRole === 'guest') {
      navigation.classList.add('hidden');
    } else {
      navigation.classList.remove('hidden');
      
      // Mostrar/ocultar elementos según el rol
      const cartBtn = document.getElementById('cart-btn');
      const ordersBtn = document.getElementById('orders-btn');
      const adminBtn = document.getElementById('admin-btn');
      const reportsBtn = document.getElementById('reports-btn');

      if (this.userRole === 'user') {
        cartBtn.style.display = 'flex';
        ordersBtn.style.display = 'flex';
        adminBtn.style.display = 'none';
        reportsBtn.style.display = 'none';
      } else if (this.userRole === 'admin') {
        cartBtn.style.display = 'none';
        ordersBtn.style.display = 'none';
        adminBtn.style.display = 'flex';
        reportsBtn.style.display = 'flex';
      }
    }
  }

  renderScreen() {
    // Ocultar todas las pantallas
    document.querySelectorAll('.screen').forEach(screen => {
      screen.classList.remove('active');
    });

    // Mostrar la pantalla actual
    const currentScreen = document.getElementById(`${this.currentScreen}-screen`);
    if (currentScreen) {
      currentScreen.classList.add('active');
    }

    // Renderizar contenido específico de cada pantalla
    switch (this.currentScreen) {
      case 'catalog':
        this.renderCatalog();
        break;
      case 'cart':
        this.renderCart();
        break;
      case 'orders':
        this.renderOrders();
        break;
      case 'admin':
        this.renderAdmin();
        break;
      case 'reports':
        this.renderReports();
        break;
    }
  }

  // Renderizado de pantallas
  async renderCatalog() {
    const productsGrid = document.getElementById('products-grid');
    const noProducts = document.getElementById('no-products');
    
    if (!productsGrid) return;

    try {
      await this.loadProducts();
      const filteredProducts = this.getFilteredProducts();
      
      if (filteredProducts.length === 0) {
        productsGrid.innerHTML = '';
        noProducts.classList.remove('hidden');
      } else {
        noProducts.classList.add('hidden');
        productsGrid.innerHTML = filteredProducts.map(product => this.createProductCard(product)).join('');
      }
    } catch (error) {
      console.error('Error rendering catalog:', error);
      productsGrid.innerHTML = '<p>Error al cargar productos</p>';
    }
  }

  async loadProducts() {
    try {
      const search = document.getElementById('search-input')?.value || '';
      const category = document.getElementById('category-filter')?.value || 'all';
      const sort = document.getElementById('sort-filter')?.value || 'name';
      
      const params = new URLSearchParams();
      if (search) params.append('search', search);
      if (category !== 'all') params.append('category', category);
      params.append('sort', sort);
      
      const result = await apiCall(`products.php?action=list&${params.toString()}`);
      this.products = result.data || [];
    } catch (error) {
      console.error('Error loading products:', error);
      this.products = [];
    }
  }

  async renderCart() {
    const emptyCart = document.getElementById('empty-cart');
    const cartContent = document.getElementById('cart-content');
    const cartItems = document.querySelector('.cart-items');
    
    if (!emptyCart || !cartContent) {
      console.error('Cart elements not found');
      return;
    }

    await this.loadCart();

    if (this.cart.length === 0) {
      emptyCart.classList.remove('hidden');
      cartContent.classList.add('hidden');
    } else {
      emptyCart.classList.add('hidden');
      cartContent.classList.remove('hidden');
      
      if (cartItems) {
        cartItems.innerHTML = this.cart.map(item => this.createCartItem(item)).join('');
      }
      
      this.updateCartSummary();
    }
  }

  async renderOrders() {
    const ordersList = document.getElementById('orders-list');
    const noOrders = document.getElementById('no-orders');
    
    if (!ordersList || !noOrders) return;

    await this.loadOrders();

    if (this.orders.length === 0) {
      ordersList.innerHTML = '';
      noOrders.classList.remove('hidden');
    } else {
      noOrders.classList.add('hidden');
      ordersList.innerHTML = this.orders.map(order => this.createOrderCard(order)).join('');
    }
  }

  async renderAdmin() {
    await this.loadProducts();
    await this.updateAdminStats();
    await this.renderAdminProducts();
  }

  async renderReports() {
    await this.updateReportStats();
  }

  // Métodos de filtrado y búsqueda
  getFilteredProducts() {
    // Los productos ya vienen filtrados del servidor
    return this.products;
  }

  // Creación de elementos HTML
  createProductCard(product) {
    const canAddToCart = this.userRole === 'user' && product.stock > 0;
    const buttonText = this.userRole === 'guest' ? 'Inicia sesión para comprar' :
                      this.userRole === 'admin' ? 'Vista de Administrador' :
                      product.stock === 0 ? 'Sin Stock' : 'Agregar al Carrito';

    return `
      <div class="product-card">
        <div class="product-image">
          <img src="${product.image}" alt="${product.name}" loading="lazy">
          <div class="product-badge">${product.category}</div>
        </div>
        <div class="product-content">
          <h3 class="product-title">${product.name}</h3>
          <div class="product-rating">
            <svg class="rating-star" viewBox="0 0 24 24" fill="currentColor">
              <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
            </svg>
            <span class="rating-text">${product.rating}</span>
            <span class="rating-count">(${product.reviews})</span>
          </div>
          <div class="product-seller">
            <svg class="seller-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/>
              <circle cx="12" cy="10" r="3"/>
            </svg>
            <span>${product.seller}</span>
          </div>
          <p class="product-description">${product.description}</p>
          <div class="product-footer">
            <span class="product-price">$${product.price.toFixed(2)}</span>
            <span class="product-stock">Stock: ${product.stock}</span>
          </div>
          <div class="product-actions">
            <button class="btn btn-primary btn-full" 
                    ${!canAddToCart ? 'disabled' : ''} 
                    onclick="app.addToCart(${product.id})">
              <svg class="btn-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="9" cy="21" r="1"/>
                <circle cx="20" cy="21" r="1"/>
                <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/>
              </svg>
              ${buttonText}
            </button>
          </div>
        </div>
      </div>
    `;
  }

  createCartItem(item) {
    return `
      <div class="cart-item">
        <div class="cart-item-content">
          <div class="cart-item-image">
            <img src="${item.image}" alt="${item.name}" loading="lazy">
          </div>
          <div class="cart-item-details">
            <div class="cart-item-header">
              <div>
                <h3 class="cart-item-title">${item.name}</h3>
                <p class="cart-item-seller">${item.seller}</p>
                <span class="cart-item-category">${item.category}</span>
              </div>
              <button class="cart-item-remove" onclick="app.updateCartQuantity(${item.id}, 0)">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <polyline points="3,6 5,6 21,6"/>
                  <path d="M19,6v14a2,2 0 0,1 -2,2H7a2,2 0 0,1 -2,-2V6m3,0V4a2,2 0 0,1 2,-2h4a2,2 0 0,1 2,2v2"/>
                </svg>
              </button>
            </div>
            <div class="cart-item-controls">
              <div class="quantity-controls">
                <button class="quantity-btn" 
                        ${item.quantity <= 1 ? 'disabled' : ''} 
                        onclick="app.updateCartQuantity(${item.id}, ${item.quantity - 1})">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="5" y1="12" x2="19" y2="12"/>
                  </svg>
                </button>
                <span class="quantity-display">${item.quantity}</span>
                <button class="quantity-btn" 
                        ${item.quantity >= item.stock ? 'disabled' : ''} 
                        onclick="app.updateCartQuantity(${item.id}, ${item.quantity + 1})">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="12" y1="5" x2="12" y2="19"/>
                    <line x1="5" y1="12" x2="19" y2="12"/>
                  </svg>
                </button>
              </div>
              <div class="cart-item-pricing">
                <p class="cart-item-unit-price">$${item.price.toFixed(2)} c/u</p>
                <p class="cart-item-total-price">$${(item.price * item.quantity).toFixed(2)}</p>
              </div>
            </div>
            ${item.quantity >= item.stock ? `
              <p class="cart-item-warning">⚠️ Cantidad máxima disponible: ${item.stock}</p>
            ` : ''}
          </div>
        </div>
      </div>
    `;
  }

  createOrderCard(order) {
    const statusClass = order.status;
    const statusText = {
      pending: 'Pendiente',
      confirmed: 'Confirmado',
      delivered: 'Entregado'
    };

    return `
      <div class="order-card">
        <div class="order-header">
          <div class="order-info">
            <h3>Pedido #${order.id}</h3>
            <p class="order-date">${order.date}</p>
          </div>
          <span class="order-status ${statusClass}">${statusText[order.status]}</span>
        </div>
        <div class="order-items">
          ${order.items.map(item => `
            <div class="order-item">
              <span class="order-item-name">${item.name}</span>
              <span class="order-item-quantity">x${item.quantity}</span>
              <span class="order-item-price">$${(item.price * item.quantity).toFixed(2)}</span>
            </div>
          `).join('')}
        </div>
        <div class="order-total">
          <span>Total</span>
          <span>$${order.total.toFixed(2)}</span>
        </div>
      </div>
    `;
  }

  // Métodos de resumen del carrito
  updateCartSummary() {
    const subtotal = this.cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
    const shipping = subtotal > CONFIG.FREE_SHIPPING_THRESHOLD ? 0 : CONFIG.SHIPPING_COST;
    const tax = subtotal * CONFIG.TAX_RATE;
    const total = subtotal + shipping + tax;

    // Actualizar elementos del DOM
    const itemCount = document.getElementById('item-count');
    const subtotalEl = document.getElementById('subtotal');
    const shippingEl = document.getElementById('shipping');
    const taxEl = document.getElementById('tax');
    const totalEl = document.getElementById('total');
    const freeShipping = document.getElementById('free-shipping');

    if (itemCount) itemCount.textContent = this.cart.length;
    if (subtotalEl) subtotalEl.textContent = `$${subtotal.toFixed(2)}`;
    if (shippingEl) shippingEl.textContent = shipping === 0 ? 'Gratis' : `$${shipping.toFixed(2)}`;
    if (taxEl) taxEl.textContent = `$${tax.toFixed(2)}`;
    if (totalEl) totalEl.textContent = `$${total.toFixed(2)}`;

    if (freeShipping) {
      if (shipping > 0) {
        freeShipping.classList.remove('hidden');
      } else {
        freeShipping.classList.add('hidden');
      }
    }
  }

  // Métodos de administración
  async updateAdminStats() {
    try {
      const result = await apiCall('reports.php?action=stats');
      const stats = result.data;
      
      const totalProducts = document.getElementById('total-products');
      const productsInStock = document.getElementById('products-in-stock');
      const totalCategories = document.getElementById('total-categories');

      if (totalProducts) totalProducts.textContent = stats.totalProducts || 0;
      if (productsInStock) productsInStock.textContent = stats.productsInStock || 0;
      if (totalCategories) totalCategories.textContent = stats.totalCategories || 0;
    } catch (error) {
      console.error('Error loading admin stats:', error);
    }
  }

  async renderAdminProducts() {
    const adminProductsList = document.getElementById('admin-products-list');
    if (!adminProductsList) return;

    await this.loadProducts();

    adminProductsList.innerHTML = this.products.map(product => `
      <div class="admin-product-card">
        <div class="admin-product-header">
          <h3 class="admin-product-title">${product.name}</h3>
          <div class="admin-product-actions">
            <button class="admin-btn edit" onclick="app.editProduct(${product.id})">Editar</button>
            <button class="admin-btn delete" onclick="app.deleteProduct(${product.id})">Eliminar</button>
          </div>
        </div>
        <div class="admin-product-details">
          <p class="admin-product-detail"><strong>Precio:</strong> $${product.price.toFixed(2)}</p>
          <p class="admin-product-detail"><strong>Categoría:</strong> ${product.category}</p>
          <p class="admin-product-detail"><strong>Vendedor:</strong> ${product.seller}</p>
          <p class="admin-product-detail"><strong>Stock:</strong> ${product.stock}</p>
          <p class="admin-product-detail"><strong>Valoración:</strong> ${product.rating}/5 (${product.reviews} reseñas)</p>
        </div>
      </div>
    `).join('');
  }

  // Métodos de reportes
  async updateReportStats() {
    try {
      const result = await apiCall('reports.php?action=stats');
      const stats = result.data;
      
      const totalSales = document.getElementById('total-sales');
      const completedOrders = document.getElementById('completed-orders');
      const productsSold = document.getElementById('products-sold');

      if (totalSales) totalSales.textContent = `$${(stats.totalSales || 0).toFixed(2)}`;
      if (completedOrders) completedOrders.textContent = stats.completedOrders || 0;
      if (productsSold) productsSold.textContent = stats.productsSold || 0;
    } catch (error) {
      console.error('Error loading report stats:', error);
    }
  }

  // Métodos de productos
  async addProduct(productData) {
    try {
      await apiCall('products.php?action=create', 'POST', productData);
      await this.renderAdmin();
      this.hideModal();
    } catch (error) {
      alert(error.message || 'Error al crear el producto');
    }
  }

  async editProduct(productId) {
    try {
      const result = await apiCall(`products.php?action=get&id=${productId}`);
      const product = result.data;
      
      if (!product) {
        alert('Producto no encontrado');
        return;
      }

      // Llenar el formulario con los datos del producto
      document.getElementById('product-name').value = product.name;
      document.getElementById('product-price').value = product.price;
      document.getElementById('product-category').value = product.category;
      document.getElementById('product-seller').value = product.seller;
      document.getElementById('product-description').value = product.description;
      document.getElementById('product-stock').value = product.stock;
      document.getElementById('product-image').value = product.image;
      
      this.editingProductId = productId;
      document.querySelector('.modal-title').textContent = 'Editar Producto';

      // Mostrar modal
      this.showModal();
    } catch (error) {
      alert(error.message || 'Error al cargar el producto');
    }
  }

  async deleteProduct(productId) {
    if (!confirm('¿Estás seguro de que quieres eliminar este producto?')) {
      return;
    }
    
    try {
      await apiCall(`products.php?action=delete&id=${productId}`, 'POST');
      await this.renderAdmin();
    } catch (error) {
      alert(error.message || 'Error al eliminar el producto');
    }
  }

  async updateProduct(productId, productData) {
    try {
      await apiCall('products.php?action=update', 'POST', { id: productId, ...productData });
      await this.renderAdmin();
      this.hideModal();
    } catch (error) {
      alert(error.message || 'Error al actualizar el producto');
    }
  }

  // Métodos del modal
  showModal() {
    const modal = document.getElementById('product-modal');
    if (modal) {
      modal.classList.remove('hidden');
    }
  }

  hideModal() {
    const modal = document.getElementById('product-modal');
    if (modal) {
      modal.classList.add('hidden');
      this.clearProductForm();
    }
  }

  clearProductForm() {
    const form = document.getElementById('product-form');
    if (form) {
      form.reset();
    }
    this.editingProductId = null;
    document.querySelector('.modal-title').textContent = 'Agregar Producto';
  }

  // Función de debug
  debug() {
    console.log('=== APP DEBUG ===');
    console.log('Current screen:', this.currentScreen);
    console.log('User role:', this.userRole);
    console.log('Cart items:', this.cart);
    console.log('Products:', this.products);
    console.log('Orders:', this.orders);
    console.log('================');
  }

  // Función de debug específica para el carrito
  debugCart() {
    console.log('=== CART DEBUG ===');
    console.log('Cart items:', this.cart);
    console.log('Cart length:', this.cart.length);
    console.log('Current screen:', this.currentScreen);
    
    const emptyCart = document.getElementById('empty-cart');
    const cartContent = document.getElementById('cart-content');
    const cartItems = document.querySelector('.cart-items');
    
    console.log('Empty cart element:', emptyCart);
    console.log('Cart content element:', cartContent);
    console.log('Cart items element:', cartItems);
    console.log('Empty cart hidden:', emptyCart?.classList.contains('hidden'));
    console.log('Cart content hidden:', cartContent?.classList.contains('hidden'));
    console.log('==================');
  }
}

// ===== INICIALIZACIÓN DE LA APLICACIÓN =====
let app;

document.addEventListener('DOMContentLoaded', function() {
  app = new AppState();
  
  // Inicializar la aplicación
  app.renderScreen();
  app.updateNavigation();
  app.updateCartUI();

  // Event listeners para navegación
  document.addEventListener('click', function(e) {
    if (e.target.matches('[data-screen]')) {
      const screen = e.target.getAttribute('data-screen');
      app.navigateTo(screen);
    }
  });

  // Event listeners para login
  const loginForm = document.getElementById('login-form');
  const registerForm = document.getElementById('register-form');
  const guestBtn = document.getElementById('guest-btn');

  if (loginForm) {
    loginForm.addEventListener('submit', async function(e) {
      e.preventDefault();
      const email = document.getElementById('login-email').value;
      const password = document.getElementById('login-password').value;
      
      try {
        await app.login(email, password);
      } catch (error) {
        // Error ya mostrado en el método login
      }
    });
  }

  if (registerForm) {
    registerForm.addEventListener('submit', async function(e) {
      e.preventDefault();
      const name = document.getElementById('register-name').value;
      const email = document.getElementById('register-email').value;
      const password = document.getElementById('register-password').value;
      const confirmPassword = document.getElementById('confirm-password').value;
      
      try {
        await app.register(name, email, password, confirmPassword);
      } catch (error) {
        // Error ya mostrado en el método register
      }
    });
  }

  if (guestBtn) {
    guestBtn.addEventListener('click', function() {
      app.loginAsGuest();
    });
  }

  // Event listeners para tabs
  document.addEventListener('click', function(e) {
    if (e.target.matches('.tab-btn')) {
      const tab = e.target.getAttribute('data-tab');
      
      // Actualizar botones de tab
      document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.classList.remove('active');
      });
      e.target.classList.add('active');
      
      // Mostrar formulario correspondiente
      document.querySelectorAll('.login-form').forEach(form => {
        form.classList.remove('active');
      });
      document.getElementById(`${tab}-form`).classList.add('active');
    }
  });

  // Event listeners para filtros
  const searchInput = document.getElementById('search-input');
  const categoryFilter = document.getElementById('category-filter');
  const sortFilter = document.getElementById('sort-filter');

  if (searchInput) {
    let searchTimeout;
    searchInput.addEventListener('input', function() {
      clearTimeout(searchTimeout);
      searchTimeout = setTimeout(() => {
        app.renderCatalog();
      }, 300);
    });
  }

  if (categoryFilter) {
    categoryFilter.addEventListener('change', function() {
      app.renderCatalog();
    });
  }

  if (sortFilter) {
    sortFilter.addEventListener('change', function() {
      app.renderCatalog();
    });
  }

  // Event listeners para carrito
  const checkoutBtn = document.getElementById('checkout-btn');
  if (checkoutBtn) {
    checkoutBtn.addEventListener('click', async function() {
      await app.placeOrder();
    });
  }

  // Event listeners para logout
  const logoutBtn = document.getElementById('logout-btn');
  if (logoutBtn) {
    logoutBtn.addEventListener('click', async function() {
      await app.logout();
    });
  }

  // Event listeners para administración
  const addProductBtn = document.getElementById('add-product-btn');
  if (addProductBtn) {
    addProductBtn.addEventListener('click', function() {
      app.showModal();
    });
  }

  // Event listeners para modal
  const modalClose = document.getElementById('modal-close');
  const modalCancel = document.getElementById('modal-cancel');
  const productForm = document.getElementById('product-form');

  if (modalClose) {
    modalClose.addEventListener('click', function() {
      app.hideModal();
    });
  }

  if (modalCancel) {
    modalCancel.addEventListener('click', function() {
      app.hideModal();
    });
  }

  if (productForm) {
    productForm.addEventListener('submit', async function(e) {
      e.preventDefault();
      
      const productData = {
        name: document.getElementById('product-name').value,
        price: parseFloat(document.getElementById('product-price').value),
        category: document.getElementById('product-category').value,
        seller: document.getElementById('product-seller').value,
        description: document.getElementById('product-description').value,
        stock: parseInt(document.getElementById('product-stock').value),
        image: document.getElementById('product-image').value
      };
      
      if (app.editingProductId) {
        await app.updateProduct(app.editingProductId, productData);
      } else {
        await app.addProduct(productData);
      }
    });
  }

  // Cerrar modal al hacer clic fuera
  const modal = document.getElementById('product-modal');
  if (modal) {
    modal.addEventListener('click', function(e) {
      if (e.target === modal || e.target.classList.contains('modal-overlay')) {
        app.hideModal();
      }
    });
  }

  // Manejo de errores de imágenes
  document.addEventListener('error', function(e) {
    if (e.target.tagName === 'IMG') {
      e.target.src = 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjAwIiBoZWlnaHQ9IjIwMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiBmaWxsPSIjZjNmNGY2Ii8+PHRleHQgeD0iNTAlIiB5PSI1MCUiIGZvbnQtZmFtaWx5PSJBcmlhbCwgc2Fucy1zZXJpZiIgZm9udC1zaXplPSIxNCIgZmlsbD0iIzk5YTNhZiIgdGV4dC1hbmNob3I9Im1pZGRsZSIgZHk9Ii4zZW0iPkltYWdlbiBubyBkaXNwb25pYmxlPC90ZXh0Pjwvc3ZnPg==';
    }
  }, true);
});

// ===== FUNCIONES GLOBALES PARA USO EN HTML =====
window.app = app;

// Función de debug global
window.debugApp = function() {
  if (app) {
    app.debug();
  } else {
    console.log('App not initialized yet');
  }
};

// Función de debug del carrito global
window.debugCart = function() {
  if (app) {
    app.debugCart();
  } else {
    console.log('App not initialized yet');
  }
};

