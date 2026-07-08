<?php
require_once __DIR__ . '/../includes/functions.php';
requireRole('client');

$user = currentUser();
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');

    if (empty($name)) {
        $error = 'Le nom est obligatoire.';
    } else {
        $db = getDB();
        $stmt = $db->prepare('UPDATE users SET name = ?, phone = ?, address = ? WHERE id = ?');
        $stmt->execute([$name, $phone, $address, $user['id']]);
        $success = 'Profil mis à jour avec succès.';
        $user = currentUser();
    }
}

$pageTitle = 'Mon Profil';
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
            <li><a href="orders.php"><i class="fas fa-list"></i> Mes commandes</a></li>
            <li><a href="profile.php" class="active"><i class="fas fa-user"></i> Mon profil</a></li>
            <li><a href="../auth/logout.php"><i class="fas fa-sign-out-alt"></i> Déconnexion</a></li>
        </ul>
    </aside>

    <main class="main-content">
        <div class="page-header">
            <h1>Mon Profil</h1>
            <p>Gérez vos informations personnelles</p>
        </div>

        <div class="glass-card" style="padding:32px;max-width:500px;">
            <?php if ($success): ?>
                <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?= sanitize($success) ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?= sanitize($error) ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <label>Nom complet</label>
                    <input type="text" name="name" class="form-control" value="<?= sanitize($user['name']) ?>" required>
                </div>
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" class="form-control" value="<?= sanitize($user['email']) ?>" disabled>
                </div>
                <div class="form-group">
                    <label>Téléphone</label>
                    <input type="tel" name="phone" class="form-control" value="<?= sanitize($user['phone'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label>Adresse par défaut</label>
                    <textarea name="address" class="form-control" rows="3"><?= sanitize($user['address'] ?? '') ?></textarea>
                </div>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Enregistrer</button>
            </form>
        </div>
    </main>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
