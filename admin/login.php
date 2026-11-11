<?php
declare(strict_types=1);
require __DIR__ . '/_auth.php';

if (($_SESSION['is_admin'] ?? false) === true) {
    header('Location: ' . site_url('index.php'), true, 303);
    exit;
}

$error = '';
$hash = admin_password_hash($pdo);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = is_string($_POST['password'] ?? null) ? $_POST['password'] : '';
    if (!csrf_is_valid($_POST['csrf_token'] ?? null)) {
        http_response_code(400);
        $error = 'Your session expired. Refresh the page and try again.';
    } elseif ($hash === '' || !password_verify($password, $hash)) {
        $error = $hash === ''
            ? 'Admin sign-in is not configured. Follow the setup steps in README.md.'
            : 'The password was not recognised.';
    } else {
        session_regenerate_id(true);
        $_SESSION['is_admin'] = true;
        header('Location: ' . site_url('index.php'), true, 303);
        exit;
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin sign in | SWD Shop</title>
    <script>
        try {
            const theme = localStorage.getItem('swd-shop-theme');
            if (theme === 'dark' || theme === 'light') document.documentElement.dataset.theme = theme;
        } catch (error) {}
    </script>
    <link rel="stylesheet" href="<?= e(site_url('../assets/style.css')) ?>">
    <link rel="stylesheet" href="<?= e(site_url('../assets/admin.css')) ?>">
    <script src="<?= e(site_url('../assets/theme.js')) ?>" defer></script>
</head>
<body class="admin-body">
<main class="admin-login-shell">
    <button class="theme-toggle login-theme-toggle" type="button" data-theme-toggle aria-label="Switch to dark mode">
        <span aria-hidden="true" data-theme-icon>&#9790;</span>
        <span data-theme-label>Dark mode</span>
    </button>
    <a class="admin-brand login-brand" href="<?= e(site_url('../index.php')) ?>">SWD <span>Shop admin</span></a>
    <section class="admin-panel login-panel">
        <p class="eyebrow">Store management</p>
        <h1>Welcome back.</h1>
        <p class="admin-muted">Sign in to manage orders and monitor your shop.</p>
        <?php if ($hash === '' && $error === ''): ?>
            <p class="admin-warning" role="status">Admin access needs setup. Follow the credential setup steps in README.md.</p>
        <?php endif; ?>
        <?php if ($error !== ''): ?><p class="form-error" role="alert"><?= e($error) ?></p><?php endif; ?>
        <form class="admin-form" method="post" action="<?= e(site_url('login.php')) ?>">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <label>
                Admin password
                <input class="field" type="password" name="password" autocomplete="current-password" required autofocus>
            </label>
            <button class="button" type="submit">Sign in</button>
        </form>
    </section>
</main>
</body>
</html>
