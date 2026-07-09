<?php
require_once __DIR__ . '/../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if (empty($name) || empty($email) || empty($password)) {
        $error = 'Veuillez remplir tous les champs obligatoires.';
    } elseif ($password !== $confirm) {
        $error = 'Les mots de passe ne correspondent pas.';
    } elseif (strlen($password) < 6) {
        $error = 'Le mot de passe doit contenir au moins 6 caractères.';
    } else {
        $db = getDB();
        $stmt = $db->prepare('SELECT id FROM users WHERE email = ?');
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $error = 'Cet email est déjà utilisé.';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $db->prepare('INSERT INTO users (name, email, phone, password, role) VALUES (?, ?, ?, ?, "client")');
            $stmt->execute([$name, $email, $phone, $hash]);
            $_SESSION['user_id'] = $db->lastInsertId();
            $_SESSION['user_role'] = 'client';
            $addressParam = isset($_GET['address']) ? '?address=' . urlencode($_GET['address']) : '';
            header('Location: ' . BASE_PATH . '/client/order.php' . $addressParam);
            exit;
        }
    }
}

if (isLoggedIn()) {
    $addressParam = isset($_GET['address']) ? '?address=' . urlencode($_GET['address']) : '';
    header('Location: ' . BASE_PATH . '/client/order.php' . $addressParam);
    exit;
}

$pageTitle = 'Inscription';
$basePath = BASE_PATH;
$bodyClass = 'auth-body';
$extraCSS = '<link rel="stylesheet" href="' . $basePath . '/public/assets/css/auth.css">';
$extraJS = '<script src="' . $basePath . '/public/assets/js/auth.js"></script>';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="auth-split">
    <div class="auth-brand">
        <div class="auth-orb auth-orb-1"></div>
        <div class="auth-orb auth-orb-2"></div>
        <div class="auth-orb auth-orb-3"></div>

        <i class="fas fa-motorcycle auth-float-icon"></i>
        <i class="fas fa-box auth-float-icon"></i>
        <i class="fas fa-location-dot auth-float-icon"></i>
        <i class="fas fa-utensils auth-float-icon"></i>

        <a href="<?= $basePath ?>/index.php" class="auth-back-home">
            <i class="fas fa-arrow-left"></i> Accueil
        </a>

        <div class="auth-brand-content">
            <div class="auth-logo-wrap">
                <div class="auth-logo-glow"></div>
                <div class="auth-logo">
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
                        <span class="auth-brand-slogan">Livraison Rapide • Tunisie</span>
                    </span>
                </div>
            </div>

            <p class="auth-tagline">
                Rejoignez Arsy Delivery et profitez d'une livraison rapide, fiable et suivie en direct.
            </p>

            <div class="auth-features">
                <div class="auth-feature">
                    <div class="auth-feature-icon"><i class="fas fa-gift"></i></div>
                    <span>Inscription gratuite en 30 secondes</span>
                </div>
                <div class="auth-feature">
                    <div class="auth-feature-icon"><i class="fas fa-utensils"></i></div>
                    <span>Food, courses, pharmacie & plus</span>
                </div>
                <div class="auth-feature">
                    <div class="auth-feature-icon"><i class="fas fa-truck-fast"></i></div>
                    <span>Suivi de livraison en temps réel</span>
                </div>
            </div>
        </div>
    </div>

    <div class="auth-form-panel">
        <div class="auth-cursor-glow"></div>

        <div class="auth-form-card">
            <div class="auth-form-header">
                <h1>Créer un compte</h1>
                <p>Commencez à commander dès maintenant</p>
            </div>

            <?php if (!empty($error)): ?>
                <div class="auth-alert auth-alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?= sanitize($error) ?>
                </div>
            <?php endif; ?>

            <a href="<?= getGoogleAuthUrl($_GET['address'] ?? '') ?>" class="auth-google-btn">
                <svg class="google-icon" viewBox="0 0 24 24" width="20" height="20" xmlns="http://www.w3.org/2000/svg">
                    <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92a5.06 5.06 0 0 1-2.2 3.32v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.1z" fill="#4285F4"/>
                    <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
                    <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/>
                    <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/>
                </svg>
                S'inscrire avec Google
            </a>

            <div class="auth-divider">ou créer un compte manuellement</div>

            <form method="POST" class="auth-form">
                <div class="auth-input-group">
                    <label for="name">Nom complet *</label>
                    <div class="auth-input-wrap">
                        <i class="fas fa-user input-icon"></i>
                        <input type="text" id="name" name="name" class="auth-input"
                               placeholder="Votre nom" required
                               value="<?= sanitize($_POST['name'] ?? '') ?>">
                        <div class="auth-input-line"></div>
                    </div>
                </div>

                <div class="auth-input-group">
                    <label for="email">Adresse email *</label>
                    <div class="auth-input-wrap">
                        <i class="fas fa-envelope input-icon"></i>
                        <input type="email" id="email" name="email" class="auth-input"
                               placeholder="votre@email.com" required
                               value="<?= sanitize($_POST['email'] ?? '') ?>">
                        <div class="auth-input-line"></div>
                    </div>
                </div>

                <div class="auth-input-group">
                    <label for="phone">Téléphone</label>
                    <div class="auth-input-wrap">
                        <i class="fas fa-phone input-icon"></i>
                        <input type="tel" id="phone" name="phone" class="auth-input"
                               placeholder="06 XX XX XX XX"
                               value="<?= sanitize($_POST['phone'] ?? '') ?>">
                        <div class="auth-input-line"></div>
                    </div>
                </div>

                <div class="auth-input-group">
                    <label for="password">Mot de passe *</label>
                    <div class="auth-input-wrap">
                        <i class="fas fa-lock input-icon"></i>
                        <input type="password" id="password" name="password" class="auth-input"
                               placeholder="••••••••" required>
                        <button type="button" class="toggle-password" aria-label="Afficher le mot de passe">
                            <i class="fas fa-eye"></i>
                        </button>
                        <div class="auth-input-line"></div>
                    </div>
                </div>

                <div class="auth-input-group">
                    <label for="confirm_password">Confirmer le mot de passe *</label>
                    <div class="auth-input-wrap">
                        <i class="fas fa-lock input-icon"></i>
                        <input type="password" id="confirm_password" name="confirm_password" class="auth-input"
                               placeholder="••••••••" required>
                        <button type="button" class="toggle-password" aria-label="Afficher le mot de passe">
                            <i class="fas fa-eye"></i>
                        </button>
                        <div class="auth-input-line"></div>
                    </div>
                </div>

                <button type="submit" class="auth-submit" style="margin-top:8px;">
                    <span class="btn-icon"><i class="fas fa-user-plus"></i></span>
                    <span class="btn-text">Créer mon compte</span>
                    <span class="btn-loader"></span>
                </button>
            </form>

            <div class="auth-footer-link">
                Déjà un compte ? <a href="login.php<?= isset($_GET['address']) ? '?address=' . urlencode($_GET['address']) : '' ?>">Se connecter</a>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
