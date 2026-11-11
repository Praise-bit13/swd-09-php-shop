<?php
declare(strict_types=1);
require __DIR__ . '/app/cart.php';

final class CheckoutStockException extends RuntimeException
{
    public function __construct(string $message, public ?int $productId = null, public ?int $availableStock = null)
    {
        parent::__construct($message);
    }
}

$errors = [];
$old = ['name' => '', 'email' => '', 'phone' => '', 'address' => ''];
$orderItems = cart_items();
$cartNotices = cart_take_notices();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($old as $field => $value) {
        $input = $_POST[$field] ?? null;
        $old[$field] = is_string($input) ? trim($input) : '';
    }

    if (!csrf_is_valid($_POST['csrf_token'] ?? null)) {
        http_response_code(400);
        $errors[] = 'Your session expired. Refresh the page and try again.';
    }

    if ($old['name'] === '' || mb_strlen($old['name']) > 150) {
        $errors[] = 'Enter your name (up to 150 characters).';
    }
    if (!filter_var($old['email'], FILTER_VALIDATE_EMAIL) || strlen($old['email']) > 254) {
        $errors[] = 'Enter a valid email address.';
    }
    if ($old['phone'] === '' || mb_strlen($old['phone']) > 40) {
        $errors[] = 'Enter a phone number (up to 40 characters).';
    }
    if ($old['address'] === '' || mb_strlen($old['address']) > 1000) {
        $errors[] = 'Enter a delivery address (up to 1,000 characters).';
    }
    if ($orderItems === []) {
        $errors[] = 'Your cart is empty. Add a product before checking out.';
    }

    if ($errors === []) {
        try {
            $pdo->beginTransaction();

            $lockProduct = $pdo->prepare(
                'SELECT id, name, price, stock
                 FROM products
                 WHERE id = :id AND is_active = 1
                 FOR UPDATE'
            );
            $lockedItems = [];
            $totalKobo = 0;
            $itemsById = [];
            foreach ($orderItems as $item) {
                $itemsById[(int) $item['id']] = $item;
            }
            ksort($itemsById, SORT_NUMERIC);

            foreach ($itemsById as $id => $cartItem) {
                $lockProduct->execute(['id' => $id]);
                $product = $lockProduct->fetch();
                if (!$product) {
                    throw new CheckoutStockException(
                        $cartItem['name'] . ' is no longer available. Please review your cart.',
                        (int) $id,
                        0
                    );
                }

                $quantity = (int) $cartItem['quantity'];
                if ((int) $product['stock'] < $quantity) {
                    throw new CheckoutStockException(
                        $product['name'] . ' has only ' . max(0, (int) $product['stock']) .
                        ' left. Your cart quantity has been adjusted; please review it and try again.',
                        (int) $id,
                        max(0, (int) $product['stock'])
                    );
                }

                $unitPriceKobo = (int) round((float) $product['price'] * 100);
                $lineTotalKobo = $unitPriceKobo * $quantity;
                $totalKobo += $lineTotalKobo;
                $lockedItems[] = [
                    'id' => (int) $product['id'],
                    'name' => (string) $product['name'],
                    'quantity' => $quantity,
                    'unit_price' => number_format($unitPriceKobo / 100, 2, '.', ''),
                    'line_total' => number_format($lineTotalKobo / 100, 2, '.', ''),
                ];
            }

            $orderTotal = number_format($totalKobo / 100, 2, '.', '');
            $reference = 'ORD-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(2)));
            $insertOrder = $pdo->prepare(
                'INSERT INTO orders (reference, customer_name, email, phone, delivery_address, total, status)
                 VALUES (:reference, :customer_name, :email, :phone, :delivery_address, :total, :status)'
            );
            $insertOrder->execute([
                'reference' => $reference,
                'customer_name' => $old['name'],
                'email' => $old['email'],
                'phone' => $old['phone'],
                'delivery_address' => $old['address'],
                'total' => $orderTotal,
                'status' => 'pending',
            ]);
            $orderId = (int) $pdo->lastInsertId();

            $insertItem = $pdo->prepare(
                'INSERT INTO order_items (order_id, product_id, product_name, unit_price, quantity)
                 VALUES (:order_id, :product_id, :product_name, :unit_price, :quantity)'
            );
            $updateStock = $pdo->prepare(
                'UPDATE products
                 SET stock = stock - :quantity
                 WHERE id = :id AND stock >= :required_quantity'
            );

            foreach ($lockedItems as $item) {
                $insertItem->execute([
                    'order_id' => $orderId,
                    'product_id' => $item['id'],
                    'product_name' => $item['name'],
                    'unit_price' => $item['unit_price'],
                    'quantity' => $item['quantity'],
                ]);
                $updateStock->execute([
                    'quantity' => $item['quantity'],
                    'id' => $item['id'],
                    'required_quantity' => $item['quantity'],
                ]);

                if ($updateStock->rowCount() !== 1) {
                    throw new CheckoutStockException(
                        $item['name'] . ' stock changed during checkout. Please review your cart and try again.'
                    );
                }
            }

            $pdo->commit();
            $_SESSION['last_order_reference'] = $reference;
            cart_clear();
            header('Location: ' . site_url('order-success.php') . '?ref=' . rawurlencode($reference), true, 303);
            exit;
        } catch (CheckoutStockException $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            if ($exception->productId !== null) {
                if ($exception->availableStock !== null && $exception->availableStock > 0) {
                    $adjustment = cart_update($exception->productId, $exception->availableStock);
                    if ($adjustment !== null) {
                        cart_remember_notice($adjustment);
                    }
                } else {
                    cart_remove($exception->productId);
                }
            }
            $errors[] = $exception->getMessage();
            $orderItems = cart_items();
            $cartNotices = array_merge($cartNotices, cart_take_notices());
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log('Checkout failed: ' . $exception->getMessage());
            $errors[] = 'We could not place your order right now. Your cart is unchanged; please try again.';
        }
    }
}

$subtotal = cart_total($orderItems);
$pageTitle = 'Checkout';
require __DIR__ . '/partials/header.php';
?>
<div class="checkout-heading">
    <p class="eyebrow">A few details, then you’re done</p>
    <h1>Secure checkout</h1>
    <p class="intro">Share your delivery details and review your order before placing it.</p>
</div>

<?php foreach ($errors as $error): ?>
    <p class="form-error" role="alert"><?= e($error) ?></p>
<?php endforeach; ?>
<?php foreach ($cartNotices as $notice): ?>
    <p class="cart-notice" role="status"><?= e($notice) ?></p>
<?php endforeach; ?>

<?php if ($orderItems === []): ?>
    <div class="empty-state">
        <h2>Nothing to check out yet</h2>
        <p>Your cart is empty. Find something you love and it will be waiting here.</p>
        <a class="button" href="<?= e(site_url('index.php')) ?>">Continue shopping</a>
    </div>
<?php else: ?>
    <div class="checkout-layout">
        <section class="checkout-panel">
            <h2>Delivery details</h2>
            <form class="checkout-form" method="post" action="<?= e(site_url('checkout.php')) ?>">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <label class="checkout-field">
                    Full name
                    <input class="field" type="text" name="name" value="<?= e($old['name']) ?>" maxlength="150" autocomplete="name" required>
                </label>
                <div class="checkout-field-grid">
                    <label class="checkout-field">
                        Email address
                        <input class="field" type="email" name="email" value="<?= e($old['email']) ?>" maxlength="254" autocomplete="email" required>
                    </label>
                    <label class="checkout-field">
                        Phone number
                        <input class="field" type="tel" name="phone" value="<?= e($old['phone']) ?>" maxlength="40" autocomplete="tel" required>
                    </label>
                </div>
                <label class="checkout-field">
                    Delivery address
                    <textarea class="field address-field" name="address" maxlength="1000" autocomplete="street-address" rows="4" required><?= e($old['address']) ?></textarea>
                </label>
                <button class="button place-order-button" type="submit">Place order · <?= e(money($subtotal)) ?></button>
                <p class="checkout-assurance">Your order total is calculated from current product prices.</p>
            </form>
        </section>

        <aside class="checkout-panel order-summary" aria-label="Order summary">
            <h2>Order summary <span><?= count($orderItems) ?> <?= count($orderItems) === 1 ? 'item' : 'items' ?></span></h2>
            <ul class="summary-list">
                <?php foreach ($orderItems as $item): ?>
                    <li class="summary-item">
                        <span class="summary-quantity"><?= (int) $item['quantity'] ?></span>
                        <span class="summary-name"><?= e($item['name']) ?></span>
                        <strong><?= e(money($item['line_total'])) ?></strong>
                    </li>
                <?php endforeach; ?>
            </ul>
            <div class="summary-total"><span>Subtotal</span><strong><?= e(money($subtotal)) ?></strong></div>
            <p class="summary-delivery">Delivery arrangements will be confirmed after you place your order.</p>
            <a class="back-link" href="<?= e(site_url('cart.php')) ?>">&larr; Edit cart</a>
        </aside>
    </div>
<?php endif; ?>
<?php require __DIR__ . '/partials/footer.php'; ?>
