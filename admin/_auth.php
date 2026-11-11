<?php
declare(strict_types=1);
require_once __DIR__ . '/../app/bootstrap.php';

function admin_require_login(): void
{
    if (($_SESSION['is_admin'] ?? false) !== true) {
        header('Location: ' . site_url('login.php'), true, 303);
        exit;
    }
}

function admin_password_hash(PDO $pdo): string
{
    $statement = $pdo->prepare('SELECT password_hash FROM admin_credentials WHERE id = :id LIMIT 1');
    $statement->execute(['id' => 1]);
    $hash = $statement->fetchColumn();
    if (is_string($hash) && $hash !== '') {
        return $hash;
    }

    $environmentHash = getenv('ADMIN_PASSWORD_HASH');
    return is_string($environmentHash) ? $environmentHash : '';
}

function admin_valid_date(mixed $value): bool
{
    if (!is_string($value) || preg_match('/\A\d{4}-\d{2}-\d{2}\z/', $value) !== 1) {
        return false;
    }

    $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
    $errors = DateTimeImmutable::getLastErrors();

    return $date !== false
        && ($errors === false || ($errors['warning_count'] === 0 && $errors['error_count'] === 0))
        && $date->format('Y-m-d') === $value;
}

function admin_header(string $title): void
{
    ?>
    <!doctype html>
    <html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title><?= e($title) ?> | SWD Shop Admin</title>
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
    <header class="admin-header">
        <div class="admin-shell admin-header-inner">
            <a class="admin-brand" href="<?= e(site_url('index.php')) ?>">SWD <span>Admin</span></a>
            <nav class="admin-nav" aria-label="Admin navigation">
                <a href="<?= e(site_url('index.php')) ?>">Overview</a>
                <a href="<?= e(site_url('orders.php')) ?>">Orders</a>
                <button class="theme-toggle" type="button" data-theme-toggle aria-label="Switch to dark mode">
                    <span aria-hidden="true" data-theme-icon>&#9790;</span>
                    <span data-theme-label>Dark mode</span>
                </button>
                <form method="post" action="<?= e(site_url('logout.php')) ?>">
                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                    <button class="admin-nav-button" type="submit">Sign out</button>
                </form>
            </nav>
        </div>
    </header>
    <main class="admin-shell admin-main">
    <?php
}

function admin_footer(): void
{
    ?>
    </main>
    </body>
    </html>
    <?php
}
