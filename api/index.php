<?php
// Point d'entrée pour Vercel (Serverless PHP)
// Vercel route toutes les requêtes dynamiques vers ce fichier grâce à vercel.json.

// On redirige l'exécution vers le front controller principal (qui sera utilisé partout ailleurs)
require __DIR__ . '/../public/index.php';
