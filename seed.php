<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit("Not found\n");
}

require __DIR__ . '/app/bootstrap.php';

$catalog = [
    'Home & Living' => [
        ['Linen Cushion Cover', 'linen-cushion-cover', 'A soft, breathable linen cushion cover in a relaxed natural shade. A simple way to bring warmth and texture to your living space.', 12500, 18],
        ['Ceramic Table Vase', 'ceramic-table-vase', 'A hand-finished ceramic vase with a clean silhouette, perfect for fresh stems or as a sculptural accent.', 18900, 11],
        ['Woven Storage Basket', 'woven-storage-basket', 'A sturdy woven basket for keeping throws, toys, or everyday essentials neatly tucked away.', 24500, 7],
    ],
    'Electronics' => [
        ['Wireless Bluetooth Speaker', 'wireless-bluetooth-speaker', 'A compact Bluetooth speaker with rich sound, simple controls, and a battery made for listening on the go.', 39500, 14],
        ['LED Desk Lamp', 'led-desk-lamp', 'An adjustable LED desk lamp with warm, even light for focused work and comfortable late-night reading.', 28900, 9],
        ['USB-C Fast Charger', 'usb-c-fast-charger', 'A pocket-sized USB-C wall charger designed for quick, reliable charging at home or while travelling.', 15500, 0],
    ],
    'Fashion' => [
        ['Everyday Canvas Tote', 'everyday-canvas-tote', 'A durable cotton-canvas tote with roomy handles for market trips, books, and daily essentials.', 9800, 25],
        ['Classic Leather Wallet', 'classic-leather-wallet', 'A slim, thoughtfully organised wallet crafted from supple leather with space for your daily cards and notes.', 32000, 8],
        ['Minimalist Wristwatch', 'minimalist-wristwatch', 'A versatile minimalist watch with a clear dial and comfortable strap, made to complement any everyday look.', 54500, 5],
    ],
    'Books & Paper' => [
        ['The Art of Slow Living', 'the-art-of-slow-living', 'A thoughtful guide to making more room for calm, creativity, and meaningful rituals in your everyday routine.', 11200, 16],
        ['Daily Notes Journal', 'daily-notes-journal', 'A lay-flat, 160-page journal with smooth paper for plans, reflections, sketches, and everything in between.', 7400, 30],
        ['Creative Ideas Card Set', 'creative-ideas-card-set', 'A set of practical prompts to help spark fresh ideas, build a creative habit, and get past a blank page.', 5600, 12],
    ],
    'Kitchen' => [
        ['Insulated Travel Mug', 'insulated-travel-mug', 'A double-wall insulated travel mug that keeps drinks at the right temperature on your commute or weekend walk.', 13900, 19],
        ['Acacia Serving Board', 'acacia-serving-board', 'A naturally grained acacia board for serving bread, cheese, and small bites with an easy, timeless style.', 21500, 6],
        ['Pour-Over Coffee Dripper', 'pour-over-coffee-dripper', 'A reusable ceramic pour-over dripper for a balanced cup and a slower, more satisfying morning ritual.', 16800, 10],
    ],
    'Phones' => [
        ['Smartphone 128GB', 'smartphone-128gb', 'A responsive everyday smartphone with a vivid display, dependable all-day battery, dual-camera system, and 128GB of storage for photos and apps.', 249900, 7],
        ['20,000mAh Power Bank', '20000mah-power-bank', 'A high-capacity portable power bank with dual USB outputs, USB-C input, and built-in charge indicators for longer days away from a wall socket.', 24500, 15],
        ['Shockproof Phone Case', 'shockproof-phone-case', 'A flexible, grippy phone case with raised edges around the screen and camera to help protect against everyday bumps and scratches.', 7500, 22],
    ],
    'Laptops' => [
        ['14-inch Everyday Laptop', '14-inch-everyday-laptop', 'A lightweight 14-inch laptop with an 8GB memory and 256GB solid-state drive configuration suited to study, office work, and everyday browsing.', 429000, 4],
        ['Padded 14-inch Laptop Sleeve', 'padded-14-inch-laptop-sleeve', 'A slim padded sleeve with a soft lining, secure zip closure, and front document pocket for carrying a laptop between home, class, and work.', 18500, 10],
        ['USB-C Laptop Dock', 'usb-c-laptop-dock', 'A compact USB-C dock that adds HDMI, USB-A, and card-reader connections to compatible laptops while keeping your desk setup tidy.', 35900, 8],
    ],
    'Accessories' => [
        ['Wireless Bluetooth Earbuds', 'wireless-bluetooth-earbuds', 'Comfortable true-wireless earbuds with touch controls, a pocket charging case, and a clear microphone for music and calls on the move.', 45000, 12],
        ['Rechargeable Wireless Mouse', 'rechargeable-wireless-mouse', 'A quiet-click wireless mouse with a rechargeable battery, adjustable sensitivity, and an ergonomic shape for everyday desk work.', 12900, 15],
        ['Braided USB-C Cable', 'braided-usb-c-cable', 'A durable one-metre braided USB-C cable for charging and data transfer, with reinforced connectors designed for everyday use.', 5900, 30],
    ],
];

$productPhotos = [
    'linen-cushion-cover' => '1584100936595-c0654b55a2e2',
    'ceramic-table-vase' => '1578500494198-246f612d3b3d',
    'woven-storage-basket' => '1590874103328-eac38a683ce7',
    'wireless-bluetooth-speaker' => '1608043152269-423dbba4e7e1',
    'led-desk-lamp' => '1507473885765-e6ed057f782c',
    'usb-c-fast-charger' => '1518770660439-4636190af475',
    'everyday-canvas-tote' => '1544816155-12df9643f363',
    'classic-leather-wallet' => '1627123424574-724758594e93',
    'minimalist-wristwatch' => '1523275335684-37898b6baf30',
    'the-art-of-slow-living' => '1544947950-fa07a98d237f',
    'daily-notes-journal' => '1517842645767-c639042777db',
    'creative-ideas-card-set' => '1541961017774-22349e4a1262',
    'insulated-travel-mug' => '1514228742587-6b1558fcca3d',
    'acacia-serving-board' => '1547592180-85f173990554',
    'pour-over-coffee-dripper' => '1495474472287-4d71bcdd2085',
    'smartphone-128gb' => '1511707171634-5f897ff02aa9',
    '20000mah-power-bank' => '1609091839311-d5365f9ff1c5',
    'shockproof-phone-case' => '1598327105666-5b89351aff97',
    '14-inch-everyday-laptop' => '1498050108023-c5249f4df085',
    'padded-14-inch-laptop-sleeve' => '1553062407-98eeb64c6a62',
    'usb-c-laptop-dock' => '1517336714731-489689fd1ca8',
    'wireless-bluetooth-earbuds' => '1606220945770-b5b6c2c55bf1',
    'rechargeable-wireless-mouse' => '1527814050087-3793815479db',
    'braided-usb-c-cable' => '1603398938378-e54eab446dde',
];

$pdo->beginTransaction();

$findCategory = $pdo->prepare('SELECT id FROM categories WHERE slug = :slug LIMIT 1');
$insertCategory = $pdo->prepare('INSERT INTO categories (name, slug) VALUES (:name, :slug)');
$findProduct = $pdo->prepare('SELECT id FROM products WHERE slug = :slug LIMIT 1');
$insertProduct = $pdo->prepare(
    'INSERT INTO products (category_id, name, slug, description, price, stock, image_url, is_active)
     VALUES (:category_id, :name, :slug, :description, :price, :stock, :image_url, 1)'
);
$updateProduct = $pdo->prepare(
    'UPDATE products
     SET category_id = :category_id, name = :name, slug = :slug, description = :description, price = :price,
         image_url = :image_url, is_active = 1
     WHERE id = :id'
);

$seeded = 0;
foreach ($catalog as $categoryName => $items) {
    $categorySlug = trim((string) preg_replace('/[^a-z0-9]+/', '-', strtolower($categoryName)), '-');
    $findCategory->execute(['slug' => $categorySlug]);
    $categoryId = $findCategory->fetchColumn();
    if ($categoryId === false) {
        $insertCategory->execute(['name' => $categoryName, 'slug' => $categorySlug]);
        $categoryId = (int) $pdo->lastInsertId();
    }

    foreach ($items as [$name, $slug, $description, $price, $stock]) {
        if (!isset($productPhotos[$slug])) {
            throw new LogicException('No product image is configured for ' . $slug . '.');
        }
        $imageUrl = 'https://images.unsplash.com/photo-' . $productPhotos[$slug]
            . '?auto=format&fit=crop&w=800&q=85';
        $findProduct->execute(['slug' => $slug]);
        $productId = $findProduct->fetchColumn();
        $values = [
            'category_id' => $categoryId,
            'name' => $name,
            'slug' => $slug,
            'description' => $description,
            'price' => $price,
            'image_url' => $imageUrl,
        ];

        if ($productId === false) {
            $values['stock'] = $stock;
            $insertProduct->execute($values);
        } else {
            $values['id'] = (int) $productId;
            $updateProduct->execute($values);
        }
        $seeded++;
    }
}

$pdo->commit();
echo "Seeded {$seeded} products across " . count($catalog) . " categories.\n";
