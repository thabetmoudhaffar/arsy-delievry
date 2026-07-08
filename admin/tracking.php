<?php
require_once __DIR__ . '/../includes/functions.php';
requireRole('admin');

$db = getDB();

$activeDeliveries = $db->query("
    SELECT o.*, c.name as client_name, d.name as driver_name,
           dl.latitude as driver_lat, dl.longitude as driver_lng
    FROM orders o
    JOIN users c ON o.client_id = c.id
    LEFT JOIN users d ON o.driver_id = d.id
    LEFT JOIN driver_locations dl ON dl.order_id = o.id
    WHERE o.status IN ('confirmed', 'picked_up', 'in_transit')
    ORDER BY o.updated_at DESC
")->fetchAll();

$pageTitle = 'Suivi Live';
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
            <li><a href="dashboard.php"><i class="fas fa-chart-line"></i> Dashboard</a></li>
            <li><a href="orders.php"><i class="fas fa-list"></i> Commandes</a></li>
            <li><a href="products.php"><i class="fas fa-box"></i> Produits</a></li>
            <li><a href="restaurants.php"><i class="fas fa-store"></i> Restaurants</a></li>
            <li><a href="drivers.php"><i class="fas fa-motorcycle"></i> Livreurs</a></li>
            <li><a href="tracking.php" class="active"><i class="fas fa-map"></i> Suivi Live</a></li>
            <li><a href="../auth/logout.php"><i class="fas fa-sign-out-alt"></i> Déconnexion</a></li>
        </ul>
    </aside>

    <main class="main-content">
        <div class="page-header">
            <h1>Suivi en Temps Réel <span class="live-indicator">LIVE</span></h1>
            <p>Visualisez toutes les livraisons actives sur la carte</p>
        </div>

        <div style="display:grid;grid-template-columns:1fr 350px;gap:24px;">
            <div class="glass-card" style="padding:0;overflow:hidden;">
                <div id="admin-map" class="map-container" style="height:600px;border:none;border-radius:var(--radius);"></div>
            </div>

            <div class="glass-card" style="padding:24px;max-height:600px;overflow-y:auto;">
                <h3 style="margin-bottom:20px;">Livraisons actives (<?= count($activeDeliveries) ?>)</h3>
                <?php if (empty($activeDeliveries)): ?>
                    <p style="color:var(--text-muted);text-align:center;padding:40px 0;">Aucune livraison en cours</p>
                <?php else: ?>
                    <?php foreach ($activeDeliveries as $delivery): ?>
                    <div class="delivery-item" data-order-id="<?= $delivery['id'] ?>"
                         data-driver-lat="<?= $delivery['driver_lat'] ?>"
                         data-driver-lng="<?= $delivery['driver_lng'] ?>"
                         data-delivery-lat="<?= $delivery['delivery_lat'] ?>"
                         data-delivery-lng="<?= $delivery['delivery_lng'] ?>"
                         style="padding:16px;border:1px solid var(--border);border-radius:8px;margin-bottom:12px;cursor:pointer;transition:var(--transition);"
                         onclick="focusDelivery(<?= $delivery['id'] ?>)">
                        <div style="display:flex;justify-content:space-between;align-items:center;">
                            <strong><?= sanitize($delivery['order_number']) ?></strong>
                            <span class="status-badge <?= getOrderStatusClass($delivery['status']) ?>"><?= getOrderStatusLabel($delivery['status']) ?></span>
                        </div>
                        <p style="font-size:0.875rem;color:var(--text-secondary);margin-top:8px;">
                            <i class="fas fa-user"></i> <?= sanitize($delivery['client_name']) ?>
                        </p>
                        <p style="font-size:0.875rem;color:var(--text-secondary);">
                            <i class="fas fa-motorcycle"></i> <?= sanitize($delivery['driver_name'] ?? 'Non assigné') ?>
                        </p>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </main>
</div>

<script>
let adminMap, markers = {}, routeLines = {};

document.addEventListener('DOMContentLoaded', () => {
    adminMap = L.map('admin-map').setView([36.8065, 10.1815], 7);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap'
    }).addTo(adminMap);

    loadDeliveries();
    setInterval(loadDeliveries, 5000);
});

async function loadDeliveries() {
    const items = document.querySelectorAll('.delivery-item');
    const allBounds = [];

    for (const item of items) {
        const orderId = item.dataset.orderId;
        const deliveryLat = parseFloat(item.dataset.deliveryLat);
        const deliveryLng = parseFloat(item.dataset.deliveryLng);

        // Remove old markers/lines
        if (markers[orderId]) {
            if (markers[orderId].driver) adminMap.removeLayer(markers[orderId].driver);
            if (markers[orderId].delivery) adminMap.removeLayer(markers[orderId].delivery);
        }
        if (routeLines[orderId]) adminMap.removeLayer(routeLines[orderId]);

        markers[orderId] = {};

        // Client destination marker (green dot)
        if (deliveryLat && deliveryLng) {
            markers[orderId].delivery = L.marker([deliveryLat, deliveryLng], {
                icon: L.divIcon({
                    html: '<div style="background:#00D9A5;width:20px;height:20px;border-radius:50%;border:2px solid white;box-shadow:0 2px 6px rgba(0,0,0,0.3);"></div>',
                    iconSize: [20, 20], iconAnchor: [10, 10]
                })
            }).addTo(adminMap).bindPopup('📍 Client #' + orderId);
            allBounds.push([deliveryLat, deliveryLng]);
        }

        // Fetch real-time driver position from API
        try {
            const data = await apiRequest('<?= BASE_PATH ?>/api/tracking.php?order_id=' + orderId);
            if (data.driver_lat && data.driver_lng) {
                const dLat = data.driver_lat, dLng = data.driver_lng;
                markers[orderId].driver = L.marker([dLat, dLng], {
                    icon: L.divIcon({
                        html: '<div style="background:#FF6B35;width:28px;height:28px;border-radius:50%;border:2px solid white;display:flex;align-items:center;justify-content:center;box-shadow:0 2px 6px rgba(0,0,0,0.3);"><i class="fas fa-motorcycle" style="color:white;font-size:12px;"></i></div>',
                        iconSize: [28, 28], iconAnchor: [14, 14]
                    })
                }).addTo(adminMap).bindPopup('🛵 Livreur #' + orderId);
                allBounds.push([dLat, dLng]);

                // Update data attributes for focusDelivery
                item.dataset.driverLat = dLat;
                item.dataset.driverLng = dLng;

                // Draw route line between driver and client
                if (deliveryLat && deliveryLng) {
                    routeLines[orderId] = L.polyline([[dLat, dLng], [deliveryLat, deliveryLng]], {
                        color: '#E5B04C', weight: 3, dashArray: '6, 6', opacity: 0.8
                    }).addTo(adminMap);
                }
            }
        } catch (e) { console.error('Tracking poll error:', e); }
    }

    // Auto-fit bounds to show all markers
    if (allBounds.length > 1) {
        adminMap.fitBounds(allBounds, { padding: [40, 40] });
    } else if (allBounds.length === 1) {
        adminMap.setView(allBounds[0], 14);
    }
}

function focusDelivery(orderId) {
    const item = document.querySelector(`[data-order-id="${orderId}"]`);
    const dLat = parseFloat(item.dataset.driverLat);
    const dLng = parseFloat(item.dataset.driverLng);
    const cLat = parseFloat(item.dataset.deliveryLat);
    const cLng = parseFloat(item.dataset.deliveryLng);

    if (dLat && dLng && cLat && cLng) {
        adminMap.fitBounds([[dLat, dLng], [cLat, cLng]], { padding: [50, 50] });
    } else if (dLat && dLng) {
        adminMap.setView([dLat, dLng], 15);
    } else if (cLat && cLng) {
        adminMap.setView([cLat, cLng], 15);
    }
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
