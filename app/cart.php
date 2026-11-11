<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

function cart_remember_notice(string $notice): void
{
    $_SESSION['cart_notices'] ??= [];
    $_SESSION['cart_notices'][] = $notice;
}

function cart_take_notices(): array
{
    $notices = $_SESSION['cart_notices'] ?? [];
    unset($_SESSION['cart_notices']);

    return is_array($notices) ? $notices : [];
}

function cart_product(int $id): array|false
{
    $statement = $GLOBALS['pdo']->prepare(
        'SELECT id, name, slug, price, stock, image_url
         FROM products
         WHERE id = :id AND is_active = 1
         LIMIT 1'
    );
    $statement->execute(['id' => $id]);

    return $statement->fetch();
}

function cart_normalize(): array
{
    $rawCart = $_SESSION['cart'] ?? [];
    $cart = [];
    if (is_array($rawCart)) {
        foreach ($rawCart as $productId => $quantity) {
            $id = filter_var((string) $productId, FILTER_VALIDATE_INT);
            $qty = is_int($quantity) || is_string($quantity)
                ? filter_var($quantity, FILTER_VALIDATE_INT)
                : false;
            if ($id !== false && $id > 0 && $qty !== false && $qty > 0) {
                $cart[(int) $id] = (int) $qty;
            }
        }
    }

    $_SESSION['cart'] = $cart;
    return $cart;
}

function cart_add(int $id, int $qty): ?string
{
    if ($id < 1 || $qty < 1) {
        throw new InvalidArgumentException('Product and quantity must be positive integers.');
    }

    $product = cart_product($id);
    if ($product === false) {
        cart_remove($id);
        return 'This product is no longer available.';
    }

    $stock = max(0, (int) $product['stock']);
    if ($stock === 0) {
        cart_remove($id);
        return $product['name'] . ' is out of stock.';
    }

    $cart = cart_normalize();
    $storedQuantity = filter_var($cart[$id] ?? 0, FILTER_VALIDATE_INT);
    $currentQuantity = $storedQuantity === false ? 0 : max(0, $storedQuantity);
    $requestedTotal = $currentQuantity + $qty;
    $newQuantity = min($requestedTotal, $stock);
    $cart[$id] = $newQuantity;
    $_SESSION['cart'] = $cart;

    return $newQuantity < $requestedTotal
        ? 'The quantity for ' . $product['name'] . ' was adjusted to the available stock (' . $newQuantity . ').'
        : null;
}

function cart_update(int $id, int $qty): ?string
{
    if ($id < 1 || $qty < 1) {
        throw new InvalidArgumentException('Product and quantity must be positive integers.');
    }

    $product = cart_product($id);
    if ($product === false) {
        cart_remove($id);
        return 'This product is no longer available and was removed from your cart.';
    }

    $stock = max(0, (int) $product['stock']);
    if ($stock === 0) {
        cart_remove($id);
        return $product['name'] . ' is out of stock and was removed from your cart.';
    }

    $adjustedQuantity = min($qty, $stock);
    $cart = cart_normalize();
    $cart[$id] = $adjustedQuantity;
    $_SESSION['cart'] = $cart;

    return $adjustedQuantity < $qty
        ? 'The quantity for ' . $product['name'] . ' was adjusted to the available stock (' . $adjustedQuantity . ').'
        : null;
}

function cart_remove(int $id): void
{
    $cart = cart_normalize();
    unset($cart[$id]);
    $_SESSION['cart'] = $cart;
}

function cart_clear(): void
{
    $_SESSION['cart'] = [];
    unset($_SESSION['cart_notices']);
}

function cart_items(): array
{
    $quantities = cart_normalize();
    if ($quantities === []) {
        return [];
    }

    $placeholders = [];
    $params = [];
    foreach (array_keys($quantities) as $index => $id) {
        $placeholder = ':product_' . $index;
        $placeholders[] = $placeholder;
        $params['product_' . $index] = $id;
    }
    $statement = $GLOBALS['pdo']->prepare(
        'SELECT id, name, slug, price, stock, image_url
         FROM products
         WHERE is_active = 1 AND id IN (' . implode(', ', $placeholders) . ')'
    );
    $statement->execute($params);

    $products = [];
    foreach ($statement->fetchAll() as $product) {
        $products[(int) $product['id']] = $product;
    }

    $items = [];
    foreach ($quantities as $id => $quantity) {
        if (!isset($products[$id])) {
            cart_remove($id);
            cart_remember_notice('A product that is no longer available was removed from your cart.');
            continue;
        }

        $product = $products[$id];
        $stock = max(0, (int) $product['stock']);
        if ($stock === 0) {
            cart_remove($id);
            cart_remember_notice($product['name'] . ' is out of stock and was removed from your cart.');
            continue;
        }

        $adjusted = $quantity > $stock;
        if ($adjusted) {
            $quantity = $stock;
            $_SESSION['cart'][$id] = $quantity;
            cart_remember_notice($product['name'] . ' quantity was reduced to the available stock (' . $stock . ').');
        }

        $product['quantity'] = $quantity;
        $product['line_total'] = round((float) $product['price'] * $quantity, 2);
        $product['stock_check'] = $quantity <= $stock;
        $product['stock_adjusted'] = $adjusted;
        $items[] = $product;
    }

    return $items;
}

function cart_total(?array $items = null): float
{
    $items ??= cart_items();

    return round(array_sum(array_map(
        static fn (array $item): float => (float) $item['line_total'],
        $items
    )), 2);
}
