<?php
require_once __DIR__ . '/../includes/functions.php';
requireRole('client');

$user = currentUser();
$db = getDB();

$stmt = $db->prepare('
    SELECT o.*, u.name as driver_name
    FROM orders o
    LEFT JOIN users u ON o.driver_id = u.id
    WHERE o.client_id = ?
    ORDER BY o.created_at DESC
');
$stmt->execute([$user['id']]);
$orders = $stmt->fetchAll();

$pageTitle = 'Mes Commandes';
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
            <li><a href="order.php"><i class="fas fa-shopping-bag"></i> Commander</a></li>
            <li><a href="dashboard.php"><i class="fas fa-home"></i> Tableau de bord</a></li>
            <li><a href="orders.php" class="active"><i class="fas fa-list"></i> Mes commandes</a></li>
            <li><a href="profile.php"><i class="fas fa-user"></i> Mon profil</a></li>
            <li><a href="../auth/logout.php"><i class="fas fa-sign-out-alt"></i> Déconnexion</a></li>
        </ul>
    </aside>

    <main class="main-content">
        <div class="page-header">
            <h1>Mes Commandes</h1>
            <p>Historique de toutes vos commandes</p>
        </div>

        <div class="glass-card" style="padding:24px;">
            <?php if (empty($orders)): ?>
                <p style="text-align:center;color:var(--text-muted);padding:40px 0;">Aucune commande</p>
                <div style="text-align:center;"><a href="order.php" class="btn btn-primary">Passer une commande</a></div>
            <?php else: ?>
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>N° Commande</th>
                            <th>Date</th>
                            <th>Adresse</th>
                            <th>Montant</th>
                            <th>Statut</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders as $order): ?>
                        <tr>
                            <td><strong><?= sanitize($order['order_number']) ?></strong></td>
                            <td><?= date('d/m/Y H:i', strtotime($order['created_at'])) ?></td>
                            <td style="max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?= sanitize($order['delivery_address']) ?></td>
                            <td><?= number_format($order['total_amount'], 2) ?> DT</td>
                            <td><span class="status-badge <?= getOrderStatusClass($order['status']) ?>"><?= getOrderStatusLabel($order['status']) ?></span></td>
                            <td>
                                <?php if (!in_array($order['status'], ['delivered', 'cancelled'])): ?>
                                <a href="track.php?id=<?= $order['id'] ?>" class="btn btn-sm btn-primary"><i class="fas fa-map-marker-alt"></i> Suivre</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </main>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
