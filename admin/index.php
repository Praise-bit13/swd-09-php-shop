<?php
declare(strict_types=1);
require __DIR__ . '/_auth.php';
admin_require_login();

$salesStatement = $pdo->query(
    "SELECT COALESCE(SUM(total), 0)
     FROM orders
     WHERE status IN ('paid', 'shipped', 'delivered')
       AND created_at >= CURDATE()
       AND created_at < CURDATE() + INTERVAL 1 DAY"
);
$todaySales = $salesStatement->fetchColumn();
$statusCounts = $pdo->query(
    'SELECT status, COUNT(*) AS order_count
     FROM orders
     GROUP BY status
     ORDER BY status'
)->fetchAll();
$topProducts = $pdo->query(
    "SELECT oi.product_name, SUM(oi.quantity) AS quantity_sold
     FROM order_items oi
     INNER JOIN orders o ON o.id = oi.order_id
     WHERE o.status <> 'cancelled'
     GROUP BY oi.product_id, oi.product_name
     ORDER BY quantity_sold DESC, oi.product_name ASC
     LIMIT 5"
)->fetchAll();
$recentOrders = $pdo->query(
    'SELECT id, reference, customer_name, total, status, created_at
     FROM orders
     ORDER BY created_at DESC, id DESC
     LIMIT 6'
)->fetchAll();

admin_header('Overview');
?>
<div class="admin-page-heading">
    <div>
        <p class="eyebrow">Store performance</p>
        <h1>Overview</h1>
        <p class="admin-muted">A clear view of what’s happening with your shop today.</p>
    </div>
    <a class="button" href="<?= e(site_url('orders.php')) ?>">Manage orders</a>
</div>

<section class="admin-stats" aria-label="Store statistics">
    <article class="admin-stat-card">
        <span>Sales today</span>
        <strong><?= e(money($todaySales)) ?></strong>
        <small>Paid, shipped and delivered orders</small>
    </article>
    <article class="admin-stat-card">
        <span>Orders today</span>
        <strong>
            <?php
            $todayOrdersStatement = $pdo->query(
                'SELECT COUNT(*) FROM orders WHERE created_at >= CURDATE() AND created_at < CURDATE() + INTERVAL 1 DAY'
            );
            echo (int) $todayOrdersStatement->fetchColumn();
            ?>
        </strong>
        <small>All statuses</small>
    </article>
</section>

<div class="admin-dashboard-grid">
    <section class="admin-panel">
        <div class="admin-section-heading"><h2>Orders by status</h2></div>
        <?php if ($statusCounts === []): ?>
            <p class="admin-muted">No orders have been placed yet.</p>
        <?php else: ?>
            <div class="status-count-list">
                <?php foreach ($statusCounts as $row): ?>
                    <a href="<?= e(site_url('orders.php')) ?>?status=<?= rawurlencode((string) $row['status']) ?>">
                        <span class="status-pill status-<?= e($row['status']) ?>"><?= e(ucfirst((string) $row['status'])) ?></span>
                        <strong><?= (int) $row['order_count'] ?></strong>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

    <section class="admin-panel">
        <div class="admin-section-heading"><h2>Top products</h2><span>By units sold</span></div>
        <?php if ($topProducts === []): ?>
            <p class="admin-muted">Sales will appear here after orders are placed.</p>
        <?php else: ?>
            <ol class="top-products">
                <?php foreach ($topProducts as $product): ?>
                    <li><span><?= e($product['product_name']) ?></span><strong><?= (int) $product['quantity_sold'] ?></strong></li>
                <?php endforeach; ?>
            </ol>
        <?php endif; ?>
    </section>
</div>

<section class="admin-panel recent-orders">
    <div class="admin-section-heading"><h2>Recent orders</h2><a href="<?= e(site_url('orders.php')) ?>">View all &rarr;</a></div>
    <?php if ($recentOrders === []): ?>
        <p class="admin-muted">New orders will show up here.</p>
    <?php else: ?>
        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead><tr><th>Reference</th><th>Customer</th><th>Status</th><th>Total</th><th>Date</th></tr></thead>
                <tbody>
                    <?php foreach ($recentOrders as $order): ?>
                        <tr>
                            <td><a class="table-link" href="<?= e(site_url('order.php')) ?>?id=<?= (int) $order['id'] ?>"><?= e($order['reference']) ?></a></td>
                            <td><?= e($order['customer_name']) ?></td>
                            <td><span class="status-pill status-<?= e($order['status']) ?>"><?= e(ucfirst((string) $order['status'])) ?></span></td>
                            <td><?= e(money($order['total'])) ?></td>
                            <td><?= e(date('M j, Y', strtotime((string) $order['created_at']))) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>
<?php admin_footer(); ?>
