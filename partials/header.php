<?php
$pageTitle = $pageTitle ?? 'SWD Shop';
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle) ?> | SWD Shop</title>
    <link rel="stylesheet" href="<?= e(site_url('assets/style.css')) ?>">
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
        </nav>
    </div>
</header>
<main class="container main-content">
