    <?php if (!empty($extraJS)): ?><?= $extraJS ?><?php endif; ?>
    <script src="<?= function_exists('assetUrl') ? assetUrl('js/main.js') : (($basePath ?? BASE_PATH) . '/assets/js/main.js') ?>"></script>
</body>
</html>
