<?php
declare(strict_types=1);
require __DIR__ . '/app/bootstrap.php';

$reference = '';
$email = '';
$order = false;
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $referenceInput = $_POST['reference'] ?? null;
    $emailInput = $_POST['email'] ?? null;
    $reference = is_string($referenceInput) ? strtoupper(trim($referenceInput)) : '';
    $email = is_string($emailInput) ? trim($emailInput) : '';

    if ($reference === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Enter a valid order reference and email address.';
    } else {
        $statement = $pdo->prepare(
            'SELECT reference, status, created_at
             FROM orders
             WHERE reference = :reference AND email = :email
             LIMIT 1'
        );
        $statement->execute(['reference' => $reference, 'email' => $email]);
        $order = $statement->fetch();
        if (!$order) {
            $error = 'We couldn’t find an order matching those details.';
        }
    }
}
$pageTitle = 'Track your order';
require __DIR__ . '/partials/header.php';
?>
<section class="lookup-card">
    <p class="eyebrow">Your order, at a glance</p>
    <h1>Track your order</h1>
    <p class="intro">Enter the reference from your confirmation and the email used at checkout.</p>
    <?php if ($error !== ''): ?><p class="form-error" role="alert"><?= e($error) ?></p><?php endif; ?>
    <form class="lookup-form" method="post" action="<?= e(site_url('order-lookup.php')) ?>">
        <label>
            Order reference
            <input class="field" type="text" name="reference" value="<?= e($reference) ?>" placeholder="ORD-20261111-7F3K" maxlength="24" required>
        </label>
        <label>
            Email address
            <input class="field" type="email" name="email" value="<?= e($email) ?>" autocomplete="email" required>
        </label>
        <button class="button" type="submit">Find my order</button>
    </form>
    <?php if ($order): ?>
        <section class="lookup-result" aria-live="polite">
            <div><span>Order</span><strong><?= e($order['reference']) ?></strong></div>
            <div><span>Status</span><strong class="status-pill status-<?= e($order['status']) ?>"><?= e(ucfirst((string) $order['status'])) ?></strong></div>
            <div><span>Placed</span><strong><?= e(date('M j, Y', strtotime((string) $order['created_at']))) ?></strong></div>
        </section>
    <?php endif; ?>
</section>
<?php require __DIR__ . '/partials/footer.php'; ?>
