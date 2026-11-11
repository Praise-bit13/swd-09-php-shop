# SWD Shop

A PHP 8 and MySQL shop for XAMPP, with a session cart, transactional checkout, order lookup, and a password-protected order admin.

## Requirements

- PHP 8.0+ with `pdo_mysql`
- MySQL or MariaDB using InnoDB
- XAMPP Apache and MySQL

## Setup

1. Copy the project to `C:\xampp\htdocs\swd\09-php-shop`.
2. Start Apache and MySQL from the XAMPP Control Panel.
3. Import [`database.sql`](./database.sql) in phpMyAdmin, or run it with the MySQL client. The script creates and selects `swd_shop`.
4. By default, the app connects to MySQL at `127.0.0.1` as `root` with an empty password. To use different settings, define `DB_HOST`, `DB_NAME`, `DB_USER`, and `DB_PASSWORD` in the Apache/PHP environment.
5. Seed the catalog from PowerShell:

   ```powershell
   C:\xampp\php\php.exe C:\xampp\htdocs\swd\09-php-shop\seed.php
   ```

6. Open [http://localhost/swd/09-php-shop/](http://localhost/swd/09-php-shop/).

The seeder creates or updates 24 sample products across eight categories, including the existing Phones, Laptops, and Accessories categories. Running it again does not create duplicates or reset stock on existing products.

## Admin sign-in

The admin area does not ship with a default password. Generate a hash from a password you choose:

```powershell
C:\xampp\php\php.exe -r "echo password_hash('20052005QAZ', PASSWORD_DEFAULT), PHP_EOL;"
```

Set the generated hash as `ADMIN_PASSWORD_HASH` in the environment Apache runs with. On a local XAMPP install, add this line to Apache's configuration (for example `C:\xampp\apache\conf\httpd.conf`) and restart Apache:

```apache
SetEnv ADMIN_PASSWORD_HASH "$2y$...paste-the-generated-hash..."
```

Do not commit the hash or a real password to the project. Open [http://localhost/swd/09-php-shop/admin/login.php](http://localhost/swd/09-php-shop/admin/login.php) to sign in. The admin has an overview dashboard, order list filters, and per-order status controls.

Orders move through `pending -> paid -> shipped -> delivered`. Pending and paid orders may be cancelled; cancelling restores their item quantities to stock in the same transaction that changes the status.

## Order lookup

Customers can look up an order at [http://localhost/swd/09-php-shop/order-lookup.php](http://localhost/swd/09-php-shop/order-lookup.php) with the order reference and checkout email address.

## Database layout

`database.sql` defines the `categories`, `products`, `orders`, and `order_items` tables used by the app. It matches the existing XAMPP database layout, including `orders.address` and the `(order_id, product_id)` key on `order_items`. Product names and unit prices are copied into order items at purchase time so order history remains accurate if catalog details later change. All tables use InnoDB; checkout and cancellation rely on its transactions and row-level locks.

If you already have tables with different column names or constraints, back up your database and adapt the application queries/schema to that existing layout rather than importing this schema over it.
