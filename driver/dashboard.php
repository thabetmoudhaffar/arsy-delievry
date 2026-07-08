<?php
require_once __DIR__ . '/../includes/functions.php';
requireRole('driver');

$user = currentUser();
$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $orderId = (int)($_POST['order_id'] ?? 0);
    $action = $_POST['action'] ?? '';

    if ($action === 'take_order' && $orderId) {
        $stmt = $db->prepare('UPDATE orders SET status = "picked_up", driver_id = ? WHERE id = ? AND driver_id IS NULL');
        $stmt->execute([$user['id'], $orderId]);
    } elseif ($action === 'update_status' && $orderId) {
        $status = $_POST['status'] ?? '';
        $validStatuses = ['picked_up', 'in_transit', 'delivered'];
        if (in_array($status, $validStatuses)) {
            $stmt = $db->prepare('UPDATE orders SET status = ?, driver_id = ? WHERE id = ?');
            $stmt->execute([$status, $user['id'], $orderId]);
        }
    }
}

$activeOrders = $db->prepare("
    SELECT o.*, c.name as client_name, c.phone as client_phone
    FROM orders o
    JOIN users c ON o.client_id = c.id
    WHERE o.driver_id = ? AND o.status IN ('confirmed', 'preparing', 'picked_up', 'in_transit')
    ORDER BY o.created_at DESC
");
$activeOrders->execute([$user['id']]);
$orders = $activeOrders->fetchAll();

$availableOrders = $db->query("
    SELECT o.*, c.name as client_name
    FROM orders o
    JOIN users c ON o.client_id = c.id
    WHERE o.driver_id IS NULL AND o.status IN ('confirmed', 'preparing')
    ORDER BY o.created_at DESC
")->fetchAll();

// Fetch order items with ingredients for all orders the driver needs to see
$allOrderIds = array_merge(
    array_column($orders, 'id'),
    array_column($availableOrders, 'id')
);

$orderItemsMap = [];
if (!empty($allOrderIds)) {
    $placeholders = implode(',', array_fill(0, count($allOrderIds), '?'));
    $stmtItems = $db->prepare("
        SELECT oi.*, p.name, p.image, p.category_id, c.name as category_name
        FROM order_items oi
        JOIN products p ON oi.product_id = p.id
        JOIN categories c ON p.category_id = c.id
        WHERE oi.order_id IN ($placeholders)
        ORDER BY oi.id
    ");
    $stmtItems->execute($allOrderIds);
    $allItems = $stmtItems->fetchAll();
    foreach ($allItems as $item) {
        $orderItemsMap[$item['order_id']][] = $item;
    }
}

$pageTitle = 'Espace Livreur';
$basePath = BASE_PATH;
$extraCSS = '<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">';
$extraJS = '<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="dashboard">
    <aside class="sidebar">
        <a href="../index.php" class="sidebar-logo">
            <div class="logo-icon" style="width:36px;height:36px;font-size:1rem;"><i class="fas fa-truck-fast"></i></div>
            Arsy Livreur
        </a>
        <ul class="sidebar-nav">
            <li><a href="dashboard.php" class="active"><i class="fas fa-motorcycle"></i> Mes livraisons</a></li>
            <li><a href="../auth/logout.php"><i class="fas fa-sign-out-alt"></i> Déconnexion</a></li>
        </ul>
    </aside>

    <main class="main-content">
        <div class="page-header">
            <h1>Bonjour, <?= sanitize($user['name']) ?> 🛵</h1>
            <p>Gérez vos livraisons et partagez votre position</p>
        </div>

        <?php if (!empty($orders)): ?>
        <div class="glass-card" style="padding:24px;margin-bottom:32px;">
            <h2 style="margin-bottom:20px;"><span class="live-indicator">ACTIF</span> Mes livraisons en cours</h2>
            <?php foreach ($orders as $order): ?>
            <div class="glass-card" style="padding:20px;margin-bottom:16px;" id="order-<?= $order['id'] ?>">
                <div style="display:flex;justify-content:space-between;align-items:start;margin-bottom:16px;">
                    <div>
                        <h3><?= sanitize($order['order_number']) ?></h3>
                        <p style="color:var(--text-secondary);"><i class="fas fa-user"></i> <?= sanitize($order['client_name']) ?> — <?= sanitize($order['client_phone'] ?? '') ?></p>
                        <p style="color:var(--text-secondary);margin-top:4px;"><i class="fas fa-map-marker-alt"></i> <?= sanitize($order['delivery_address']) ?></p>
                    </div>
                    <span class="status-badge <?= getOrderStatusClass($order['status']) ?>"><?= getOrderStatusLabel($order['status']) ?></span>
                </div>

                <?php $items = $orderItemsMap[$order['id']] ?? []; ?>
                <?php if (!empty($items)): ?>
                <div class="driver-order-items">
                    <h4 style="margin-bottom:10px;font-size:0.9rem;color:var(--text-muted);"><i class="fas fa-receipt"></i> Détail de la commande</h4>
                    <?php foreach ($items as $item): ?>
                    <div class="driver-order-item">
                        <div class="driver-order-item-info">
                            <strong><?= sanitize($item['name']) ?></strong>
                            <span class="driver-order-item-qty">x<?= (int)$item['quantity'] ?></span>
                            <span class="driver-order-item-price"><?= number_format($item['unit_price'] * $item['quantity'], 2) ?> DT</span>
                        </div>
                        <?php $custom = formatOrderCustomization($item['customization'] ?? null); if ($custom): ?>
                        <div class="driver-order-item-ingredients">
                            <i class="fas fa-list-check"></i> <?= sanitize($custom) ?>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                    <div class="driver-order-total">
                        <span>Total</span>
                        <strong><?= number_format($order['total_amount'], 2) ?> DT</strong>
                    </div>
                </div>
                <?php endif; ?>

                <?php if (in_array($order['status'], ['picked_up', 'in_transit'])): ?>
                <div id="map-<?= $order['id'] ?>" class="map-container" style="margin-bottom:16px;"></div>
                <div class="alert alert-info" id="gps-status-<?= $order['id'] ?>">
                    <i class="fas fa-satellite-dish"></i> Partage GPS en cours...
                </div>
                <?php endif; ?>

                <form method="POST" style="display:flex;gap:12px;flex-wrap:wrap;">
                    <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                    <input type="hidden" name="action" value="update_status">
                    <?php if ($order['status'] === 'confirmed' || $order['status'] === 'preparing'): ?>
                    <button type="submit" name="status" value="picked_up" class="btn btn-primary btn-sm">
                        <i class="fas fa-box"></i> Commande récupérée
                    </button>
                    <?php endif; ?>
                    <?php if ($order['status'] === 'picked_up'): ?>
                    <button type="submit" name="status" value="in_transit" class="btn btn-primary btn-sm">
                        <i class="fas fa-truck"></i> En route
                    </button>
                    <?php endif; ?>
                    <?php if (in_array($order['status'], ['picked_up', 'in_transit'])): ?>
                    <button type="submit" name="status" value="delivered" class="btn btn-accent btn-sm" onclick="stopTracking(<?= $order['id'] ?>)">
                        <i class="fas fa-check"></i> Livré
                    </button>
                    <?php endif; ?>
                </form>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <?php if (!empty($availableOrders)): ?>
        <div class="glass-card" style="padding:24px;">
            <h2 style="margin-bottom:20px;">Commandes disponibles</h2>
            <?php foreach ($availableOrders as $order): ?>
            <div class="driver-available-order">
                <div style="flex:1;">
                    <strong><?= sanitize($order['order_number']) ?></strong>
                    <p style="color:var(--text-secondary);font-size:0.875rem;"><i class="fas fa-map-marker-alt"></i> <?= sanitize($order['delivery_address']) ?></p>
                    <?php $items = $orderItemsMap[$order['id']] ?? []; ?>
                    <?php if (!empty($items)): ?>
                    <div class="driver-order-items driver-order-items--compact">
                        <?php foreach ($items as $item): ?>
                        <div class="driver-order-item">
                            <div class="driver-order-item-info">
                                <strong><?= sanitize($item['name']) ?></strong>
                                <span class="driver-order-item-qty">x<?= (int)$item['quantity'] ?></span>
                            </div>
                            <?php $custom = formatOrderCustomization($item['customization'] ?? null); if ($custom): ?>
                            <div class="driver-order-item-ingredients">
                                <i class="fas fa-list-check"></i> <?= sanitize($custom) ?>
                            </div>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                    <p style="color:var(--accent);font-weight:600;margin-top:8px;"><?= number_format($order['total_amount'], 2) ?> DT</p>
                </div>
                <form method="POST">
                    <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                    <input type="hidden" name="action" value="take_order">
                    <button type="submit" class="btn btn-primary btn-sm">
                        <i class="fas fa-hand-pointer"></i> Prendre
                    </button>
                </form>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <?php if (empty($orders) && empty($availableOrders)): ?>
        <div class="glass-card" style="padding:60px;text-align:center;">
            <i class="fas fa-motorcycle" style="font-size:4rem;color:var(--text-muted);margin-bottom:20px;"></i>
            <h3>Aucune livraison pour le moment</h3>
            <p style="color:var(--text-secondary);">Les nouvelles commandes apparaîtront ici</p>
        </div>
        <?php endif; ?>
    </main>
</div>

<script>
const trackers = {};

document.addEventListener('DOMContentLoaded', () => {
    <?php foreach ($orders as $order): ?>
    <?php if (in_array($order['status'], ['picked_up', 'in_transit'])): ?>
    (function() {
        const orderId = <?= $order['id'] ?>;
        const mapEl = 'map-' + orderId;
        const clientLat = <?= $order['delivery_lat'] ?: 36.8065 ?>;
        const clientLng = <?= $order['delivery_lng'] ?: 10.1815 ?>;
        const map = L.map(mapEl).setView([clientLat, clientLng], 14);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);

        <?php if ($order['delivery_lat'] && $order['delivery_lng']): ?>
        L.marker([clientLat, clientLng], {
            icon: L.divIcon({
                html: '<div style="background:#00D9A5;width:20px;height:20px;border-radius:50%;border:2px solid white;"></div>',
                iconSize: [20, 20], iconAnchor: [10, 10]
            })
        }).addTo(map).bindPopup('Destination (Client)');
        <?php endif; ?>

        const driverMarker = L.marker([36.8065, 10.1815], {
            icon: L.divIcon({
                html: '<div style="background:#FF6B35;width:28px;height:28px;border-radius:50%;border:2px solid white;display:flex;align-items:center;justify-content:center;"><i class="fas fa-motorcycle" style="color:white;font-size:12px;"></i></div>',
                iconSize: [28, 28], iconAnchor: [14, 14]
            })
        }).addTo(map);

        // Draw path between driver and client
        const routeLine = L.polyline([[36.8065, 10.1815], [clientLat, clientLng]], {
            color: '#E5B04C',
            weight: 3,
            dashArray: '6, 6',
            opacity: 0.8
        }).addTo(map);

        trackers[orderId] = new DriverLocationUpdater(orderId);
        trackers[orderId].start();

        const originalUpdate = trackers[orderId].updateLocation.bind(trackers[orderId]);
        trackers[orderId].updateLocation = async function(position) {
            await originalUpdate(position);
            const dLat = position.coords.latitude;
            const dLng = position.coords.longitude;
            driverMarker.setLatLng([dLat, dLng]);
            routeLine.setLatLngs([[dLat, dLng], [clientLat, clientLng]]);
            map.fitBounds(L.latLngBounds([dLat, dLng], [clientLat, clientLng]), { padding: [40, 40] });
            document.getElementById('gps-status-' + orderId).innerHTML =
                '<i class="fas fa-check-circle"></i> Position partagée: ' +
                dLat.toFixed(4) + ', ' + dLng.toFixed(4);
        };
    })();
    <?php endif; ?>
    <?php endforeach; ?>
});

function stopTracking(orderId) {
    if (trackers[orderId]) trackers[orderId].stop();
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
