# Vercel Deployment Guide

Ce projet a été configuré pour être déployé **directement sur Vercel** en conservant son architecture PHP monolithique, grâce à `vercel-php`.

## Prérequis pour Vercel
1. Un compte Vercel.
2. Une base de données MySQL hébergée dans le Cloud (ex: TiDB, PlanetScale, Aiven, ou un VPS). Vercel n'héberge pas de bases de données MySQL.

## Étapes de déploiement
1. Poussez ce code sur un dépôt GitHub, GitLab ou Bitbucket.
2. Sur Vercel, créez un nouveau projet et importez ce dépôt.
3. Allez dans **Settings > Environment Variables** et ajoutez :
   - `DB_HOST` : L'adresse de votre base de données Cloud
   - `DB_NAME` : Le nom de la base de données
   - `DB_USER` : Votre utilisateur DB
   - `DB_PASS` : Votre mot de passe DB
   - `GOOGLE_CLIENT_ID` et `GOOGLE_CLIENT_SECRET` (optionnel)
4. Cliquez sur **Deploy**. 

## Fonctionnement sous le capot
- Le fichier `vercel.json` indique à Vercel d'utiliser l'environnement `vercel-php@0.6.1` pour tous les fichiers `.php`.
- Le fichier `config/database.php` détecte automatiquement Vercel et ajuste dynamiquement `BASE_PATH` (à la racine `""`) et `APP_URL`.
- Les requêtes vers `/assets/` sont automatiquement redirigées vers `/public/assets/` par Vercel.

## ⚠️ Limites du Serverless (Important)
Vercel est un environnement **Serverless**. Cela implique deux choses pour un projet PHP classique :
1. **Les images uploadées** : Les fichiers uploadés dans le dossier `/public/assets/images/products` disparaîtront au bout de quelques heures. Pour une vraie mise en production sur Vercel, il faudra brancher le code d'upload vers un service Cloud comme **AWS S3** ou **Cloudinary**.
2. **Les sessions** : Vercel gère mal les sessions PHP basées sur des fichiers locaux. Les utilisateurs pourraient être déconnectés aléatoirement. Pour régler ça, il faudrait stocker les sessions dans la base de données ou utiliser des JWT (Json Web Tokens).

*Si ces limites sont gênantes, il est préférable de déployer ce code sur **Railway**, **Render** ou un **VPS classique (Hostinger)**.*
