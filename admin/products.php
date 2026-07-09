<?php
require_once __DIR__ . '/../includes/functions.php';
requireRole('admin');

$db = getDB();
ensureProductSchema($db);
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    try {
        if ($action === 'add') {
            $imageName = uploadProductImage($_FILES['image'] ?? []);

            $restaurantIdInput = !empty($_POST['restaurant_id']) ? (int)$_POST['restaurant_id'] : null;
            $stmt = $db->prepare('INSERT INTO products (category_id, restaurant_id, name, description, price, image, available) VALUES (?, ?, ?, ?, ?, ?, ?)');
            $stmt->execute([
                (int)$_POST['category_id'],
                $restaurantIdInput,
                trim($_POST['name']),
                trim($_POST['description']),
                (float)$_POST['price'],
                $imageName ?? 'default-food.jpg',
                isset($_POST['available']) ? 1 : 0
            ]);
            $productId = (int)$db->lastInsertId();

            $ingredientNames = $_POST['ingredient_names'] ?? [];
            $ingredientDefaults = $_POST['ingredient_default'] ?? [];
            $ingredientPrices = $_POST['ingredient_prices'] ?? [];
            $stmtIng = $db->prepare('INSERT INTO product_ingredients (product_id, name, is_default, price) VALUES (?, ?, ?, ?)');

            foreach ($ingredientNames as $i => $name) {
                $name = trim($name);
                if ($name === '') continue;
                $isDefault = isset($ingredientDefaults[$i]) && $ingredientDefaults[$i] === '1' ? 1 : 0;
                $price = (float)($ingredientPrices[$i] ?? 0.00);
                $stmtIng->execute([$productId, $name, $isDefault, $price]);
            }

            // Save Sizes
            $sizeNames = $_POST['size_names'] ?? [];
            $sizePrices = $_POST['size_prices'] ?? [];
            $stmtSize = $db->prepare('INSERT INTO product_sizes (product_id, name, price) VALUES (?, ?, ?)');

            foreach ($sizeNames as $s => $name) {
                $name = trim($name);
                if ($name === '') continue;
                $price = (float)($sizePrices[$s] ?? 0.00);
                $stmtSize->execute([$productId, $name, $price]);
            }

            $message = 'Produit ajouté avec succès.';
        } elseif ($action === 'delete') {
            $productId = (int)$_POST['product_id'];
            $stmt = $db->prepare('SELECT image FROM products WHERE id = ?');
            $stmt->execute([$productId]);
            $img = $stmt->fetchColumn();
            if ($img && $img !== 'default-food.jpg') {
                $path = __DIR__ . '/../public/assets/images/products/' . $img;
                if (file_exists($path)) unlink($path);
            }
            $stmt = $db->prepare('DELETE FROM products WHERE id = ?');
            $stmt->execute([$productId]);
            $message = 'Produit supprimé.';
        } elseif ($action === 'toggle') {
            $stmt = $db->prepare('UPDATE products SET available = NOT available WHERE id = ?');
            $stmt->execute([(int)$_POST['product_id']]);
            $message = 'Disponibilité mise à jour.';
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

$categories = $db->query('SELECT * FROM categories')->fetchAll();
$restaurants_list = $db->query('SELECT id, name FROM restaurants ORDER BY name')->fetchAll();
$products = $db->query('SELECT p.*, c.name as category_name, r.name as restaurant_name FROM products p JOIN categories c ON p.category_id = c.id LEFT JOIN restaurants r ON p.restaurant_id = r.id ORDER BY p.id DESC')->fetchAll();

foreach ($products as &$p) {
    $p['ingredients'] = getProductIngredients((int)$p['id']);
    $p['sizes'] = getProductSizes((int)$p['id']);
    $p['image_url'] = getProductImageUrl($p, BASE_PATH);
}
unset($p);

$pageTitle = 'Gestion Produits';
$basePath = BASE_PATH;
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
            <li><a href="dashboard.php"><i class="fas fa-chart-line"></i> Dashboard</a></li>
            <li><a href="orders.php"><i class="fas fa-list"></i> Commandes</a></li>
            <li><a href="products.php" class="active"><i class="fas fa-box"></i> Produits</a></li>
            <li><a href="restaurants.php"><i class="fas fa-store"></i> Restaurants</a></li>
            <li><a href="drivers.php"><i class="fas fa-motorcycle"></i> Livreurs</a></li>
            <li><a href="tracking.php"><i class="fas fa-map"></i> Suivi Live</a></li>
            <li><a href="../auth/logout.php"><i class="fas fa-sign-out-alt"></i> Déconnexion</a></li>
        </ul>
    </aside>

    <main class="main-content">
        <div class="page-header">
            <h1>Gestion des Produits</h1>
            <p>Ajoutez des produits avec photo et ingrédients personnalisables</p>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?= sanitize($message) ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?= sanitize($error) ?></div>
        <?php endif; ?>

        <div class="glass-card" style="padding:24px;margin-bottom:32px;">
            <h3 style="margin-bottom:20px;"><i class="fas fa-plus-circle"></i> Ajouter un produit</h3>
            <form method="POST" enctype="multipart/form-data" class="product-form">
                <input type="hidden" name="action" value="add">
                <div class="product-form-grid">
                    <div class="form-group">
                        <label>Nom *</label>
                        <input type="text" name="name" class="form-control" required placeholder="Ex: Burger Classic">
                    </div>
                    <div class="form-group">
                        <label>Catégorie *</label>
                        <select name="category_id" class="form-control" required>
                            <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['id'] ?>"><?= sanitize($cat['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Restaurant <small style="color:var(--text-muted);">(Optionnel)</small></label>
                        <select name="restaurant_id" class="form-control">
                            <option value="">-- Aucun (Produit Général) --</option>
                            <?php foreach ($restaurants_list as $r): ?>
                            <option value="<?= $r['id'] ?>"><?= sanitize($r['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Prix (DT) *</label>
                        <input type="number" name="price" class="form-control" step="0.01" min="0" required placeholder="45.00">
                    </div>
                    <div class="form-group">
                        <label>Photo du produit</label>
                        <input type="file" name="image" class="form-control" accept="image/jpeg,image/png,image/webp,image/gif" id="product-image-input">
                        <small style="color:var(--text-muted);">JPG, PNG, WEBP — max 5 Mo</small>
                        <div id="image-preview" class="image-preview" hidden>
                            <img src="" alt="Aperçu">
                        </div>
                    </div>
                    <div class="form-group form-group--full">
                        <label>Description</label>
                        <textarea name="description" class="form-control" rows="2" placeholder="Description du produit..."></textarea>
                    </div>
                    <div class="form-group form-group--full">
                        <label style="display:flex;align-items:center;gap:8px;">
                            <input type="checkbox" name="available" checked> Disponible à la vente
                        </label>
                    </div>
                </div>

                <div class="ingredients-section">
                    <div class="ingredients-section-header">
                        <h4><i class="fas fa-list-check"></i> Ingrédients & Suppléments</h4>
                        <p>Ajoutez des ingrédients. Cochez "Par défaut" s'il est inclus, et optionnellement un prix de supplément (ex: +1.50 DT).</p>
                    </div>
                    <div id="ingredients-list" class="ingredients-list" style="display:flex;flex-direction:column;gap:10px;margin-bottom:12px;">
                        <div class="ingredient-row" style="display:flex;gap:10px;align-items:center;">
                            <input type="text" name="ingredient_names[]" class="form-control" placeholder="Ex: Fromage, Bacon...">
                            <input type="number" name="ingredient_prices[]" class="form-control" step="0.01" min="0" value="0.00" placeholder="+0.00 DT" style="width:120px;">
                            <label class="ingredient-default-label" style="display:flex;align-items:center;gap:6px;font-size:0.85rem;white-space:nowrap;cursor:pointer;">
                                <input type="hidden" name="ingredient_default[]" value="1">
                                <input type="checkbox" checked onchange="this.previousElementSibling.value = this.checked ? '1' : '0'">
                                Par défaut
                            </label>
                            <button type="button" class="btn-remove-ingredient" onclick="removeIngredientRow(this)" title="Supprimer" style="background:none;border:none;color:#EF4444;font-size:1.5rem;cursor:pointer;line-height:1;">&times;</button>
                        </div>
                    </div>
                    <button type="button" class="btn btn-secondary btn-sm" onclick="addIngredientRow()">
                        <i class="fas fa-plus"></i> Ajouter un ingrédient
                    </button>
                </div>

                <!-- Sizes Section -->
                <div class="sizes-section" style="margin-top: 28px; border-top: 1px solid var(--border); padding-top: 24px;">
                    <div class="sizes-section-header" style="margin-bottom:16px;">
                        <h4><i class="fas fa-crop-simple"></i> Tailles & Prix Déclines</h4>
                        <p>Optionnel. Si le produit a plusieurs déclinaisons (ex: Moyenne = 15.00 DT, Grande = 20.00 DT), renseignez-les ici. Dans ce cas, le prix général ci-dessus sera ignoré.</p>
                    </div>
                    <div id="sizes-list" class="sizes-list" style="display:flex;flex-direction:column;gap:10px;margin-bottom:12px;">
                        <!-- Size rows dynamically added here -->
                    </div>
                    <button type="button" class="btn btn-secondary btn-sm" onclick="addSizeRow()">
                        <i class="fas fa-plus"></i> Ajouter une taille / portion
                    </button>
                </div>

                <button type="submit" class="btn btn-primary" style="margin-top:32px;">
                    <i class="fas fa-plus"></i> Ajouter le produit
                </button>
            </form>
        </div>

        <div class="glass-card" style="padding:24px;">
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Photo</th>
                            <th>Produit</th>
                            <th>Ingrédients & Prix Extra</th>
                            <th>Tailles / Déclinaisons</th>
                            <th>Catégorie</th>
                            <th>Prix de Base</th>
                            <th>Disponible</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($products as $p): ?>
                        <tr>
                            <td>
                                <img src="<?= sanitize($p['image_url']) ?>" alt="" class="product-thumb">
                            </td>
                            <td>
                                <strong><?= sanitize($p['name']) ?></strong>
                                <div style="font-size:0.8rem;color:var(--text-muted);"><?= sanitize($p['description']) ?></div>
                            </td>
                            <td>
                                <?php if (!empty($p['ingredients'])): ?>
                                    <div class="ingredient-tags" style="display:flex;flex-wrap:wrap;gap:4px;">
                                        <?php foreach ($p['ingredients'] as $ing): ?>
                                        <span class="ingredient-tag <?= $ing['is_default'] ? 'ingredient-tag--default' : '' ?>" style="font-size:0.75rem;padding:2px 8px;">
                                            <?= sanitize($ing['name']) ?>
                                            <?php if ((float)$ing['price'] > 0): ?>
                                                <span style="color:var(--brand-gold);font-weight:600;">(+<?= number_format($ing['price'], 2) ?> DT)</span>
                                            <?php endif; ?>
                                        </span>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else: ?>
                                    <span style="color:var(--text-muted);font-size:0.85rem;">—</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (!empty($p['sizes'])): ?>
                                    <div class="sizes-list-table" style="display:flex;flex-direction:column;gap:3px;font-size:0.85rem;">
                                        <?php foreach ($p['sizes'] as $sz): ?>
                                        <span>• <strong><?= sanitize($sz['name']) ?></strong> : <?= number_format($sz['price'], 2) ?> DT</span>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else: ?>
                                    <span style="color:var(--text-muted);font-size:0.85rem;">Unique</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?= sanitize($p['category_name']) ?>
                                <?php if (!empty($p['restaurant_name'])): ?>
                                    <div style="font-size:0.75rem;color:var(--brand-gold);margin-top:2px;"><i class="fas fa-store"></i> <?= sanitize($p['restaurant_name']) ?></div>
                                <?php endif; ?>
                            </td>
                            <td><?= number_format($p['price'], 2) ?> DT</td>
                            <td>
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="action" value="toggle">
                                    <input type="hidden" name="product_id" value="<?= $p['id'] ?>">
                                    <button type="submit" class="status-badge <?= $p['available'] ? 'status-delivered' : 'status-cancelled' ?>" style="border:none;cursor:pointer;">
                                        <?= $p['available'] ? 'Oui' : 'Non' ?>
                                    </button>
                                </form>
                            </td>
                            <td>
                                <form method="POST" style="display:inline;" onsubmit="return confirm('Supprimer ce produit?')">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="product_id" value="<?= $p['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-secondary" style="color:#EF4444;"><i class="fas fa-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>

<script>
function addIngredientRow() {
    const list = document.getElementById('ingredients-list');
    const row = document.createElement('div');
    row.className = 'ingredient-row';
    row.style.display = 'flex';
    row.style.gap = '10px';
    row.style.alignItems = 'center';
    row.innerHTML = `
        <input type="text" name="ingredient_names[]" class="form-control" placeholder="Ex: Fromage, Bacon...">
        <input type="number" name="ingredient_prices[]" class="form-control" step="0.01" min="0" value="0.00" placeholder="+0.00 DT" style="width:120px;">
        <label class="ingredient-default-label" style="display:flex;align-items:center;gap:6px;font-size:0.85rem;white-space:nowrap;cursor:pointer;">
            <input type="hidden" name="ingredient_default[]" value="1">
            <input type="checkbox" checked onchange="this.previousElementSibling.value = this.checked ? '1' : '0'">
            Par défaut
        </label>
        <button type="button" class="btn-remove-ingredient" onclick="removeIngredientRow(this)" title="Supprimer" style="background:none;border:none;color:#EF4444;font-size:1.5rem;cursor:pointer;line-height:1;">&times;</button>
    `;
    list.appendChild(row);
}

function removeIngredientRow(btn) {
    const list = document.getElementById('ingredients-list');
    if (list.children.length > 1) {
        btn.closest('.ingredient-row').remove();
    } else {
        // Just clear value if it's the only one left
        const inputs = btn.closest('.ingredient-row').querySelectorAll('input[type="text"], input[type="number"]');
        inputs.forEach(i => i.value = i.type === 'number' ? '0.00' : '');
    }
}

function addSizeRow() {
    const list = document.getElementById('sizes-list');
    const row = document.createElement('div');
    row.className = 'size-row';
    row.style.display = 'flex';
    row.style.gap = '10px';
    row.style.alignItems = 'center';
    row.innerHTML = `
        <input type="text" name="size_names[]" class="form-control" placeholder="Ex: Moyenne, Grande..." required>
        <input type="number" name="size_prices[]" class="form-control" step="0.01" min="0" placeholder="Prix (DT) (ex: 20.00)" required style="width:180px;">
        <button type="button" class="btn-remove-size" onclick="removeSizeRow(this)" title="Supprimer" style="background:none;border:none;color:#EF4444;font-size:1.5rem;cursor:pointer;line-height:1;">&times;</button>
    `;
    list.appendChild(row);
}

function removeSizeRow(btn) {
    btn.closest('.size-row').remove();
}

document.getElementById('product-image-input')?.addEventListener('change', function() {
    const preview = document.getElementById('image-preview');
    const img = preview?.querySelector('img');
    if (!this.files?.[0] || !preview || !img) return;
    img.src = URL.createObjectURL(this.files[0]);
    preview.hidden = false;
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
