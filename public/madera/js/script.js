// Variables globales
let cart = [];
let allProducts = [];
let currentProduct = null;
let quickviewQuantity = 1;
let isLoggedIn = false;
let currentUser = null;
let users = JSON.parse(localStorage.getItem('users')) || [];
let authToken = localStorage.getItem('authToken');
let currentSessionId = localStorage.getItem('sessionId') || generateSessionId();

// Variables para el catálogo de dos niveles
let currentCategory = '';
let currentProducts = [];
let filteredProducts = [];


/* ======  CATALOGO 2-NIVELES  ====== */
let currentCatSlug = '';          // categoría activa
let rawProds   = [];              // productos sin filtro de la cat
let filtProds  = []; 

// Variables de productos y categorías desde API
let products = [];
let categories = [];

// NEWSLETTER VARIABLES
let newsletterSubscribers = JSON.parse(localStorage.getItem('newsletterSubscribers')) || [];
let newsletterStats = JSON.parse(localStorage.getItem('newsletterStats')) || {
    subscribers: 1247,
    offersSent: 156,
    exclusiveContent: 28
};

// API BASE URL
const API_BASE_URL = 'https://madera-viva-laravel-production.up.railway.app/api';



// Delegación global: UN SOLO LISTENER para productos y ofertas
document.addEventListener('DOMContentLoaded', () => {
    document.addEventListener('click', handleGlobalClicks);
});

function handleGlobalClicks(e) {
    /* ---------- 1. ADD TO CART (productos u ofertas) ---------- */
    const addBtn = e.target.closest('.add-to-cart, .offer-add-to-cart');
    if (addBtn) {
        e.preventDefault?.(e);   // por si es <a>
        e.stopPropagation();
        const { id, name, price, image } = addBtn.dataset;
        if (!id || !name || !price || !image) {
            console.warn('❌ Faltan datos en el botón de carrito');
            return;
        }
        addToCart(id, name, price, image);
        return; // evita que siga evaluando
    }

    /* ---------- 2. QUICKVIEW (tarjetas, pero no en botón) ---------- */
    const card = e.target.closest('.product-card, .offer-card');
    if (card && !e.target.closest('.add-to-cart, .offer-add-to-cart')) {
        const productId = card.dataset.id;
        if (productId) openQuickview(productId);
    }
}


// Generar session ID único
function generateSessionId() {
    const sessionId = 'session_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
    localStorage.setItem('sessionId', sessionId);
    return sessionId;
}

// Headers para las peticiones API
function getAuthHeaders() {
    const headers = {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-Session-ID': currentSessionId
    };
    
    if (authToken) {
        headers['Authorization'] = `Bearer ${authToken}`;
    }
    
    return headers;
}

// Catálogo de productos (como fallback)
const fallbackProducts = [
    {
        id: 1,
        name: "Mesa de Centro Roble Clásica",
        price: 4999,
        brand: "MaderaViva",
        image_url: "https://images.unsplash.com/photo-1567538096630-e0c55bd6374c?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80",
        category: "mesas",
        description: "Mesa de centro fabricada en roble macizo con acabado natural. Diseño moderno y funcional que se adapta a cualquier espacio de tu hogar.",
        features: ["Madera maciza de roble", "100x50x40 cm", "Acabado natural con protección ecológica", "Garantía 2 años"],
        is_offer: true,
        old_price: 5999
    },
    {
        id: 2,
        name: "Mesa de Comedor Extensible Premium",
        price: 12999,
        brand: "MaderaViva",
        image_url: "https://images.unsplash.com/photo-1533090368676-1fd25485db88?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80",
        category: "mesas",
        description: "Mesa de comedor extensible de roble macizo con capacidad para 6-8 personas. Diseño robusto y elegante para reuniones familiares.",
        features: ["Roble macizo premium", "180x90x75 cm, extensible hasta 240 cm", "Sistema extensible alemán", "Garantía 5 años"],
        is_offer: false
    },
    {
        id: 3,
        name: "Silla de Comedor Elegante",
        price: 2499,
        brand: "MaderaViva",
        image_url: "https://images.unsplash.com/photo-1503602642458-232111445657?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80",
        category: "sillas",
        description: "Silla de comedor con diseño elegante y ergonómico. Perfecta para complementar tu mesa de comedor.",
        features: ["Madera de alta calidad", "Respaldo ergonómico", "Acabado en tono natural", "Garantía 2 años"],
        is_offer: true,
        old_price: 2999
    },
    {
        id: 4,
        name: "Sofá Seccional Moderno",
        price: 18999,
        brand: "MaderaViva",
        image_url: "https://images.unsplash.com/photo-1586023492125-27b2c045efd7?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80",
        category: "sofas",
        description: "Sofá seccional moderno con diseño contemporáneo y máxima comodidad. Ideal para salas espaciosas.",
        features: ["Estructura en madera sólida", "Espuma de alta densidad", "Tela resistente", "Garantía 3 años"],
        is_offer: false
    }
];

// ========== SISTEMA DE AUTENTICACIÓN ==========

// Función para mostrar alertas
function showAlert(message, type = 'success') {
    // Crear contenedor de alertas si no existe
    let alertContainer = document.getElementById('alert-container');
    if (!alertContainer) {
        alertContainer = document.createElement('div');
        alertContainer.id = 'alert-container';
        alertContainer.className = 'alert-container';
        document.body.appendChild(alertContainer);
    }

    const alert = document.createElement('div');
    alert.className = `alert alert-${type}`;
    alert.textContent = message;
    alertContainer.appendChild(alert);

    setTimeout(() => {
        alert.remove();
    }, 3000);
}

// Función para actualizar la interfaz de usuario según el estado de autenticación
function updateAuthUI() {
    const authIcons = document.getElementById('auth-icons');
    const userInfo = document.getElementById('user-info');
    const userName = document.getElementById('user-name');
    const protectedElements = document.querySelectorAll('[data-protected]');

    if (isLoggedIn && currentUser) {
        // Usuario logueado
        if (authIcons) authIcons.classList.add('d-none');
        if (userInfo) userInfo.classList.remove('d-none');
        if (userName) userName.textContent = currentUser.name;
        
        // Mostrar elementos protegidos
        protectedElements.forEach(el => {
            el.style.display = el.dataset.protectedDisplay || 'block';
        });
    } else {
        // Usuario no logueado
        if (authIcons) authIcons.classList.remove('d-none');
        if (userInfo) userInfo.classList.add('d-none');
        
        // Ocultar elementos protegidos
        protectedElements.forEach(el => {
            el.style.display = 'none';
        });
    }
}


// Función para registrar un nuevo usuario
async function registerUser(name, email, password) {
    try {
        const response = await fetch(`${API_BASE_URL}/register`, {
            method: 'POST',
            headers: getAuthHeaders(),
            body: JSON.stringify({
                name: name,
                email: email,
                password: password,
                password_confirmation: password
            })
        });

        const data = await response.json();

        if (data.success) {
            showAlert('¡Registro exitoso! Ahora puedes iniciar sesión.');
            return true;
        } else {
            if (data.errors) {
                const errorMessage = Object.values(data.errors).flat().join(', ');
                showAlert(errorMessage, 'danger');
            } else {
                showAlert(data.message || 'Error en el registro', 'danger');
            }
            return false;
        }
    } catch (error) {
        console.error('Error en registro:', error);
        showAlert('Error de conexión. Por favor, intenta más tarde.', 'danger');
        return false;
    }
}

// Función para iniciar sesión
async function loginUser(email, password) {
    try {
        const response = await fetch(`${API_BASE_URL}/login`, {
            method: 'POST',
            headers: getAuthHeaders(),
            body: JSON.stringify({
                email: email,
                password: password
            })
        });

        const data = await response.json();

        if (data.success) {
            isLoggedIn = true;
            currentUser = data.user;
            authToken = data.token;
            
            // Guardar en localStorage
            localStorage.setItem('authToken', authToken);
            localStorage.setItem('currentUser', JSON.stringify(currentUser));
            
            updateAuthUI();
            showAlert(data.message || `¡Bienvenido de nuevo, ${currentUser.name}!`);
            
            // Cargar carrito del usuario
            await loadUserCart();
            
            return true;
        } else {
            showAlert(data.message || 'Correo electrónico o contraseña incorrectos', 'danger');
            return false;
        }
    } catch (error) {
        console.error('Error en login:', error);
        showAlert('Error de conexión. Por favor, intenta más tarde.', 'danger');
        return false;
    }
}

// Función para cerrar sesión
async function logoutUser() {
    try {
        if (authToken) {
            await fetch(`${API_BASE_URL}/logout`, {
                method: 'POST',
                headers: getAuthHeaders()
            });
        }
    } catch (error) {
        console.error('Error en logout:', error);
    } finally {
        // Limpiar estado local
        isLoggedIn = false;
        currentUser = null;
        authToken = null;
        cart = [];
        
        // Limpiar localStorage
        localStorage.removeItem('authToken');
        localStorage.removeItem('currentUser');
        
        updateAuthUI();
        updateCart();
        showAlert('Sesión cerrada correctamente');
    }
}

// Función para verificar autenticación al cargar la página
async function checkAuthStatus() {
    if (authToken) {
        try {
            const response = await fetch(`${API_BASE_URL}/me`, {
                method: 'GET',
                headers: getAuthHeaders()
            });

            if (response.ok) {
                const data = await response.json();
                if (data.success) {
                    isLoggedIn = true;
                    currentUser = data.user;
                    await loadUserCart();
                } else {
                    // Token inválido, limpiar estado
                    await clearAuthState();
                }
            } else if (response.status === 401) {
                // No autorizado, limpiar estado
                await clearAuthState();
            }
        } catch (error) {
            console.error('Error verificando autenticación:', error);
            await clearAuthState();
        }
    }
    updateAuthUI();
}

// Función para limpiar estado de autenticación
async function clearAuthState() {
    isLoggedIn = false;
    currentUser = null;
    authToken = null;
    cart = [];
    
    localStorage.removeItem('authToken');
    localStorage.removeItem('currentUser');
    
    updateAuthUI();
    updateCart();
}



// ========== SISTEMA DE CARRITO ==========

// Función para cargar el carrito del usuario
async function loadUserCart() {
    try {
        if (!isLoggedIn) {
            await loadLocalCart();
            return;
        }

        const response = await fetch(`${API_BASE_URL}/cart`, {
            method: 'GET',
            headers: getAuthHeaders()
        });

        if (response.ok) {
            const data = await response.json();
            if (data.success) {
                // Convertir datos del API al formato local
                cart = data.cart.items.map(item => ({
                    id: item.id || item.product_id,
                    product_id: item.product_id,
                    name: item.product?.name || 'Producto',
                    price: parseFloat(item.unit_price || 0),
                    image: item.product?.image_url || item.product?.image || '',
                    quantity: item.quantity,
                    subtotal: parseFloat(item.subtotal || 0),
                    discount: parseFloat(item.discount || 0)
                }));
                updateCart();
            }
        } else if (response.status === 404) {
            // Carrito vacío - inicializar array vacío
            cart = [];
            updateCart();
        }
    } catch (error) {
        console.error('Error cargando carrito:', error);
        await loadLocalCart();
    }
}

// Función para cargar carrito local
async function loadLocalCart() {
    const savedCart = localStorage.getItem('cart');
    if (savedCart) {
        cart = JSON.parse(savedCart);
        updateCart();
    }
}

// Función para guardar carrito local
function saveLocalCart() {
    localStorage.setItem('cart', JSON.stringify(cart));
}

// Función unificada para añadir productos al carrito
// Función para añadir productos al carrito
async function addToCart(productId, name, price, image, quantity = 1) {
    if (!isLoggedIn) {
        showAlert('Debes iniciar sesión para añadir productos al carrito', 'warning');
        openAuthModal();
        return;
    }

    try {
        const response = await fetch(`${API_BASE_URL}/cart/add`, {
            method: 'POST',
            headers: getAuthHeaders(),
            body: JSON.stringify({
                product_id: parseInt(productId),
                quantity: quantity
            })
        });

        const data = await response.json();

        if (data.success) {
            // Actualizar carrito local con la respuesta del servidor
            if (data.cart && data.cart.items) {
                cart = data.cart.items.map(item => ({
                    id: item.id, // cart_item_id
                    product_id: item.product_id,
                    name: item.product?.name || name,
                    price: parseFloat(item.unit_price || price),
                    image: item.product?.image_url || item.product?.image || image,
                    quantity: item.quantity,
                    subtotal: parseFloat(item.subtotal || (item.unit_price * item.quantity)),
                    discount: parseFloat(item.discount || 0)
                }));
            }
            
            updateCart();
            showAlert(data.message || 'Producto añadido al carrito', 'success');
            
            // Feedback visual en los botones
            showAddToCartFeedback(productId);
        } else {
            showAlert(data.message || 'Error al añadir al carrito', 'danger');
        }
    } catch (error) {
        console.error('Error añadiendo al carrito:', error);
        showAlert('Error de conexión al añadir al carrito', 'danger');
    }
}

// Función para mostrar feedback visual en los botones de añadir al carrito
function showAddToCartFeedback(productId) {
    const buttons = document.querySelectorAll(`
        .add-to-cart[data-id="${productId}"],
        .offer-add-to-cart[data-id="${productId}"],
        .quickview-add-to-cart[data-id="${productId}"]
    `);

    buttons.forEach(button => {
        const originalText = button.textContent;
        const originalBgColor = button.style.backgroundColor;

        // Cambiar texto y estilo del botón
        button.textContent = '¡Añadido!';
        button.style.backgroundColor = '#7D6E5D';
        button.classList.add('added');

        // Restaurar texto y estilo original después de 2 segundos
        setTimeout(() => {
            button.textContent = originalText.includes('Añadir') ? 'Añadir al carrito' : 'Agregar al carrito';
            button.style.backgroundColor = originalBgColor;
            button.classList.remove('added');
        }, 2000);
    });
}

// Función para actualizar cantidad en el carrito
async function updateQuantity(productId, change) {
    if (!isLoggedIn) {
        showAlert('Debes iniciar sesión para modificar el carrito', 'warning');
        return;
    }

    const item = cart.find(item => item.product_id == productId || item.id == productId);
    if (!item) return;

    const newQuantity = item.quantity + change;

    if (newQuantity <= 0) {
        await removeFromCart(productId);
        return;
    }

    try {
        const cartItemId = getCartItemId(productId);
        
        const response = await fetch(`${API_BASE_URL}/cart/items/${cartItemId}`, {
            method: 'PUT',
            headers: getAuthHeaders(),
            body: JSON.stringify({
                quantity: newQuantity
            })
        });

        const data = await response.json();

        if (data.success) {
            // Actualizar carrito local con los datos del servidor
            cart = data.cart.items.map(item => ({
                id: item.id || item.product_id,
                product_id: item.product_id,
                name: item.product?.name || item.name,
                price: parseFloat(item.unit_price || item.price),
                image: item.product?.image_url || item.product?.image || item.image,
                quantity: item.quantity,
                subtotal: parseFloat(item.subtotal),
                discount: parseFloat(item.discount || 0)
            }));
            
            updateCart();
            showAlert('Cantidad actualizada correctamente', 'success');
        } else {
            showAlert(data.message || 'Error al actualizar cantidad', 'danger');
        }
    } catch (error) {
        console.error('Error actualizando cantidad:', error);
        showAlert('Error de conexión al actualizar cantidad', 'danger');
    }
}

// Función para obtener el ID del item del carrito desde la respuesta del servidor
function getCartItemId(productId, cartItems = null) {
    const items = cartItems || cart;
    const item = items.find(item => item.product_id == productId || item.id == productId);
    return item ? item.id : productId; // Fallback al product_id si no hay cart_item_id
}

// Función para eliminar producto del carrito
async function removeFromCart(productId) {
    if (!isLoggedIn) {
        showAlert('Debes iniciar sesión para modificar el carrito', 'warning');
        return;
    }

    try {
        const cartItemId = getCartItemId(productId);
        
        const response = await fetch(`${API_BASE_URL}/cart/items/${cartItemId}`, {
            method: 'DELETE',
            headers: getAuthHeaders()
        });

        const data = await response.json();

        if (data.success) {
            // Actualizar carrito local
            cart = data.cart?.items?.map(item => ({
                id: item.id || item.product_id,
                product_id: item.product_id,
                name: item.product?.name || item.name,
                price: parseFloat(item.unit_price || item.price),
                image: item.product?.image_url || item.product?.image || item.image,
                quantity: item.quantity,
                subtotal: parseFloat(item.subtotal),
                discount: parseFloat(item.discount || 0)
            })) || [];
            
            updateCart();
            showAlert(data.message || 'Producto eliminado del carrito', 'success');
        } else {
            showAlert(data.message || 'Error al eliminar producto', 'danger');
        }
    } catch (error) {
        console.error('Error eliminando del carrito:', error);
        showAlert('Error de conexión al eliminar producto', 'danger');
    }
}

// Función  para vaciar el carrito
async function clearCart() {
    if (!isLoggedIn) {
        showAlert('Debes iniciar sesión para modificar el carrito', 'warning');
        return;
    }

    try {
        const response = await fetch(`${API_BASE_URL}/cart/clear`, {
            method: 'DELETE',
            headers: getAuthHeaders()
        });

        const data = await response.json();

        if (data.success) {
            cart = [];
            updateCart();
            showAlert(data.message || 'Carrito vaciado correctamente', 'success');
        } else {
            showAlert(data.message || 'Error al vaciar carrito', 'danger');
        }
    } catch (error) {
        console.error('Error vaciando carrito:', error);
        showAlert('Error de conexión al vaciar carrito', 'danger');
    }
}

// Función para actualizar la visualización del carrito
function updateCart() {
    const cartCount = document.querySelector('.cart-count');
    const itemCount = cart.reduce((total, item) => total + item.quantity, 0);
    
    if (cartCount) cartCount.textContent = itemCount;

    const cartItems = document.getElementById('cart-items');
    const emptyCart = document.getElementById('empty-cart');
    const totalAmount = document.getElementById('total-amount');
    const subtotalAmount = document.getElementById('subtotal-amount');
    const discountAmount = document.getElementById('discount-amount');
    const checkoutBtn = document.getElementById('checkout-btn');
    const checkoutMessage = document.getElementById('checkout-message');
    const clearCartBtn = document.getElementById('clear-cart-btn');

    // Limpiar el contenido del carrito
    if (cartItems) {
        cartItems.innerHTML = '';

        if (cart.length === 0) {
            // Mostrar carrito vacío
            if (emptyCart) emptyCart.style.display = 'block';
            const cartFooter = document.querySelector('.cart-footer');
            if (cartFooter) cartFooter.style.display = 'block';
            if (totalAmount) totalAmount.textContent = '$0 MXN';
            if (subtotalAmount) subtotalAmount.textContent = '$0 MXN';
            if (discountAmount) discountAmount.textContent = '$0 MXN';
            
            // Deshabilitar botón de checkout y mostrar mensaje
            if (checkoutBtn) checkoutBtn.disabled = true;
            if (checkoutMessage) checkoutMessage.classList.remove('d-none');
            if (clearCartBtn) clearCartBtn.style.display = 'none';
        } else {
            // Ocultar mensaje de carrito vacío
            if (emptyCart) emptyCart.style.display = 'none';
            const cartFooter = document.querySelector('.cart-footer');
            if (cartFooter) cartFooter.style.display = 'block';

            // Calcular totales
            const subtotal = cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
            const totalDiscount = cart.reduce((sum, item) => sum + (item.discount || 0), 0);
            const total = subtotal - totalDiscount;

            if (subtotalAmount) subtotalAmount.textContent = `$${subtotal.toLocaleString('es-MX')} MXN`;
            if (discountAmount) discountAmount.textContent = `-$${totalDiscount.toLocaleString('es-MX')} MXN`;
            if (totalAmount) totalAmount.textContent = `$${total.toLocaleString('es-MX')} MXN`;

            // Habilitar botón de checkout y ocultar mensaje
            if (checkoutBtn) checkoutBtn.disabled = false;
            if (checkoutMessage) checkoutMessage.classList.add('d-none');
            if (clearCartBtn) clearCartBtn.style.display = 'block';

            // Renderizar items del carrito
            cart.forEach(item => {
                const cartItem = document.createElement('div');
                cartItem.className = 'cart-item';
                cartItem.innerHTML = `
                    <div class="cart-item-image">
                        <img src="${item.image}" alt="${item.name}" onerror="this.src='https://images.unsplash.com/photo-1567538096630-e0c55bd6374c?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80'">
                    </div>
                    <div class="cart-item-details">
                        <div class="cart-item-name">
                            ${item.name}
                            <button class="cart-item-remove" data-id="${item.product_id || item.id}">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                        <p class="cart-item-price">$${item.price.toLocaleString('es-MX')} MXN</p>
                        ${item.discount > 0 ? `<p class="cart-item-discount text-success">Descuento: -$${item.discount.toLocaleString('es-MX')} MXN</p>` : ''}
                        <div class="cart-item-quantity">
                            <button class="quantity-btn minus" data-id="${item.product_id || item.id}">-</button>
                            <span>${item.quantity}</span>
                            <button class="quantity-btn plus" data-id="${item.product_id || item.id}">+</button>
                        </div>
                        <p class="cart-item-subtotal">Subtotal: $${((item.price * item.quantity) - (item.discount || 0)).toLocaleString('es-MX')} MXN</p>
                    </div>
                `;
                cartItems.appendChild(cartItem);
            });

            // Añadir event listeners
            document.querySelectorAll('.quantity-btn.minus').forEach(btn => {
                btn.addEventListener('click', () => updateQuantity(btn.dataset.id, -1));
            });

            document.querySelectorAll('.quantity-btn.plus').forEach(btn => {
                btn.addEventListener('click', () => updateQuantity(btn.dataset.id, 1));
            });

            document.querySelectorAll('.cart-item-remove').forEach(btn => {
                btn.addEventListener('click', () => removeFromCart(btn.dataset.id));
            });
        }
    }

    // Guardar carrito localmente como respaldo
    saveLocalCart();
}


// ========== SISTEMA DE PRODUCTOS Y CATEGORÍAS DESDE API ==========

// FUNCIÓN LOADCATEGORIES 
async function loadCategories() {
    try {
        console.log('🔄 Cargando categorías desde API...');
        
        const response = await fetch(`${API_BASE_URL}/categories`, {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            }
        });
        
        console.log('📊 Status de respuesta:', response.status);
        console.log('📋 Headers de respuesta:', response.headers);
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        // Verificar que la respuesta sea JSON
        const contentType = response.headers.get('content-type');
        if (!contentType || !contentType.includes('application/json')) {
            const textResponse = await response.text();
            console.error('❌ La respuesta no es JSON:', textResponse.substring(0, 200));
            throw new Error('La respuesta del servidor no es JSON válido');
        }
        
        const data = await response.json();
        console.log('📦 Datos recibidos:', data);
        
        if (data.success && data.data) {
            console.log(`✅ ${data.data.length} categorías cargadas desde API`);
            return data.data;
        } else {
            console.warn('⚠️ API devolvió success=false, usando respaldo');
            return loadFallbackCategories();
        }
    } catch (error) {
        console.error('❌ Error cargando categorías:', error);
        console.log('🔄 Usando categorías de respaldo...');
        return loadFallbackCategories();
    }
}


// FUNCIÓN LOADPRODUCTS 
async function loadProducts() {
    try {
        console.log('🔄 Cargando productos desde API...');
        
        const response = await fetch(`${API_BASE_URL}/products`, {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            }
        });
        
        console.log('📊 Status de productos:', response.status);
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        // Verificar contenido
        const contentType = response.headers.get('content-type');
        if (!contentType || !contentType.includes('application/json')) {
            const text = await response.text();
            console.error('❌ Respuesta de productos no es JSON:', text.substring(0, 200));
            throw new Error('Respuesta de productos no es JSON');
        }
        
        const data = await response.json();
        console.log('📦 Datos de productos recibidos:', data);
        
        if (data.success && data.data) {
            products = data.data.map(product => {
                const finalPrice = product.discount_percentage > 0 && product.old_price
                    ? product.old_price * (1 - product.discount_percentage / 100)
                    : product.price;

                return {
                    id: product.id,
                    name: product.name,
                    description: product.description,
                    price: parseFloat(finalPrice),
                    original_price: parseFloat(product.price),
                    discount: parseFloat(product.discount_percentage || 0),
                    old_price: product.old_price ? parseFloat(product.old_price) : null,
                    category: product.category?.name || product.category,
                    category_id: product.category_id,
                    image_url: product.image_url,
                    brand: product.brand,
                    features: product.features || [],
                    is_offer: product.discount_percentage > 0,
                    active: product.active
                };
            });
            
            console.log(`✅ ${products.length} productos cargados desde API`);
            return true;
        } else {
            console.error('❌ Error en respuesta API de productos:', data.message);
            loadFallbackProducts();
            return false;
        }
    } catch (error) {
        console.error('❌ Error cargando productos:', error);
        loadFallbackProducts();
        return false;
    }
}

///FUNCION PARA CARGAR TODOS LOS PRODUCTOS
async function loadAllProducts() {
    try {
        const res = await fetch(`${API_BASE_URL}/products`);
        const data = await res.json();
        if (data.success) {
            const all = data.data.filter(p => p.active);
            renderProductsGrid(all); // tu función que pinta tarjetas
        }
    } catch (e) {
        console.error('Error cargando todos los productos:', e);
    }
}

function renderProductsGrid(list) {
  const grid = document.getElementById('products-grid'); // dentro de #catalog
  if (!grid) return;
  grid.innerHTML = '';

  if (list.length === 0) {
    grid.innerHTML = `<p class="no-products">No hay productos para esta categoría.</p>`;
    return;
  }

  list.forEach(p => {
    const card = document.createElement('div');
    card.className = 'product-card fade-in';
    card.dataset.id = p.id;
    card.dataset.brand = p.brand;
    card.dataset.price = p.price;

    const finalPrice = p.discount_percentage > 0 && p.old_price
      ? p.old_price * (1 - p.discount_percentage / 100)
      : p.price;

    const imgUrl = p.image_url ?? 'https://images.unsplash.com/photo-1567538096630-e0c55bd6374c?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80';

    card.innerHTML = `
      <div class="product-image">
        <img src="${imgUrl}" alt="${p.name}" onerror="this.src='https://images.unsplash.com/photo-1567538096630-e0c55bd6374c?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80'">
        ${p.discount_percentage > 0 ? `<div class="product-badge">-${p.discount_percentage}%</div>` : ''}
      </div>
      <div class="product-info">
        <h3 class="product-name">${p.name}</h3>
        <p class="product-brand">${p.brand}</p>
        <div class="product-price-container">
          <span class="product-price">$${finalPrice.toLocaleString('es-MX')} MXN</span>
          ${p.discount_percentage > 0 && p.old_price ? `<span class="product-old-price">$${p.old_price.toLocaleString('es-MX')} MXN</span>` : ''}
        </div>
        <button class="add-to-cart"
                data-id="${p.id}"
                data-name="${p.name}"
                data-price="${finalPrice}"
                data-image="${imgUrl}">
          Agregar al carrito
        </button>
      </div>
    `;
    grid.appendChild(card);

    // Abrir quickview al clic en tarjeta (menos botón)
    card.addEventListener('click', function (e) {
      if (!e.target.classList.contains('add-to-cart')) openQuickview(p.id);
    });
  });
}

// Botón Volver
document.addEventListener('click', e => {
  const back = e.target.closest('.back-button');
  if (back) {
    document.querySelectorAll('#catalog .view').forEach(v => v.classList.remove('active'));
    document.getElementById('category-summary-view').classList.add('active');
  }
});


document.addEventListener('DOMContentLoaded', async () => {
    await checkAuthStatus();
    await loadCategories(); // 1. datos → array
    renderCatCards();       // 2. ¡pintar!
    initCatalogSection();   // 3. botón volver + mostrar Vista A
});




/* 1. Pinta Vista A */
function renderCatCards(){
    const grid = document.getElementById('categories-grid');
    if(!grid){ console.error('Grid no encontrado'); return; }
    grid.innerHTML = ''; // limpia
    console.log('Pintando', categories.length, 'categorías');
    categories.forEach(c=>{
        const card = document.createElement('div');
        card.className = 'category-card';
        card.dataset.slug = c.slug || c.id;
        card.innerHTML = `
            <img src="${c.image_url}" alt="${c.name}" onerror="this.src='https://i.imgur.com/1LjmKhB.jpeg'">
            <h3>${c.name}</h3>
        `;
        grid.appendChild(card);
    });
}

/* 2. Vista B – carga productos */
async function showProdView(slug){
    currentCatSlug = slug;
    toggleViews('product-list-view');
    const cat = categories.find(x=>(x.slug||x.id)==slug);
    if(cat) document.getElementById('product-list-title').textContent = cat.name;

    const res = await fetch(`${API_BASE_URL}/categories/${slug}/products`);
    const data = await res.json();
    rawProds = (data.success? data.data : []).map(mapProduct);
    filtProds = [...rawProds];
    renderProdGrid();
    buildFilters();
}

/* 3. Mapea producto */
// FUNCIÓN MAPPRODUCT 
function mapProduct(p) {
    const finalPrice = (p.discount_percentage && p.old_price) 
        ? p.old_price * (1 - p.discount_percentage / 100) 
        : p.price;

    return {
        id: p.id,
        name: p.name,
        price: Number(finalPrice),
        old_price: p.old_price ? Number(p.old_price) : null,
        discount: p.discount_percentage || 0,
        brand: p.brand || 'MaderaViva',
        image_url: p.image_url || 'https://images.unsplash.com/photo-1567538096630-e0c55bd6374c?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80',
        description: p.description || 'Producto de calidad premium',
        features: p.features || []
    };
}

/* 4. Pinta grilla */
// RENDERPRODGRID 
function renderProdGrid() {
    const grid = document.getElementById('products-category-grid');
    if (!grid) {
        console.error('❌ No se encontró products-category-grid');
        return;
    }
    
    grid.innerHTML = '';
    
    if (filtProds.length === 0) {
        grid.innerHTML = `
            <div class="no-products" style="text-align: center; padding: 3rem; grid-column: 1 / -1;">
                <i class="fas fa-search" style="font-size: 3rem; color: var(--gray); margin-bottom: 1rem;"></i>
                <p>No se encontraron productos con los filtros aplicados.</p>
                <button onclick="applyFilters()" class="btn-primary" style="margin-top: 1rem;">
                    Mostrar todos los productos
                </button>
            </div>
        `;
        return;
    }
    
    filtProds.forEach(p => {
        const card = document.createElement('div');
        card.className = 'product-card fade-in';
        card.dataset.id = p.id;
        
        card.innerHTML = `
            <div class="product-image">
                <img src="${p.image_url}" alt="${p.name}" 
                     onerror="this.src='https://images.unsplash.com/photo-1567538096630-e0c55bd6374c?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80'">
                ${p.discount > 0 ? `<div class="product-badge">-${p.discount}%</div>` : ''}
            </div>
            <div class="product-info">
                <h3 class="product-name">${p.name}</h3>
                <p class="product-brand">${p.brand}</p>
                
                <div class="product-price-container">
                    <span class="product-price">$${p.price.toLocaleString('es-MX')} MXN</span>
                    ${p.old_price && p.old_price > p.price ? 
                        `<span class="product-old-price">$${p.old_price.toLocaleString('es-MX')} MXN</span>` : ''}
                </div>
                
                <button class="add-to-cart"
                        data-id="${p.id}"
                        data-name="${p.name}"
                        data-price="${p.price}"
                        data-image="${p.image_url}">
                    Añadir al carrito
                </button>
            </div>
        `;
        
        grid.appendChild(card);
        
        // Event listener para quickview
        card.addEventListener('click', e => {
            if (!e.target.classList.contains('add-to-cart')) {
                openQuickview(p.id);
            }
        });
    });
    
    console.log(`✅ ${filtProds.length} productos renderizados`);
}

/* 5. Filtros */
// BUILD FILTERS 
function buildFilters() {
    const panel = document.getElementById('filters-panel');
    if (!panel) {
        console.warn('❌ No se encontró filters-panel');
        return;
    }
    
    if (rawProds.length === 0) {
        panel.innerHTML = '<p>No hay productos para filtrar</p>';
        return;
    }
    
    // Calcular rango de precios
    const maxPrice = Math.max(...rawProds.map(x => x.price), 1000);
    const brands = [...new Set(rawProds.map(x => x.brand).filter(Boolean))];
    
    panel.innerHTML = `
        <div class="filter-group">
            <h3 class="filter-title">Filtrar Productos</h3>
            
            <label for="priceRange">Precio máximo: 
                <span id="priceVal">$${maxPrice.toLocaleString('es-MX')} MXN</span>
            </label>
            <input type="range" id="priceRange" min="0" max="${maxPrice}" value="${maxPrice}" 
                   class="price-slider">
            
            <label for="brandSel" style="margin-top: 1rem;">Marca</label>
            <select id="brandSel" class="brand-select">
                <option value="all">Todas las marcas</option>
                ${brands.map(b => `<option value="${b}">${b}</option>`).join('')}
            </select>
        </div>
        
        <div class="filter-stats">
            <small>Mostrando ${filtProds.length} de ${rawProds.length} productos</small>
        </div>
    `;
    
    // Event listeners
    document.getElementById('priceRange').addEventListener('input', function(e) {
        document.getElementById('priceVal').textContent = '$' + 
            Number(e.target.value).toLocaleString('es-MX') + ' MXN';
        applyFilters();
    });
    
    document.getElementById('brandSel').addEventListener('change', applyFilters);
}

// APPLY FILTERS 
function applyFilters() {
    const priceRange = document.getElementById('priceRange');
    const brandSel = document.getElementById('brandSel');
    
    if (!priceRange || !brandSel) return;
    
    const maxPrice = Number(priceRange.value);
    const selectedBrand = brandSel.value;
    
    filtProds = rawProds.filter(p => {
        const priceMatch = p.price <= maxPrice;
        const brandMatch = selectedBrand === 'all' || p.brand === selectedBrand;
        return priceMatch && brandMatch;
    });
    
    renderProdGrid();
}

/* 6. Volver */
function showCatView(){ toggleViews('category-summary-view'); }

/* 7. Alternar vistas */
// SISTEMA DE VISTAS 
function toggleViews(showId) {
    console.log('🔄 Cambiando a vista:', showId);
    
    const views = document.querySelectorAll('#catalog-view-manager .view');
    views.forEach(view => {
        if (view.id === showId) {
            view.style.display = 'block';
            view.classList.add('active');
        } else {
            view.style.display = 'none';
            view.classList.remove('active');
        }
    });
}

function showCatView() {
    toggleViews('category-summary-view');
}
//FUNCIÓN SHOWPRODVIEW 
async function showProdView(slug) {
    try {
        console.log('🔄 Cargando productos para categoría:', slug);
        
        currentCatSlug = slug;
        toggleViews('product-list-view');
        
        // Actualizar título
        const cat = categories.find(x => (x.slug || x.id) == slug);
        const titleElement = document.getElementById('product-list-title');
        if (titleElement && cat) {
            titleElement.textContent = cat.name;
        }
        
        // Mostrar loading
        const grid = document.getElementById('products-category-grid');
        if (grid) {
            grid.innerHTML = `
                <div class="loading" style="text-align: center; padding: 3rem; grid-column: 1 / -1;">
                    <i class="fas fa-spinner fa-spin" style="font-size: 2rem; color: var(--olive);"></i>
                    <p>Cargando productos...</p>
                </div>
            `;
        }
        
        // Cargar productos - CON MANEJO DE ERRORES MEJORADO
        let categoryProducts = [];
        try {
            console.log(`🔍 Solicitando: ${API_BASE_URL}/categories/${slug}/products`);
            const response = await fetch(`${API_BASE_URL}/categories/${slug}/products`, {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                }
            });
            
            console.log('📊 Status de categoría:', response.status);
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            // Verificar que sea JSON
            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
                const text = await response.text();
                console.error('❌ Respuesta de categoría no es JSON:', text.substring(0, 200));
                throw new Error('Respuesta no es JSON');
            }
            
            const data = await response.json();
            console.log('📦 Datos de categoría recibidos:', data);
            
            if (data.success && data.data) {
                categoryProducts = data.data;
                console.log(`✅ ${categoryProducts.length} productos cargados desde API`);
            } else {
                throw new Error('API no devolvió datos válidos: ' + (data.message || 'Unknown error'));
            }
        } catch (apiError) {
            console.warn('⚠️ Error con API, usando productos locales:', apiError);
            // Fallback más inteligente
            categoryProducts = products.filter(p => {
                // Intentar matching por slug, id, o nombre de categoría
                return p.category === slug || 
                       p.category_id == slug || 
                       (p.category && p.category.toLowerCase().includes(slug.toLowerCase()));
            });
            console.log(`🔄 ${categoryProducts.length} productos cargados localmente`);
        }
        
        // Si no hay productos, mostrar mensaje
        if (categoryProducts.length === 0) {
            console.warn('⚠️ No se encontraron productos para esta categoría');
            if (grid) {
                grid.innerHTML = `
                    <div class="no-products" style="text-align: center; padding: 3rem; grid-column: 1 / -1;">
                        <i class="fas fa-box-open" style="font-size: 3rem; color: var(--gray); margin-bottom: 1rem;"></i>
                        <p>No hay productos disponibles en esta categoría.</p>
                        <button onclick="showCatView()" class="btn-primary" style="margin-top: 1rem;">
                            Volver a categorías
                        </button>
                    </div>
                `;
            }
            return;
        }
        
        // Mapear y procesar productos
        rawProds = categoryProducts.map(mapProduct);
        filtProds = [...rawProds];
        
        // Renderizar
        renderProdGrid();
        buildFilters();
        
        console.log('✅ Vista de productos cargada correctamente');
        
    } catch (error) {
        console.error('❌ Error crítico en showProdView:', error);
        showAlert('Error al cargar los productos de esta categoría', 'danger');
        
        // Volver a categorías en caso de error
        showCatView();
    }
}


/* ----------  9. Inicializar catálogo ---------- */
function initCatalogSection() {
    console.log('🔄 Inicializando sección de catálogo...');
    
    // 1. Renderizar categorías
    renderCatCards();
    
    // 2. Configurar event listeners para las tarjetas de categoría
    const categoriesGrid = document.getElementById('categories-grid');
    if (categoriesGrid) {
        categoriesGrid.addEventListener('click', (e) => {
            const card = e.target.closest('.category-card');
            if (card && card.dataset.slug) {
                console.log('📁 Categoría seleccionada:', card.dataset.slug);
                showProdView(card.dataset.slug);
            }
        });
    }
    
    // 3. Configurar botón volver
    const backButton = document.querySelector('#product-list-view .back-button');
    if (backButton) {
        backButton.addEventListener('click', () => {
            console.log('↩️ Volviendo a categorías');
            showCatView();
        });
    }
    
    // 4. Mostrar vista inicial
    toggleViews('category-summary-view');
    console.log('✅ Catálogo inicializado correctamente');
}

// Función para cargar productos en oferta desde la API
// Función mejorada para cargar productos en oferta
async function loadOfferProducts() {
    try {
        const response = await fetch(`${API_BASE_URL}/products/offers`);
        const data = await response.json();
        
        if (data.success && data.data.length > 0) {
            renderOfferProducts(data.data);
            initOffersSwiper();
        } else {
            // Fallback: mostrar productos con descuento > 0
            const offerProducts = products.filter(product => product.discount > 0);
            if (offerProducts.length > 0) {
                renderOfferProducts(offerProducts);
                initOffersSwiper();
            } else {
                renderOfferProducts(fallbackProducts.filter(p => p.is_offer));
                initOffersSwiper();
            }
        }
    } catch (error) {
        console.error('Error cargando ofertas:', error);
        const offerProducts = products.filter(product => product.discount > 0);
        if (offerProducts.length > 0) {
            renderOfferProducts(offerProducts);
            initOffersSwiper();
        }
    }
}


// Función para cargar productos por categoría desde la API
async function loadProductsByCategory(categorySlug) {
    try {
        const response = await fetch(`${API_BASE_URL}/categories/${categorySlug}/products`);
        const data = await response.json();
        
        if (data.success) {
            return data.data || [];
        } else {
            console.error('Error cargando productos por categoría:', data.message);
            return products.filter(product => product.category === categorySlug || 
                                           product.category_id === categorySlug);
        }
    } catch (error) {
        console.error('Error cargando productos por categoría:', error);
        return products.filter(product => product.category === categorySlug || 
                                       product.category_id === categorySlug);
    }
}

// ========== FUNCIONES DE RENDERIZADO ==========
function renderOfferProducts(offerProducts) {
    const offersSwiperWrapper = document.querySelector('.offers-swiper .swiper-wrapper');
    if (!offersSwiperWrapper) return;

    offersSwiperWrapper.innerHTML = '';

    offerProducts.forEach(product => {
        const slide = document.createElement('div');
        slide.className = 'swiper-slide';

        const finalPrice = product.discount > 0 && product.old_price 
            ? product.old_price * (1 - product.discount / 100)
            : product.price;

        const oldPrice = product.discount > 0 ? product.old_price || product.original_price : null;
        const discount = product.discount;

        slide.innerHTML = `
            <div class="offer-card" data-id="${product.id}">
                ${discount > 0 ? `<div class="offer-badge">-${discount}%</div>` : ''}
                <div class="offer-image">
                    <img src="${product.image_url || 'https://images.unsplash.com/photo-1567538096630-e0c55bd6374c?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80'}" 
                         alt="${product.name}" 
                         onerror="this.src='https://images.unsplash.com/photo-1567538096630-e0c55bd6374c?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80'">
                </div>
                <div class="offer-info">
                    <h3 class="offer-name">${product.name}</h3>
                    <p class="offer-brand" style="color: var(--gray); font-size: 0.9rem; margin-bottom: 8px;">${product.brand}</p>
                    <p class="offer-description">${product.description ? (product.description.length > 100 ? product.description.substring(0, 100) + '...' : product.description) : 'Producto de calidad premium'}</p>
                    
                    <div class="offer-price-container">
                        <span class="offer-price">$${finalPrice.toLocaleString('es-MX')} MXN</span>
                        ${oldPrice && oldPrice > finalPrice ? `<span class="offer-old-price">$${oldPrice.toLocaleString('es-MX')} MXN</span>` : ''}
                        ${discount > 0 ? `<span class="offer-discount">-${discount}%</span>` : ''}
                    </div>
                    
                    <button class="offer-add-to-cart" 
                            data-id="${product.id}"
                            data-name="${product.name}"
                            data-price="${finalPrice}"
                            data-image="${product.image_url}">
                        Agregar al carrito
                    </button>
                </div>
            </div>
        `;
        
        offersSwiperWrapper.appendChild(slide);
    });

    if (window.offersSwiper) {
        window.offersSwiper.update();
    }


    // Añadir event listeners para abrir quickview al hacer clic en la tarjeta
    document.querySelectorAll('.offer-card').forEach(card => {
        card.addEventListener('click', function(e) {
            if (!e.target.classList.contains('offer-add-to-cart')) {
                const productId = this.getAttribute('data-id');
                openQuickview(productId);
            }
        });
    });

}




// Función para renderizar productos en el catálogo
function renderProducts() {
    const productsGrid = document.getElementById('products-grid');
    if (!productsGrid) return;

    productsGrid.innerHTML = '';

    if (filteredProducts.length === 0) {
        productsGrid.innerHTML = `
            <div class="no-products-message">
                <i class="fas fa-search" style="font-size: 3rem; margin-bottom: 1rem; color: var(--gray);"></i>
                <p>No se encontraron productos para esta categoría.</p>
                <button class="back-button" onclick="showCategoryView()">
                    <i class="fas fa-arrow-left"></i> Volver a categorías
                </button>
            </div>
        `;
        return;
    }

    filteredProducts.forEach(product => {
        const productCard = document.createElement('div');
        productCard.className = 'product-card fade-in';
        productCard.setAttribute('data-id', product.id);

        const finalPrice = product.discount > 0 && product.old_price
            ? product.old_price * (1 - product.discount / 100)
            : product.price;

        const imgUrl = product.image_url || 'https://images.unsplash.com/photo-1567538096630-e0c55bd6374c?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80';

        productCard.innerHTML = `
            <div class="product-image">
                <img src="${imgUrl}" alt="${product.name}"
                     onerror="this.src='https://images.unsplash.com/photo-1567538096630-e0c55bd6374c?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80'">
                ${product.discount > 0 ? `<div class="product-badge">-${product.discount}%</div>` : ''}
            </div>
            <div class="product-info">
                <h3 class="product-name">${product.name}</h3>
                <p class="product-brand" style="color: var(--gray); font-size: 0.9rem; margin-bottom: 8px;">${product.brand}</p>

                <div class="product-price-container">
                    <span class="product-price">$${finalPrice.toLocaleString('es-MX')} MXN</span>
                    ${product.discount > 0 && product.old_price
                        ? `<span class="product-old-price">$${product.old_price.toLocaleString('es-MX')} MXN</span>` : ''}
                </div>

                <button class="add-to-cart"
                        data-id="${product.id}"
                        data-name="${product.name}"
                        data-price="${finalPrice}"
                        data-image="${imgUrl}">
                    Agregar al carrito
                </button>
            </div>
        `;

        productsGrid.appendChild(productCard);

        // Abrir quickview al clic en la tarjeta (menos en el botón)
        productCard.addEventListener('click', function (e) {
            if (!e.target.classList.contains('add-to-cart')) {
                openQuickview(product.id);
            }
        });
    });
}


// ========== FUNCIONES DE FALLBACK ==========

//FALLBACKS 
function loadFallbackCategories() {
    const fallbackCategories = [
        { 
            id: 'mesas', 
            name: 'Mesas', 
            slug: 'mesas', 
            image_url: 'https://images.unsplash.com/photo-1586023492125-27b2c045efd7?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80', 
            products_count: 2 
        },
        { 
            id: 'sillas', 
            name: 'Sillas', 
            slug: 'sillas', 
            image_url: 'https://images.unsplash.com/photo-1503602642458-232111445657?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80', 
            products_count: 1 
        },
        { 
            id: 'sofas', 
            name: 'Sofás', 
            slug: 'sofas', 
            image_url: 'https://images.unsplash.com/photo-1586023492125-27b2c045efd7?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80', 
            products_count: 1 
        },
        { 
            id: 'dormitorios', 
            name: 'Dormitorios', 
            slug: 'dormitorios', 
            image_url: 'https://images.unsplash.com/photo-1586023492125-27b2c045efd7?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80', 
            products_count: 2 
        }
    ];
    
    console.log('🔄 Usando categorías de fallback:', fallbackCategories.length);
    return fallbackCategories;
}

function loadFallbackProducts() {
    console.log('🔄 Usando productos de fallback');
    products = [...fallbackProducts]; // Crear copia para no modificar el original
}

// ========== SISTEMA DE CATÁLOGO ==========

// INITIALIZEAPP 
async function initializeApp() {
    try {
        console.log('🚀 Iniciando aplicación MaderaViva...');
        
        // 1. Verificar conectividad básica
        console.log('🔗 Verificando conectividad...');
        
        // 2. Cargar datos en orden con manejo de errores independiente
        await checkAuthStatus();
        
        // Cargar categorías (con fallback si falla)
        try {
            const categoriesData = await loadCategories();
            categories = categoriesData || [];
            console.log(`✅ ${categories.length} categorías cargadas`);
        } catch (catError) {
            console.error('❌ Error cargando categorías:', catError);
            categories = loadFallbackCategories();
        }
        
        // Cargar productos (con fallback si falla)
        try {
            await loadProducts();
            console.log(`✅ ${products.length} productos cargados`);
        } catch (prodError) {
            console.error('❌ Error cargando productos:', prodError);
            loadFallbackProducts();
        }
        
        // 3. Inicializar UI con los datos disponibles
        initCatalogSection();
        
        // 4. Cargar componentes adicionales (pueden fallar silenciosamente)
        try {
            await loadOfferProducts();
        } catch (e) {
            console.warn('⚠️ Ofertas no cargadas:', e.message);
        }
        
        try {
            await loadAllProducts();
        } catch (e) {
            console.warn('⚠️ Todos los productos no cargados:', e.message);
        }
        
        initSmoothScrolling();
        initNewsletter();
        
        console.log('🎉 Aplicación inicializada correctamente');
        
        // Mostrar estado resumido
        console.log('📊 RESUMEN:');
        console.log('  - Categorías:', categories.length);
        console.log('  - Productos:', products.length);
        console.log('  - Usuario:', isLoggedIn ? currentUser?.name : 'No logueado');
        
    } catch (error) {
        console.error('💥 Error crítico en initializeApp:', error);
        
        // Intentar cargar con datos mínimos
        categories = loadFallbackCategories();
        loadFallbackProducts();
        initCatalogSection();
        
        showAlert('La aplicación se cargó en modo limitado. Algunas funciones pueden no estar disponibles.', 'warning');
    }
}



// FUNCIÓN DE DIAGNÓSTICO PARA DEBUGGEAR
async function diagnoseAPI() {
    console.group('🔍 DIAGNÓSTICO DE API');
    
    try {
        // Probar endpoint de categorías
        console.log('1. Probando endpoint de categorías...');
        const catResponse = await fetch(`${API_BASE_URL}/categories`);
        console.log('   Status:', catResponse.status);
        console.log('   OK:', catResponse.ok);
        
        const catText = await catResponse.text();
        console.log('   Contenido (primeros 200 chars):', catText.substring(0, 200));
        
        // Intentar parsear como JSON
        try {
            const catJson = JSON.parse(catText);
            console.log('   JSON válido:', true);
            console.log('   Estructura:', catJson);
        } catch (e) {
            console.log('   ❌ JSON inválido:', e.message);
        }
        
        // Probar endpoint de productos
        console.log('2. Probando endpoint de productos...');
        const prodResponse = await fetch(`${API_BASE_URL}/products`);
        console.log('   Status:', prodResponse.status);
        console.log('   OK:', prodResponse.ok);
        
        const prodText = await prodResponse.text();
        console.log('   Contenido (primeros 200 chars):', prodText.substring(0, 200));
        
    } catch (error) {
        console.error('❌ Error en diagnóstico:', error);
    }
    
    console.groupEnd();
}

// Ejecutar diagnóstico en la consola del navegador cuando necesites
// diagnoseAPI();


// Ejecutar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', initializeApp);






// FUNCIÓN CORREGIDA - showProductView
async function showProductView(categorySlug) {
    try {
        console.log('🔄 Cambiando a vista de productos para:', categorySlug);
        
        // 1. Ocultar vista de categorías y mostrar vista de productos
        const categoryView = document.getElementById('category-summary-view');
        const productView = document.getElementById('product-detail-view');
        
        if (!categoryView || !productView) {
            console.error('❌ No se encontraron las vistas del catálogo');
            return;
        }
        
        // Ocultar categorías, mostrar productos
        categoryView.style.display = 'none';
        productView.style.display = 'block';
        
        // 2. Mostrar loading
        const productsGrid = document.getElementById('products-grid');
        if (productsGrid) {
            productsGrid.innerHTML = `
                <div class="loading-products" style="text-align: center; padding: 3rem;">
                    <i class="fas fa-spinner fa-spin" style="font-size: 2rem; color: var(--olive);"></i>
                    <p>Cargando productos...</p>
                </div>
            `;
        }
        
        // 3. Actualizar título de la categoría
        const categoryTitle = document.querySelector('#product-detail-view .section-title');
        const category = categories.find(cat => cat.slug === categorySlug || cat.id === categorySlug);
        
        if (categoryTitle && category) {
            categoryTitle.textContent = category.name;
        }
        
        // 4. Cargar productos de esta categoría
        let categoryProducts = [];
        
        try {
            // Intentar cargar desde API
            const response = await fetch(`${API_BASE_URL}/categories/${categorySlug}/products`);
            const data = await response.json();
            
            if (data.success) {
                categoryProducts = data.data;
                console.log(`✅ ${categoryProducts.length} productos cargados desde API`);
            } else {
                throw new Error('API no devolvié datos válidos');
            }
        } catch (apiError) {
            console.warn('⚠️ Error con API, usando filtrado local:', apiError);
            // Fallback: filtrar productos locales
            categoryProducts = products.filter(product => 
                product.category === categorySlug || 
                product.category_id == categorySlug
            );
            console.log(`🔄 ${categoryProducts.length} productos cargados localmente`);
        }
        
        // 5. Actualizar variables globales
        currentProducts = categoryProducts;
        filteredProducts = [...currentProducts];
        
        // 6. Renderizar productos
        renderProducts();
        
        // 7. Preparar filtros
        resetFilters();
        
        console.log('✅ Vista de productos cargada correctamente');
        
    } catch (error) {
        console.error('❌ Error en showProductView:', error);
        showAlert('Error al cargar los productos de la categoría', 'danger');
        
        // Volver a la vista de categorías en caso de error
        showCategoryView();
    }
}

async function verifyToken() {
    try {
        const res = await fetch(`${API_BASE_URL}/auth/verify`, {
            headers: { Authorization: `Bearer ${authToken}` }
        });
        return res.ok;
    } catch {
        return false;
    }
}


async function loadFeaturedProducts() {
    try {
        const res = await fetch(`${API_BASE_URL}/products/featured`);
        const data = await res.json();
        return data.success ? data.data : [];
    } catch {
        return []; // vacío si falla
    }
}


// ==========================================
//  CARGA INICIAL DEL CATÁLOGO
// ==========================================


async function loadCartFromServer() {
    console.log('loadCartFromServer: sin implementar aún');
    // cuando tengas el endpoint real lo llenas
}


async function init(){
    try{
        console.log('Iniciando MaderaViva...');

        await checkAuthStatus();   // token
        await loadCategories();    // cats → array global
        initCatalogSection();      // Vista A + listeners

        await loadProducts();      // productos generales
        await loadOfferProducts(); // ofertas
        initSwiper();
        initSmoothScrolling();
        initNewsletter();
        updateAuthUI();

   
        console.log('✅ MaderaViva inicializada correctamente');
    }catch(err){
        console.error('❌ Error inicializando la aplicación:',err);
        showAlert('Error al cargar algunos componentes','warning');
    }
}



function initSwiper() {
    const swiperWrapper = document.querySelector('.offers-swiper .swiper-wrapper');
    if (!swiperWrapper) return;

    // Configuración más flexible
    const config = {
        slidesPerView: 1,
        spaceBetween: 20,
        loop: true,
        autoplay: {
            delay: 5000,
            disableOnInteraction: false,
        },
        pagination: {
            el: '.swiper-pagination',
            clickable: true,
        },
        navigation: {
            nextEl: '.swiper-button-next',
            prevEl: '.swiper-button-prev',
        },
        breakpoints: {
            640: {
                slidesPerView: 1,
                spaceBetween: 20,
            },
            768: {
                slidesPerView: 2,
                spaceBetween: 30,
            },
            1024: {
                slidesPerView: 3,
                spaceBetween: 30,
            },
        },
    };

    // Inicializar Swiper siempre
    window.offersSwiper = new Swiper('.offers-swiper', config);
}

/* ----------  VISTAS A/B  ---------- */
async function showProductView(categoryRef) {
    // 1. Cambio de vista
    document.querySelectorAll('#catalog .view').forEach(v => v.classList.remove('active'));
    document.getElementById('product-detail-view').classList.add('active');

    // 2. Cargar productos de la categoría
    const res = await fetch(`${API_BASE_URL}/products?category=${categoryRef}`);
    const data = await res.json();
    currentProducts  = data.success ? data.data : [];
    filteredProducts = [...currentProducts];

    // 3. Pintar y preparar filtros
    renderProductsGrid(currentProducts);
    resetFilters();
}

// FUNCIÓN applyFilters SIMPLIFICADA
function applyFilters() {
    const priceRange = document.getElementById('priceRange');
    const brandFilter = document.getElementById('brandFilter');
    
    if (!priceRange || !brandFilter || !currentProducts.length) {
        // Si no hay filtros, mostrar todos los productos
        filteredProducts = [...currentProducts];
        renderProducts();
        return;
    }

    const maxPrice = parseInt(priceRange.value);
    const selectedBrand = brandFilter.value;

    filteredProducts = currentProducts.filter(product => {
        const priceMatch = product.price <= maxPrice;
        const brandMatch = selectedBrand === 'all' || product.brand === selectedBrand;
        return priceMatch && brandMatch;
    });

    renderProducts();
}


// FUNCIÓN PARA VOLVER A CATEGORÍAS
function showCategoryView() {
    console.log('🔄 Volviendo a vista de categorías');
    
    const categoryView = document.getElementById('category-summary-view');
    const productView = document.getElementById('product-detail-view');
    
    if (categoryView && productView) {
        categoryView.style.display = 'block';
        productView.style.display = 'none';
    }
}


// FUNCIÓN resetFilters 
function resetFilters() {
    // Crear filtros si no existen
    createFiltersPanel();
    
    const priceRange = document.getElementById('priceRange');
    const priceValue = document.getElementById('priceValue');
    const brandFilter = document.getElementById('brandFilter');

    if (!priceRange || !priceValue || !brandFilter) {
        console.warn('Filtros no disponibles - continuando sin filtros');
        return;
    }

    // Precio
    const maxPrice = currentProducts.length > 0 ? 
        Math.max(...currentProducts.map(p => p.price), 50000) : 50000;
    
    priceRange.max = Math.ceil(maxPrice / 1000) * 1000;
    priceRange.value = maxPrice;
    priceValue.textContent = `$${maxPrice.toLocaleString('es-MX')} MXN`;

    // Marcas
    const brands = [...new Set(currentProducts.map(p => p.brand).filter(Boolean))];
    brandFilter.innerHTML = '<option value="all">Todas las marcas</option>';
    brands.forEach(b => {
        brandFilter.innerHTML += `<option value="${b}">${b}</option>`;
    });

    // Eventos
    priceRange.addEventListener('input', applyFilters);
    brandFilter.addEventListener('change', applyFilters);

    // Aplicar filtros iniciales
    applyFilters();
}


// Funciones para filtrar productos
function updatePriceValue() {
    const priceRange = document.querySelector('.price-range');
    const priceValue = document.querySelector('.price-value');

    if (priceRange && priceValue) {
        const value = parseInt(priceRange.value);
        priceValue.textContent = `$${value.toLocaleString('es-MX')} MXN`;
    }
}



filteredProducts.forEach(product => {
        const productCard = document.createElement('div');
        productCard.className = 'product-card fade-in';
        productCard.setAttribute('data-id', product.id);
        productCard.innerHTML = `
            <div class="product-image">
                <img src="${product.image}" alt="${product.name}">
            </div>
            <div class="product-info">
                <h3 class="product-name">${product.name}</h3>
                <p class="product-price">$${product.price.toLocaleString('es-MX')} MXN</p>
                <button class="add-to-cart" data-id="${product.id}" data-name="${product.name}" data-price="${product.price}" data-image="${product.image}">Agregar al carrito</button>
            </div>
        `;
        productsGrid.appendChild(productCard);
        
        // Event listener para abrir vista rápida al hacer clic en la tarjeta
        productCard.addEventListener('click', function(e) {
            if (!e.target.classList.contains('add-to-cart')) {
                openQuickview(product.id);
            }
        });
        
        
    });

// ========== VISTA RÁPIDA DE PRODUCTOS ==========

// Función para abrir vista rápida
async function openQuickview(productId) {
    try {
        // Buscar producto en los datos cargados
        let product = products.find(p => p.id == productId);
        
        // Si no se encuentra, intentar cargar desde API
        if (!product) {
            const response = await fetch(`${API_BASE_URL}/products/${productId}`);
            if (response.ok) {
                const data = await response.json();
                if (data.success) {
                    product = data.data;
                }
            }
        }

        // Fallback a productos de respaldo
        if (!product) {
            product = fallbackProducts.find(p => p.id == productId);
        }

        if (product) {
            const quickviewTitle = document.getElementById('quickview-title');
            const quickviewPrice = document.getElementById('quickview-price');
            const quickviewImg = document.getElementById('quickview-img');
            const quickviewBrand = document.getElementById('quickview-brand');
            const quickviewDescription = document.getElementById('quickview-description');
            const quickviewAdd = document.getElementById('quickview-add');
            const quickviewQuantityElement = document.getElementById('quickview-quantity');
            const quickviewModal = document.getElementById('quickview-modal');

            // Calcular precio final
            const finalPrice = product.discount > 0 && product.old_price 
                ? product.old_price * (1 - product.discount / 100)
                : product.price;

            // Actualizar características del producto
            const featureMaterial = document.getElementById('feature-material');
            const featureDimensions = document.getElementById('feature-dimensions');
            const featureFinish = document.getElementById('feature-finish');
            const featureWarranty = document.getElementById('feature-warranty');

            if (quickviewTitle) quickviewTitle.textContent = product.name;
            if (quickviewPrice) quickviewPrice.textContent = `$${finalPrice.toLocaleString('es-MX')} MXN`;
            if (quickviewImg) {
                quickviewImg.src = product.image_url || product.image;
                quickviewImg.alt = product.name;
                quickviewImg.onerror = function() {
                    this.src = 'https://images.unsplash.com/photo-1567538096630-e0c55bd6374c?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80';
                };
            }
            if (quickviewBrand) quickviewBrand.textContent = product.brand || 'MaderaViva';
            if (quickviewDescription) quickviewDescription.textContent = product.description || 'Producto de calidad premium.';

            // Mostrar información de descuento si existe
            if (product.discount > 0) {
                const discountElement = document.getElementById('quickview-discount');
                if (discountElement) {
                    discountElement.innerHTML = `
                        <div class="discount-info">
                            <span class="old-price">$${product.old_price ? product.old_price.toLocaleString('es-MX') : product.original_price.toLocaleString('es-MX')} MXN</span>
                            <span class="discount-badge">-${product.discount}%</span>
                        </div>
                    `;
                    discountElement.style.display = 'block';
                }
            }

            // Actualizar características
            if (product.features && Array.isArray(product.features)) {
                if (featureMaterial) featureMaterial.textContent = product.features[0] || 'Material premium';
                if (featureDimensions) featureDimensions.textContent = product.features[1] || 'Dimensiones estándar';
                if (featureFinish) featureFinish.textContent = product.features[2] || 'Acabado de calidad';
                if (featureWarranty) featureWarranty.textContent = product.features[3] || 'Garantía incluida';
            } else {
                // Características por defecto
                if (featureMaterial) featureMaterial.textContent = 'Madera de alta calidad';
                if (featureDimensions) featureDimensions.textContent = 'Dimensiones variables';
                if (featureFinish) featureFinish.textContent = 'Acabado profesional';
                if (featureWarranty) featureWarranty.textContent = '2 años de garantía';
            }

            if (quickviewAdd) {
                quickviewAdd.setAttribute('data-id', product.id);
                quickviewAdd.setAttribute('data-name', product.name);
                quickviewAdd.setAttribute('data-price', finalPrice);
                quickviewAdd.setAttribute('data-image', product.image_url || product.image);
            }

            // Reset quantity
            quickviewQuantity = 1;
            if (quickviewQuantityElement) quickviewQuantityElement.textContent = quickviewQuantity;

            if (quickviewModal) openModal(quickviewModal);
        } else {
            showAlert('Producto no encontrado', 'danger');
        }
    } catch (error) {
        console.error('Error abriendo vista rápida:', error);
        showAlert('Error al cargar el producto', 'danger');
    }
}

// Función para cerrar vista rápida
function closeQuickviewModal() {
    const quickviewModal = document.getElementById('quickview-modal');
    const addedConfirmation = document.getElementById('added-confirmation');
    
    if (quickviewModal) closeModal(quickviewModal);
    if (addedConfirmation) addedConfirmation.classList.remove('show');
}

// Funciones para control de cantidad en vista rápida
function decreaseQuantity() {
    if (quickviewQuantity > 1) {
        quickviewQuantity--;
        const quickviewQuantityElement = document.getElementById('quickview-quantity');
        if (quickviewQuantityElement) quickviewQuantityElement.textContent = quickviewQuantity;
    }
}

function increaseQuantity() {
    quickviewQuantity++;
    const quickviewQuantityElement = document.getElementById('quickview-quantity');
    if (quickviewQuantityElement) quickviewQuantityElement.textContent = quickviewQuantity;
}

// Función para añadir al carrito desde vista rápida
function addToCartFromQuickview() {
    const quickviewAdd = document.getElementById('quickview-add');
    const addedConfirmation = document.getElementById('added-confirmation');

    if (quickviewAdd) {
        const id = quickviewAdd.getAttribute('data-id');
        const name = quickviewAdd.getAttribute('data-name');
        const price = quickviewAdd.getAttribute('data-price');
        const image = quickviewAdd.getAttribute('data-image');

        addToCart(id, name, price, image, quickviewQuantity);

        // Mostrar mensaje de confirmación
        if (addedConfirmation) {
            addedConfirmation.textContent = '¡Producto añadido al carrito!';
            addedConfirmation.classList.add('show');
            setTimeout(() => {
                addedConfirmation.classList.remove('show');
            }, 3000);
        }
    }
}

// ========== NEWSLETTER SYSTEM ==========

// Función para validar email
function isValidEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
}

// Función para mostrar estado de carga en el botón
function setNewsletterLoadingState(loading) {
    const subscribeBtn = document.getElementById('subscribeBtn');
    const btnText = document.getElementById('btnText');
    const spinner = document.getElementById('spinner');

    if (subscribeBtn && btnText && spinner) {
        if (loading) {
            subscribeBtn.disabled = true;
            btnText.textContent = 'Procesando...';
            spinner.style.display = 'inline-block';
        } else {
            subscribeBtn.disabled = false;
            btnText.textContent = 'Suscribirse';
            spinner.style.display = 'none';
        }
    }
}

// Función para manejar la suscripción al newsletter
async function handleNewsletterSubscription(email) {
    try {
        setNewsletterLoadingState(true);

        // Validar email
        if (!isValidEmail(email)) {
            showAlert('Por favor, introduce un email válido', 'error');
            setNewsletterLoadingState(false);
            return;
        }

        // Enviar a la API
        const response = await fetch(`${API_BASE_URL}/newsletter/subscribe`, {
            method: 'POST',
            headers: getAuthHeaders(),
            body: JSON.stringify({ email: email })
        });

        const data = await response.json();

        if (response.ok) {
            // Agregar a la lista local
            newsletterSubscribers.push(email);
            localStorage.setItem('newsletterSubscribers', JSON.stringify(newsletterSubscribers));

            // Actualizar estadísticas
            newsletterStats.subscribers++;
            localStorage.setItem('newsletterStats', JSON.stringify(newsletterStats));
            updateNewsletterStatistics();

            // Mostrar mensaje de éxito
            showAlert('¡Te has suscrito exitosamente! Pronto recibirás nuestras novedades.', 'success');

            // Resetear formulario
            const newsletterForm = document.getElementById('newsletterForm');
            if (newsletterForm) newsletterForm.reset();
        } else {
            showAlert(data.message || 'Error en la suscripción', 'error');
        }

    } catch (error) {
        console.error('Error:', error);
        showAlert('Error de conexión. Por favor, intenta más tarde.', 'error');
    } finally {
        setNewsletterLoadingState(false);
    }
}

// Función para actualizar estadísticas del newsletter
function updateNewsletterStatistics() {
    const subscribersCount = document.getElementById('subscribersCount');
    const offersSent = document.getElementById('offersSent');
    const exclusiveContent = document.getElementById('exclusiveContent');

    if (subscribersCount) subscribersCount.textContent = newsletterStats.subscribers.toLocaleString();
    if (offersSent) offersSent.textContent = newsletterStats.offersSent.toLocaleString();
    if (exclusiveContent) exclusiveContent.textContent = newsletterStats.exclusiveContent.toLocaleString();
}

// Función para inicializar el newsletter
function initNewsletter() {
    // Actualizar estadísticas al cargar
    updateNewsletterStatistics();

    // Manejar envío del formulario
    const newsletterForm = document.getElementById('newsletterForm');
    if (newsletterForm) {
        newsletterForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const emailInput = document.getElementById('newsletterEmail');
            if (emailInput) {
                const email = emailInput.value.trim();
                await handleNewsletterSubscription(email);
            }
        });
    }
}

// ========== MODALES ==========

// Funciones para los modales
function openModal(modal) {
    if (modal) {
        modal.classList.add('open');
        if (overlay) overlay.classList.add('open');
        document.body.style.overflow = 'hidden';
    }
}

function closeModal(modal) {
    if (modal) {
        modal.classList.remove('open');
        if (overlay) overlay.classList.remove('open');
        document.body.style.overflow = 'auto';
    }
}

// Modal de autenticación
function openAuthModal() {
    const authModal = document.getElementById('auth-modal');
    const overlay = document.getElementById('overlay');
    
    if (authModal && overlay) {
        authModal.classList.add('open');
        overlay.classList.add('open');
        document.body.style.overflow = 'hidden';
    }
}

function closeAuthModal() {
    const authModal = document.getElementById('auth-modal');
    const overlay = document.getElementById('overlay');
    
    if (authModal && overlay) {
        authModal.classList.remove('open');
        overlay.classList.remove('open');
        document.body.style.overflow = 'auto';
        
        // Limpiar formularios
        const loginForm = document.getElementById('login-form');
        const registerForm = document.getElementById('register-form');
        if (loginForm) loginForm.reset();
        if (registerForm) registerForm.reset();
    }
}

// Modal de perfil
function openProfileModal() {
    const profileModal = document.getElementById('profile-modal');
    const overlay = document.getElementById('overlay');
    
    if (profileModal && overlay && currentUser) {
        // Actualizar información del perfil
        const profileName = document.getElementById('profile-name');
        const profileEmail = document.getElementById('profile-email');
        const profilePhone = document.getElementById('profile-phone');
        const profileAddress = document.getElementById('profile-address');

        if (profileName) profileName.textContent = currentUser.name;
        if (profileEmail) profileEmail.textContent = currentUser.email;
        if (profilePhone) profilePhone.textContent = currentUser.phone || 'No especificado';
        if (profileAddress) profileAddress.textContent = currentUser.address || 'No especificada';

        profileModal.classList.add('open');
        overlay.classList.add('open');
        document.body.style.overflow = 'hidden';
    }
}

function initEventListeners() {
    console.log('initEventListeners: sin implementar aún');
    // aquí irán listeners globales que necesites más adelante
}

function closeProfileModal() {
    const profileModal = document.getElementById('profile-modal');
    const overlay = document.getElementById('overlay');
    
    if (profileModal && overlay) {
        profileModal.classList.remove('open');
        overlay.classList.remove('open');
        document.body.style.overflow = 'auto';
    }
}

// Cambiar entre pestañas de login/registro
function switchAuthTab(tab) {
    const loginTab = document.getElementById('login-tab');
    const registerTab = document.getElementById('register-tab');
    const loginForm = document.getElementById('login-form');
    const registerForm = document.getElementById('register-form');
    const authModalTitle = document.getElementById('auth-modal-title');

    if (tab === 'login') {
        if (loginTab) loginTab.classList.add('active');
        if (registerTab) registerTab.classList.remove('active');
        if (loginForm) loginForm.classList.remove('d-none');
        if (registerForm) registerForm.classList.add('d-none');
        if (authModalTitle) authModalTitle.textContent = 'Iniciar Sesión';
    } else {
        if (loginTab) loginTab.classList.remove('active');
        if (registerTab) registerTab.classList.add('active');
        if (loginForm) loginForm.classList.add('d-none');
        if (registerForm) registerForm.classList.remove('d-none');
        if (authModalTitle) authModalTitle.textContent = 'Registrarse';
    }
}

// ========== SCROLL SUAVE ==========

// Función para scroll suave a las secciones
function initSmoothScrolling() {
    // Navegación superior
    document.querySelectorAll('nav a').forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const targetId = this.getAttribute('href');
            const targetSection = document.querySelector(targetId);
            if (targetSection) {
                // Remover clase active de todos los enlaces
                document.querySelectorAll('nav a').forEach(a => a.classList.remove('active'));
                // Agregar clase active al enlace clickeado
                this.classList.add('active');
                // Scroll suave
                targetSection.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });

    // Navegación inferior
    document.querySelectorAll('.bottom-nav-item').forEach(link => {
        if (link.getAttribute('href')) {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                const targetId = this.getAttribute('href');
                const targetSection = document.querySelector(targetId);
                if (targetSection) {
                    // Remover clase active de todos los enlaces
                    document.querySelectorAll('.bottom-nav-item').forEach(a => a.classList.remove('active'));
                    // Agregar clase active al enlace clickeado
                    this.classList.add('active');
                    // Scroll suave
                    targetSection.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        }
    });

    // Actualizar navegación al hacer scroll
    window.addEventListener('scroll', function() {
        const sections = document.querySelectorAll('section[id]');
        const navLinks = document.querySelectorAll('nav a, .bottom-nav-item[href]');

        let current = '';
        sections.forEach(section => {
            const sectionTop = section.offsetTop;
            const sectionHeight = section.clientHeight;
            if (scrollY >= (sectionTop - 100)) {
                current = section.getAttribute('id');
            }
        });

        navLinks.forEach(link => {
            link.classList.remove('active');
            if (link.getAttribute('href') === `#${current}`) {
                link.classList.add('active');
            }
        });
    });
}



// Reinicializar cuando el usuario inicie sesión
window.reinitializeOnLogin = async function() {
    await loadUserCart();
    await loadProducts();
    await loadOfferProducts();
};

// Añadir event listener para el botón de vaciar carrito
document.addEventListener('DOMContentLoaded', function() {
    const clearCartBtn = document.getElementById('clear-cart-btn');
    if (clearCartBtn) {
        clearCartBtn.addEventListener('click', clearCart);
    }
});




// ========== EVENT LISTENERS GLOBALES ==========

// Elementos del DOM
const searchButton = document.getElementById('search-button');
const mobileSearchButton = document.getElementById('mobile-search-button');
const cartButton = document.getElementById('cart-button');
const cartModal = document.getElementById('cart-modal');
const closeCart = document.getElementById('close-cart');
const checkoutBtn = document.getElementById('checkout-btn');
const checkoutModal = document.getElementById('checkout-modal');
const closeCheckout = document.getElementById('close-checkout');
const checkoutForm = document.getElementById('checkout-form');
const quickviewModal = document.getElementById('quickview-modal');
const closeQuickview = document.getElementById('close-quickview');
const searchModal = document.getElementById('search-modal');
const closeSearch = document.getElementById('close-search');
const modalSearchInput = document.getElementById('modal-search-input');
const successMessage = document.getElementById('success-message');
const successBtn = document.getElementById('success-btn');
const overlay = document.getElementById('overlay');
const themeToggle = document.getElementById('theme-toggle');

// Botones de autenticación
const loginButton = document.getElementById('login-button');
const registerButton = document.getElementById('register-button');
const closeAuth = document.getElementById('close-auth');
const closeProfile = document.getElementById('close-profile');
const loginTab = document.getElementById('login-tab');
const registerTab = document.getElementById('register-tab');
const loginForm = document.getElementById('login-form');
const registerForm = document.getElementById('register-form');
const userInfo = document.getElementById('user-info');
const logoutButton = document.getElementById('logout-button');

// Controles de vista rápida
const quickviewMinus = document.getElementById('quickview-minus');
const quickviewPlus = document.getElementById('quickview-plus');
const quickviewAdd = document.getElementById('quickview-add');

// Event Listeners para modales
if (searchButton) {
    searchButton.addEventListener('click', () => {
        openModal(searchModal);
        if (modalSearchInput) modalSearchInput.focus();
    });
}

if (mobileSearchButton) {
    mobileSearchButton.addEventListener('click', () => {
        openModal(searchModal);
        if (modalSearchInput) modalSearchInput.focus();
    });
}

if (cartButton) cartButton.addEventListener('click', () => openModal(cartModal));
if (closeCart) closeCart.addEventListener('click', () => closeModal(cartModal));

if (checkoutBtn) {
    checkoutBtn.addEventListener('click', () => {
        if (cart.length > 0) {
            closeModal(cartModal);
            updateCheckout();
            openModal(checkoutModal);
        }
    });
}

if (closeCheckout) closeCheckout.addEventListener('click', () => closeModal(checkoutModal));

if (checkoutForm) {
    checkoutForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        
        if (!isLoggedIn) {
            showAlert('Debes iniciar sesión para finalizar la compra', 'warning');
            openAuthModal();
            return;
        }

        if (cart.length === 0) {
            showAlert('Debe agregar productos antes de finalizar la compra', 'warning');
            return;
        }

        const checkoutData = {
            name: document.getElementById('name').value.trim(),
            email: document.getElementById('email').value.trim(),
            shipping_address: [ // ✅ array con 3 elementos
                document.getElementById('address').value.trim(),
                document.getElementById('city').value.trim(),
                document.getElementById('zipcode').value.trim()
            ],
            payment_method: 'tarjeta',
            notes: document.getElementById('order-notes')?.value.trim() || ''
        };

        const success = await processCheckout(checkoutData);
        
        if (success) {
            closeModal(checkoutModal);
            openModal(successMessage);
            checkoutForm.reset();
        }
    });
}

if (closeQuickview) closeQuickview.addEventListener('click', () => closeQuickviewModal());
if (closeSearch) closeSearch.addEventListener('click', () => closeModal(searchModal));

if (modalSearchInput) {
    modalSearchInput.addEventListener('keyup', (e) => {
        const query = modalSearchInput.value.trim();
        if (query.length > 2) {
            performSearch(query);
        } else {
            const searchResults = document.getElementById('search-results');
            const noResults = document.getElementById('no-results');
            if (searchResults) searchResults.innerHTML = '';
            if (noResults) noResults.style.display = 'block';
        }
    });
}

if (successBtn) successBtn.addEventListener('click', () => closeModal(successMessage));

if (overlay) {
    overlay.addEventListener('click', () => {
        closeModal(cartModal);
        closeModal(checkoutModal);
        closeModal(quickviewModal);
        closeModal(searchModal);
        closeModal(successMessage);
        closeAuthModal();
        closeProfileModal();
        if (modalSearchInput) modalSearchInput.value = '';
    });
}

// Event listeners para controles de cantidad en quickview
if (quickviewMinus) quickviewMinus.addEventListener('click', decreaseQuantity);
if (quickviewPlus) quickviewPlus.addEventListener('click', increaseQuantity);
if (quickviewAdd) quickviewAdd.addEventListener('click', addToCartFromQuickview);

// Event listeners de autenticación
if (loginButton) {
    loginButton.addEventListener('click', function() {
        openAuthModal();
        switchAuthTab('login');
    });
}

if (registerButton) {
    registerButton.addEventListener('click', function() {
        openAuthModal();
        switchAuthTab('register');
    });
}

if (closeAuth) closeAuth.addEventListener('click', closeAuthModal);
if (closeProfile) closeProfile.addEventListener('click', closeProfileModal);

if (loginTab) {
    loginTab.addEventListener('click', function() {
        switchAuthTab('login');
    });
}

if (registerTab) {
    registerTab.addEventListener('click', function() {
        switchAuthTab('register');
    });
}

if (loginForm) {
    loginForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        const email = document.getElementById('login-email').value;
        const password = document.getElementById('login-password').value;
        
        if (await loginUser(email, password)) {
            closeAuthModal();
        }
    });
}

if (registerForm) {
    registerForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        const name = document.getElementById('register-name').value;
        const email = document.getElementById('register-email').value;
        const password = document.getElementById('register-password').value;
        const confirmPassword = document.getElementById('register-confirm').value;

        // Validaciones básicas
        if (password !== confirmPassword) {
            showAlert('Las contraseñas no coinciden', 'danger');
            return;
        }

        if (password.length < 6) {
            showAlert('La contraseña debe tener al menos 6 caracteres', 'danger');
            return;
        }

        if (await registerUser(name, email, password)) {
            switchAuthTab('login');
            const loginEmail = document.getElementById('login-email');
            if (loginEmail) loginEmail.value = email;
        }
    });
}

// Menú de usuario
if (userInfo) {
    userInfo.addEventListener('click', function() {
        const userMenu = document.getElementById('user-menu');
        if (userMenu) userMenu.classList.toggle('open');
    });
}

// Cerrar sesión
if (logoutButton) {
    logoutButton.addEventListener('click', function() {
        logoutUser();
        const userMenu = document.getElementById('user-menu');
        if (userMenu) userMenu.classList.remove('open');
    });
}

// Cerrar menú de usuario al hacer clic fuera de él
document.addEventListener('click', function(e) {
    const userInfo = document.getElementById('user-info');
    const userMenu = document.getElementById('user-menu');
    
    if (userInfo && userMenu && !userInfo.contains(e.target)) {
        userMenu.classList.remove('open');
    }
});

// Tema oscuro/claro
if (themeToggle) {
    themeToggle.addEventListener('click', () => {
        document.body.classList.toggle('dark-mode');
        if (document.body.classList.contains('dark-mode')) {
            themeToggle.innerHTML = '<i class="fas fa-sun"></i>';
            localStorage.setItem('theme', 'dark');
        } else {
            themeToggle.innerHTML = '<i class="fas fa-moon"></i>';
            localStorage.setItem('theme', 'light');
        }
    });

    // Cargar tema guardado
    if (localStorage.getItem('theme') === 'dark') {
        document.body.classList.add('dark-mode');
        themeToggle.innerHTML = '<i class="fas fa-sun"></i>';
    }
}

// ========== FUNCIONES AUXILIARES ==========

// Función para finalizar la compra
async function processCheckout(checkoutData) {
    if (!isLoggedIn) {
        showAlert('Debes iniciar sesión para finalizar la compra', 'warning');
        openAuthModal();
        return false;
    }

    if (cart.length === 0) {
        showAlert('Debe agregar productos antes de finalizar la compra', 'warning');
        return false;
    }

    try {
        const response = await fetch(`${API_BASE_URL}/checkout`, {
            method: 'POST',
            headers: getAuthHeaders(),
            body: JSON.stringify(checkoutData)
        });

        const data = await response.json();

        if (data.success) {
            // Limpiar carrito después de compra exitosa
            cart = [];
            updateCart();
            
            showAlert(data.message || '¡Compra realizada con éxito!', 'success');
            return true;
        } else {
            showAlert(data.message || 'Error al procesar la compra', 'danger');
            return false;
        }
    } catch (error) {
        console.error('Error en checkout:', error);
        showAlert('Error de conexión al procesar la compra', 'danger');
        return false;
    }
}

// Función para actualizar vista de checkout
// Función para actualizar la vista de checkout
function updateCheckout() {
    const checkoutItems = document.getElementById('checkout-items');
    const checkoutSubtotal = document.getElementById('checkout-subtotal');
    const checkoutDiscount = document.getElementById('checkout-discount');
    const checkoutTotal = document.getElementById('checkout-total');

    if (!checkoutItems) return;

    checkoutItems.innerHTML = '';

    if (cart.length === 0) {
        checkoutItems.innerHTML = '<p class="text-center">No hay productos en el carrito</p>';
        return;
    }

    // Calcular totales
    const subtotal = cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
    const totalDiscount = cart.reduce((sum, item) => sum + (item.discount || 0), 0);
    const total = subtotal - totalDiscount;

    // Renderizar items en checkout
    cart.forEach(item => {
        const checkoutItem = document.createElement('div');
        checkoutItem.className = 'checkout-item';
        checkoutItem.innerHTML = `
            <div class="checkout-item-image">
                <img src="${item.image}" alt="${item.name}" onerror="this.src='https://images.unsplash.com/photo-1567538096630-e0c55bd6374c?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80'">
            </div>
            <div class="checkout-item-details">
                <h4>${item.name}</h4>
                <p>Cantidad: ${item.quantity}</p>
                <p>Precio: $${item.price.toLocaleString('es-MX')} MXN</p>
                ${item.discount > 0 ? `<p class="text-success">Descuento: -$${item.discount.toLocaleString('es-MX')} MXN</p>` : ''}
                <p><strong>Subtotal: $${((item.price * item.quantity) - (item.discount || 0)).toLocaleString('es-MX')} MXN</strong></p>
            </div>
        `;
        checkoutItems.appendChild(checkoutItem);
    });

       // Actualizar totales
    if (checkoutSubtotal) checkoutSubtotal.textContent = `$${subtotal.toLocaleString('es-MX')} MXN`;
    if (checkoutDiscount) checkoutDiscount.textContent = `-$${totalDiscount.toLocaleString('es-MX')} MXN`;
    if (checkoutTotal) checkoutTotal.textContent = `$${total.toLocaleString('es-MX')} MXN`;
}

// Función para buscar productos
// Función para buscar productos en la API
async function performSearch(query) {
    try {
        const response = await fetch(`${API_BASE_URL}/products/search?query=${encodeURIComponent(query)}`);
        const data = await response.json();
        
        if (data.success) {
            displaySearchResults(data.data);
        } else {
            // Fallback: búsqueda local
            const results = products.filter(product => 
                product.name.toLowerCase().includes(query.toLowerCase()) ||
                product.description.toLowerCase().includes(query.toLowerCase())
            );
            displaySearchResults(results);
        }
    } catch (error) {
        console.error('Error en búsqueda:', error);
        // Fallback: búsqueda local
        const results = products.filter(product => 
            product.name.toLowerCase().includes(query.toLowerCase()) ||
            product.description.toLowerCase().includes(query.toLowerCase())
        );
        displaySearchResults(results);
    }
}

// Función para mostrar resultados de búsqueda
function displaySearchResults(results) {
    // Implementación de mostrar resultados
}





// ✅ Función reutilizable para iniciar / reiniciar Swiper
function initOffersSwiper() {
    // Destruir instancia previa si existe
    if (window.offersSwiper) {
        window.offersSwiper.destroy(true, true);
    }

    // Crear nueva instancia
    window.offersSwiper = new Swiper('.offers-swiper', {
        slidesPerView: 1,
        spaceBetween: 30,
        loop: false,
        centeredSlides: true,
        autoplay: {
            delay: 5000,
            disableOnInteraction: false,
        },
        pagination: {
            el: '.swiper-pagination',
            clickable: true,
        },
        navigation: {
            nextEl: '.swiper-button-next',
            prevEl: '.swiper-button-prev',
        },
        breakpoints: {
            640: {
                slidesPerView: 1,
                spaceBetween: 20,
            },
            768: {
                slidesPerView: 2,
                spaceBetween: 30,
            },
            1024: {
                slidesPerView: 3,
                spaceBetween: 30,
            },
        },
    });
}

// ✅ Inicializar una vez al cargar la página
document.addEventListener('DOMContentLoaded', initOffersSwiper);






// ========== DEBUG Y LOGS MEJORADOS ==========

// Función para verificar estado de la API
async function checkAPIStatus() {
    try {
        console.log('🔍 Verificando estado de la API...');
        const response = await fetch(`${API_BASE_URL}/products`);
        console.log('📊 Status de respuesta:', response.status);
        
        const data = await response.json();
        console.log('📦 Respuesta de la API:', data);
        
        if (data.success) {
            console.log(`✅ API funcionando. ${data.data.length} productos encontrados.`);
        } else {
            console.error('❌ Error en respuesta API:', data.message);
        }
    } catch (error) {
        console.error('❌ Error conectando con la API:', error);
    }
}




//////////////////////DOLIBARRRRRRRR***************************************
// Función para cargar el dashboard Dolibarr
function loadDolibarrDashboard() {
    // Ocultar contenido actual y mostrar dashboard
    const mainContent = document.getElementById('main-content');
    
    // Crear interfaz del dashboard
    mainContent.innerHTML = `
        <div class="container">
            <h1>🚀 Dashboard Dolibarr - Madera Viva</h1>
            
            <div class="stats" id="stats">
                <div class="stat-card">
                    <h3>👥 Usuarios</h3>
                    <div id="userCount">-</div>
                </div>
                <div class="stat-card">
                    <h3>📦 Productos</h3>
                    <div id="productCount">-</div>
                </div>
                <div class="stat-card">
                    <h3>🏢 Terceros</h3>
                    <div id="thirdpartyCount">-</div>
                </div>
                <div class="stat-card">
                    <h3>🔗 Estado</h3>
                    <div id="connectionStatus">-</div>
                </div>
            </div>

            <div class="card">
                <h2>🔗 Conexión y Sistema</h2>
                <button onclick="testDolibarrConnection()">Probar Conexión</button>
                <button onclick="getSystemStatus()">Estado del Sistema</button>
            </div>

            <div class="card">
                <h2>👥 Gestión de Usuarios</h2>
                <button onclick="getUsers()">Obtener Usuarios</button>
                <button onclick="getContacts()">Obtener Contactos</button>
            </div>

            <div class="card">
                <h2>📦 Inventario y Productos</h2>
                <button onclick="getProducts()">Obtener Productos</button>
                <button onclick="getCategories()">Obtener Categorías</button>
            </div>

            <div class="result" id="dolibarrResult"></div>
        </div>
    `;

    // Cargar estadísticas iniciales
    loadDolibarrStats();
}

// Funciones para llamar a la API Laravel
async function testDolibarrConnection() {
    showDolibarrLoading('Probando conexión...');
    try {
        const response = await fetch('/dolibarr/test');
        const data = await response.json();
        showDolibarrResult(data, 'success');
        document.getElementById('connectionStatus').innerHTML = '<span style="color: green;">✅ Conectado</span>';
    } catch (error) {
        showDolibarrError(error);
        document.getElementById('connectionStatus').innerHTML = '<span style="color: red;">❌ Error</span>';
    }
}

async function getUsers() {
    showDolibarrLoading('Obteniendo usuarios...');
    try {
        const response = await fetch('/dolibarr/users');
        const data = await response.json();
        document.getElementById('userCount').textContent = data.data ? data.data.length : 0;
        showDolibarrResult(data);
    } catch (error) {
        showDolibarrError(error);
    }
}

async function getProducts() {
    showDolibarrLoading('Obteniendo productos...');
    try {
        const response = await fetch('/dolibarr/products');
        const data = await response.json();
        document.getElementById('productCount').textContent = data.data ? data.data.length : 0;
        showDolibarrResult(data);
    } catch (error) {
        showDolibarrError(error);
    }
}

async function getThirdParties() {
    showDolibarrLoading('Obteniendo terceros...');
    try {
        const response = await fetch('/dolibarr/thirdparties');
        const data = await response.json();
        document.getElementById('thirdpartyCount').textContent = data.data ? data.data.length : 0;
        showDolibarrResult(data);
    } catch (error) {
        showDolibarrError(error);
    }
}

// Funciones auxiliares
function loadDolibarrStats() {
    testDolibarrConnection();
    getUsers();
    getProducts();
    getThirdParties();
}

function showDolibarrLoading(message) {
    document.getElementById('dolibarrResult').innerHTML = `<p>⏳ ${message}</p>`;
}

function showDolibarrResult(data, type = '') {
    const resultDiv = document.getElementById('dolibarrResult');
    const jsonString = JSON.stringify(data, null, 2);
    
    if (type === 'success') {
        resultDiv.innerHTML = `<div style="color: green;">${data.message}</div><pre>${jsonString}</pre>`;
    } else {
        resultDiv.innerHTML = `<pre>${jsonString}</pre>`;
    }
}

function showDolibarrError(error) {
    document.getElementById('dolibarrResult').innerHTML = 
        `<div style="color: red;">Error: ${error.message}</div>`;
}

// Funciones adicionales que faltan en tu código
async function getSystemStatus() {
    showDolibarrLoading('Obteniendo estado del sistema...');
    try {
        const response = await fetch('/dolibarr/status');
        const data = await response.json();
        showDolibarrResult(data);
    } catch (error) {
        showDolibarrError(error);
    }
}

async function getContacts() {
    showDolibarrLoading('Obteniendo contactos...');
    try {
        const response = await fetch('/dolibarr/contacts');
        const data = await response.json();
        showDolibarrResult(data);
    } catch (error) {
        showDolibarrError(error);
    }
}

async function getCategories() {
    showDolibarrLoading('Obteniendo categorías...');
    try {
        const response = await fetch('/dolibarr/categories');
        const data = await response.json();
        showDolibarrResult(data);
    } catch (error) {
        showDolibarrError(error);
    }
}


console.log('MaderaViva JavaScript cargado correctamente con integración completa');

