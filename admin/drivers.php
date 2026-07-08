<?php
require_once __DIR__ . '/../includes/functions.php';
requireRole('admin');

$db = getDB();
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $password = $_POST['password'] ?? 'driver123';

        if ($name && $email) {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            try {
                $stmt = $db->prepare('INSERT INTO users (name, email, phone, password, role) VALUES (?, ?, ?, ?, "driver")');
                $stmt->execute([$name, $email, $phone, $hash]);
                $message = 'Livreur ajouté.';
            } catch (PDOException $e) {
                $message = 'Erreur: email déjà utilisé.';
            }
        }
    }
}

$drivers = $db->query("
    SELECT u.*, 
           (SELECT COUNT(*) FROM orders WHERE driver_id = u.id AND status = 'delivered') as deliveries,
           (SELECT COUNT(*) FROM orders WHERE driver_id = u.id AND status IN ('picked_up', 'in_transit')) as active
    FROM users u WHERE u.role = 'driver'
")->fetchAll();

$pageTitle = 'Gestion Livreurs';
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
            <li><a href="restaurants.php"><i class="fas fa-store"></i> Restaurants</a></li>
            <li><a href="drivers.php" class="active"><i class="fas fa-motorcycle"></i> Livreurs</a></li>
            <li><a href="tracking.php"><i class="fas fa-map"></i> Suivi Live</a></li>
            <li><a href="../auth/logout.php"><i class="fas fa-sign-out-alt"></i> Déconnexion</a></li>
        </ul>
    </aside>

    <main class="main-content">
        <div class="page-header">
            <h1>Gestion des Livreurs</h1>
            <p>Gérez votre équipe de livraison</p>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?= sanitize($message) ?></div>
        <?php endif; ?>

        <div class="glass-card" style="padding:24px;margin-bottom:32px;">
            <h3 style="margin-bottom:20px;">Ajouter un livreur</h3>
            <form method="POST" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:16px;align-items:end;">
                <input type="hidden" name="action" value="add">
                <div class="form-group" style="margin:0;">
                    <label>Nom</label>
                    <input type="text" name="name" class="form-control" required>
                </div>
                <div class="form-group" style="margin:0;">
                    <label>Email</label>
                    <input type="email" name="email" class="form-control" required>
                </div>
                <div class="form-group" style="margin:0;">
                    <label>Téléphone</label>
                    <input type="tel" name="phone" class="form-control">
                </div>
                <div class="form-group" style="margin:0;">
                    <label>Mot de passe</label>
                    <input type="password" name="password" class="form-control" value="driver123">
                </div>
                <button type="submit" class="btn btn-primary"><i class="fas fa-plus"></i> Ajouter</button>
            </form>
        </div>

        <div class="stats-grid" style="margin-bottom:32px;">
            <?php foreach ($drivers as $driver): ?>
            <div class="glass-card stat-card">
                <div class="stat-icon orange"><i class="fas fa-motorcycle"></i></div>
                <div class="stat-info">
                    <h3><?= sanitize($driver['name']) ?></h3>
                    <p><?= sanitize($driver['phone'] ?? $driver['email']) ?></p>
                    <div style="margin-top:8px;font-size:0.875rem;">
                        <span style="color:var(--accent);"><?= $driver['deliveries'] ?> livraisons</span>
                        <?php if ($driver['active'] > 0): ?>
                        <span class="live-indicator" style="margin-left:8px;"><?= $driver['active'] ?> active(s)</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </main>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
