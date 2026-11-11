<?php
$pageTitle = $pageTitle ?? 'SWD Shop';
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle) ?> | SWD Shop</title>
    <script>
        try {
            const theme = localStorage.getItem('swd-shop-theme');
            if (theme === 'dark' || theme === 'light') document.documentElement.dataset.theme = theme;
        } catch (error) {}
    </script>
    <link rel="stylesheet" href="<?= e(site_url('assets/style.css')) ?>">
    <script src="<?= e(site_url('assets/theme.js')) ?>" defer></script>
</head>
<body>
<header class="site-header">
    <div class="container header-inner">
        <a class="brand" href="<?= e(site_url('index.php')) ?>" aria-label="SWD Shop home">
            <span class="brand-mark">S</span>
            <span>SWD<span class="brand-light"> Shop</span></span>
        </a>
        <nav class="header-nav" aria-label="Main navigation">
            <a href="<?= e(site_url('index.php')) ?>">Shop</a>
            <a class="cart-link" href="<?= e(site_url('cart.php')) ?>" aria-label="Shopping cart">
                Cart <span class="cart-badge"><?= cart_count() ?></span>
            </a>
            <button class="theme-toggle" type="button" data-theme-toggle aria-label="Switch to dark mode">
                <span aria-hidden="true" data-theme-icon>&#9790;</span>
                <span data-theme-label>Dark mode</span>
            </button>
        </nav>
    </div>
</header>
<main class="container main-content">
