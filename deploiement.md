# 🚀 Guide de Déploiement Arsy Delivery

Ce guide vous explique étape par étape comment déployer votre site **Arsy Delivery** sur internet (hébergement mutualisé, VPS, cPanel, LWS, Hostinger, o2switch, etc.).

---

## Étape 1 : Configuration des Fichiers de Production

Grâce à la centralisation des chemins effectuée, vous n'avez que **deux fichiers** à modifier pour que tout le site s'adapte à votre nom de domaine.

### 1. Fichier `config/database.php`
Ouvrez le fichier [config/database.php](file:///c:/xampp/htdocs/arsy%20delievry/config/database.php) et modifiez les paramètres de connexion à la base de données de votre hébergeur, ainsi que la section **Deployment Paths Configuration** :

```php
// Connexion Base de Données Production
define('DB_HOST', 'NOM_HOTE_DE_VOTRE_BASE'); // Souvent 'localhost' ou une IP
define('DB_NAME', 'NOM_DE_VOTRE_BDD');
define('DB_USER', 'UTILISATEUR_DE_VOTRE_BDD');
define('DB_PASS', 'MOT_DE_PASSE_DE_VOTRE_BDD');
define('DB_CHARSET', 'utf8mb4');

// ─── Deployment Paths Configuration ───
// Pour le déploiement sur internet dans le dossier racine de votre domaine (ex: https://arsydelivery.tn) :
define('BASE_PATH', '');
define('APP_URL', 'https://votre-site.com'); // Remplacez par votre vrai nom de domaine (sans / à la fin)
```

### 2. Fichier `config/google.php` (Optionnel)
Si vous utilisez Google Login, ouvrez [config/google.php](file:///c:/xampp/htdocs/arsy%20delievry/config/google.php) et remplacez les clés par vos clés Google Console de production. La redirection Google s'adaptera automatiquement grâce à `APP_URL`.

---

## Étape 2 : Importation de la Base de Données

Vous avez deux méthodes pour créer la base de données en production :

### Méthode A (Recommandée & Automatique)
1. Créez une base de données vide chez votre hébergeur.
2. Téléversez tous vos fichiers sur le serveur (voir Étape 3).
3. Accédez à l'URL suivante depuis votre navigateur : `https://votre-site.com/setup.php`
4. Le script va créer toutes les tables de la base de données et insérer les données de démo automatiquement.
5. **⚠️ TRÈS IMPORTANT :** Une fois le setup terminé, supprimez le fichier `setup.php` du serveur par sécurité.

### Méthode B (Manuelle via phpMyAdmin)
1. Allez sur votre phpMyAdmin local (`http://localhost/phpmyadmin`).
2. Sélectionnez la base `arsy_delivery` et cliquez sur **Exporter** (Format SQL).
3. Allez sur le phpMyAdmin de votre hébergeur, sélectionnez votre nouvelle base de données, et cliquez sur **Importer** pour téléverser votre fichier `.sql`.

---

## Étape 3 : Mise en ligne des fichiers

Téléversez l'intégralité du dossier `arsy delievry/` dans le répertoire principal de votre hébergement :
- Sur cPanel / Plesk : généralement dans le dossier `public_html/`.
- Via FTP (FileZilla) : connectez-vous avec vos identifiants FTP et glissez-déposez les fichiers.

**⚠️ Permissions de dossiers :**
Assurez-vous que le dossier `assets/images/products/` a les permissions en écriture (**755** ou **777** selon les hébergeurs) pour permettre l'ajout de photos de produits depuis l'espace admin.

---

## Étape 4 : Configuration Google Console (OAuth)

Si vous utilisez Google Sign-In :
1. Connectez-vous sur la [Google Cloud Console](https://console.cloud.google.com/).
2. Allez dans **API & Services** > **Identifiants**.
3. Modifiez vos identifiants OAuth 2.0.
4. Dans la section **Origines JavaScript autorisées**, ajoutez votre domaine : `https://votre-site.com`
5. Dans la section **URI de redirection autorisés**, ajoutez l'URL exacte : `https://votre-site.com/auth/google-callback.php`
6. Enregistrez les modifications. (La mise à jour peut prendre quelques minutes à être propagée par Google).
