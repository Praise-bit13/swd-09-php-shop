<?php
declare(strict_types=1);
require __DIR__ . '/app/bootstrap.php';

$categories = $pdo->query('SELECT id, name, slug FROM categories ORDER BY name')->fetchAll();
$selectedCategory = is_string($_GET['category'] ?? null) ? trim($_GET['category']) : '';
$search = is_string($_GET['q'] ?? null) ? trim($_GET['q']) : '';
$sort = is_string($_GET['sort'] ?? null) ? $_GET['sort'] : 'newest';
$sortOptions = [
    'newest' => 'p.created_at DESC, p.id DESC',
    'price_asc' => 'p.price ASC, p.id DESC',
    'price_desc' => 'p.price DESC, p.id DESC',
];
if (!isset($sortOptions[$sort])) {
    $sort = 'newest';
}

$where = ['p.is_active = 1'];
$params = [];
if ($selectedCategory !== '') {
    $where[] = 'c.slug = :category';
    $params['category'] = $selectedCategory;
}
if ($search !== '') {
    $where[] = '(p.name LIKE :search_name OR p.description LIKE :search_description)';
    $params['search_name'] = '%' . $search . '%';
    $params['search_description'] = '%' . $search . '%';
}

$sql = 'SELECT p.id, p.name, p.slug, p.price, p.stock, p.image_url, c.name AS category_name
        FROM products p
        LEFT JOIN categories c ON c.id = p.category_id
        WHERE ' . implode(' AND ', $where) . '
        ORDER BY ' . $sortOptions[$sort];
$statement = $pdo->prepare($sql);
$statement->execute($params);
$products = $statement->fetchAll();

$pageTitle = 'Shop';
require __DIR__ . '/partials/header.php';
?>
<section class="shop-heading">
    <p class="eyebrow">Good things, thoughtfully chosen</p>
    <h1>Find your next favourite.</h1>
    <p class="intro">Explore everyday essentials and little things worth keeping.</p>
</section>

<form class="shop-toolbar" method="get" action="<?= e(site_url('index.php')) ?>">
    <?php if ($selectedCategory !== ''): ?>
        <input type="hidden" name="category" value="<?= e($selectedCategory) ?>">
    <?php endif; ?>
    <div class="search-form">
        <input class="field" type="search" name="q" value="<?= e($search) ?>" placeholder="Search products..." aria-label="Search products">
        <button class="button" type="submit">Search</button>
    </div>
    <select class="select" name="sort" aria-label="Sort products" onchange="this.form.submit()">
        <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>>Newest arrivals</option>
        <option value="price_asc" <?= $sort === 'price_asc' ? 'selected' : '' ?>>Price: low to high</option>
        <option value="price_desc" <?= $sort === 'price_desc' ? 'selected' : '' ?>>Price: high to low</option>
    </select>
</form>

<nav class="category-list" aria-label="Product categories">
    <a class="category-link <?= $selectedCategory === '' ? 'active' : '' ?>" href="<?= e(site_url('index.php')) ?>">All products</a>
    <?php foreach ($categories as $category): ?>
        <a class="category-link <?= $selectedCategory === (string) $category['slug'] ? 'active' : '' ?>"
           href="<?= e(site_url('index.php')) ?>?category=<?= rawurlencode((string) $category['slug']) ?><?= $search !== '' ? '&amp;q=' . rawurlencode($search) : '' ?>&amp;sort=<?= e($sort) ?>">
            <?= e($category['name']) ?>
        </a>
    <?php endforeach; ?>
</nav>

<div class="results-line">
    <span><?= count($products) ?> <?= count($products) === 1 ? 'product' : 'products' ?> found</span>
    <?php if ($search !== ''): ?><span>Results for “<?= e($search) ?>”</span><?php endif; ?>
</div>

<?php if ($products === []): ?>
    <div class="empty-state">
        <h2>No products found</h2>
        <p>Try another search or browse all products.</p>
    </div>
<?php else: ?>
    <section class="product-grid" aria-label="Products">
        <?php foreach ($products as $product): ?>
            <article class="product-card">
                <a class="product-image-wrap" href="<?= e(site_url('product.php')) ?>?slug=<?= rawurlencode((string) $product['slug']) ?>">
                    <img class="product-image" src="<?= e($product['image_url']) ?>" alt="<?= e($product['name']) ?>" loading="lazy">
                    <?php if ((int) $product['stock'] < 1): ?><span class="stock-badge">Out of stock</span><?php endif; ?>
                </a>
                <div class="product-info">
                    <?php if ($product['category_name'] !== null): ?><p class="product-category"><?= e($product['category_name']) ?></p><?php endif; ?>
                    <h2 class="product-name"><a href="<?= e(site_url('product.php')) ?>?slug=<?= rawurlencode((string) $product['slug']) ?>"><?= e($product['name']) ?></a></h2>
                    <p class="product-price"><?= e(money($product['price'])) ?></p>
                </div>
            </article>
        <?php endforeach; ?>
    </section>
<?php endif; ?>
<?php require __DIR__ . '/partials/footer.php'; ?>
