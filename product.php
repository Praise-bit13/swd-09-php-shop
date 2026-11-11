<?php
declare(strict_types=1);
require __DIR__ . '/app/bootstrap.php';

$slug = is_string($_GET['slug'] ?? null) ? trim($_GET['slug']) : '';
$statement = $pdo->prepare(
    'SELECT p.id, p.name, p.slug, p.description, p.price, p.stock, p.image_url, c.name AS category_name
     FROM products p
     LEFT JOIN categories c ON c.id = p.category_id
     WHERE p.slug = :slug AND p.is_active = 1
     LIMIT 1'
);
$statement->execute(['slug' => $slug]);
$product = $statement->fetch();

if (!$product) {
    http_response_code(404);
    $pageTitle = 'Product not found';
    require __DIR__ . '/partials/header.php';
    ?>
    <div class="empty-state">
        <h1>Product not found</h1>
        <p>This product may have moved or is no longer available.</p>
        <a class="button" href="<?= e(site_url('index.php')) ?>">Back to shop</a>
    </div>
    <?php
    require __DIR__ . '/partials/footer.php';
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_is_valid($_POST['csrf_token'] ?? null)) {
        http_response_code(400);
        $error = 'Your session expired. Refresh the page and try again.';
    } else {
        $quantity = filter_var($_POST['quantity'] ?? null, FILTER_VALIDATE_INT);
        if ($quantity === false || $quantity === null || $quantity < 1) {
            $error = 'Enter a valid quantity.';
        } else {
            $stockCheck = $pdo->prepare('SELECT stock FROM products WHERE id = :id AND is_active = 1');
            $stockCheck->execute(['id' => $product['id']]);
            $availableStock = $stockCheck->fetchColumn();
            $cart = $_SESSION['cart'] ?? [];
            $currentQuantity = is_array($cart) && isset($cart[$product['id']]) ? (int) $cart[$product['id']] : 0;

            if ($availableStock === false || $availableStock < 1) {
                $error = 'This product is out of stock.';
            } elseif ($quantity + $currentQuantity > (int) $availableStock) {
                $error = 'Only ' . max(0, (int) $availableStock - $currentQuantity) . ' more available.';
            } else {
                if (!is_array($cart)) {
                    $cart = [];
                }
                $cart[(int) $product['id']] = $currentQuantity + $quantity;
                $_SESSION['cart'] = $cart;
                header('Location: ' . site_url('product.php') . '?slug=' . rawurlencode((string) $product['slug']) . '&added=1');
                exit;
            }
        }
    }
}

$added = isset($_GET['added']);
$pageTitle = (string) $product['name'];
require __DIR__ . '/partials/header.php';
?>
<a class="back-link" href="<?= e(site_url('index.php')) ?>">&larr; Back to shop</a>
<section class="product-detail">
    <div class="detail-image-wrap">
        <img class="detail-image" src="<?= e($product['image_url']) ?>" alt="<?= e($product['name']) ?>">
    </div>
    <div class="detail-copy">
        <?php if ($product['category_name'] !== null): ?><p class="eyebrow"><?= e($product['category_name']) ?></p><?php endif; ?>
        <h1><?= e($product['name']) ?></h1>
        <p class="detail-price"><?= e(money($product['price'])) ?></p>
        <p class="detail-description"><?= e($product['description']) ?></p>
        <?php if ($added): ?><p class="stock-note">Added to your cart.</p><?php endif; ?>
        <?php if ($error !== ''): ?><p class="form-error" role="alert"><?= e($error) ?></p><?php endif; ?>
        <?php if ((int) $product['stock'] > 0): ?>
            <p class="stock-note"><?= (int) $product['stock'] ?> in stock</p>
        <?php else: ?>
            <p class="stock-note out">Out of stock</p>
        <?php endif; ?>
        <form class="add-form" method="post" action="<?= e(site_url('product.php')) ?>?slug=<?= rawurlencode((string) $product['slug']) ?>">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <label class="quantity-field">
                Quantity
                <input class="field" type="number" name="quantity" min="1" max="<?= (int) $product['stock'] ?>" value="1" required <?= (int) $product['stock'] < 1 ? 'disabled' : '' ?>>
            </label>
            <button class="button" type="submit" <?= (int) $product['stock'] < 1 ? 'disabled' : '' ?>>Add to cart</button>
        </form>
    </div>
</section>
<?php require __DIR__ . '/partials/footer.php'; ?>
