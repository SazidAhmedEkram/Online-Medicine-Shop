<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?= e(csrf_token()) ?>">
    <title><?= e($title ?? APP_NAME) ?> | <?= e(APP_NAME) ?></title>
    <link rel="stylesheet" href="<?= asset('assets/css/style.css') ?>">
</head>
<body data-app-url="<?= e(app_url()) ?>">
    <header class="site-header">
        <a class="brand" href="<?= url('/') ?>">
            <span class="brand-mark">OMS</span>
            <span><?= e(APP_NAME) ?></span>
        </a>
        <button class="nav-toggle" type="button" data-nav-toggle aria-label="Toggle navigation">Menu</button>
        <nav class="nav" data-nav>
            <a href="<?= url('/') ?>">Home</a>
            <?php if ($user_role === "admin"): ?>
                <a href="<?= url('/admin') ?>">Dashboard</a>
                <a href="<?= url('/admin/medicines') ?>">Medicines</a>
                <a href="<?= url('/admin/categories') ?>">Categories</a>
                <a href="<?= url('/admin/orders') ?>">Orders</a>
                <a href="<?= url('/admin/history') ?>">History</a>
                <a href="<?= url('/admin/customers') ?>">Customers</a>
            <?php elseif ($user_role === "customer"): ?>
                <a href="<?= url('/cart') ?>">Cart <span class="cart-count" data-cart-count><?= e($cart_count) ?></span></a>
            <?php endif; ?>

            <?php if ($user_logged_in): ?>
                <a href="<?= url('/profile') ?>"><?= e($user_name ?: 'Profile') ?></a>
                <form class="nav-form" method="post" action="<?= url('/logout') ?>">
                    <?= csrf_field() ?>
                    <button type="submit">Logout</button>
                </form>
            <?php else: ?>
                <a href="<?= url('/login') ?>">Login</a>
                <a class="nav-cta" href="<?= url('/register') ?>">Register</a>
            <?php endif; ?>
        </nav>
    </header>

    <main class="page">
        <?php if ($flash_success): ?>
            <div class="alert success"><?= e($flash_success) ?></div>
        <?php endif; ?>
        <?php if ($flash_error): ?>
            <div class="alert error"><?= e($flash_error) ?></div>
        <?php endif; ?>
