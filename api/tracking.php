<?php
require_once __DIR__ . '/../includes/functions.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $orderId = (int)($_GET['order_id'] ?? 0);
    if (!$orderId) jsonResponse(['error' => 'Order ID required'], 400);

    $db = getDB();
    $stmt = $db->prepare('
        SELECT o.*, dl.latitude as driver_lat, dl.longitude as driver_lng, dl.updated_at as location_updated,
               u.name as driver_name, u.phone as driver_phone
        FROM orders o
        LEFT JOIN driver_locations dl ON dl.order_id = o.id
        LEFT JOIN users u ON o.driver_id = u.id
        WHERE o.id = ?
    ');
    $stmt->execute([$orderId]);
    $order = $stmt->fetch();

    if (!$order) jsonResponse(['error' => 'Order not found'], 404);

    jsonResponse([
        'order_id' => $order['id'],
        'order_number' => $order['order_number'],
        'status' => $order['status'],
        'delivery_lat' => (float)$order['delivery_lat'],
        'delivery_lng' => (float)$order['delivery_lng'],
        'driver_lat' => $order['driver_lat'] ? (float)$order['driver_lat'] : null,
        'driver_lng' => $order['driver_lng'] ? (float)$order['driver_lng'] : null,
        'driver_name' => $order['driver_name'],
        'driver_phone' => $order['driver_phone'],
        'location_updated' => $order['location_updated'],
    ]);
}

if ($method === 'POST') {
    requireRole('driver');
    $data = json_decode(file_get_contents('php://input'), true);
    $orderId = (int)($data['order_id'] ?? 0);
    $lat = (float)($data['latitude'] ?? 0);
    $lng = (float)($data['longitude'] ?? 0);

    if (!$orderId || !$lat || !$lng) {
        jsonResponse(['error' => 'Invalid data'], 400);
    }

    $user = currentUser();
    $db = getDB();

    $stmt = $db->prepare('SELECT id FROM driver_locations WHERE driver_id = ? AND order_id = ?');
    $stmt->execute([$user['id'], $orderId]);
    $existing = $stmt->fetch();

    if ($existing) {
        $stmt = $db->prepare('UPDATE driver_locations SET latitude = ?, longitude = ? WHERE driver_id = ? AND order_id = ?');
        $stmt->execute([$lat, $lng, $user['id'], $orderId]);
    } else {
        $stmt = $db->prepare('INSERT INTO driver_locations (driver_id, order_id, latitude, longitude) VALUES (?, ?, ?, ?)');
        $stmt->execute([$user['id'], $orderId, $lat, $lng]);
    }

    jsonResponse(['success' => true, 'latitude' => $lat, 'longitude' => $lng]);
}

jsonResponse(['error' => 'Method not allowed'], 405);
