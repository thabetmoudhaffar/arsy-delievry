<?php
require_once __DIR__ . '/../includes/functions.php';
requireRole('client');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['error' => 'Method not allowed'], 405);
}

$data = json_decode(file_get_contents('php://input'), true);
$items = $data['items'] ?? [];
$address = trim($data['address'] ?? '');
$lat = (float)($data['latitude'] ?? 0);
$lng = (float)($data['longitude'] ?? 0);
$notes = trim($data['notes'] ?? '');

if (empty($items) || empty($address)) {
    jsonResponse(['error' => 'Items and address required'], 400);
}

$user = currentUser();
$db = getDB();
ensureProductSchema($db);

try {
    $db->beginTransaction();

    $total = 0;
    foreach ($items as $item) {
        $productId = (int)$item['id'];

        // Determine base price based on size
        $basePrice = null;
        if (!empty($item['size'])) {
            $stmt = $db->prepare('SELECT price FROM product_sizes WHERE product_id = ? AND name = ?');
            $stmt->execute([$productId, $item['size']]);
            $sizePrice = $stmt->fetchColumn();
            if ($sizePrice !== false) {
                $basePrice = (float)$sizePrice;
            }
        }

        if ($basePrice === null) {
            $stmt = $db->prepare('SELECT price FROM products WHERE id = ? AND available = 1');
            $stmt->execute([$productId]);
            $productPrice = $stmt->fetchColumn();
            if ($productPrice === false) throw new Exception('Produit non trouvé ou non disponible : ' . $productId);
            $basePrice = (float)$productPrice;
        }

        // Add extra ingredients prices
        $extraPrice = 0.00;
        if (!empty($item['ingredients'])) {
            $selectedIngs = (array)$item['ingredients'];
            $dbIngs = getProductIngredients($productId);
            foreach ($dbIngs as $dbIng) {
                if (in_array($dbIng['name'], $selectedIngs) && (float)$dbIng['price'] > 0) {
                    $extraPrice += (float)$dbIng['price'];
                }
            }
        }

        $itemUnitPrice = $basePrice + $extraPrice;
        $total += $itemUnitPrice * $item['quantity'];
    }

    $orderNumber = generateOrderNumber();
    $stmt = $db->prepare('INSERT INTO orders (order_number, client_id, delivery_address, delivery_lat, delivery_lng, total_amount, notes, status) VALUES (?, ?, ?, ?, ?, ?, ?, "pending")');
    $stmt->execute([$orderNumber, $user['id'], $address, $lat ?: null, $lng ?: null, $total, $notes]);
    $orderId = $db->lastInsertId();

    foreach ($items as $item) {
        $productId = (int)$item['id'];

        // Determine base price based on size
        $basePrice = null;
        if (!empty($item['size'])) {
            $stmt = $db->prepare('SELECT price FROM product_sizes WHERE product_id = ? AND name = ?');
            $stmt->execute([$productId, $item['size']]);
            $sizePrice = $stmt->fetchColumn();
            if ($sizePrice !== false) {
                $basePrice = (float)$sizePrice;
            }
        }

        if ($basePrice === null) {
            $stmt = $db->prepare('SELECT price FROM products WHERE id = ?');
            $stmt->execute([$productId]);
            $basePrice = (float)$stmt->fetchColumn();
        }

        $extraPrice = 0.00;
        $ingredients = $item['ingredients'] ?? [];
        if (!empty($ingredients)) {
            $dbIngs = getProductIngredients($productId);
            foreach ($dbIngs as $dbIng) {
                if (in_array($dbIng['name'], $ingredients) && (float)$dbIng['price'] > 0) {
                    $extraPrice += (float)$dbIng['price'];
                }
            }
        }

        $itemUnitPrice = $basePrice + $extraPrice;

        // Save both size and ingredients in customization column
        $customizationData = [];
        if (!empty($item['size'])) {
            $customizationData['size'] = $item['size'];
        }
        if (!empty($ingredients)) {
            $customizationData['ingredients'] = array_values($ingredients);
        }

        $customizationJson = !empty($customizationData)
            ? json_encode($customizationData, JSON_UNESCAPED_UNICODE)
            : null;

        $stmt = $db->prepare('INSERT INTO order_items (order_id, product_id, quantity, unit_price, customization) VALUES (?, ?, ?, ?, ?)');
        $stmt->execute([$orderId, $productId, $item['quantity'], $itemUnitPrice, $customizationJson]);
    }

    $db->commit();
    jsonResponse(['success' => true, 'order_id' => $orderId, 'order_number' => $orderNumber]);
} catch (Exception $e) {
    $db->rollBack();
    jsonResponse(['error' => $e->getMessage()], 500);
}
