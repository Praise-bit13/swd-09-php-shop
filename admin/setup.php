<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$password = getenv('ADMIN_INITIAL_PASSWORD');
if (!is_string($password) || $password === '') {
    fwrite(STDERR, "Set ADMIN_INITIAL_PASSWORD temporarily before running this setup script.\n");
    exit(1);
}

require __DIR__ . '/_auth.php';

$hash = password_hash($password, PASSWORD_DEFAULT);
putenv('ADMIN_INITIAL_PASSWORD');
if (!is_string($hash)) {
    throw new RuntimeException('Could not hash the admin password.');
}

$pdo->exec(
    'CREATE TABLE IF NOT EXISTS admin_credentials (
        id TINYINT UNSIGNED NOT NULL,
        password_hash VARCHAR(255) NOT NULL,
        updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
);

$statement = $pdo->prepare(
    'INSERT INTO admin_credentials (id, password_hash)
     VALUES (1, :password_hash)
     ON DUPLICATE KEY UPDATE password_hash = :updated_password_hash'
);
$statement->execute([
    'password_hash' => $hash,
    'updated_password_hash' => $hash,
]);

$storedHash = admin_password_hash($pdo);
if ($storedHash === '' || !password_verify($password, $storedHash)) {
    throw new RuntimeException('The saved admin password hash could not be verified.');
}

$password = '';
fwrite(STDOUT, "Admin password hash saved to swd_shop.admin_credentials.\n");
