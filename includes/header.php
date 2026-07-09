<?php
$basePath = BASE_PATH;
$pageTitle = $pageTitle ?? 'Arsy Delivery';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= sanitize($pageTitle) ?> - Arsy Delivery</title>
    <link rel="stylesheet" href="<?= $basePath ?>/public/assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <?php if (!empty($extraCSS)): ?><?= $extraCSS ?><?php endif; ?>
    <script>window.BASE_PATH = '<?= BASE_PATH ?>';</script>
</head>
<body<?= !empty($bodyClass) ? ' class="' . sanitize($bodyClass) . '"' : '' ?>>
    <?php if (empty($bodyClass) || !in_array($bodyClass, ['auth-body', 'glovo-home'])): ?>
    <div class="bg-animated"></div>
    <div class="particles"></div>
    <?php endif; ?>
