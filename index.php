<?php
if (isset($_GET['debug_env']) && $_GET['debug_env'] === 'env2026') {
    require_once __DIR__ . '/config/database.php';
    header('Content-Type: application/json');
    echo json_encode([
        'APP_ENV' => APP_ENV,
        'VERCEL' => getenv('VERCEL'),
        'VERCEL_ENV' => getenv('VERCEL_ENV'),
        'DATABASE_URL' => getenv('DATABASE_URL') !== false ? 'set' : 'empty',
        'AIVEN_MYSQL_URI' => getenv('AIVEN_MYSQL_URI') !== false ? 'set' : 'empty',
        'DB_HOST' => getenv('DB_HOST') ?: 'empty',
        'DB_PORT' => getenv('DB_PORT') ?: 'empty',
        'DB_NAME' => getenv('DB_NAME') ?: 'empty',
        'DB_USER' => getenv('DB_USER') ?: 'empty',
        'DB_SSL_MODE' => getenv('DB_SSL_MODE') ?: 'empty',
        'DB_SSL_CA' => getenv('DB_SSL_CA') !== false ? 'set' : 'empty',
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    exit;
}
if (isset($_GET['debug_ssl']) && $_GET['debug_ssl'] === 'env2026') {
    require_once __DIR__ . '/config/database.php';
    header('Content-Type: application/json');
    $caEnv = getenv('DB_SSL_CA');
    $resolved = '';
    try {
        $resolved = resolveDbSslCaPath(($caEnv !== false && $caEnv !== '') ? $caEnv : DB_SSL_CA);
    } catch (Throwable $e) {
        $resolved = 'error:' . $e->getMessage();
    }
    $res = [
        'DB_SSL_CA_env' => $caEnv !== false && $caEnv !== '' ? 'set' : 'empty',
        'resolved_path' => $resolved,
        'file_exists' => $resolved !== '' && file_exists($resolved) ? 'yes' : ( $resolved === '' ? 'n/a' : 'no'),
        'defined_PDO_MYSQL_ATTR_SSL_CA' => defined('PDO::MYSQL_ATTR_SSL_CA') ? 'yes' : 'no',
        'defined_PDO_MYSQL_ATTR_SSL_VERIFY_SERVER_CERT' => defined('PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT') ? 'yes' : 'no',
        'DB_SSL_VERIFY_SERVER_CERT' => defined('DB_SSL_VERIFY_SERVER_CERT') ? DB_SSL_VERIFY_SERVER_CERT : 'undefined',
        'sys_get_temp_dir' => sys_get_temp_dir(),
    ];
    echo json_encode($res, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    exit;
}
if (isset($_GET['debug_db']) && $_GET['debug_db'] === 'env2026') {
    require_once __DIR__ . '/config/database.php';
    header('Content-Type: application/json');
    try {
        $pdo = getDB();
        echo json_encode(['status' => 'ok', 'dsn' => 'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    } catch (Throwable $exception) {
        echo json_encode(['status' => 'error', 'message' => $exception->getMessage(), 'file' => $exception->getFile(), 'line' => $exception->getLine()], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }
    exit;
}
if (isset($_GET['debug_tcp']) && $_GET['debug_tcp'] === 'env2026') {
    header('Content-Type: application/json');
    $host = 'mysql-1d5749f-mouthaffar4242-e2a3.e.aivencloud.com';
    $port = 25411;
    $result = ['host' => $host, 'port' => $port];
    $ip = gethostbyname($host);
    $result['resolved_ip'] = $ip;
    $result['dns_ok'] = ($ip !== $host);
    $result['checkdnsrr'] = checkdnsrr($host, 'A');
    $errno = 0;
    $errstr = '';
    $start = microtime(true);
    $socket = @fsockopen($host, $port, $errno, $errstr, 10);
    $result['fsockopen'] = $socket ? 'ok' : 'failed';
    $result['errno'] = $errno;
    $result['errstr'] = $errstr;
    $result['duration_ms'] = round((microtime(true) - $start) * 1000, 2);
    if ($socket) {
        fclose($socket);
    }
    echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    exit;
}
require_once __DIR__ . '/includes/functions.php';
$pageTitle = 'Accueil';
$basePath = BASE_PATH;
$bodyClass = 'glovo-home';
$extraCSS = '<link rel="stylesheet" href="' . assetUrl('css/home.css') . '">';
$extraJS = '<script src="' . assetUrl('js/home.js') . '"></script>';

$categories = [];
$products = [];
$user = null;
$homepageError = null;

try {
    $db = getDB();
    $categories = $db->query('SELECT * FROM categories')->fetchAll();
    $products = $db->query('SELECT p.*, c.name as category_name FROM products p JOIN categories c ON p.category_id = c.id WHERE p.available = 1 LIMIT 12')->fetchAll();
    $user = currentUser();
} catch (Throwable $exception) {
    error_log('Homepage bootstrap failed: ' . $exception->getMessage());
    $homepageError = "La connexion a la base de donnees n'est pas encore disponible sur le serveur.";
}

$orderLink = $user ? 'client/order.php' : 'auth/register.php';
require_once __DIR__ . '/includes/header.php';

$categoryColors = [
    'Food' => ['#FF6B6B', 'fa-utensils'],
    'Drinks' => ['#4ECDC4', 'fa-glass-water'],
    'Groceries' => ['#95E86C', 'fa-basket-shopping'],
    'Pharmacy' => ['#C77DFF', 'fa-pills'],
    'Other' => ['#FFD166', 'fa-box'],
];
?>

<header class="glovo-header">
    <div class="glovo-header-inner">
        <a href="index.php" class="glovo-logo">
            <?php $brandVariant = 'header'; require __DIR__ . '/includes/brand-name.php'; ?>
        </a>

        <button type="button" class="glovo-location" id="location-btn">
            <i class="fas fa-location-dot"></i>
            <span class="glovo-location-text">
                <small>Livrer à</small>
                <strong id="location-label">Choisir une adresse</strong>
            </span>
            <i class="fas fa-chevron-down"></i>
        </button>

        <nav class="glovo-nav">
            <?php if ($user): ?>
                <a href="<?= $user['role'] === 'admin' ? 'admin/dashboard.php' : ($user['role'] === 'driver' ? 'driver/dashboard.php' : 'client/order.php') ?>" class="glovo-nav-link">
                    <i class="fas fa-user"></i> Mon compte
                </a>
            <?php else: ?>
                <a href="auth/login.php" class="glovo-nav-link">Connexion</a>
                <a href="auth/register.php" class="glovo-nav-btn">S'inscrire</a>
            <?php endif; ?>
        </nav>

        <button class="glovo-menu-btn" id="glovo-menu-btn"><i class="fas fa-bars"></i></button>
    </div>
</header>

<?php if ($homepageError !== null): ?>
<section style="padding: 16px 20px 0; background: transparent;">
    <div style="max-width: 1200px; margin: 0 auto; background: #fff3cd; color: #664d03; border: 1px solid #ffecb5; border-radius: 14px; padding: 14px 16px;">
        <?= sanitize($homepageError) ?> Verifie `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER` et `DB_PASS` dans Vercel.
    </div>
</section>
<?php endif; ?>

<section class="glovo-hero">
    <div class="hero-bg" aria-hidden="true">
        <div class="hero-mesh"></div>
        <div class="hero-orb hero-orb-a"></div>
        <div class="hero-orb hero-orb-b"></div>
        <div class="hero-orb hero-orb-c"></div>
        <div class="hero-shine"></div>
        <div class="hero-grid-pattern"></div>
    </div>

    <div class="glovo-hero-layout">
        <div class="glovo-hero-inner">
            <div class="glovo-hero-brand-wrap fade-in">
                <?php $brandVariant = 'hero'; require __DIR__ . '/includes/brand-name.php'; ?>
                <p class="glovo-hero-slogan">Livraison rapide · Tunisie</p>
            </div>

            <h1 class="fade-in">Tout ce dont vous avez besoin,<br><span>livré chez vous</span></h1>
            <p class="glovo-hero-desc fade-in">Nourriture, courses, pharmacie et plus — en quelques clics</p>

            <form class="glovo-search glovo-search-glass fade-in" id="glovo-search-form" action="<?= $orderLink ?>" method="get">
                <div class="glovo-search-box">
                    <i class="fas fa-magnifying-glass"></i>
                    <input type="text" name="q" placeholder="Que voulez-vous commander ?" autocomplete="off">
                </div>
                <div class="glovo-search-divider"></div>
                <div class="glovo-address-box">
                    <i class="fas fa-location-dot"></i>
                    <input type="text" id="hero-address" name="address" placeholder="Votre adresse de livraison" required>
                </div>
                <button type="submit" class="glovo-search-btn">
                    <span>Commander</span>
                    <i class="fas fa-arrow-right"></i>
                </button>
            </form>

            <div class="glovo-hero-pills fade-in">
                <div class="hero-pill"><i class="fas fa-bolt"></i> 25-40 min</div>
                <div class="hero-pill"><i class="fas fa-map-pin"></i> GPS live</div>
                <div class="hero-pill"><i class="fas fa-star"></i> 4.9/5</div>
            </div>
        </div>

        <div class="hero-bento fade-in" id="hero-bento">
            <?php $heroImgs = getHeroFoodImages(); ?>
            <div class="bento-card bento-main">
                <img src="<?= $heroImgs[0]['url'] ?>" alt="Pizza">
                <div class="bento-overlay"><span>🍕 Top restos</span></div>
            </div>
            <div class="bento-card bento-wide">
                <img src="<?= $heroImgs[1]['url'] ?>" alt="Burger">
                <div class="bento-overlay"><span>🍔 Burgers</span></div>
            </div>
            <div class="bento-card bento-square">
                <img src="<?= $heroImgs[2]['url'] ?>" alt="Plats">
                <div class="bento-overlay"><span>🥗 Frais</span></div>
            </div>
            <div class="bento-card bento-circle">
                <img src="<?= $heroImgs[3]['url'] ?>" alt="Livreur">
            </div>
            <div class="bento-float-badge">
                <i class="fas fa-motorcycle"></i>
                <div>
                    <strong>En route</strong>
                    <span>Suivi en direct</span>
                </div>
            </div>
        </div>
    </div>

    <div class="hero-wave" aria-hidden="true">
        <svg viewBox="0 0 1440 80" preserveAspectRatio="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M0,40 C360,90 720,0 1080,40 C1260,60 1380,50 1440,45 L1440,80 L0,80 Z" fill="#F5F6F8"/>
        </svg>
    </div>
</section>

<main class="glovo-main">
    <!-- Categories horizontal scroll -->
    <section class="glovo-section" id="categories">
        <div class="glovo-section-head">
            <h2>Catégories</h2>
            <a href="<?= $orderLink ?>">Voir tout <i class="fas fa-chevron-right"></i></a>
        </div>
        <div class="glovo-categories-scroll" id="categories-scroll">
            <?php foreach ($categories as $i => $cat):
                $color = $categoryColors[$cat['name']][0] ?? '#E5B04C';
            ?>
            <a href="<?= $orderLink ?>?category=<?= $cat['id'] ?>" class="glovo-cat-item fade-in">
                <div class="glovo-cat-icon glovo-cat-photo">
                    <img src="<?= getCategoryImageUrl($cat['name']) ?>" alt="<?= sanitize($cat['name']) ?>" loading="lazy">
                </div>
                <span><?= sanitize($cat['name']) ?></span>
            </a>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- Promo banners -->
    <section class="glovo-section">
        <div class="glovo-promos">
            <div class="glovo-promo-card promo-gold fade-in">
                <div class="glovo-promo-text">
                    <span class="glovo-promo-badge">Offre spéciale</span>
                    <h3>Livraison express</h3>
                    <p>Recevez votre commande en moins de 30 minutes</p>
                </div>
                <div class="glovo-promo-icon"><i class="fas fa-motorcycle"></i></div>
            </div>
            <div class="glovo-promo-card promo-blue fade-in">
                <div class="glovo-promo-text">
                    <span class="glovo-promo-badge">Nouveau</span>
                    <h3>Suivi en direct</h3>
                    <p>Suivez votre livreur sur la carte en temps réel</p>
                </div>
                <div class="glovo-promo-icon"><i class="fas fa-map-location-dot"></i></div>
            </div>
        </div>
    </section>

    <!-- Popular products - Glovo store cards -->
    <section class="glovo-section" id="products">
        <div class="glovo-section-head">
            <h2>Populaires près de vous</h2>
            <a href="<?= $orderLink ?>">Tout voir <i class="fas fa-chevron-right"></i></a>
        </div>
        <div class="glovo-stores-scroll" id="stores-scroll">
            <?php
            foreach ($products as $i => $product):
                $imgUrl = getProductImageUrl($product, $basePath);
                $deliveryMin = 25 + ($i % 3) * 5;
            ?>
            <a href="<?= $orderLink ?>" class="glovo-store-card fade-in">
                <div class="glovo-store-img">
                    <img src="<?= $imgUrl ?>" alt="<?= sanitize($product['name']) ?>" loading="lazy">
                    <?php if ($i < 3): ?>
                    <span class="glovo-store-badge">Top</span>
                    <?php endif; ?>
                </div>
                <div class="glovo-store-info">
                    <h3><?= sanitize($product['name']) ?></h3>
                    <p class="glovo-store-meta">
                        <span class="glovo-rating"><i class="fas fa-star"></i> 4.<?= 5 + ($i % 4) ?></span>
                        <span>·</span>
                        <span><?= $deliveryMin ?>-<?= $deliveryMin + 10 ?> min</span>
                    </p>
                    <p class="glovo-store-cat"><?= sanitize($product['category_name']) ?></p>
                    <p class="glovo-store-price">À partir de <strong><?= number_format($product['price'], 2) ?> DT</strong></p>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- All products grid -->
    <section class="glovo-section glovo-section-alt">
        <div class="glovo-section-head">
            <h2>Tous les produits</h2>
        </div>
        <div class="glovo-products-grid">
            <?php foreach ($products as $i => $product):
                $imgUrl = getProductImageUrl($product, $basePath);
            ?>
            <a href="<?= $orderLink ?>" class="glovo-product-card fade-in">
                <div class="glovo-product-img">
                    <img src="<?= $imgUrl ?>" alt="<?= sanitize($product['name']) ?>" loading="lazy">
                </div>
                <div class="glovo-product-body">
                    <span class="glovo-product-cat"><?= sanitize($product['category_name']) ?></span>
                    <h3><?= sanitize($product['name']) ?></h3>
                    <p><?= sanitize($product['description']) ?></p>
                    <div class="glovo-product-foot">
                        <strong><?= number_format($product['price'], 2) ?> DT</strong>
                        <span class="glovo-add-btn"><i class="fas fa-plus"></i></span>
                    </div>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
        <div class="glovo-section-cta">
            <a href="<?= $orderLink ?>" class="glovo-cta-btn">Voir le menu complet <i class="fas fa-arrow-right"></i></a>
        </div>
    </section>

    <!-- How it works -->
    <section class="glovo-section" id="features">
        <div class="glovo-section-head glovo-section-head-center">
            <h2>Comment ça marche ?</h2>
            <p>Commandez en 3 étapes simples</p>
        </div>
        <div class="glovo-steps">
            <div class="glovo-step fade-in">
                <div class="glovo-step-num">1</div>
                <div class="glovo-step-icon"><i class="fas fa-map-marker-alt"></i></div>
                <h3>Choisissez votre adresse</h3>
                <p>Entrez où vous voulez être livré en Tunisie</p>
            </div>
            <div class="glovo-step fade-in">
                <div class="glovo-step-num">2</div>
                <div class="glovo-step-icon"><i class="fas fa-shopping-bag"></i></div>
                <h3>Passez commande</h3>
                <p>Parcourez les catégories et ajoutez au panier</p>
            </div>
            <div class="glovo-step fade-in">
                <div class="glovo-step-num">3</div>
                <div class="glovo-step-icon"><i class="fas fa-motorcycle"></i></div>
                <h3>Suivez la livraison</h3>
                <p>Localisez votre livreur en temps réel sur la carte</p>
            </div>
        </div>
    </section>

    <!-- Bottom CTA -->
    <section class="glovo-app-cta fade-in">
        <div class="glovo-app-cta-inner">
            <?php $brandVariant = 'cta'; require __DIR__ . '/includes/brand-name.php'; ?>
            <div>
                <h2>Prêt à commander ?</h2>
                <p>Créez votre compte et profitez de la livraison express</p>
            </div>
            <?php if ($user): ?>
                <a href="<?= $orderLink ?>" class="glovo-cta-btn">Commander maintenant</a>
            <?php else: ?>
                <a href="auth/register.php" class="glovo-cta-btn">Commencer gratuitement</a>
            <?php endif; ?>
        </div>
    </section>
</main>

<footer class="glovo-footer">
    <div class="glovo-footer-inner">
        <div class="glovo-footer-brand">
            <?php $brandVariant = 'footer'; require __DIR__ . '/includes/brand-name.php'; ?>
            <p>Livraison rapide de nourriture, courses et bien plus en Tunisie.</p>
        </div>
        <div class="glovo-footer-links">
            <h4>Arsy Delivery</h4>
            <a href="#categories">Catégories</a>
            <a href="#products">Produits</a>
            <a href="<?= $orderLink ?>">Commander</a>
        </div>
        <div class="glovo-footer-links">
            <h4>Compte</h4>
            <a href="auth/login.php">Connexion</a>
            <a href="auth/register.php">Inscription</a>
        </div>
        <div class="glovo-footer-links">
            <h4>Contact</h4>
            <a href="tel:+21620000000"><i class="fas fa-phone"></i> +216 23 213 335</a>
            <a href="mailto:contact@arsy.com"><i class="fas fa-envelope"></i> arsy.delievry@gmail.com</a>
        </div>
    </div>
    <div class="glovo-footer-bottom">
        <p>&copy; <?= date('Y') ?> Arsy Delivery. Tous droits réservés.</p>
    </div>
</footer>

<!-- Location modal -->
<div class="glovo-modal" id="location-modal">
    <div class="glovo-modal-backdrop"></div>
    <div class="glovo-modal-box">
        <button type="button" class="glovo-modal-close" id="modal-close"><i class="fas fa-times"></i></button>
        <h3><i class="fas fa-location-dot"></i> Où livrer ?</h3>
        <p>Entrez votre adresse pour voir les options disponibles</p>
        <input type="text" id="modal-address" class="glovo-modal-input" placeholder="Ex: Avenue Habib Bourguiba, Tunis">
        <div id="location-msg" class="location-msg" hidden></div>
        <button type="button" class="glovo-cta-btn" id="modal-save" style="width:100%;margin-top:16px;">
            Confirmer l'adresse
        </button>
        <button type="button" class="glovo-locate-btn" id="modal-locate">
            <i class="fas fa-crosshairs"></i> Utiliser ma position
        </button>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
