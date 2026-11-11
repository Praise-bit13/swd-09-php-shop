<?php
declare(strict_types=1);
require __DIR__ . '/app/cart.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_is_valid($_POST['csrf_token'] ?? null)) {
        http_response_code(400);
        $error = 'Your session expired. Refresh the page and try again.';
    } else {
        $action = is_string($_POST['action'] ?? null) ? $_POST['action'] : '';
        if ($action === 'clear') {
            cart_clear();
            header('Location: ' . site_url('cart.php'));
            exit;
        }

        $idInput = $_POST['product_id'] ?? null;
        $id = is_int($idInput) || is_string($idInput)
            ? filter_var($idInput, FILTER_VALIDATE_INT)
            : false;
        if ($id === false || $id === null || $id < 1) {
            http_response_code(400);
            $error = 'Invalid product.';
        } elseif ($action === 'remove') {
            cart_remove($id);
            header('Location: ' . site_url('cart.php'));
            exit;
        } elseif ($action === 'update') {
            $quantityInput = $_POST['quantity'] ?? null;
            $quantity = is_int($quantityInput) || is_string($quantityInput)
                ? filter_var($quantityInput, FILTER_VALIDATE_INT)
                : false;
            if ($quantity === false || $quantity === null || $quantity < 1) {
                http_response_code(400);
                $error = 'Enter a valid quantity.';
            } else {
                $notice = cart_update($id, $quantity);
                if ($notice !== null) {
                    cart_remember_notice($notice);
                }
                header('Location: ' . site_url('cart.php'));
                exit;
            }
        } else {
            http_response_code(400);
            $error = 'Invalid cart action.';
        }
    }
}

$items = cart_items();
$notices = cart_take_notices();
$total = cart_total($items);
$pageTitle = 'Your cart';
require __DIR__ . '/partials/header.php';
?>
<p class="eyebrow">Your selection</p>
<h1>Your cart</h1>
<p class="intro">Review quantities and current prices before checkout.</p>

<?php if ($error !== ''): ?><p class="form-error" role="alert"><?= e($error) ?></p><?php endif; ?>
<?php foreach ($notices as $notice): ?>
    <p class="cart-notice" role="status"><?= e($notice) ?></p>
<?php endforeach; ?>

<?php if ($items === []): ?>
    <div class="empty-state">
        <h2>Your cart is empty</h2>
        <p>Browse the shop and add something you love.</p>
        <a class="button" href="<?= e(site_url('index.php')) ?>">Continue shopping</a>
    </div>
<?php else: ?>
    <div class="cart-table-wrap">
        <table class="cart-table">
            <thead>
                <tr>
                    <th scope="col">Product</th>
                    <th scope="col">Price</th>
                    <th scope="col">Quantity</th>
                    <th scope="col">Line total</th>
                    <th scope="col"><span class="visually-hidden">Actions</span></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $item): ?>
                    <tr>
                        <td>
                            <a class="cart-product" href="<?= e(site_url('product.php')) ?>?slug=<?= rawurlencode((string) $item['slug']) ?>">
                                <img src="<?= e($item['image_url']) ?>" alt="" loading="lazy">
                                <span><?= e($item['name']) ?></span>
                            </a>
                            <form id="cart-item-<?= (int) $item['id'] ?>" method="post" action="<?= e(site_url('cart.php')) ?>">
                                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                <input type="hidden" name="product_id" value="<?= (int) $item['id'] ?>">
                            </form>
                        </td>
                        <td data-label="Price"><?= e(money($item['price'])) ?></td>
                        <td data-label="Quantity">
                            <input class="field cart-quantity" type="number" name="quantity" form="cart-item-<?= (int) $item['id'] ?>" min="1" max="<?= (int) $item['stock'] ?>" value="<?= (int) $item['quantity'] ?>" aria-label="Quantity for <?= e($item['name']) ?>" required>
                        </td>
                        <td data-label="Line total"><?= e(money($item['line_total'])) ?></td>
                        <td class="cart-actions">
                            <button class="button button-small" type="submit" form="cart-item-<?= (int) $item['id'] ?>" name="action" value="update">Update</button>
                            <button class="text-button" type="submit" form="cart-item-<?= (int) $item['id'] ?>" name="action" value="remove" formnovalidate>Remove</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="cart-summary">
        <form method="post" action="<?= e(site_url('cart.php')) ?>">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <button class="text-button" type="submit" name="action" value="clear">Clear cart</button>
        </form>
        <div class="cart-total">
            <span>Subtotal</span>
            <strong><?= e(money($total)) ?></strong>
        </div>
        <a class="button checkout-button" href="<?= e(site_url('checkout.php')) ?>">Proceed to checkout</a>
    </div>
<?php endif; ?>
<?php require __DIR__ . '/partials/footer.php'; ?>
