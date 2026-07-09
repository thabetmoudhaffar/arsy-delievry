<?php
// Point d'entrée pour Vercel (Serverless PHP)
// Vercel route toutes les requêtes dynamiques vers ce fichier grâce à vercel.json.

$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$requestUri = ltrim($requestUri, '/');

// Si la racine est demandée, charger la page d'accueil
if ($requestUri === '' || $requestUri === 'index.php') {
    require __DIR__ . '/../index.php';
    exit;
}

// Sinon, essayer de trouver le fichier PHP demandé
$file = __DIR__ . '/../' . $requestUri;
if (file_exists($file) && is_file($file) && pathinfo($file, PATHINFO_EXTENSION) === 'php') {
    require $file;
    exit;
}

// Fallback: 404
http_response_code(404);
echo "404 Not Found: " . htmlspecialchars($requestUri);
