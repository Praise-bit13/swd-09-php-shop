<?php
declare(strict_types=1);
require __DIR__ . '/_auth.php';
admin_require_login();

final class AdminOrderNotFoundException extends RuntimeException
{
}

$idInput = $_GET['id'] ?? $_POST['id'] ?? null;
$id = is_string($idInput) || is_int($idInput) ? filter_var($idInput, FILTER_VALIDATE_INT) : false;
if ($id === false || $id === null || $id < 1) {
    http_response_code(404);
    exit('Order not found.');
}

$allowedTransitions = [
    'pending' => ['paid', 'cancelled'],
    'paid' => ['shipped', 'cancelled'],
    'shipped' => ['delivered'],
    'delivered' => [],
    'cancelled' => [],
];
$error = '';
$notice = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $targetInput = $_POST['status'] ?? null;
    $target = is_string($targetInput) ? $targetInput : '';
    if (!csrf_is_valid($_POST['csrf_token'] ?? null)) {
        http_response_code(400);
        $error = 'Your session expired. Refresh the page and try again.';
    } elseif (!in_array($target, ['paid', 'shipped', 'delivered', 'cancelled'], true)) {
        http_response_code(400);
        $error = 'Choose a valid order status.';
    } else {
        try {
            $pdo->beginTransaction();
            $lockOrder = $pdo->prepare('SELECT id, status FROM orders WHERE id = :id FOR UPDATE');
            $lockOrder->execute(['id' => $id]);
            $lockedOrder = $lockOrder->fetch();
            if (!$lockedOrder) {
                throw new AdminOrderNotFoundException('Order not found.');
            }

            $currentStatus = (string) $lockedOrder['status'];
            if (!in_array($target, $allowedTransitions[$currentStatus] ?? [], true)) {
                throw new DomainException('That status change is not allowed.');
            }

            if ($target === 'cancelled') {
                $lockItems = $pdo->prepare(
                    'SELECT product_id, quantity
                     FROM order_items
                     WHERE order_id = :order_id
                    ORDER BY product_id
                    FOR UPDATE'
                );
                $lockItems->execute(['order_id' => $id]);
                $restoreStock = $pdo->prepare('UPDATE products SET stock = stock + :quantity WHERE id = :id');
                foreach ($lockItems->fetchAll() as $item) {
                    $restoreStock->execute([
                        'quantity' => (int) $item['quantity'],
                        'id' => (int) $item['product_id'],
                    ]);
                    if ($restoreStock->rowCount() !== 1) {
                        throw new RuntimeException('A product could not be restocked; the order was not cancelled.');
                    }
                }
            }

            $changeStatus = $pdo->prepare('UPDATE orders SET status = :status WHERE id = :id');
            $changeStatus->execute(['status' => $target, 'id' => $id]);
            $pdo->commit();
            header('Location: ' . site_url('order.php') . '?id=' . (int) $id . '&updated=1', true, 303);
            exit;
        } catch (DomainException $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $error = $exception->getMessage();
        } catch (AdminOrderNotFoundException $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            http_response_code(404);
            exit('Order not found.');
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log('Admin order update failed: ' . $exception->getMessage());
            $error = 'The order could not be updated. No changes were saved; please try again.';
        }
    }
}

$orderStatement = $pdo->prepare('SELECT * FROM orders WHERE id = :id LIMIT 1');
$orderStatement->execute(['id' => $id]);
$order = $orderStatement->fetch();
if (!$order) {
    http_response_code(404);
    exit('Order not found.');
}
$itemsStatement = $pdo->prepare(
    'SELECT product_id, product_name, unit_price, quantity
     FROM order_items
     WHERE order_id = :order_id
    ORDER BY product_id'
);
$itemsStatement->execute(['order_id' => $id]);
$items = $itemsStatement->fetchAll();
$currentStatus = (string) $order['status'];
$transitions = $allowedTransitions[$currentStatus] ?? [];
$updatedInput = $_GET['updated'] ?? null;
if ($updatedInput === '1') {
    $notice = 'Order status updated successfully.';
}

admin_header('Order ' . (string) $order['reference']);
?>
<a class="admin-back-link" href="<?= e(site_url('orders.php')) ?>">&larr; Back to orders</a>
<div class="admin-page-heading order-heading">
    <div>
        <p class="eyebrow">Order details</p>
        <h1><?= e($order['reference']) ?></h1>
        <p class="admin-muted">Placed <?= e(date('M j, Y \a\t H:i', strtotime((string) $order['created_at']))) ?></p>
    </div>
    <span class="status-pill status-large status-<?= e($currentStatus) ?>"><?= e(ucfirst($currentStatus)) ?></span>
</div>

<?php if ($error !== ''): ?><p class="form-error" role="alert"><?= e($error) ?></p><?php endif; ?>
<?php if ($notice !== ''): ?><p class="admin-success" role="status"><?= e($notice) ?></p><?php endif; ?>

<div class="admin-detail-grid">
    <section class="admin-panel">
        <div class="admin-section-heading"><h2>Items ordered</h2><span><?= count($items) ?> lines</span></div>
        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead><tr><th>Product</th><th>Unit price</th><th>Quantity</th><th>Line total</th></tr></thead>
                <tbody>
                    <?php foreach ($items as $item): ?>
                        <tr>
                            <td><?= e($item['product_name']) ?></td>
                            <td><?= e(money($item['unit_price'])) ?></td>
                            <td><?= (int) $item['quantity'] ?></td>
                            <td><?= e(money((float) $item['unit_price'] * (int) $item['quantity'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot><tr><th colspan="3">Order total</th><th><?= e(money($order['total'])) ?></th></tr></tfoot>
            </table>
        </div>
    </section>

    <aside class="admin-panel">
        <h2>Customer</h2>
        <dl class="customer-details">
            <dt>Name</dt><dd><?= e($order['customer_name']) ?></dd>
            <dt>Email</dt><dd><a href="mailto:<?= e($order['email']) ?>"><?= e($order['email']) ?></a></dd>
            <dt>Phone</dt><dd><a href="tel:<?= e($order['phone']) ?>"><?= e($order['phone']) ?></a></dd>
            <dt>Delivery address</dt><dd><?= nl2br(e($order['address'])) ?></dd>
        </dl>
        <hr class="admin-divider">
        <h2>Update status</h2>
        <?php if ($transitions === []): ?>
            <p class="admin-muted">This order is <?= e($currentStatus) ?> and has no further status changes.</p>
        <?php else: ?>
            <form class="admin-form" method="post" action="<?= e(site_url('order.php')) ?>?id=<?= (int) $id ?>">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="id" value="<?= (int) $id ?>">
                <label for="status">Next status</label>
                <select class="select" id="status" name="status" required>
                    <?php foreach ($transitions as $transition): ?>
                        <option value="<?= e($transition) ?>"><?= e(ucfirst($transition)) ?></option>
                    <?php endforeach; ?>
                </select>
                <?php if (in_array('cancelled', $transitions, true)): ?>
                    <p class="admin-warning">Cancelling restores the ordered quantities to product stock.</p>
                <?php endif; ?>
                <button class="button" type="submit">Save status</button>
            </form>
        <?php endif; ?>
    </aside>
</div>
<?php admin_footer(); ?>
