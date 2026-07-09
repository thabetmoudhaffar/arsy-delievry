// Arsy Delivery - Main JavaScript

// Global delivery coordinates
let userLat = parseFloat(localStorage.getItem('arsy_lat')) || null;
let userLng = parseFloat(localStorage.getItem('arsy_lng')) || null;

document.addEventListener('DOMContentLoaded', () => {
    initParticles();
    initNavbar();
    initFadeIn();
    initCart();
    initDeliveryAddress();
    initMobileSidebar();
    initLogoutConfirmation();
});

// Floating food/grocery/pharmacy icon silhouettes with seamless page transitions
function initParticles() {
    const container = document.querySelector('.particles');
    if (!container) return;

    // Persist start time in session storage to keep animations continuous
    let particlesStart = sessionStorage.getItem('particles_start_time');
    if (!particlesStart) {
        particlesStart = Date.now().toString();
        sessionStorage.setItem('particles_start_time', particlesStart);
    }
    const elapsed = (Date.now() - parseInt(particlesStart)) / 1000;

    const icons = [
        'fa-burger', 'fa-pizza-slice', 'fa-drumstick-bite', 'fa-ice-cream',
        'fa-mug-hot', 'fa-wine-bottle', 'fa-cookie-bite', 'fa-cheese',
        'fa-apple-whole', 'fa-carrot', 'fa-lemon', 'fa-pepper-hot',
        'fa-basket-shopping', 'fa-cart-shopping', 'fa-box-open',
        'fa-prescription-bottle-medical', 'fa-capsules', 'fa-pills',
        'fa-truck-fast', 'fa-motorcycle', 'fa-bag-shopping',
        'fa-utensils', 'fa-bowl-food', 'fa-cake-candles', 'fa-glass-water'
    ];

    // Seeded random function to ensure the exact same icons are built on every page load
    let seed = 42;
    function seededRandom() {
        const x = Math.sin(seed++) * 10000;
        return x - Math.floor(x);
    }

    for (let i = 0; i < 25; i++) {
        const particle = document.createElement('i');
        const icon = icons[Math.floor(seededRandom() * icons.length)];
        particle.className = `fa-solid ${icon} particle` + (seededRandom() > 0.8 ? ' glow' : '');
        particle.style.left = (seededRandom() * 100) + '%';
        particle.style.fontSize = (18 + seededRandom() * 26) + 'px';
        
        const duration = 18 + seededRandom() * 22;
        const initialDelay = seededRandom() * 20;
        
        // Negative animation delay starts the animation mid-flight based on elapsed session time
        const adjustedDelay = -((elapsed - initialDelay) % duration);
        
        particle.style.animationDuration = duration + 's';
        particle.style.animationDelay = adjustedDelay + 's';
        container.appendChild(particle);
    }
}

// Navbar scroll effect
function initNavbar() {
    const navbar = document.querySelector('.navbar');
    if (!navbar) return;

    window.addEventListener('scroll', () => {
        navbar.classList.toggle('scrolled', window.scrollY > 50);
    });

    const mobileBtn = document.querySelector('.mobile-menu-btn');
    const navLinks = document.querySelector('.nav-links');
    if (mobileBtn && navLinks) {
        mobileBtn.addEventListener('click', () => {
            navLinks.style.display = navLinks.style.display === 'flex' ? 'none' : 'flex';
        });
    }
}

// Fade in on scroll
function initFadeIn() {
    const elements = document.querySelectorAll('.fade-in');
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('visible');
            }
        });
    }, { threshold: 0.1 });

    elements.forEach(el => observer.observe(el));
}

// Cart functionality
let cart = JSON.parse(localStorage.getItem('arsy_cart') || '[]');
let activeProductId = null;

function getCartKey(item) {
    const ings = (item.ingredients || []).slice().sort().join('|');
    return `${item.id}::${ings}`;
}

function initCart() {
    updateCartUI();
}

function addToCart(product) {
    const key = getCartKey(product);
    const existing = cart.find(item => getCartKey(item) === key);
    if (existing) {
        existing.quantity++;
    } else {
        cart.push({ ...product, quantity: 1 });
    }
    saveCart();
    updateCartUI();
    showNotification('Produit ajouté au panier!');
}

function removeFromCart(index) {
    cart.splice(index, 1);
    saveCart();
    updateCartUI();
}

function updateQuantity(index, delta) {
    const item = cart[index];
    if (item) {
        item.quantity += delta;
        if (item.quantity <= 0) {
            removeFromCart(index);
        } else {
            saveCart();
            updateCartUI();
        }
    }
}

function saveCart() {
    localStorage.setItem('arsy_cart', JSON.stringify(cart));
}

function getCartTotal() {
    return cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
}

function updateCartUI() {
    const cartCount = document.querySelector('.cart-count');
    const cartItems = document.querySelector('.cart-items');
    const cartTotal = document.querySelector('.cart-total-amount');

    const totalItems = cart.reduce((sum, item) => sum + item.quantity, 0);
    if (cartCount) cartCount.textContent = totalItems;

    if (cartItems) {
        if (cart.length === 0) {
            cartItems.innerHTML = '<p style="text-align:center;color:var(--text-muted);padding:40px 0;">Votre panier est vide</p>';
        } else {
            cartItems.innerHTML = cart.map((item, index) => {
                const ings = (item.ingredients || []).length
                    ? `<div class="cart-item-ingredients">${item.ingredients.join(', ')}</div>`
                    : '';
                return `
                <div class="cart-item">
                    <div class="cart-item-info">
                        <h4>${item.name}</h4>
                        ${ings}
                        <div class="price">${item.price.toFixed(2)} DT</div>
                    </div>
                    <div class="cart-item-qty">
                        <button onclick="updateQuantity(${index}, -1)">-</button>
                        <span>${item.quantity}</span>
                        <button onclick="updateQuantity(${index}, 1)">+</button>
                    </div>
                </div>
            `}).join('');
        }
    }

    if (cartTotal) cartTotal.textContent = getCartTotal().toFixed(2) + ' DT';
}

function toggleCart() {
    const opening = !document.querySelector('.cart-sidebar')?.classList.contains('open');
    document.querySelector('.cart-sidebar')?.classList.toggle('open');
    document.querySelector('.cart-overlay')?.classList.toggle('open');
    if (opening) {
        initCartMap();
    }
}

function clearCart() {
    cart = [];
    saveCart();
    updateCartUI();
}

// ── Product modal (sizes & ingredients) ──
function openProductModal(productId) {
    const catalog = typeof PRODUCTS_CATALOG !== 'undefined' ? PRODUCTS_CATALOG : [];
    const product = catalog.find(p => p.id === productId);
    if (!product) return;

    // If product has no sizes AND no ingredients, add directly
    if ((!product.ingredients || product.ingredients.length === 0) && (!product.sizes || product.sizes.length === 0)) {
        addToCart({
            id: product.id,
            name: product.name,
            price: product.price,
            image: product.image,
            ingredients: [],
            size: null
        });
        return;
    }

    activeProductId = productId;

    document.getElementById('modal-product-image').src = product.image;
    document.getElementById('modal-product-image').alt = product.name;
    document.getElementById('modal-product-name').textContent = product.name;

    // Render Sizes
    const sizesSection = document.getElementById('modal-sizes-section');
    const sizesList = document.getElementById('modal-sizes-list');
    if (product.sizes && product.sizes.length > 0) {
        sizesSection.hidden = false;
        sizesList.innerHTML = product.sizes.map((sz, idx) => `
            <label class="size-chip-label" style="position:relative;cursor:pointer;">
                <input type="radio" name="modal-product-size" value="${sz.id}" data-name="${sz.name.replace(/"/g, '&quot;')}" data-price="${sz.price}" ${idx === 0 ? 'checked' : ''} onchange="updateModalPriceDisplay()" style="position:absolute;opacity:0;width:0;height:0;">
                <span class="size-chip-text" style="display:inline-block;padding:8px 16px;border-radius:20px;border:1.5px solid var(--border);font-size:0.875rem;font-weight:600;transition:var(--transition);">${sz.name} (${sz.price.toFixed(2)} DT)</span>
            </label>
        `).join('');
    } else {
        sizesSection.hidden = true;
        sizesList.innerHTML = '';
    }

    // Render Ingredients
    const section = document.getElementById('modal-ingredients-section');
    const list = document.getElementById('modal-ingredients-list');
    if (product.ingredients && product.ingredients.length > 0) {
        section.hidden = false;
        list.innerHTML = product.ingredients.map(ing => {
            const extraPrice = ing.price > 0 ? ` (+${ing.price.toFixed(2)} DT)` : '';
            return `
                <label class="ingredient-checkbox">
                    <input type="checkbox" value="${ing.name.replace(/"/g, '&quot;')}" data-price="${ing.price}" ${ing.is_default ? 'checked' : ''} onchange="updateModalPriceDisplay()">
                    <span>${ing.name}${extraPrice}</span>
                </label>
            `;
        }).join('');
    } else {
        section.hidden = true;
        list.innerHTML = '';
    }

    updateModalPriceDisplay();

    document.getElementById('product-modal').classList.add('open');
    document.getElementById('product-modal-overlay').classList.add('open');
}

function updateModalPriceDisplay() {
    const catalog = typeof PRODUCTS_CATALOG !== 'undefined' ? PRODUCTS_CATALOG : [];
    const product = catalog.find(p => p.id === activeProductId);
    if (!product) return;

    let basePrice = product.price;

    // Check size selection
    const selectedSizeInput = document.querySelector('input[name="modal-product-size"]:checked');
    if (selectedSizeInput) {
        basePrice = parseFloat(selectedSizeInput.dataset.price);
    }

    // Check checked ingredients extra prices
    let extraPrice = 0;
    document.querySelectorAll('#modal-ingredients-list input[type="checkbox"]:checked').forEach(cb => {
        extraPrice += parseFloat(cb.dataset.price || 0);
    });

    const totalPrice = basePrice + extraPrice;
    document.getElementById('modal-product-price').textContent = totalPrice.toFixed(2) + ' DT';
}

function closeProductModal() {
    activeProductId = null;
    document.getElementById('product-modal')?.classList.remove('open');
    document.getElementById('product-modal-overlay')?.classList.remove('open');
}

function confirmAddToCart() {
    const catalog = typeof PRODUCTS_CATALOG !== 'undefined' ? PRODUCTS_CATALOG : [];
    const product = catalog.find(p => p.id === activeProductId);
    if (!product) return;

    let basePrice = product.price;
    let selectedSizeName = null;
    const selectedSizeInput = document.querySelector('input[name="modal-product-size"]:checked');
    if (selectedSizeInput) {
        basePrice = parseFloat(selectedSizeInput.dataset.price);
        selectedSizeName = selectedSizeInput.dataset.name;
    }

    const selectedIngredients = [];
    let extraPrice = 0;
    document.querySelectorAll('#modal-ingredients-list input[type="checkbox"]:checked').forEach(cb => {
        selectedIngredients.push(cb.value);
        extraPrice += parseFloat(cb.dataset.price || 0);
    });

    const finalPrice = basePrice + extraPrice;

    // Format display name
    let displayName = product.name;
    if (selectedSizeName) {
        displayName += ` (${selectedSizeName})`;
    }

    addToCart({
        id: product.id,
        name: displayName,
        price: finalPrice,
        image: product.image,
        ingredients: selectedIngredients,
        size: selectedSizeName
    });

    closeProductModal();
    toggleCart();
}

// ── Geolocation helpers ──
async function reverseGeocode(lat, lng) {
    try {
        const res = await fetch(`https://nominatim.openstreetmap.org/reverse?lat=${lat}&lon=${lng}&format=json&accept-language=fr`);
        if (!res.ok) return null;
        const data = await res.json();
        return data.display_name || null;
    } catch { return null; }
}

async function geocodeAddress(address) {
    try {
        const res = await fetch(`https://nominatim.openstreetmap.org/search?q=${encodeURIComponent(address)}&format=json&limit=1&countrycodes=tn`);
        if (!res.ok) return null;
        const data = await res.json();
        if (data && data.length > 0) return { lat: parseFloat(data[0].lat), lng: parseFloat(data[0].lon) };
        return null;
    } catch { return null; }
}

function setAddressStatus(type, message) {
    const el = document.getElementById('address-status');
    if (!el) return;
    el.className = `address-status address-status--${type}`;
    el.innerHTML = message;
    el.hidden = !message;
}

let cartMap = null;
let cartMarker = null;

function initCartMap() {
    const mapEl = document.getElementById('cart-map');
    if (!mapEl) return;

    const defaultLat = userLat || 36.8065;
    const defaultLng = userLng || 10.1815;

    if (!cartMap) {
        cartMap = L.map('cart-map').setView([defaultLat, defaultLng], 13);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(cartMap);

        const markerIcon = L.divIcon({
            html: '<div style="background:#FF6B35;width:20px;height:20px;border-radius:50%;border:3px solid white;box-shadow:0 2px 8px rgba(0,0,0,0.5);"></div>',
            iconSize: [20, 20],
            iconAnchor: [10, 10]
        });

        cartMarker = L.marker([defaultLat, defaultLng], {
            draggable: true,
            icon: markerIcon
        }).addTo(cartMap);

        cartMarker.on('dragend', async function(e) {
            const position = cartMarker.getLatLng();
            saveCoords(position.lat, position.lng, false); // false to avoid recursive setView
            setAddressStatus('loading', '<i class="fas fa-spinner fa-spin"></i> Recherche de l\'adresse...');
            const address = await reverseGeocode(position.lat, position.lng);
            if (address) {
                document.getElementById('delivery-address').value = address;
                setAddressStatus('success', '<i class="fas fa-check-circle"></i> Adresse mise à jour par déplacement ✓');
            }
        });

        cartMap.on('click', async function(e) {
            const position = e.latlng;
            cartMarker.setLatLng(position);
            saveCoords(position.lat, position.lng, false);
            setAddressStatus('loading', '<i class="fas fa-spinner fa-spin"></i> Recherche de l\'adresse...');
            const address = await reverseGeocode(position.lat, position.lng);
            if (address) {
                document.getElementById('delivery-address').value = address;
                setAddressStatus('success', '<i class="fas fa-check-circle"></i> Adresse mise à jour par clic ✓');
            }
        });
    } else {
        cartMap.setView([defaultLat, defaultLng], 13);
        cartMarker.setLatLng([defaultLat, defaultLng]);
    }

    setTimeout(() => {
        cartMap.invalidateSize();
    }, 200);
}

function saveCoords(lat, lng, updateMap = true) {
    if (lat != null && lng != null) {
        localStorage.setItem('arsy_lat', lat);
        localStorage.setItem('arsy_lng', lng);
        userLat = lat;
        userLng = lng;

        if (updateMap && cartMap && cartMarker) {
            cartMarker.setLatLng([lat, lng]);
            cartMap.setView([lat, lng], 13);
        }
    }
}

// ── Delivery address ──
function initDeliveryAddress() {
    const field = document.getElementById('delivery-address');
    if (!field) return;

    // Clear stale localStorage to avoid old wrong coords
    localStorage.removeItem('arsy_address');
    localStorage.removeItem('arsy_lat');
    localStorage.removeItem('arsy_lng');
    userLat = null;
    userLng = null;

    const prefill = (field.dataset.prefill || '').trim();
    const fallback = (field.dataset.fallback || '').trim();

    // Refresh button
    document.getElementById('btn-refresh-location')?.addEventListener('click', () => {
        detectGPS(field);
    });

    // Debounced geocoding when user types
    let geocodeTimer = null;
    field.addEventListener('input', () => {
        clearTimeout(geocodeTimer);
        const val = field.value.trim();
        if (!val) return;
        setAddressStatus('loading', '<i class="fas fa-spinner fa-spin"></i> Recherche...');
        geocodeTimer = setTimeout(async () => {
            const coords = await geocodeAddress(val);
            if (coords) {
                saveCoords(coords.lat, coords.lng);
                setAddressStatus('success', '<i class="fas fa-check-circle"></i> Adresse localisée ✓');
            } else {
                setAddressStatus('warning', '<i class="fas fa-exclamation-triangle"></i> Adresse introuvable sur la carte');
            }
        }, 1000);
    });

    // Start GPS detection
    detectGPS(field, prefill || fallback);
}

async function detectGPS(field, fallbackAddress) {
    if (!field) return;

    if (!navigator.geolocation) {
        if (fallbackAddress) {
            field.value = fallbackAddress;
            const coords = await geocodeAddress(fallbackAddress);
            if (coords) saveCoords(coords.lat, coords.lng);
        }
        setAddressStatus('info', '<i class="fas fa-info-circle"></i> GPS non disponible — saisissez votre adresse');
        return;
    }

    setAddressStatus('loading', '<i class="fas fa-spinner fa-spin"></i> Détection GPS en cours...');

    navigator.geolocation.getCurrentPosition(
        async (pos) => {
            const lat = pos.coords.latitude;
            const lng = pos.coords.longitude;
            saveCoords(lat, lng);

            setAddressStatus('loading', '<i class="fas fa-spinner fa-spin"></i> Recherche de l\'adresse...');
            const address = await reverseGeocode(lat, lng);
            const finalAddr = address || `${lat.toFixed(5)}, ${lng.toFixed(5)}`;

            if (!field.value.trim()) {
                field.value = finalAddr;
            }
            setAddressStatus('success', '<i class="fas fa-location-dot"></i> Position GPS détectée ✓ (' + lat.toFixed(4) + ', ' + lng.toFixed(4) + ')');
        },
        async () => {
            if (fallbackAddress && !field.value.trim()) {
                field.value = fallbackAddress;
                const coords = await geocodeAddress(fallbackAddress);
                if (coords) {
                    saveCoords(coords.lat, coords.lng);
                    setAddressStatus('warning', '<i class="fas fa-exclamation-triangle"></i> GPS refusé — adresse géocodée');
                } else {
                    setAddressStatus('warning', '<i class="fas fa-exclamation-triangle"></i> GPS refusé — saisissez votre adresse');
                }
            } else if (field.value.trim()) {
                const coords = await geocodeAddress(field.value.trim());
                if (coords) {
                    saveCoords(coords.lat, coords.lng);
                    setAddressStatus('success', '<i class="fas fa-check-circle"></i> Adresse localisée ✓');
                }
            } else {
                setAddressStatus('warning', '<i class="fas fa-exclamation-triangle"></i> Autorisez la localisation ou saisissez votre adresse');
            }
        },
        { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 }
    );
}

// ── Place order ──
async function placeOrder() {
    if (cart.length === 0) {
        showNotification('Votre panier est vide', 'error');
        return;
    }
    const address = document.getElementById('delivery-address')?.value.trim();
    if (!address) {
        showNotification('Veuillez entrer votre adresse de livraison', 'error');
        return;
    }

    let lat = userLat;
    let lng = userLng;

    // If no GPS coords yet, try geocoding the typed address
    if (!lat || !lng) {
        setAddressStatus('loading', '<i class="fas fa-spinner fa-spin"></i> Localisation de l\'adresse...');
        const coords = await geocodeAddress(address);
        if (coords) {
            lat = coords.lat;
            lng = coords.lng;
            saveCoords(lat, lng);
        }
    }

    try {
        const result = await apiRequest((window.BASE_PATH || '') + '/api/orders.php', {
            method: 'POST',
            body: JSON.stringify({
                items: cart.map(i => ({ id: i.id, quantity: i.quantity, ingredients: i.ingredients || [], size: i.size || null })),
                address: address,
                latitude: lat,
                longitude: lng,
                notes: document.getElementById('order-notes')?.value || ''
            })
        });
        clearCart();
        toggleCart();
        showNotification('Commande passée avec succès!');
        setTimeout(() => window.location.href = 'track.php?id=' + result.order_id, 1500);
    } catch (e) {
        showNotification(e.message, 'error');
    }
}

// Notifications
function showNotification(message, type = 'success') {
    const notification = document.createElement('div');
    notification.className = `alert alert-${type}`;
    notification.style.cssText = 'position:fixed;top:100px;right:20px;z-index:9999;animation:slideIn 0.3s ease;';
    notification.innerHTML = `<i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i> ${message}`;
    document.body.appendChild(notification);
    setTimeout(() => {
        notification.style.animation = 'slideOut 0.3s ease';
        setTimeout(() => notification.remove(), 300);
    }, 3000);
}

// API helpers
async function apiRequest(url, options = {}) {
    const response = await fetch(url, {
        headers: { 'Content-Type': 'application/json', ...options.headers },
        ...options
    });
    const data = await response.json();
    if (!response.ok) throw new Error(data.error || 'Request failed');
    return data;
}

// Real-time tracking
class DeliveryTracker {
    constructor(orderId, mapElementId) {
        this.orderId = orderId;
        this.mapElementId = mapElementId;
        this.map = null;
        this.driverMarker = null;
        this.deliveryMarker = null;
        this.pollInterval = null;
    }

    async init() {
        const data = await apiRequest((window.BASE_PATH || '') + `/api/tracking.php?order_id=${this.orderId}`);
        
        const clientLat = data.delivery_lat || 36.8065;
        const clientLng = data.delivery_lng || 10.1815;
        
        this.map = L.map(this.mapElementId).setView([clientLat, clientLng], 13);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap'
        }).addTo(this.map);

        const deliveryIcon = L.divIcon({
            className: 'delivery-marker',
            html: '<div style="background:#00D9A5;width:24px;height:24px;border-radius:50%;border:3px solid white;box-shadow:0 2px 10px rgba(0,0,0,0.3);"></div>',
            iconSize: [24, 24],
            iconAnchor: [12, 12]
        });

        const driverIcon = L.divIcon({
            className: 'driver-marker',
            html: '<div style="background:#FF6B35;width:32px;height:32px;border-radius:50%;border:3px solid white;box-shadow:0 2px 10px rgba(0,0,0,0.3);display:flex;align-items:center;justify-content:center;"><i class="fas fa-motorcycle" style="color:white;font-size:14px;"></i></div>',
            iconSize: [32, 32],
            iconAnchor: [16, 16]
        });

        if (data.delivery_lat && data.delivery_lng) {
            this.deliveryMarker = L.marker([clientLat, clientLng], { icon: deliveryIcon })
                .addTo(this.map)
                .bindPopup('📍 Adresse de livraison');
        }

        const driverLat = data.driver_lat || clientLat;
        const driverLng = data.driver_lng || clientLng;

        this.driverMarker = L.marker([driverLat, driverLng], { icon: driverIcon })
            .addTo(this.map)
            .bindPopup('🛵 Livreur');

        // Draw dynamic routing line between driver and client
        this.routeLine = L.polyline([[driverLat, driverLng], [clientLat, clientLng]], {
            color: '#E5B04C',
            weight: 4,
            dashArray: '8, 8',
            opacity: 0.8
        }).addTo(this.map);

        if (data.driver_lat && data.driver_lng && data.delivery_lat && data.delivery_lng) {
            this.map.fitBounds(L.latLngBounds([driverLat, driverLng], [clientLat, clientLng]), { padding: [50, 50] });
        }

        this.startPolling();
    }

    startPolling() {
        this.pollInterval = setInterval(async () => {
            try {
                const data = await apiRequest((window.BASE_PATH || '') + `/api/tracking.php?order_id=${this.orderId}`);
                if (data.driver_lat && data.driver_lng && this.driverMarker) {
                    const dLat = data.driver_lat;
                    const dLng = data.driver_lng;
                    const cLat = data.delivery_lat || 36.8065;
                    const cLng = data.delivery_lng || 10.1815;

                    this.driverMarker.setLatLng([dLat, dLng]);
                    
                    if (this.routeLine) {
                        this.routeLine.setLatLngs([[dLat, dLng], [cLat, cLng]]);
                    }

                    this.map.fitBounds(L.latLngBounds([dLat, dLng], [cLat, cLng]), { padding: [50, 50] });
                }
                if (data.status === 'delivered') {
                    this.stopPolling();
                }
            } catch (e) {
                console.error('Tracking error:', e);
            }
        }, 5000);
    }

    stopPolling() {
        if (this.pollInterval) {
            clearInterval(this.pollInterval);
            this.pollInterval = null;
        }
    }
}

// Driver location updater
class DriverLocationUpdater {
    constructor(orderId) {
        this.orderId = orderId;
        this.watchId = null;
    }

    start() {
        if (!navigator.geolocation) {
            showNotification('Géolocalisation non supportée', 'error');
            return;
        }

        this.watchId = navigator.geolocation.watchPosition(
            (position) => this.updateLocation(position),
            (error) => showNotification('Erreur de géolocalisation', 'error'),
            { enableHighAccuracy: true, maximumAge: 10000 }
        );
    }

    async updateLocation(position) {
        try {
            await apiRequest((window.BASE_PATH || '') + '/api/tracking.php', {
                method: 'POST',
                body: JSON.stringify({
                    order_id: this.orderId,
                    latitude: position.coords.latitude,
                    longitude: position.coords.longitude
                })
            });
        } catch (e) {
            console.error('Location update failed:', e);
        }
    }

    stop() {
        if (this.watchId) {
            navigator.geolocation.clearWatch(this.watchId);
        }
    }
}

// Add animation styles
const style = document.createElement('style');
style.textContent = `
    @keyframes slideIn { from { transform: translateX(100%); opacity: 0; } to { transform: translateX(0); opacity: 1; } }
    @keyframes slideOut { from { transform: translateX(0); opacity: 1; } to { transform: translateX(100%); opacity: 0; } }
`;
document.head.appendChild(style);

// Dynamic Mobile Sidebar Drawer Selector & Event Listeners
function initMobileSidebar() {
    const sidebar = document.querySelector('.sidebar');
    if (!sidebar) return;

    // Create the floating burger menu button
    const toggleBtn = document.createElement('button');
    toggleBtn.className = 'mobile-sidebar-toggle';
    toggleBtn.innerHTML = '<i class="fas fa-bars"></i>';
    document.body.appendChild(toggleBtn);

    // Toggle click event
    toggleBtn.addEventListener('click', (e) => {
        e.stopPropagation();
        sidebar.classList.toggle('open');
        toggleBtn.innerHTML = sidebar.classList.contains('open') ? '<i class="fas fa-times"></i>' : '<i class="fas fa-bars"></i>';
    });

    // Close when clicking outside
    document.addEventListener('click', (e) => {
        if (sidebar.classList.contains('open') && !sidebar.contains(e.target) && !toggleBtn.contains(e.target)) {
            sidebar.classList.remove('open');
            toggleBtn.innerHTML = '<i class="fas fa-bars"></i>';
        }
    });
}

// Premium Logout Confirmation Modal Interceptor
function initLogoutConfirmation() {
    document.addEventListener('click', (e) => {
        const logoutLink = e.target.closest('a[href*="logout.php"]');
        if (!logoutLink) return;

        // Skip interception if this is the confirmation trigger (safety check)
        if (logoutLink.classList.contains('btn-confirm-logout')) return;

        e.preventDefault();
        const logoutUrl = logoutLink.href;

        // Create modal backdrop overlay
        const modalOverlay = document.createElement('div');
        modalOverlay.className = 'logout-modal-overlay';
        modalOverlay.innerHTML = `
            <div class="logout-modal-card glass-card">
                <div class="logout-modal-icon">
                    <i class="fas fa-sign-out-alt"></i>
                </div>
                <h3>Se déconnecter ?</h3>
                <p>Êtes-vous sûr de vouloir fermer votre session active ?</p>
                <div class="logout-modal-buttons">
                    <button class="btn btn-secondary btn-cancel-logout">Annuler</button>
                    <button type="button" class="btn btn-accent btn-confirm-logout">Déconnexion</button>
                </div>
            </div>
        `;

        document.body.appendChild(modalOverlay);

        // Animate showing
        setTimeout(() => {
            modalOverlay.classList.add('show');
        }, 10);

        // Confirm action (uses button navigation to bypass recursive link click interception)
        modalOverlay.querySelector('.btn-confirm-logout').addEventListener('click', () => {
            window.location.href = logoutUrl;
        });

        // Cancel actions
        const closeModal = () => {
            modalOverlay.classList.remove('show');
            setTimeout(() => {
                modalOverlay.remove();
            }, 300);
        };

        modalOverlay.querySelector('.btn-cancel-logout').addEventListener('click', closeModal);
        modalOverlay.addEventListener('click', (evt) => {
            if (evt.target === modalOverlay) {
                closeModal();
            }
        });
    });
}
