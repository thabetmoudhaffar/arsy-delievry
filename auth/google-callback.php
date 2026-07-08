<?php
require_once __DIR__ . '/../includes/functions.php';

// Ensure the google_id column exists
ensureGoogleSchema();

// Retrieve the address from state parameter (if any)
$addressParam = '';
if (!empty($_GET['state'])) {
    $addressParam = '?address=' . urlencode($_GET['state']);
}

// Error fallback
if (empty($_GET['code'])) {
    header('Location: ' . BASE_PATH . '/auth/login.php?error=google_cancelled');
    exit;
}

// Exchange authorization code for user profile
$googleUser = getGoogleUser($_GET['code']);

if (!$googleUser || empty($googleUser['email'])) {
    header('Location: ' . BASE_PATH . '/auth/login.php?error=google_failed');
    exit;
}

$db = getDB();

// 1. Try to find by google_id
$stmt = $db->prepare('SELECT * FROM users WHERE google_id = ?');
$stmt->execute([$googleUser['google_id']]);
$user = $stmt->fetch();

// 2. If not found by google_id, try by email
if (!$user) {
    $stmt = $db->prepare('SELECT * FROM users WHERE email = ?');
    $stmt->execute([$googleUser['email']]);
    $user = $stmt->fetch();

    // Link existing account to Google
    if ($user) {
        $stmt = $db->prepare('UPDATE users SET google_id = ? WHERE id = ?');
        $stmt->execute([$googleUser['google_id'], $user['id']]);
    }
}

// 3. If still not found, create a new client account
if (!$user) {
    $randomPassword = password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT);
    $stmt = $db->prepare('INSERT INTO users (name, email, google_id, phone, password, role) VALUES (?, ?, ?, "", ?, "client")');
    $stmt->execute([
        $googleUser['name'],
        $googleUser['email'],
        $googleUser['google_id'],
        $randomPassword,
    ]);
    $user = [
        'id'   => $db->lastInsertId(),
        'role' => 'client',
    ];
}

// 4. Log the user in
$_SESSION['user_id']   = $user['id'];
$_SESSION['user_role'] = $user['role'];

// 5. Redirect based on role
$redirect = match ($user['role']) {
    'admin'  => BASE_PATH . '/admin/dashboard.php',
    'driver' => BASE_PATH . '/driver/dashboard.php',
    default  => BASE_PATH . '/client/order.php' . $addressParam,
};
header('Location: ' . $redirect);
exit;
