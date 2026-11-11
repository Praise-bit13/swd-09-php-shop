<?php
declare(strict_types=1);
require __DIR__ . '/app/bootstrap.php';

$referenceInput = $_GET['ref'] ?? null;
$reference = is_string($referenceInput) ? $referenceInput : '';
$order = false;
if (
    preg_match('/\AORD-\d{8}-[A-F0-9]{4}\z/', $reference) === 1
    && hash_equals((string) ($_SESSION['last_order_reference'] ?? ''), $reference)
) {
    $statement = $pdo->prepare(
        'SELECT reference, customer_name, total
         FROM orders
         WHERE reference = :reference
         LIMIT 1'
    );
    $statement->execute(['reference' => $reference]);
    $order = $statement->fetch();
}

if (!$order) {
    http_response_code(404);
}
$pageTitle = $order ? 'Order confirmed' : 'Order not found';
require __DIR__ . '/partials/header.php';
?>
<?php if ($order): ?>
    <section class="success-card">
        <div class="success-icon" aria-hidden="true">&#10003;</div>
        <p class="eyebrow">Order received</p>
        <h1>Thank you, <?= e($order['customer_name']) ?>.</h1>
        <p class="success-copy">Your order has been placed. Keep this reference for your records; we’ll be in touch about delivery.</p>
        <div class="success-details">
            <div><span>Order reference</span><strong><?= e($order['reference']) ?></strong></div>
            <div><span>Order total</span><strong><?= e(money($order['total'])) ?></strong></div>
        </div>
        <a class="button" href="<?= e(site_url('index.php')) ?>">Continue shopping</a>
        <p class="success-copy"><a href="<?= e(site_url('order-lookup.php')) ?>">Look up this order later &rarr;</a></p>
    </section>
<?php else: ?>
    <div class="empty-state">
        <h1>Order not found</h1>
        <p>We couldn’t find an order matching that reference.</p>
        <a class="button" href="<?= e(site_url('index.php')) ?>">Back to shop</a>
    </div>
<?php endif; ?>
<?php require __DIR__ . '/partials/footer.php'; ?>
