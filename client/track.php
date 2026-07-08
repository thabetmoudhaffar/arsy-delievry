<?php
require_once __DIR__ . '/../includes/functions.php';
requireRole('client');

$orderId = (int)($_GET['id'] ?? 0);
if (!$orderId) {
    header('Location: order.php');
    exit;
}

$user = currentUser();
$db = getDB();

$stmt = $db->prepare('
    SELECT o.*, u.name as driver_name, u.phone as driver_phone
    FROM orders o
    LEFT JOIN users u ON o.driver_id = u.id
    WHERE o.id = ? AND o.client_id = ?
');
$stmt->execute([$orderId, $user['id']]);
$order = $stmt->fetch();

if (!$order) {
    header('Location: order.php');
    exit;
}

$stmt = $db->prepare('SELECT oi.*, p.name FROM order_items oi JOIN products p ON oi.product_id = p.id WHERE oi.order_id = ?');
$stmt->execute([$orderId]);
$items = $stmt->fetchAll();

$statusSteps = ['pending', 'confirmed', 'preparing', 'picked_up', 'in_transit', 'delivered'];
$currentStep = array_search($order['status'], $statusSteps);
if ($currentStep === false) $currentStep = -1;

$stepLabels = [
    'pending' => 'Commande reçue',
    'confirmed' => 'Commande confirmée',
    'preparing' => 'En préparation',
    'picked_up' => 'Récupérée par le livreur',
    'in_transit' => 'En route vers vous',
    'delivered' => 'Livrée',
];

$pageTitle = 'Suivi Commande';
$basePath = BASE_PATH;
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
            <li><a href="order.php"><i class="fas fa-shopping-bag"></i> Commander</a></li>
            <li><a href="dashboard.php"><i class="fas fa-home"></i> Tableau de bord</a></li>
            <li><a href="orders.php"><i class="fas fa-list"></i> Mes commandes</a></li>
            <li><a href="profile.php"><i class="fas fa-user"></i> Mon profil</a></li>
            <li><a href="../auth/logout.php"><i class="fas fa-sign-out-alt"></i> Déconnexion</a></li>
        </ul>
    </aside>

    <main class="main-content">
        <div class="page-header">
            <h1>Suivi de commande</h1>
            <p>Commande <strong><?= sanitize($order['order_number']) ?></strong>
                <span class="status-badge <?= getOrderStatusClass($order['status']) ?>" style="margin-left:12px;">
                    <?= getOrderStatusLabel($order['status']) ?>
                </span>
            </p>
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:24px;">
            <div>
                <?php if (!in_array($order['status'], ['delivered', 'cancelled', 'pending'])): ?>
                <div class="glass-card" style="padding:24px;margin-bottom:24px;">
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
                        <h3><i class="fas fa-map-marker-alt"></i> Localisation en temps réel</h3>
                        <span class="live-indicator">LIVE</span>
                    </div>
                    <div id="tracking-map" class="map-container large"></div>
                    <?php if ($order['driver_name']): ?>
                    <div style="margin-top:16px;padding:16px;background:rgba(255,107,53,0.1);border-radius:8px;">
                        <strong><i class="fas fa-motorcycle"></i> <?= sanitize($order['driver_name']) ?></strong>
                        <?php if ($order['driver_phone']): ?>
                        <span style="margin-left:12px;color:var(--text-secondary);"><?= sanitize($order['driver_phone']) ?></span>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

                <div class="glass-card" style="padding:24px;">
                    <h3 style="margin-bottom:20px;">Progression</h3>
                    <div class="tracking-timeline">
                        <?php foreach ($statusSteps as $i => $step): ?>
                        <?php if ($order['status'] === 'cancelled' && $step !== 'pending') continue; ?>
                        <div class="timeline-step <?= $i < $currentStep ? 'completed' : ($i === $currentStep ? ($order['status'] === 'delivered' ? 'completed' : 'active') : '') ?>">
                            <div class="timeline-icon">
                                <i class="fas fa-<?= ['clock', 'check', 'fire', 'box', 'truck', 'house'][$i] ?>"></i>
                            </div>
                            <div class="timeline-content">
                                <h4><?= $stepLabels[$step] ?></h4>
                                <?php if ($i === $currentStep && $order['status'] === 'delivered'): ?>
                                <p>Terminé</p>
                                <?php elseif ($i === $currentStep): ?>
                                <p>En cours...</p>
                                <?php elseif ($i < $currentStep): ?>
                                <p>Terminé</p>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <div>
                <div class="glass-card" style="padding:24px;margin-bottom:24px;">
                    <h3 style="margin-bottom:16px;">Détails de la commande</h3>
                    <?php foreach ($items as $item): ?>
                    <div style="display:flex;justify-content:space-between;padding:12px 0;border-bottom:1px solid var(--border);">
                        <span>
                            <?= sanitize($item['name']) ?> x<?= $item['quantity'] ?>
                            <?php $custom = formatOrderCustomization($item['customization'] ?? null); if ($custom): ?>
                            <div style="font-size:0.8rem;color:var(--text-muted);margin-top:4px;"><i class="fas fa-list-check"></i> <?= sanitize($custom) ?></div>
                            <?php endif; ?>
                        </span>
                        <span><?= number_format($item['unit_price'] * $item['quantity'], 2) ?> DT</span>
                    </div>
                    <?php endforeach; ?>
                    <div style="display:flex;justify-content:space-between;padding-top:16px;font-weight:700;font-size:1.125rem;">
                        <span>Total</span>
                        <span style="color:var(--accent);"><?= number_format($order['total_amount'], 2) ?> DT</span>
                    </div>
                </div>

                <div class="glass-card" style="padding:24px;">
                    <h3 style="margin-bottom:16px;">Adresse de livraison</h3>
                    <p style="color:var(--text-secondary);"><i class="fas fa-map-marker-alt" style="color:var(--primary);"></i> <?= sanitize($order['delivery_address']) ?></p>
                    <?php if ($order['notes']): ?>
                    <p style="margin-top:12px;color:var(--text-muted);font-size:0.875rem;"><i class="fas fa-sticky-note"></i> <?= sanitize($order['notes']) ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>
</div>

<?php if (!in_array($order['status'], ['delivered', 'cancelled', 'pending'])): ?>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const tracker = new DeliveryTracker(<?= $orderId ?>, 'tracking-map');
    tracker.init();
});
</script>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
