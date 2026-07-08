<?php
/**
 * Arsy Delivery - Database Setup
 * Run once: http://localhost/arsy%20delievry/setup.php
 */
require_once __DIR__ . '/config/database.php';

$messages = [];

try {
    $pdo = new PDO('mysql:host=' . DB_HOST . ';charset=' . DB_CHARSET, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);

    $sql = file_get_contents(__DIR__ . '/database/schema.sql');
    $statements = array_filter(array_map('trim', explode(';', $sql)));

    foreach ($statements as $statement) {
        if (!empty($statement)) {
            $pdo->exec($statement);
        }
    }

    $messages[] = ['success', 'Base de données créée avec succès!'];
    $messages[] = ['info', 'Comptes de démo: admin@arsy.com / password | driver@arsy.com / password'];
} catch (PDOException $e) {
    $messages[] = ['error', 'Erreur: ' . $e->getMessage()];
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setup - Arsy Delivery</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body>
    <div class="bg-animated"></div>
    <div class="auth-page">
        <div class="glass-card auth-card fade-in visible">
            <div style="text-align:center;margin-bottom:24px;">
                <div class="logo-icon" style="margin:0 auto 16px;"><i class="fas fa-truck-fast"></i></div>
            </div>
            <h1>Installation Arsy Delivery</h1>
            <?php foreach ($messages as [$type, $msg]): ?>
                <div class="alert alert-<?= $type === 'error' ? 'error' : ($type === 'success' ? 'success' : 'info') ?>">
                    <i class="fas fa-<?= $type === 'error' ? 'exclamation-circle' : ($type === 'success' ? 'check-circle' : 'info-circle') ?>"></i>
                    <?= htmlspecialchars($msg) ?>
                </div>
            <?php endforeach; ?>
            <a href="index.php" class="btn btn-primary" style="width:100%;margin-top:16px;">
                <i class="fas fa-home"></i> Aller au site
            </a>
        </div>
    </div>
</body>
</html>
