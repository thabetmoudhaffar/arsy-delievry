<?php
require_once __DIR__ . '/../includes/functions.php';
requireRole('admin');

$db = getDB();
ensureRestaurantSchema();

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    try {
        if ($action === 'add') {
            $name = trim($_POST['name'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $specialty = trim($_POST['specialty'] ?? '');
            $image = trim($_POST['image'] ?? '');
            $rating = (float)($_POST['rating'] ?? 4.5);
            $delivery_time = trim($_POST['delivery_time'] ?? '20-30 min');

            if (empty($name)) {
                throw new Exception('Le nom du restaurant est obligatoire.');
            }

            if (empty($image)) {
                $image = 'https://images.unsplash.com/photo-1555396273-367ea4eb4db5?w=600&h=400&fit=crop&q=80'; // default restaurant placeholder
            }

            $stmt = $db->prepare('INSERT INTO restaurants (name, description, specialty, image, rating, delivery_time) VALUES (?, ?, ?, ?, ?, ?)');
            $stmt->execute([$name, $description, $specialty, $image, $rating, $delivery_time]);
            $message = 'Restaurant ajouté avec succès.';
        } elseif ($action === 'delete') {
            $restaurantId = (int)$_POST['restaurant_id'];
            $stmt = $db->prepare('DELETE FROM restaurants WHERE id = ?');
            $stmt->execute([$restaurantId]);
            $message = 'Restaurant supprimé.';
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

$restaurants = $db->query('SELECT * FROM restaurants ORDER BY id DESC')->fetchAll();

$pageTitle = 'Gestion Restaurants';
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
            <li><a href="products.php"><i class="fas fa-box"></i> Produits</a></li>
            <li><a href="restaurants.php" class="active"><i class="fas fa-store"></i> Restaurants</a></li>
            <li><a href="drivers.php"><i class="fas fa-motorcycle"></i> Livreurs</a></li>
            <li><a href="tracking.php"><i class="fas fa-map"></i> Suivi Live</a></li>
            <li><a href="../auth/logout.php"><i class="fas fa-sign-out-alt"></i> Déconnexion</a></li>
        </ul>
    </aside>

    <main class="main-content">
        <div class="page-header">
            <h1>Gestion des Restaurants</h1>
            <p>Ajoutez et gérez les restaurants partenaires et leurs caractéristiques</p>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?= sanitize($message) ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?= sanitize($error) ?></div>
        <?php endif; ?>

        <!-- Form card -->
        <div class="glass-card" style="padding:24px;margin-bottom:32px;">
            <h3 style="margin-bottom:20px;"><i class="fas fa-plus-circle"></i> Ajouter un restaurant</h3>
            <form method="POST" class="product-form">
                <input type="hidden" name="action" value="add">
                <div class="product-form-grid" style="display:grid;grid-template-columns:repeat(auto-fit, minmax(220px, 1fr));gap:20px;">
                    <div class="form-group">
                        <label>Nom du restaurant *</label>
                        <input type="text" name="name" class="form-control" required placeholder="Ex: Pizzeria Da Piero">
                    </div>
                    <div class="form-group">
                        <label>Spécialité / Type de cuisine</label>
                        <input type="text" name="specialty" class="form-control" placeholder="Ex: Italien, Burgers, Sushi">
                    </div>
                    <div class="form-group">
                        <label>Temps de livraison estimé</label>
                        <input type="text" name="delivery_time" class="form-control" value="20-30 min" placeholder="Ex: 25-40 min">
                    </div>
                    <div class="form-group">
                        <label>Note Google / Interne (sur 5)</label>
                        <input type="number" name="rating" class="form-control" step="0.1" min="1" max="5" value="4.5" placeholder="4.5">
                    </div>
                    <div class="form-group form-group--full" style="grid-column: 1 / -1;">
                        <label>Lien URL de l'image de couverture</label>
                        <input type="url" name="image" class="form-control" placeholder="https://images.unsplash.com/... ou laissez vide pour une image par défaut">
                    </div>
                    <div class="form-group form-group--full" style="grid-column: 1 / -1;">
                        <label>Description du restaurant</label>
                        <textarea name="description" class="form-control" rows="2" placeholder="Description courte des spécialités..."></textarea>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary" style="margin-top:20px;">
                    <i class="fas fa-plus"></i> Ajouter le restaurant
                </button>
            </form>
        </div>

        <!-- Table card -->
        <div class="glass-card" style="padding:24px;">
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Photo</th>
                            <th>Restaurant</th>
                            <th>Spécialité</th>
                            <th>Temps estimé</th>
                            <th>Note</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($restaurants as $rest): ?>
                        <tr>
                            <td>
                                <img src="<?= sanitize($rest['image']) ?>" alt="" class="product-thumb" style="width:60px;height:45px;object-fit:cover;border-radius:6px;">
                            </td>
                            <td>
                                <strong><?= sanitize($rest['name']) ?></strong>
                                <div style="font-size:0.8rem;color:var(--text-muted);"><?= sanitize($rest['description']) ?></div>
                            </td>
                            <td><span class="ingredient-tag ingredient-tag--default"><?= sanitize($rest['specialty'] ?: 'Général') ?></span></td>
                            <td><i class="fas fa-clock" style="font-size:0.8rem;margin-right:4px;"></i> <?= sanitize($rest['delivery_time']) ?></td>
                            <td><i class="fas fa-star" style="color:var(--brand-gold);"></i> <strong><?= number_format($rest['rating'], 1) ?></strong></td>
                            <td>
                                <form method="POST" style="display:inline;" onsubmit="return confirm('Supprimer ce restaurant? Cela déliera tous ses produits.')">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="restaurant_id" value="<?= $rest['id'] ?>">
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

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
