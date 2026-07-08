<?php
require_once __DIR__ . '/../includes/functions.php';
requireRole('admin');

$db = getDB();

$stats = [
    'orders' => $db->query('SELECT COUNT(*) FROM orders')->fetchColumn(),
    'pending' => $db->query("SELECT COUNT(*) FROM orders WHERE status = 'pending'")->fetchColumn(),
    'in_transit' => $db->query("SELECT COUNT(*) FROM orders WHERE status IN ('picked_up', 'in_transit')")->fetchColumn(),
    'revenue' => $db->query("SELECT COALESCE(SUM(total_amount), 0) FROM orders WHERE status = 'delivered'")->fetchColumn(),
    'clients' => $db->query("SELECT COUNT(*) FROM users WHERE role = 'client'")->fetchColumn(),
    'drivers' => $db->query("SELECT COUNT(*) FROM users WHERE role = 'driver'")->fetchColumn(),
];

$recentOrders = $db->query('
    SELECT o.*, u.name as client_name
    FROM orders o
    JOIN users u ON o.client_id = u.id
    ORDER BY o.created_at DESC LIMIT 10
')->fetchAll();

$user = currentUser();
$pageTitle = 'Admin Dashboard';
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
            <li><a href="dashboard.php" class="active"><i class="fas fa-chart-line"></i> Dashboard</a></li>
            <li><a href="orders.php"><i class="fas fa-list"></i> Commandes</a></li>
            <li><a href="products.php"><i class="fas fa-box"></i> Produits</a></li>
            <li><a href="restaurants.php"><i class="fas fa-store"></i> Restaurants</a></li>
            <li><a href="drivers.php"><i class="fas fa-motorcycle"></i> Livreurs</a></li>
            <li><a href="tracking.php"><i class="fas fa-map"></i> Suivi Live</a></li>
            <li><a href="../auth/logout.php"><i class="fas fa-sign-out-alt"></i> Déconnexion</a></li>
        </ul>
    </aside>

    <main class="main-content">
        <div class="page-header">
            <h1>Tableau de Bord Admin</h1>
            <p>Vue d'ensemble de Arsy Delivery</p>
        </div>

        <div class="stats-grid">
            <div class="glass-card stat-card">
                <div class="stat-icon orange"><i class="fas fa-shopping-bag"></i></div>
                <div class="stat-info"><h3><?= $stats['orders'] ?></h3><p>Total commandes</p></div>
            </div>
            <div class="glass-card stat-card">
                <div class="stat-icon blue"><i class="fas fa-clock"></i></div>
                <div class="stat-info"><h3><?= $stats['pending'] ?></h3><p>En attente</p></div>
            </div>
            <div class="glass-card stat-card">
                <div class="stat-icon purple"><i class="fas fa-truck"></i></div>
                <div class="stat-info"><h3><?= $stats['in_transit'] ?></h3><p>En livraison</p></div>
            </div>
            <div class="glass-card stat-card">
                <div class="stat-icon green"><i class="fas fa-coins"></i></div>
                <div class="stat-info"><h3><?= number_format($stats['revenue'], 0) ?> DT</h3><p>Revenus</p></div>
            </div>
        </div>

        <div class="glass-card" style="padding:24px;">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;">
                <h2>Commandes récentes</h2>
                <a href="orders.php" class="btn btn-secondary btn-sm">Voir tout</a>
            </div>
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>N° Commande</th>
                            <th>Client</th>
                            <th>Date</th>
                            <th>Montant</th>
                            <th>Statut</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentOrders as $order): ?>
                        <tr>
                            <td><strong><?= sanitize($order['order_number']) ?></strong></td>
                            <td><?= sanitize($order['client_name']) ?></td>
                            <td><?= date('d/m/Y H:i', strtotime($order['created_at'])) ?></td>
                            <td><?= number_format($order['total_amount'], 2) ?> DT</td>
                            <td><span class="status-badge <?= getOrderStatusClass($order['status']) ?>"><?= getOrderStatusLabel($order['status']) ?></span></td>
                            <td><a href="orders.php?id=<?= $order['id'] ?>" class="btn btn-sm btn-secondary">Gérer</a></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
