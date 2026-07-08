<?php
require_once __DIR__ . '/../includes/functions.php';
requireRole('admin');

$db = getDB();
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $orderId = (int)($_POST['order_id'] ?? 0);
    $action = $_POST['action'] ?? '';

    if ($action === 'update_status') {
        $status = $_POST['status'] ?? '';
        $driverId = (int)($_POST['driver_id'] ?? 0) ?: null;
        $validStatuses = ['pending', 'confirmed', 'preparing', 'picked_up', 'in_transit', 'delivered', 'cancelled'];
        if (in_array($status, $validStatuses)) {
            $stmt = $db->prepare('UPDATE orders SET status = ?, driver_id = ? WHERE id = ?');
            $stmt->execute([$status, $driverId, $orderId]);
            $message = 'Commande mise à jour.';
        }
    }
}

$orderId = (int)($_GET['id'] ?? 0);
$drivers = $db->query("SELECT id, name FROM users WHERE role = 'driver'")->fetchAll();

if ($orderId) {
    $stmt = $db->prepare('
        SELECT o.*, c.name as client_name, c.phone as client_phone, d.name as driver_name
        FROM orders o
        JOIN users c ON o.client_id = c.id
        LEFT JOIN users d ON o.driver_id = d.id
        WHERE o.id = ?
    ');
    $stmt->execute([$orderId]);
    $order = $stmt->fetch();

    $stmt = $db->prepare('SELECT oi.*, p.name FROM order_items oi JOIN products p ON oi.product_id = p.id WHERE oi.order_id = ?');
    $stmt->execute([$orderId]);
    $items = $stmt->fetchAll();
}

$orders = $db->query('
    SELECT o.*, c.name as client_name, d.name as driver_name
    FROM orders o
    JOIN users c ON o.client_id = c.id
    LEFT JOIN users d ON o.driver_id = d.id
    ORDER BY o.created_at DESC
')->fetchAll();

$pageTitle = 'Gestion Commandes';
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
            <li><a href="orders.php" class="active"><i class="fas fa-list"></i> Commandes</a></li>
            <li><a href="products.php"><i class="fas fa-box"></i> Produits</a></li>
            <li><a href="restaurants.php"><i class="fas fa-store"></i> Restaurants</a></li>
            <li><a href="drivers.php"><i class="fas fa-motorcycle"></i> Livreurs</a></li>
            <li><a href="tracking.php"><i class="fas fa-map"></i> Suivi Live</a></li>
            <li><a href="../auth/logout.php"><i class="fas fa-sign-out-alt"></i> Déconnexion</a></li>
        </ul>
    </aside>

    <main class="main-content">
        <div class="page-header">
            <h1>Gestion des Commandes</h1>
            <p>Gérez et suivez toutes les commandes</p>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?= sanitize($message) ?></div>
        <?php endif; ?>

        <?php if ($orderId && $order): ?>
        <div class="glass-card" style="padding:24px;margin-bottom:32px;">
            <div style="display:flex;justify-content:space-between;align-items:start;margin-bottom:24px;">
                <div>
                    <h2><?= sanitize($order['order_number']) ?></h2>
                    <p style="color:var(--text-secondary);">Client: <?= sanitize($order['client_name']) ?> — <?= sanitize($order['client_phone'] ?? '') ?></p>
                </div>
                <span class="status-badge <?= getOrderStatusClass($order['status']) ?>"><?= getOrderStatusLabel($order['status']) ?></span>
            </div>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:24px;">
                <div>
                    <h4 style="margin-bottom:12px;">Articles</h4>
                    <?php foreach ($items as $item): ?>
                    <div style="display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid var(--border);">
                        <span>
                            <?= sanitize($item['name']) ?> x<?= $item['quantity'] ?>
                            <?php $custom = formatOrderCustomization($item['customization'] ?? null); if ($custom): ?>
                            <div style="font-size:0.8rem;color:var(--text-muted);margin-top:4px;"><i class="fas fa-list-check"></i> <?= sanitize($custom) ?></div>
                            <?php endif; ?>
                        </span>
                        <span><?= number_format($item['unit_price'] * $item['quantity'], 2) ?> DT</span>
                    </div>
                    <?php endforeach; ?>
                    <div style="font-weight:700;margin-top:12px;">Total: <?= number_format($order['total_amount'], 2) ?> DT</div>
                </div>
                <div>
                    <h4 style="margin-bottom:12px;">Livraison</h4>
                    <p style="color:var(--text-secondary);"><i class="fas fa-map-marker-alt"></i> <?= sanitize($order['delivery_address']) ?></p>
                    <?php if ($order['notes']): ?>
                    <p style="margin-top:8px;color:var(--text-muted);"><?= sanitize($order['notes']) ?></p>
                    <?php endif; ?>
                </div>
            </div>

            <form method="POST" style="margin-top:24px;padding-top:24px;border-top:1px solid var(--border);display:flex;gap:16px;align-items:end;flex-wrap:wrap;">
                <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                <input type="hidden" name="action" value="update_status">
                <div class="form-group" style="margin:0;">
                    <label>Statut</label>
                    <select name="status" class="form-control">
                        <?php foreach (['pending','confirmed','preparing','picked_up','in_transit','delivered','cancelled'] as $s): ?>
                        <option value="<?= $s ?>" <?= $order['status'] === $s ? 'selected' : '' ?>><?= getOrderStatusLabel($s) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group" style="margin:0;">
                    <label>Livreur</label>
                    <select name="driver_id" class="form-control">
                        <option value="">— Aucun —</option>
                        <?php foreach ($drivers as $d): ?>
                        <option value="<?= $d['id'] ?>" <?= $order['driver_id'] == $d['id'] ? 'selected' : '' ?>><?= sanitize($d['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Mettre à jour</button>
                <a href="orders.php" class="btn btn-secondary">Retour</a>
            </form>
        </div>
        <?php endif; ?>

        <div class="glass-card" style="padding:24px;">
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>N° Commande</th>
                            <th>Client</th>
                            <th>Livreur</th>
                            <th>Date</th>
                            <th>Montant</th>
                            <th>Statut</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders as $o): ?>
                        <tr>
                            <td><strong><?= sanitize($o['order_number']) ?></strong></td>
                            <td><?= sanitize($o['client_name']) ?></td>
                            <td><?= sanitize($o['driver_name'] ?? '—') ?></td>
                            <td><?= date('d/m/Y H:i', strtotime($o['created_at'])) ?></td>
                            <td><?= number_format($o['total_amount'], 2) ?> DT</td>
                            <td><span class="status-badge <?= getOrderStatusClass($o['status']) ?>"><?= getOrderStatusLabel($o['status']) ?></span></td>
                            <td><a href="orders.php?id=<?= $o['id'] ?>" class="btn btn-sm btn-primary">Gérer</a></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
