<?php
declare(strict_types=1);
require __DIR__ . '/_auth.php';
admin_require_login();

$validStatuses = ['pending', 'paid', 'shipped', 'delivered', 'cancelled'];
$statusInput = $_GET['status'] ?? '';
$status = is_string($statusInput) && in_array($statusInput, $validStatuses, true) ? $statusInput : '';
$fromInput = $_GET['from'] ?? '';
$toInput = $_GET['to'] ?? '';
$from = admin_valid_date($fromInput) ? $fromInput : '';
$to = admin_valid_date($toInput) ? $toInput : '';
$filterError = (($fromInput !== '' && $from === '') || ($toInput !== '' && $to === ''))
    ? 'One or more dates were invalid; those date filters were ignored.'
    : '';
if ($from !== '' && $to !== '' && $from > $to) {
    $filterError = 'The start date must be on or before the end date.';
    $from = '';
    $to = '';
}
$filters = [];
$params = [];
if ($status !== '') {
    $filters[] = 'o.status = :status';
    $params['status'] = $status;
}
if ($from !== '') {
    $filters[] = 'o.created_at >= :date_from';
    $params['date_from'] = $from . ' 00:00:00';
}
if ($to !== '') {
    $filters[] = 'o.created_at < DATE_ADD(:date_to, INTERVAL 1 DAY)';
    $params['date_to'] = $to;
}
$where = $filters === [] ? '' : 'WHERE ' . implode(' AND ', $filters);
$statement = $pdo->prepare(
    'SELECT o.id, o.reference, o.customer_name, o.email, o.status, o.total, o.created_at,
            COALESCE(SUM(oi.quantity), 0) AS item_count
     FROM orders o
     LEFT JOIN order_items oi ON oi.order_id = o.id
     ' . $where . '
     GROUP BY o.id, o.reference, o.customer_name, o.email, o.status, o.total, o.created_at
     ORDER BY o.created_at DESC, o.id DESC'
);
$statement->execute($params);
$orders = $statement->fetchAll();
$filteredTotal = array_sum(array_map(static fn (array $order): float => (float) $order['total'], $orders));

admin_header('Orders');
?>
<div class="admin-page-heading">
    <div>
        <p class="eyebrow">Order management</p>
        <h1>Orders</h1>
        <p class="admin-muted">Review customer orders and keep fulfilment moving.</p>
    </div>
    <div class="admin-heading-total"><span>Filtered total</span><strong><?= e(money($filteredTotal)) ?></strong></div>
</div>

<form class="admin-filter-form" method="get" action="<?= e(site_url('orders.php')) ?>">
    <label>Status
        <select class="select" name="status">
            <option value="">All statuses</option>
            <?php foreach ($validStatuses as $option): ?>
                <option value="<?= e($option) ?>" <?= $status === $option ? 'selected' : '' ?>><?= e(ucfirst($option)) ?></option>
            <?php endforeach; ?>
        </select>
    </label>
    <label>From date<input class="field" type="date" name="from" value="<?= e($from) ?>"></label>
    <label>To date<input class="field" type="date" name="to" value="<?= e($to) ?>"></label>
    <button class="button" type="submit">Apply filters</button>
    <a class="admin-clear-link" href="<?= e(site_url('orders.php')) ?>">Clear</a>
</form>

<?php if ($filterError !== ''): ?><p class="form-error" role="alert"><?= e($filterError) ?></p><?php endif; ?>
<section class="admin-panel">
    <div class="admin-section-heading">
        <h2><?= count($orders) ?> <?= count($orders) === 1 ? 'order' : 'orders' ?></h2>
        <span>Item counts show total units ordered</span>
    </div>
    <?php if ($orders === []): ?>
        <p class="admin-muted">No orders match these filters.</p>
    <?php else: ?>
        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead><tr><th>Reference</th><th>Customer</th><th>Items</th><th>Status</th><th>Total</th><th>Placed</th></tr></thead>
                <tbody>
                    <?php foreach ($orders as $order): ?>
                        <tr>
                            <td><a class="table-link" href="<?= e(site_url('order.php')) ?>?id=<?= (int) $order['id'] ?>"><?= e($order['reference']) ?></a></td>
                            <td><span><?= e($order['customer_name']) ?></span><small><?= e($order['email']) ?></small></td>
                            <td><?= (int) $order['item_count'] ?></td>
                            <td><span class="status-pill status-<?= e($order['status']) ?>"><?= e(ucfirst((string) $order['status'])) ?></span></td>
                            <td><?= e(money($order['total'])) ?></td>
                            <td><?= e(date('M j, Y H:i', strtotime((string) $order['created_at']))) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>
<?php admin_footer(); ?>
