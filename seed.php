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
     SET category_id = :category_id, name = :name, description = :description, price = :price,
         stock = :stock, image_url = :image_url, is_active = 1
     WHERE id = :id'
);

$seeded = 0;
foreach ($catalog as $categoryName => $items) {
    $categorySlug = strtolower(str_replace(' ', '-', $categoryName));
    $findCategory->execute(['slug' => $categorySlug]);
    $categoryId = $findCategory->fetchColumn();
    if ($categoryId === false) {
        $insertCategory->execute(['name' => $categoryName, 'slug' => $categorySlug]);
        $categoryId = (int) $pdo->lastInsertId();
    }

    foreach ($items as [$name, $slug, $description, $price, $stock]) {
        $imageUrl = 'https://placehold.co/600x400?text=' . rawurlencode($name);
        $findProduct->execute(['slug' => $slug]);
        $productId = $findProduct->fetchColumn();
        $values = [
            'category_id' => $categoryId,
            'name' => $name,
            'slug' => $slug,
            'description' => $description,
            'price' => $price,
            'stock' => $stock,
            'image_url' => $imageUrl,
        ];

        if ($productId === false) {
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
