<?php
session_start();
require_once __DIR__ . '/../config/database.php';

function isLoggedIn(): bool {
    return isset($_SESSION['user_id']);
}

function currentUser(): ?array {
    if (!isLoggedIn()) return null;
    $db = getDB();
    $stmt = $db->prepare('SELECT id, name, email, phone, address, role FROM users WHERE id = ?');
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch() ?: null;
}

function requireRole(string ...$roles): void {
    $user = currentUser();
    if (!$user || !in_array($user['role'], $roles)) {
        header('Location: ' . BASE_PATH . '/index.php');
        exit;
    }
}

function generateOrderNumber(): string {
    return 'ARS-' . strtoupper(substr(uniqid(), -6)) . '-' . date('md');
}

function jsonResponse(array $data, int $code = 200): void {
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

function sanitize(string $str): string {
    return htmlspecialchars(trim($str), ENT_QUOTES, 'UTF-8');
}

function getOrderStatusLabel(string $status): string {
    $labels = [
        'pending' => 'En attente',
        'confirmed' => 'Confirmée',
        'preparing' => 'En préparation',
        'picked_up' => 'Récupérée',
        'in_transit' => 'En livraison',
        'delivered' => 'Livrée',
        'cancelled' => 'Annulée',
    ];
    return $labels[$status] ?? $status;
}

function getOrderStatusClass(string $status): string {
    $classes = [
        'pending' => 'status-pending',
        'confirmed' => 'status-confirmed',
        'preparing' => 'status-preparing',
        'picked_up' => 'status-picked',
        'in_transit' => 'status-transit',
        'delivered' => 'status-delivered',
        'cancelled' => 'status-cancelled',
    ];
    return $classes[$status] ?? '';
}

function getProductImageUrl(array $product, string $basePath = BASE_PATH): string {
    $localFile = $product['image'] ?? '';
    if ($localFile && $localFile !== 'default-food.jpg') {
        $path = __DIR__ . '/../public/assets/images/products/' . $localFile;
        if (file_exists($path)) {
            return $basePath . '/public/assets/images/products/' . rawurlencode($localFile);
        }
    }

    $name = strtolower($product['name'] ?? '');
    $category = strtolower($product['category_name'] ?? '');

    $urls = [
        'burger' => 'https://images.unsplash.com/photo-1568901346375-23c9450c58cd?w=600&h=400&fit=crop&q=80',
        'pizza' => 'https://images.unsplash.com/photo-1574071318508-1cdbab80d002?w=600&h=400&fit=crop&q=80',
        'tacos' => 'https://images.unsplash.com/photo-1551504734-5ee1c4a1479b?w=600&h=400&fit=crop&q=80',
        'pasta' => 'https://images.unsplash.com/photo-1621996346565-e3dbc646d9a9?w=600&h=400&fit=crop&q=80',
        'juice' => 'https://images.unsplash.com/photo-1600271886742-f049cd451bba?w=600&h=400&fit=crop&q=80',
        'smoothie' => 'https://images.unsplash.com/photo-1505252587547-aa664d4b6e9e?w=600&h=400&fit=crop&q=80',
        'grocery' => 'https://images.unsplash.com/photo-1542838132-92c53300491e?w=600&h=400&fit=crop&q=80',
        'pharmacy' => 'https://images.unsplash.com/photo-1587854692152-cf40b89c2bca?w=600&h=400&fit=crop&q=80',
        'package' => 'https://images.unsplash.com/photo-1566576912321-d58ddd7a6088?w=600&h=400&fit=crop&q=80',
    ];

    foreach ($urls as $keyword => $url) {
        if (str_contains($name, $keyword) || str_contains($category, $keyword)) {
            return $url;
        }
    }

    if (str_contains($category, 'drink')) {
        return $urls['juice'];
    }
    if (str_contains($category, 'food')) {
        return $urls['burger'];
    }

    return 'https://images.unsplash.com/photo-1504674900247-0877df9cc836?w=600&h=400&fit=crop&q=80';
}

function getCategoryImageUrl(string $categoryName): string {
    $map = [
        'Food' => 'https://images.unsplash.com/photo-1504674900247-0877df9cc836?w=200&h=200&fit=crop&q=80',
        'Drinks' => 'https://images.unsplash.com/photo-1544145945-f90425340c7e?w=200&h=200&fit=crop&q=80',
        'Groceries' => 'https://images.unsplash.com/photo-1542838132-92c53300491e?w=200&h=200&fit=crop&q=80',
        'Pharmacy' => 'https://images.unsplash.com/photo-1584308666744-24d5c474f2ae?w=200&h=200&fit=crop&q=80',
        'Other' => 'https://images.unsplash.com/photo-1566576912321-d58ddd7a6088?w=200&h=200&fit=crop&q=80',
    ];
    return $map[$categoryName] ?? $map['Food'];
}

function getHeroFoodImages(): array {
    return [
        ['url' => 'https://images.unsplash.com/photo-1565299624946-b28f40a0ae38?w=400&h=400&fit=crop&q=80', 'alt' => 'Pizza', 'class' => 'hero-food-1'],
        ['url' => 'https://images.unsplash.com/photo-1568901346375-23c9450c58cd?w=400&h=400&fit=crop&q=80', 'alt' => 'Burger', 'class' => 'hero-food-2'],
        ['url' => 'https://images.unsplash.com/photo-1555939594-58d7cb561ad1?w=400&h=400&fit=crop&q=80', 'alt' => 'Petit déjeuner', 'class' => 'hero-food-3'],
        ['url' => 'https://images.unsplash.com/photo-1526367790999-0150786686a2?w=500&h=500&fit=crop&q=80', 'alt' => 'Livreur', 'class' => 'hero-food-delivery'],
    ];
}

function ensureCustomizationSchema(): void {
    static $done = false;
    if ($done) return;
    $done = true;

    $db = getDB();
    // 1. Add price column to product_ingredients if not exists
    $col = $db->query("SHOW COLUMNS FROM product_ingredients LIKE 'price'")->fetch();
    if (!$col) {
        $db->exec('ALTER TABLE product_ingredients ADD COLUMN price DECIMAL(10,2) NOT NULL DEFAULT 0.00');
    }

    // 2. Create product_sizes table if not exists
    $db->exec('CREATE TABLE IF NOT EXISTS product_sizes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        product_id INT NOT NULL,
        name VARCHAR(100) NOT NULL,
        price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
    )');
}

function getProductSizes(int $productId): array {
    $db = getDB();
    ensureCustomizationSchema();
    $stmt = $db->prepare('SELECT id, name, price FROM product_sizes WHERE product_id = ? ORDER BY price ASC');
    $stmt->execute([$productId]);
    return $stmt->fetchAll();
}

function ensureRestaurantSchema(): void {
    static $done = false;
    if ($done) return;
    $done = true;

    $db = getDB();

    // 1. Create restaurants table
    $db->exec('CREATE TABLE IF NOT EXISTS restaurants (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(150) NOT NULL,
        description TEXT NULL,
        specialty VARCHAR(100) NULL,
        image VARCHAR(255) DEFAULT "default-restaurant.jpg",
        rating DECIMAL(2,1) DEFAULT 4.5,
        delivery_time VARCHAR(50) DEFAULT "20-30 min",
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )');

    // 2. Add restaurant_id to products if not exists
    $col = $db->query("SHOW COLUMNS FROM products LIKE 'restaurant_id'")->fetch();
    if (!$col) {
        $db->exec('ALTER TABLE products ADD COLUMN restaurant_id INT NULL AFTER category_id');
        $db->exec('ALTER TABLE products ADD FOREIGN KEY (restaurant_id) REFERENCES restaurants(id) ON DELETE SET NULL');
    }

    // 3. Insert mock restaurants if empty
    $count = $db->query('SELECT COUNT(*) FROM restaurants')->fetchColumn();
    if ($count == 0) {
        $stmt = $db->prepare('INSERT INTO restaurants (name, description, specialty, image, rating, delivery_time) VALUES (?, ?, ?, ?, ?, ?)');
        
        $stmt->execute([
            'Le Bistro Gourmet',
            'Savourez nos burgers premium faits maison avec des ingrédients frais de qualité supérieure et nos frites croustillantes.',
            'Burgers & Viandes',
            'https://images.unsplash.com/photo-1550547660-d9450f859349?w=600&h=400&fit=crop&q=80',
            4.8,
            '20-35 min'
        ]);
        $bistroId = $db->lastInsertId();

        $stmt->execute([
            'La Bella Italia',
            'Des pizzas traditionnelles cuites au feu de bois et des pâtes fraîches préparées selon des recettes ancestrales italiennes.',
            'Italien & Pizzas',
            'https://images.unsplash.com/photo-1513104890138-7c749659a591?w=600&h=400&fit=crop&q=80',
            4.7,
            '25-40 min'
        ]);
        $bellaId = $db->lastInsertId();

        $stmt->execute([
            'Jasmin de Tunis',
            'Le meilleur de la gastronomie tunisienne traditionnelle : couscous, kafteji, ojja et brik au thon préparés avec passion.',
            'Tunisien & Traditionnel',
            'https://images.unsplash.com/photo-1541518763669-27fef04b14ea?w=600&h=400&fit=crop&q=80',
            4.6,
            '15-30 min'
        ]);
        $jasminId = $db->lastInsertId();

        // 4. Update existing products to associate with mock restaurants
        $db->exec("UPDATE products SET restaurant_id = {$bistroId} WHERE name LIKE '%burger%' OR name LIKE '%tacos%'");
        $db->exec("UPDATE products SET restaurant_id = {$bellaId} WHERE name LIKE '%pizza%' OR name LIKE '%pasta%'");
    }
}

function ensureProductSchema(PDO $db): void {
    static $done = false;
    if ($done) return;
    $done = true;

    $db->exec('CREATE TABLE IF NOT EXISTS product_ingredients (
        id INT AUTO_INCREMENT PRIMARY KEY,
        product_id INT NOT NULL,
        name VARCHAR(100) NOT NULL,
        is_default TINYINT(1) DEFAULT 1,
        FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
    )');

    $col = $db->query("SHOW COLUMNS FROM order_items LIKE 'customization'")->fetch();
    if (!$col) {
        $db->exec('ALTER TABLE order_items ADD COLUMN customization TEXT NULL');
    }

    ensureCustomizationSchema();
    ensureRestaurantSchema();
}

function getProductIngredients(int $productId): array {
    $db = getDB();
    ensureProductSchema($db);
    $stmt = $db->prepare('SELECT id, name, is_default, price FROM product_ingredients WHERE product_id = ? ORDER BY id');
    $stmt->execute([$productId]);
    return $stmt->fetchAll();
}

function uploadProductImage(array $file): ?string {
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Erreur lors du téléchargement de l\'image.');
    }

    $allowed = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mime, $allowed, true)) {
        throw new RuntimeException('Format d\'image non supporté (JPG, PNG, WEBP, GIF).');
    }

    if (($file['size'] ?? 0) > 5 * 1024 * 1024) {
        throw new RuntimeException('L\'image ne doit pas dépasser 5 Mo.');
    }

    $ext = match ($mime) {
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'image/gif' => 'gif',
        default => 'jpg',
    };

    $dir = __DIR__ . '/../public/assets/images/products';
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }

    $filename = 'product_' . uniqid() . '.' . $ext;
    if (!move_uploaded_file($file['tmp_name'], $dir . '/' . $filename)) {
        throw new RuntimeException('Impossible d\'enregistrer l\'image.');
    }

    return $filename;
}

function formatOrderCustomization(?string $json): string {
    if (!$json) return '';
    $data = json_decode($json, true);
    if (!is_array($data)) return '';

    $parts = [];
    if (!empty($data['size'])) {
        $parts[] = 'Taille : ' . $data['size'];
    }
    if (!empty($data['ingredients'])) {
        $parts[] = 'Ingrédients : ' . implode(', ', $data['ingredients']);
    }
    return implode(' | ', $parts);
}

// ─── Google OAuth Helpers ───

function ensureGoogleSchema(): void {
    static $done = false;
    if ($done) return;
    $done = true;

    $db = getDB();
    $col = $db->query("SHOW COLUMNS FROM users LIKE 'google_id'")->fetch();
    if (!$col) {
        $db->exec('ALTER TABLE users ADD COLUMN google_id VARCHAR(255) NULL AFTER email');
    }
}

function getGoogleAuthUrl(string $returnAddress = ''): string {
    require_once __DIR__ . '/../config/google.php';

    // If client ID is default placeholder, fall back to mock sandbox selector for immediate testing
    if (GOOGLE_CLIENT_ID === 'YOUR_GOOGLE_CLIENT_ID') {
        return BASE_PATH . '/auth/google-mock.php?state=' . urlencode($returnAddress);
    }

    $state = $returnAddress ?: '';
    $params = http_build_query([
        'client_id'     => GOOGLE_CLIENT_ID,
        'redirect_uri'  => GOOGLE_REDIRECT_URI,
        'response_type' => 'code',
        'scope'         => 'openid email profile',
        'access_type'   => 'online',
        'prompt'        => 'select_account',
        'state'         => $state,
    ]);
    return 'https://accounts.google.com/o/oauth2/v2/auth?' . $params;
}

function getGoogleUser(string $code): ?array {
    require_once __DIR__ . '/../config/google.php';

    // If using the default placeholder client ID, process the mock code format
    if (GOOGLE_CLIENT_ID === 'YOUR_GOOGLE_CLIENT_ID') {
        if (str_starts_with($code, 'mock_')) {
            $parts = explode('|', substr($code, 5));
            return [
                'google_id' => $parts[0] ?? 'mock_google_id',
                'email'     => $parts[1] ?? 'demo@gmail.com',
                'name'      => urldecode($parts[2] ?? 'Google User'),
                'picture'   => '',
            ];
        }
        return null;
    }

    // Exchange code for access token
    $ch = curl_init('https://oauth2.googleapis.com/token');
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POSTFIELDS     => http_build_query([
            'code'          => $code,
            'client_id'     => GOOGLE_CLIENT_ID,
            'client_secret' => GOOGLE_CLIENT_SECRET,
            'redirect_uri'  => GOOGLE_REDIRECT_URI,
            'grant_type'    => 'authorization_code',
        ]),
        CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded'],
    ]);
    $response = curl_exec($ch);
    curl_close($ch);

    $tokenData = json_decode($response, true);
    if (empty($tokenData['access_token'])) {
        return null;
    }

    // Fetch user profile
    $ch = curl_init('https://www.googleapis.com/oauth2/v2/userinfo');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => ['Authorization: Bearer ' . $tokenData['access_token']],
    ]);
    $profile = curl_exec($ch);
    curl_close($ch);

    $userData = json_decode($profile, true);
    if (empty($userData['id'])) {
        return null;
    }

    return [
        'google_id' => $userData['id'],
        'email'     => $userData['email'] ?? '',
        'name'      => $userData['name'] ?? '',
        'picture'   => $userData['picture'] ?? '',
    ];
}

