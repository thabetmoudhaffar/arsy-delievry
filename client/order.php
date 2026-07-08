<?php
require_once __DIR__ . '/../includes/functions.php';
requireRole('client');

$db = getDB();
ensureRestaurantSchema();

$categoryId = (int)($_GET['category'] ?? 0);
$restaurantId = (int)($_GET['restaurant_id'] ?? 0);

$categories = $db->query('SELECT * FROM categories')->fetchAll();

// Detect the Food category ID dynamically by name
$foodCategoryId = 0;
foreach ($categories as $cat) {
    if (strtolower($cat['name']) === 'food') {
        $foodCategoryId = (int)$cat['id'];
        break;
    }
}

$showRestaurants = false;
$restaurants = [];
$currentRestaurant = null;
$products = [];

if ($categoryId === $foodCategoryId && $foodCategoryId > 0 && !$restaurantId) {
    // Food Category - list restaurants first
    $showRestaurants = true;
    $restaurants = $db->query('SELECT * FROM restaurants ORDER BY name')->fetchAll();
} else {
    if ($categoryId === $foodCategoryId && $foodCategoryId > 0 && $restaurantId) {
        // Food Category - list specific restaurant menu
        $stmt = $db->prepare('SELECT * FROM restaurants WHERE id = ?');
        $stmt->execute([$restaurantId]);
        $currentRestaurant = $stmt->fetch();

        $stmt = $db->prepare('SELECT p.*, c.name as category_name FROM products p JOIN categories c ON p.category_id = c.id WHERE p.available = 1 AND p.category_id = ? AND p.restaurant_id = ?');
        $stmt->execute([$categoryId, $restaurantId]);
        $products = $stmt->fetchAll();
    } else { // Other categories - list products directly
        if ($categoryId) {
            $stmt = $db->prepare('SELECT p.*, c.name as category_name FROM products p JOIN categories c ON p.category_id = c.id WHERE p.available = 1 AND p.category_id = ?');
            $stmt->execute([$categoryId]);
        } else {
            $stmt = $db->query('SELECT p.*, c.name as category_name FROM products p JOIN categories c ON p.category_id = c.id WHERE p.available = 1');
        }
        $products = $stmt->fetchAll();
    }
}

$basePath = BASE_PATH;
foreach ($products as &$product) {
    $product['ingredients'] = getProductIngredients((int)$product['id']);
    $product['sizes'] = getProductSizes((int)$product['id']);
    $product['image_url'] = getProductImageUrl($product, $basePath);
}
unset($product);

$user = currentUser();
$pageTitle = 'Commander';
$extraCSS = '<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">';
$extraJS = '<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="dashboard">
    <aside class="sidebar">
        <a href="../index.php" class="sidebar-logo">
            <span class="arsy-brand">
                <span class="arsy-brand-text">
                    <span class="arsy-word-arsy">Arsy</span>
                    <span class="arsy-word-delivery">
                        Del<span class="arsy-pin-letter"><i class="fas fa-location-dot" aria-hidden="true"></i></span>very
                    </span>
                </span>
                <svg class="arsy-brand-curve" viewBox="0 0 120 40" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                    <path d="M8 32 Q 40 4, 95 28 L 95 34 L 88 34 L 88 28" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" fill="none"/>
                </svg>
                <span class="arsy-brand-slogan">Livraison Rapide • Tunisie</span>
            </span>
        </a>
        <ul class="sidebar-nav">
            <li><a href="order.php" class="active"><i class="fas fa-shopping-bag"></i> Commander</a></li>
            <li><a href="dashboard.php"><i class="fas fa-home"></i> Tableau de bord</a></li>
            <li><a href="orders.php"><i class="fas fa-list"></i> Mes commandes</a></li>
            <li><a href="profile.php"><i class="fas fa-user"></i> Mon profil</a></li>
            <li><a href="../auth/logout.php"><i class="fas fa-sign-out-alt"></i> Déconnexion</a></li>
        </ul>
    </aside>

    <main class="main-content">
        <div class="page-header" style="display:flex;justify-content:space-between;align-items:center;">
            <div>
                <h1>Commander</h1>
                <p>Choisissez vos produits et passez commande</p>
            </div>
            <button class="btn btn-primary" onclick="toggleCart()">
                <i class="fas fa-shopping-cart"></i> Panier (<span class="cart-count">0</span>)
            </button>
        </div>

        <div style="display:flex;gap:12px;margin-bottom:32px;flex-wrap:wrap;">
            <a href="order.php" class="btn <?= !$categoryId ? 'btn-primary' : 'btn-secondary' ?> btn-sm">Tous</a>
            <?php foreach ($categories as $cat): ?>
            <a href="order.php?category=<?= $cat['id'] ?>" class="btn <?= $categoryId == $cat['id'] ? 'btn-primary' : 'btn-secondary' ?> btn-sm">
                <i class="fas <?= sanitize($cat['icon']) ?>"></i> <?= sanitize($cat['name']) ?>
            </a>
            <?php endforeach; ?>
        </div>

        <?php if ($showRestaurants): ?>
            <!-- Show Restaurants List -->
            <div style="margin-bottom: 24px;">
                <h2 style="font-size:1.5rem;font-weight:700;margin-bottom:8px;"><i class="fas fa-store"></i> Restaurants Partenaires</h2>
                <p style="color:var(--text-secondary);font-size:0.95rem;">Choisissez un restaurant pour voir son menu exclusif</p>
            </div>

            <div class="restaurants-grid" style="display:grid;grid-template-columns:repeat(auto-fill, minmax(300px, 1fr));gap:24px;">
                <?php foreach ($restaurants as $rest): ?>
                <a href="order.php?category=<?= $foodCategoryId ?>&restaurant_id=<?= $rest['id'] ?>" class="glass-card restaurant-card" style="text-decoration:none;color:inherit;display:flex;flex-direction:column;border-radius:var(--radius);overflow:hidden;border:1px solid rgba(255,255,255,0.05);transition:var(--transition);height:100%;">
                    <div class="restaurant-image" style="height:170px;position:relative;overflow:hidden;">
                        <img src="<?= sanitize($rest['image']) ?>" alt="<?= sanitize($rest['name']) ?>" style="width:100%;height:100%;object-fit:cover;transition:transform 0.5s;" loading="lazy">
                        <div style="position:absolute;top:12px;right:12px;background:rgba(15,23,42,0.85);backdrop-filter:blur(5px);padding:4px 10px;border-radius:20px;font-size:0.8rem;font-weight:700;color:var(--brand-gold);display:flex;align-items:center;gap:4px;border:1px solid rgba(229,176,76,0.3);">
                            <i class="fas fa-star"></i> <?= number_format($rest['rating'], 1) ?>
                        </div>
                    </div>
                    <div class="restaurant-info" style="padding:20px;display:flex;flex-direction:column;flex:1;background:rgba(15,23,42,0.45);">
                        <span style="font-size:0.75rem;font-weight:700;text-transform:uppercase;color:var(--primary);margin-bottom:8px;letter-spacing:0.5px;"><?= sanitize($rest['specialty']) ?></span>
                        <h3 style="font-size:1.2rem;font-weight:700;margin-bottom:6px;color:var(--text-primary);"><?= sanitize($rest['name']) ?></h3>
                        <p style="font-size:0.85rem;color:var(--text-secondary);line-height:1.4;margin-bottom:14px;flex:1;"><?= sanitize($rest['description']) ?></p>
                        <div style="display:flex;justify-content:space-between;align-items:center;font-size:0.8rem;color:var(--text-muted);border-top:1px solid rgba(255,255,255,0.05);padding-top:12px;">
                            <span><i class="fas fa-clock"></i> <?= sanitize($rest['delivery_time']) ?></span>
                            <span style="color:var(--primary);font-weight:600;">Voir la carte <i class="fas fa-arrow-right" style="font-size:0.75rem;margin-left:4px;"></i></span>
                        </div>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>

        <?php else: ?>
            <!-- Show Products List (As before) -->
            <?php if ($currentRestaurant): ?>
                <!-- Restaurant Header Banner -->
                <div class="restaurant-banner glass-card" style="display:flex;align-items:center;gap:24px;padding:24px;margin-bottom:28px;border:1px solid rgba(255,179,25,0.15);background:linear-gradient(135deg, rgba(255,179,25,0.08) 0%, rgba(15,23,42,0.6) 100%);">
                    <div style="width:70px;height:70px;border-radius:50%;overflow:hidden;border:2px solid var(--brand-gold);flex-shrink:0;box-shadow:0 0 15px rgba(229,176,76,0.25);">
                        <img src="<?= sanitize($currentRestaurant['image']) ?>" alt="" style="width:100%;height:100%;object-fit:cover;">
                    </div>
                    <div style="flex:1;">
                        <a href="order.php?category=<?= $foodCategoryId ?>" style="color:var(--brand-gold);text-decoration:none;font-size:0.85rem;font-weight:600;display:inline-flex;align-items:center;gap:6px;margin-bottom:6px;"><i class="fas fa-chevron-left"></i> Retour aux restaurants</a>
                        <h2 style="font-size:1.6rem;font-weight:800;color:var(--text-primary);"><?= sanitize($currentRestaurant['name']) ?></h2>
                        <p style="color:var(--text-secondary);font-size:0.9rem;margin-top:4px;"><?= sanitize($currentRestaurant['description']) ?></p>
                        <div style="display:flex;gap:16px;margin-top:10px;font-size:0.8rem;color:var(--text-muted);">
                            <span><i class="fas fa-star" style="color:var(--brand-gold);"></i> <?= number_format($currentRestaurant['rating'], 1) ?></span>
                            <span><i class="fas fa-clock"></i> <?= sanitize($currentRestaurant['delivery_time']) ?></span>
                            <span><i class="fas fa-utensils"></i> <?= sanitize($currentRestaurant['specialty']) ?></span>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <div class="products-grid">
                <?php if (empty($products)): ?>
                    <div style="grid-column:1/-1;text-align:center;padding:48px;color:var(--text-muted);">
                        <i class="fas fa-box-open" style="font-size:3rem;margin-bottom:16px;display:block;"></i>
                        Aucun produit disponible dans cette section.
                    </div>
                <?php endif; ?>

                <?php foreach ($products as $product): ?>
                <div class="glass-card product-card">
                    <div class="product-image">
                        <img src="<?= sanitize($product['image_url']) ?>" alt="<?= sanitize($product['name']) ?>" loading="lazy">
                    </div>
                    <div class="product-info">
                        <span class="category-badge"><?= sanitize($product['category_name']) ?></span>
                        <h3><?= sanitize($product['name']) ?></h3>
                        <p><?= sanitize($product['description']) ?></p>
                        <?php if (!empty($product['ingredients'])): ?>
                        <div class="product-ingredients-hint">
                            <i class="fas fa-list-check"></i> <?= count($product['ingredients']) ?> ingrédient(s) au choix
                        </div>
                        <?php endif; ?>
                        <div class="product-footer">
                            <span class="product-price">
                                <?php if (!empty($product['sizes'])): ?>
                                    <span style="font-size:0.75rem;font-weight:500;color:var(--text-muted);display:block;margin-bottom:2px;">À partir de</span>
                                    <?= number_format($product['sizes'][0]['price'], 2) ?> DT
                                <?php else: ?>
                                    <?= number_format($product['price'], 2) ?> DT
                                <?php endif; ?>
                            </span>
                            <button class="btn btn-primary btn-sm" onclick="openProductModal(<?= (int)$product['id'] ?>)">
                                <i class="fas fa-plus"></i> Ajouter
                            </button>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>
</div>

<div class="cart-overlay" onclick="toggleCart()"></div>
<div class="cart-sidebar">
    <div class="cart-header">
        <h3><i class="fas fa-shopping-cart"></i> Mon Panier</h3>
        <button onclick="toggleCart()" style="background:none;border:none;color:var(--text-secondary);font-size:1.5rem;cursor:pointer;">&times;</button>
    </div>
    <div class="cart-body">
        <div class="cart-items"></div>
        <div class="cart-checkout-form">
            <div class="form-group">
                <label class="cart-address-label">
                    Adresse de livraison *
                    <button type="button" id="btn-refresh-location" class="btn-locate-sm" title="Actualiser ma position">
                        <i class="fas fa-crosshairs"></i>
                    </button>
                </label>
                <div id="address-status" class="address-status address-status--loading" hidden>
                    <i class="fas fa-spinner fa-spin"></i> Détection de votre position...
                </div>
                <textarea id="delivery-address" class="form-control" rows="2"
                          placeholder="Saisissez ou glissez le repère orange sur la carte..."
                          data-fallback="<?= sanitize($user['address'] ?? '') ?>"
                          data-prefill="<?= sanitize(trim($_GET['address'] ?? '')) ?>"></textarea>
                <div id="cart-map-container" style="margin-top:10px;border-radius:var(--radius-sm);overflow:hidden;border:1px solid var(--border);height:180px;">
                    <div id="cart-map" style="width:100%;height:100%;"></div>
                </div>
                <small style="color:var(--text-secondary);display:block;margin-top:6px;font-size:0.75rem;">
                    <i class="fas fa-info-circle"></i> Déplacez le marqueur orange sur la carte pour affiner votre position.
                </small>
            </div>
            <div class="form-group" style="margin-bottom:0;">
                <label>Notes (optionnel)</label>
                <input type="text" id="order-notes" class="form-control" placeholder="Instructions spéciales...">
            </div>
        </div>
    </div>
    <div class="cart-footer">
        <div class="cart-total">
            <span>Total:</span>
            <span class="cart-total-amount">0.00 DT</span>
        </div>
        <button class="btn btn-accent" style="width:100%;" onclick="placeOrder()">
            <i class="fas fa-check"></i> Confirmer la commande
        </button>
    </div>
</div>

<div class="product-modal-overlay" id="product-modal-overlay" onclick="closeProductModal()"></div>
<div class="product-modal" id="product-modal">
    <button class="product-modal-close" onclick="closeProductModal()">&times;</button>
    <div class="product-modal-image">
        <img id="modal-product-image" src="" alt="">
    </div>
    <div class="product-modal-body">
        <h3 id="modal-product-name"></h3>
        <p class="product-modal-price" id="modal-product-price"></p>
        
        <!-- Sizes section -->
        <div id="modal-sizes-section" hidden style="margin-bottom: 20px;">
            <h4 style="margin-bottom:8px;font-size:0.95rem;font-weight:600;"><i class="fas fa-crop-simple"></i> Taille de portion</h4>
            <div id="modal-sizes-list" class="modal-sizes-list" style="display:flex;flex-wrap:wrap;gap:8px;"></div>
        </div>

        <div id="modal-ingredients-section" hidden>
            <h4 style="margin-bottom:8px;font-size:0.95rem;font-weight:600;"><i class="fas fa-list-check"></i> Choisissez vos ingrédients</h4>
            <div id="modal-ingredients-list" class="modal-ingredients-list"></div>
        </div>
        <button class="btn btn-accent" style="width:100%;margin-top:20px;" onclick="confirmAddToCart()">
            <i class="fas fa-cart-plus"></i> Ajouter au panier
        </button>
    </div>
</div>

<script>
const PRODUCTS_CATALOG = <?= json_encode(array_map(fn($p) => [
    'id' => (int)$p['id'],
    'name' => $p['name'],
    'price' => (float)$p['price'],
    'image' => $p['image_url'],
    'ingredients' => array_map(fn($i) => [
        'id' => (int)$i['id'],
        'name' => $i['name'],
        'is_default' => (bool)$i['is_default'],
        'price' => (float)$i['price'],
    ], $p['ingredients']),
    'sizes' => array_map(fn($s) => [
        'id' => (int)$s['id'],
        'name' => $s['name'],
        'price' => (float)$s['price'],
    ], $p['sizes'] ?? []),
], $products), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS) ?>;
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
