# Haarsingharr.pk — Premium Jewellery E-commerce Platform

> **Jewelry For Every You** — A complete, production-oriented Laravel 12 + Blade + Tailwind jewellery storefront and Super Admin dashboard inspired by Zeesy.pk. Original brand identity, original UI, complete database-backed workflows.

![Haarsingharr.pk](https://img.shields.io/badge/Haarsingharr.pk-Laravel%2012-#C8A968) ![PHP](https://img.shields.io/badge/PHP-8.3+-#1F1B16) ![License](https://img.shields.io/badge/License-Proprietary-#6F6A63)

> **Brand assets**: storefront logo (`public/images/logo.png`), hero banner (`public/images/hero-banner.png`), brand icon (`public/images/brand-icon.png`), plus a full favicon set generated from the brand icon (`public/images/favicon.ico`, `apple-touch-icon.png`, `android-chrome-192x192.png`, `android-chrome-512x512.png`, etc.) and a web manifest (`public/site.webmanifest`).

---

## Table of Contents

1. [Overview](#overview)
2. [Technology Stack](#technology-stack)
3. [Architecture](#architecture)
4. [Installation — Local Development](#installation--local-development)
5. [Installation — cPanel / CloudLinux (No Terminal)](#installation--cpanel--cloudlinux-no-terminal)
6. [Installation — VPS (Ubuntu/Debian)](#installation--vps-ubuntudebian)
7. [Configuration Reference](#configuration-reference)
8. [Security Checklist](#security-checklist)
9. [Database Schema Overview](#database-schema-overview)
10. [Permission Matrix](#permission-matrix)
11. [Payment Gateway Setup](#payment-gateway-setup)
12. [Cron & Queue Setup](#cron--queue-setup)
13. [Backup & Restoration Guide](#backup--restoration-guide)
14. [Performance Notes](#performance-notes)
15. [Testing](#testing)
16. [Completed Features](#completed-features)
17. [Known Limitations](#known-limitations)
18. [Production Deployment Checklist](#production-deployment-checklist)

---

## Overview

Haarsingharr is a complete jewellery e-commerce platform built for the Pakistani market, with Pakistani payment gateways (COD, JazzCash, Easypaisa) and a multi-role staff permission system. The storefront is fully responsive and renders server-side for SEO and first-paint performance. The Super Admin dashboard supports 8 default roles with granular permissions.

### What's Included

- **Storefront** (35+ pages): home, shop, categories, collections, product details, cart, checkout, customer account, wishlist, order tracking, about, contact, FAQ, size guides, jewellery care, legal pages, 404, sitemap.xml, robots.txt.
- **Super Admin** (23 modules): dashboard, products, variants, categories, collections, inventory, orders, payments, refunds, customers, coupons, reviews, CMS, homepage builder, banners, shipping zones, store locations, staff, roles, reports, audit logs, security events, active sessions, settings.
- **Payment abstraction**: COD (built-in), JazzCash, Easypaisa, Stripe adapter pattern.
- **Security**: CSRF, XSS protection, secure sessions, MFA for privileged accounts, rate limiting, audit logging, security event logging, immutable ledger for inventory & payments, idempotent checkout.
- **Tests**: Pest feature tests for auth, checkout, stock reservation, payment webhook idempotency, role boundaries, audit immutability.

---

## Technology Stack

| Component          | Choice                                            |
|--------------------|---------------------------------------------------|
| Backend            | Laravel 12.x                                      |
| Language           | PHP 8.3+                                          |
| Database           | MySQL 8+ (InnoDB, utf8mb4)                        |
| Frontend           | Blade, Tailwind CSS 4, Alpine.js                 |
| Assets             | Vite                                              |
| Auth               | Laravel built-in + Spatie permission              |
| Background         | Laravel queue (database or Redis)                |
| Cache              | Database (default) or Redis                      |
| Tests              | Pest 3.x                                          |
| Image processing   | Intervention Image                                |
| PDF                | laravel-dompdf (invoices, packing slips)         |
| Excel              | maatwebsite/excel (reports)                      |

### Required PHP Extensions

`pdo_mysql`, `mbstring`, `openssl`, `curl`, `fileinfo`, `xml`, `ctype`, `dom`, `tokenizer`, `session`, `bcmath` (for monetary calculations), `gd` or `imagick` (for image processing), `zip` (for deployment archives).

---

## Architecture

```
Customer browser
    ↓
Responsive HTML, compiled CSS, minimal JavaScript (Alpine.js)
    ↓
Apache + PHP + Laravel 12
   - Routing
   - Authentication (sessions, MFA)
   - Authorization (policies, gates, Spatie permissions)
   - Server-side price recalculation
   - Idempotent checkout
   - Atomic stock reservation (row-level locking)
    ↓
MySQL 8+ (InnoDB)
   - Products, variants, inventory ledger
   - Orders, payments, refunds (snapshots preserved)
   - Coupons, reviews, audit logs (immutable)
    ↓
External services
   - SMTP (transactional email)
   - Payment gateways (COD, JazzCash, Easypaisa, Stripe)
   - Courier tracking (TCS, Leopards, DHL)
   - Optional CDN for static assets
```

### Code Organisation Rules

- **Controllers** handle HTTP requests and responses only.
- **Form Request classes** validate input.
- **Services** own complex business logic (cart, checkout, inventory, payment, audit, coupons).
- **Policies** enforce permission checks at the model level.
- **Models** define relationships, casts, and query scopes.
- **Jobs** handle slow work (email, exports, refunds).
- **Webhook controllers** verify provider signatures and delegate to services.
- **Blade components** provide consistent reusable UI elements.

---

## Installation — Local Development

### Prerequisites

- PHP 8.3 or newer
- Composer
- MySQL 8+ or MariaDB 10.6+
- Node.js 20+ and npm (only for asset compilation)

### Steps

```bash
# 1. Clone or unzip the project
cd /path/to/haarsingharr

# 2. Install PHP dependencies
composer install

# 3. Install frontend dependencies
npm install

# 4. Compile assets for development (with hot reload)
npm run dev
# Or for production:
npm run build

# 5. Configure environment
cp .env.example .env
php artisan key:generate

# 6. Edit .env with your local database credentials
# DB_DATABASE=haarsingharr_jewellery
# DB_USERNAME=your_user
# DB_PASSWORD=your_password

# 7. Run migrations and seed development data
php artisan migrate:fresh --seed

# 8. Create the Super Admin account
php artisan haarsingharr:install-super-admin \
  --email=admin@haarsingharr.local \
  --password='Str0ngAdmin@2025' \
  --first=Admin --last=User

# 9. Start the dev server
php artisan serve

# 10. Visit http://localhost:8000
```

The admin dashboard is at `/admin`. Use the credentials from step 8.

### Default Development Credentials

After `php artisan migrate:fresh --seed`:
- **Admin**: email & password you set in step 8.
- **Demo customer**: `ayesha@haarsingharr.pk` / `Haarsingharr@2025` (created by the seeder on first customer registration).

> ⚠️ **Never ship development credentials to production.** Always run `php artisan migrate:fresh --seed --env=production` only on a clean staging database, not production.

---

## Installation — cPanel / CloudLinux (No Terminal)

This deployment approach is designed around uploading pre-built files through cPanel File Manager and importing the database via phpMyAdmin.

### Step 1 — Check Hosting

1. In cPanel, open **MultiPHP Manager** and select PHP 8.3 or newer.
2. Open **Select PHP Version** and confirm these extensions are enabled: `pdo_mysql`, `mbstring`, `openssl`, `curl`, `fileinfo`, `xml`, `ctype`, `dom`, `tokenizer`, `session`, `bcmath`, `gd` (or `imagick`), `zip`.
3. Contact your host if any required extension is unavailable.

### Step 2 — Prepare the Release ZIP

On your development machine:

```bash
# Build production assets
npm run build

# Install production-only dependencies
composer install --no-dev --optimize-autoloader

# Cache config and routes (optional but recommended)
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# Export the database schema as SQL
php artisan migrate:fresh --seed   # only if you want sample data
mysqldump -u root -p haarsingharr_jewellery > haarsingharr_schema.sql

# Create a release ZIP excluding dev files
zip -r haarsingharr-release.zip . \
  -x "node_modules/*" \
  -x ".env" \
  -x "storage/logs/*.log" \
  -x "tests/*" \
  -x ".git/*"
```

### Step 3 — Upload the Application

1. In cPanel **File Manager**, create a directory at the same level as `public_html` — for example `/home/CPANEL_USER/haarsingharr`.
2. Upload `haarsingharr-release.zip` into that directory and extract it.
3. Move **only the contents of** `public/` (the `index.php`, `.htaccess`, `build/` directory, `robots.txt`, `favicon.ico`) into `/home/CPANEL_USER/public_html/`.
4. Edit the new `public_html/index.php` so the path points to the private directory:

   ```php
   // public_html/index.php (modified)
   require __DIR__.'/../haarsingharr/vendor/autoload.php';
   $app = require_once __DIR__.'/../haarsingharr/bootstrap/app.php';
   ```

   This way the application code, `.env`, vendor folder, and storage stay private.

### Step 4 — Create the Database

1. Open cPanel **MySQL Databases**.
2. Create a database (e.g. `cpaneluser_haarsingharr`) and a dedicated user.
3. Assign the user to the database with these privileges: `SELECT, INSERT, UPDATE, DELETE, CREATE, ALTER, INDEX, DROP, LOCK TABLES, REFERENCES`.
4. Open **phpMyAdmin**, select your database, click **Import**, upload `haarsingharr_schema.sql`, click **Go**.

### Step 5 — Configure Production Settings

Create `.env` inside the private `haarsingharr/` directory:

```env
APP_NAME="Haarsingharr"
APP_ENV=production
APP_KEY=                       # generate locally; never expose
APP_DEBUG=false
APP_URL=https://shop.your-domain.com
APP_FORCE_HTTPS=true

DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=cpaneluser_haarsingharr
DB_USERNAME=cpaneluser_haarsingharr_user
DB_PASSWORD=your_strong_password

CACHE_STORE=database
SESSION_DRIVER=database
QUEUE_CONNECTION=database

MAIL_MAILER=smtp
MAIL_HOST=mail.your-domain.com
MAIL_PORT=587
MAIL_ENCRYPTION=tls
MAIL_USERNAME=orders@your-domain.com
MAIL_PASSWORD=your_email_password
MAIL_FROM_ADDRESS=orders@your-domain.com
MAIL_FROM_NAME="Haarsingharr Jewellery"

# Payment gateways (only enable the ones with approved merchant accounts)
COD_ENABLED=true
JAZZCASH_ENABLED=false
EASYPAYSA_ENABLED=false
```

**Set permissions** (via cPanel File Manager → right-click → Permissions):

| Path                       | Recommended | Notes                              |
|----------------------------|-------------|------------------------------------|
| `storage/` (recursive)    | 775         | Web-server user needs write access |
| `bootstrap/cache/`         | 775         |                                    |
| `.env`                     | 640         | Read-only for web server          |
| All other files            | 644         |                                    |

> Never use `777` permissions.

### Step 6 — Create the Super Admin

cPanel hosts without terminal access cannot run `php artisan haarsingharr:install-super-admin`. Use the included `install.php` web installer (delete it after use):

1. Upload `install.php` (provided in the project root) into your private `haarsingharr/` directory.
2. Visit `https://shop.your-domain.com/install.php` (only works once; auto-deletes).
3. Fill in the Super Admin email, name, and a strong password.
4. The installer creates the account, enrolls it in the `super-admin` role, and deletes itself.

> If `install.php` does not self-delete, manually remove it via File Manager. Leaving it accessible is a critical security risk.

### Step 7 — Test Before Launch

Use the sandbox payment credentials first. Verify:

- [ ] Product uploads work.
- [ ] Cart totals are correct.
- [ ] Checkout recalculates server-side.
- [ ] COD order placement succeeds.
- [ ] JazzCash/Easypaisa sandbox redirects work.
- [ ] Webhook signature verification passes.
- [ ] Email notifications arrive.
- [ ] Staff permission boundaries are respected.
- [ ] Mobile layouts render correctly.
- [ ] 404 and 500 error pages render.
- [ ] Backup restoration works.

---

## Installation — VPS (Ubuntu/Debian)

```bash
# 1. Install PHP 8.3 + extensions
sudo apt update
sudo apt install -y php8.3-cli php8.3-fpm php8.3-mysql php8.3-mbstring \
    php8.3-curl php8.3-xml php8.3-gd php8.3-bcmath php8.3-zip \
    php8.3-intl php8.3-opcache php8.3-redis composer nginx mysql-server

# 2. Configure MySQL
sudo mysql_secure_installation
sudo mysql -u root -p
> CREATE DATABASE haarsingharr_jewellery CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
> CREATE USER 'haarsingharr'@'localhost' IDENTIFIED BY 'a_strong_password';
> GRANT ALL PRIVILEGES ON haarsingharr_jewellery.* TO 'haarsingharr'@'localhost';
> FLUSH PRIVILEGES;

# 3. Upload the project
sudo mkdir -p /var/www/haarsingharr
sudo chown -R $USER:$USER /var/www/haarsingharr
# Upload haarsingharr-release.zip and extract to /var/www/haarsingharr/

# 4. Install dependencies
cd /var/www/haarsingharr
composer install --no-dev --optimize-autoloader
npm install && npm run build
php artisan config:cache && php artisan route:cache && php artisan view:cache

# 5. Migrate database
php artisan migrate --force
php artisan haarsingharr:install-super-admin --email=admin@haarsingharr.local --password='Str0ngAdmin@2025'

# 6. Set permissions
sudo chown -R www-data:www-data /var/www/haarsingharr
sudo chown -R $USER:www-data /var/www/haarsingharr/storage /var/www/haarsingharr/bootstrap/cache
sudo chmod -R 775 /var/www/haarsingharr/storage /var/www/haarsingharr/bootstrap/cache

# 7. Configure Nginx
sudo tee /etc/nginx/sites-available/haarsingharr << 'EOF'
server {
    listen 80;
    server_name shop.haarsingharr.pk;
    root /var/www/haarsingharr/public;
    index index.php;
    client_max_body_size 12M;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_read_timeout 120;
    }

    location ~ /\.env {
        deny all;
    }
    location ~ ^/(vendor|storage|tests|database)/ {
        deny all;
    }
}
EOF
sudo ln -s /etc/nginx/sites-available/haarsingharr /etc/nginx/sites-enabled/
sudo nginx -t && sudo systemctl reload nginx

# 8. Configure SSL with Certbot
sudo apt install -y certbot python3-certbot-nginx
sudo certbot --nginx -d shop.haarsingharr.pk

# 9. Set up cron for scheduled tasks
sudo crontab -e
# Add: * * * * * cd /var/www/haarsingharr && php artisan schedule:run >> /dev/null 2>&1

# 10. Set up a queue worker (using systemd)
sudo tee /etc/systemd/system/haarsingharr-queue.service << 'EOF'
[Unit]
Description=Haarsingharr queue worker
After=network.target

[Service]
User=www-data
Group=www-data
Restart=always
ExecStart=/usr/bin/php /var/www/haarsingharr/artisan queue:work --sleep=3 --tries=3 --max-time=3600

[Install]
WantedBy=multi-user.target
EOF
sudo systemctl enable --now haarsingharr-queue
```

---

## Configuration Reference

Every setting documented in `.env.example` has a comment. Key environment variables:

| Variable | Default | Notes |
|----------|---------|-------|
| `APP_ENV` | `production` | Always `production` on live; `local` for dev |
| `APP_DEBUG` | `false` | NEVER `true` in production |
| `APP_KEY` | — | Generate via `php artisan key:generate` |
| `APP_FORCE_HTTPS` | `true` | Forces HTTPS redirects & secure cookies |
| `ADMIN_PATH` | `admin` | Obscure this for extra security |
| `MFA_ENABLED` | `true` | Forces MFA enrollment for super-admin & store-admin |
| `LOGIN_MAX_ATTEMPTS` | `5` | Login rate limiting |
| `LOGIN_DECAY_MINUTES` | `15` | Lockout duration |
| `INVENTORY_RESERVE_MINUTES` | `30` | Stock reservation expiry |
| `INVENTORY_BACKORDERS` | `false` | Set `true` to allow backorders |
| `COD_MAX_VALUE` | `200000` | COD limit (use gateway for larger orders) |
| `JAZZCASH_ENABLED` | `false` | Only enable after merchant approval |
| `EASYPAYSA_ENABLED` | `false` | Only enable after merchant approval |

---

## Security Checklist

### Pre-launch

- [ ] `APP_DEBUG=false` in production `.env`.
- [ ] `APP_KEY` set and unique; rotated from dev.
- [ ] `APP_FORCE_HTTPS=true`; SSL certificate active.
- [ ] `.env` permissions `640`; never web-accessible.
- [ ] `vendor/`, `storage/`, `.env`, `tests/` directories blocked at web server level.
- [ ] All rate limits configured: login (5/15min), password reset (3/hour), reviews (3/day), checkout (5/min).
- [ ] MFA enforced for `super-admin` and `store-admin` roles.
- [ ] Audit log directory (`storage/logs/audit.log`) writable but not world-readable.
- [ ] CSP, HSTS, X-Frame-Options, X-Content-Type-Options headers set via `SecureHeaders` middleware.
- [ ] No hardcoded credentials in source control.
- [ ] No demo accounts in production database.
- [ ] `php artisan config:cache`, `route:cache`, `view:cache` run.
- [ ] Error pages (404, 500, 503) customised and do not leak stack traces.
- [ ] Backup strategy tested (database + storage).

### Post-launch Monitoring

- [ ] Failed login attempts monitored via `security_events` table.
- [ ] Audit log review weekly (sensitive actions: refunds, role changes, settings updates).
- [ ] Inventory alerts reviewed daily (low stock, out of stock).
- [ ] Failed payment rate reviewed daily.
- [ ] Backup restoration tested quarterly.

---

## Database Schema Overview

40+ tables organised in 4 logical groups:

### Users & Permissions
`users`, `addresses`, `roles`, `permissions`, `role_user`, `permission_role`, `user_permissions`, `password_reset_tokens`, `sessions`, `active_sessions`

### Catalogue & Inventory
`categories`, `collections`, `collection_product`, `products`, `product_variants`, `product_images`, `product_tags`, `product_tag`, `product_relations`, `inventory_movements` (immutable ledger), `stock_reservations`, `inventory_alerts`, `suppliers`, `purchase_orders`, `purchase_order_items`

### Cart, Orders & Payments
`carts`, `cart_items`, `wishlists`, `wishlist_items`, `orders`, `order_items` (snapshots), `order_addresses`, `order_status_histories`, `coupons`, `coupon_redemptions`, `payments`, `payment_events`, `refunds`, `shipments`, `exchange_requests`

### CMS, Marketing & Admin
`cms_pages`, `cms_sections`, `faqs`, `blog_posts`, `blog_tags`, `blog_post_tag`, `homepage_sections`, `banners`, `navigation_menus`, `reviews`, `review_images`, `product_questions`, `contact_enquiries`, `newsletter_subscriptions`, `store_locations`, `store_settings`, `redirects`, `audit_logs` (immutable), `security_events`, `email_templates`, `notification_preferences`

### System
`jobs`, `job_batches`, `failed_jobs`, `cache`, `cache_locks`

> **Critical design decisions**:
> - Money stored as `DECIMAL(12,2)`, never floating point.
> - Order items carry snapshots (name, SKU, price, attributes) so historical orders stay correct after products change.
> - `inventory_movements` and `audit_logs` are append-only — the model layer throws on `update()` or `delete()`.
> - `stock_reservations` are released atomically via row-level locking.

---

## Permission Matrix

Eight default roles ship in `RolesAndPermissionsSeeder`. The Super Admin can edit any role's permissions and create custom roles.

| Capability                     | Super Admin | Store Admin | Product Mgr | Inventory Mgr | Order Mgr | Support | Marketing | Report Viewer |
|--------------------------------|:-----------:|:-----------:|:-----------:|:-------------:|:---------:|:-------:|:---------:|:--------------:|
| Manage staff & roles           | ✓           | —           | —           | —             | —         | —       | —         | —              |
| Edit store settings            | ✓           | limited     | —           | —             | —         | —       | —         | —              |
| Configure payment gateways     | ✓           | —           | —           | —             | —         | —       | —         | —              |
| Approve refunds                | ✓           | configurable| —           | —             | configurable | —     | —         | —              |
| Manage products                | ✓           | ✓           | ✓           | —             | —         | —       | limited   | —              |
| Adjust cost prices             | ✓           | ✓           | —           | —             | —         | —       | —         | —              |
| Adjust stock                   | ✓           | ✓           | limited     | ✓             | limited  | —       | —         | —              |
| Manage orders                  | ✓           | ✓           | —           | —             | ✓        | limited | —         | —              |
| View customer information      | ✓           | ✓           | —           | —             | limited  | limited | —         | —              |
| Manage homepage & banners      | ✓           | ✓           | —           | —             | —        | —       | ✓         | —              |
| Manage coupons                 | ✓           | ✓           | —           | —             | —        | —       | ✓         | —              |
| View reports                   | ✓           | ✓           | limited     | limited       | limited  | —       | limited  | ✓              |
| View audit logs                | ✓           | limited     | —           | —             | —        | —       | —         | —              |

**Security rules**:
- Permissions are enforced server-side via policies — hiding buttons is insufficient.
- Staff accounts cannot grant themselves permissions.
- A Store Admin cannot remove the last active Super Admin.
- Refund approvals and sensitive settings changes produce audit entries.

---

## Payment Gateway Setup

### Cash on Delivery (COD)
Built-in. No external merchant account required. Enable by setting `COD_ENABLED=true`. Configurable max value (`COD_MAX_VALUE`).

### JazzCash
1. Apply for a JazzCash merchant account at https://jazzcash.com.pk.
2. After approval, receive: Merchant ID, Password, Integrity Salt.
3. Configure sandbox credentials in `.env`:
   ```env
   JAZZCASH_ENABLED=true
   JAZZCASH_ENV=sandbox
   JAZZCASH_MERCHANT_ID=YOUR_MERCHANT_ID
   JAZZCASH_PASSWORD=YOUR_PASSWORD
   JAZZCASH_INTEGRITY_SALT=YOUR_SALT
   ```
4. Test in sandbox. When ready for production, change `JAZZCASH_ENV=production` and replace with live credentials.
5. Configure the webhook URL in the JazzCash merchant portal: `https://shop.your-domain.com/webhooks/payments/jazzcash`.

### Easypaisa
1. Apply for an Easypaisa merchant account at https://easypaisa.com.pk.
2. After approval, receive: Store ID, Hash.
3. Configure sandbox credentials in `.env`:
   ```env
   EASYPAYSA_ENABLED=true
   EASYPAYSA_ENV=sandbox
   EASYPAYSA_STORE_ID=YOUR_STORE_ID
   EASYPAYSA_HASH=YOUR_HASH
   ```
4. Configure the webhook URL: `https://shop.your-domain.com/webhooks/payments/easypaisa`.

### Stripe (International cards, optional)
Enable only if serving international customers. Configure via Stripe Dashboard → Developers → API keys:
```env
STRIPE_ENABLED=true
STRIPE_PUBLISHABLE_KEY=pk_...
STRIPE_SECRET_KEY=sk_...
STRIPE_WEBHOOK_SECRET=whsec_...
```

> **Never store** card numbers, CVVs, PINs, or payment-provider secret keys in application tables or logs. The `AuditService::sanitize()` method automatically redacts sensitive fields.

### Webhook Signature Verification

Every webhook is verified server-side:

```php
// JazzCash adapter — recomputes pp_SecureHash and compares to received value
if (! hash_equals($computedHash, $receivedHash)) {
    throw new RuntimeException('JazzCash webhook signature invalid.');
}
```

Webhooks are idempotent — repeated events with the same `provider_event_id` are detected and skipped.

---

## Cron & Queue Setup

### Cron (cPanel)
1. In cPanel, open **Cron Jobs**.
2. Add this job (runs every minute):
   ```
   * * * * * cd /home/CPANEL_USER/haarsingharr && /usr/local/bin/php artisan schedule:run >> /dev/null 2>&1
   ```
3. Verify the PHP path matches your cPanel MultiPHP configuration (often `/usr/local/bin/php` or `/opt/alt/php83/usr/bin/php`).

### Cron (VPS)
```bash
sudo crontab -e -u www-data
# Add:
* * * * * cd /var/www/haarsingharr && /usr/bin/php artisan schedule:run >> /dev/null 2>&1
```

### Scheduled Tasks

| Task                              | Schedule        | Description                          |
|-----------------------------------|-----------------|--------------------------------------|
| `haarsingharr:release-reservations`     | every minute    | Releases expired stock reservations  |
| `haarsingharr:cleanup-reservations`     | hourly          | Full cleanup of orphan reservations  |
| `haarsingharr:newsletter-digest`        | daily 09:00     | Sends daily newsletter digest        |
| `queue:work --stop-when-empty`    | every 5 minutes | Fallback queue processing            |

### Queue Worker

For cPanel hosts without persistent queue workers, the fallback `queue:work --stop-when-empty` cron keeps the queue flowing. For VPS or hosts with supervisor support:

```bash
# Using systemd (see VPS installation section)
sudo systemctl start haarsingharr-queue
sudo systemctl enable haarsingharr-queue
```

---

## Backup & Restoration Guide

### Daily Backup (cPanel)

1. In cPanel, open **Backup** or **Backup Wizard**.
2. Select **Generate Backup**.
3. Download the backup file (includes home directory and MySQL database).

For automated backups, schedule a cron job:

```bash
# Daily 3 AM: dump database + storage tarball
0 3 * * * mysqldump -u USER -p'PASS' DATABASE > /home/CPANEL_USER/backups/db-$(date +\%Y\%m\%d).sql
0 3 * * * tar czf /home/CPANEL_USER/backups/storage-$(date +\%Y\%m\%d).tar.gz /home/CPANEL_USER/haarsingharr/storage
# Cleanup backups older than 14 days
0 4 * * * find /home/CPANEL_USER/backups -type f -mtime +14 -delete
```

### Restoration Procedure

1. **Database restore**:
   ```bash
   mysql -u USER -p DATABASE < haarsingharr_backup.sql
   ```
   Or via phpMyAdmin → Import → upload `.sql` file.

2. **Storage restore**:
   ```bash
   tar xzf storage_backup.tar.gz -C /var/www/haarsingharr/
   sudo chown -R www-data:www-data /var/www/haarsingharr/storage
   ```

3. **Clear caches** after restore:
   ```bash
   php artisan config:clear
   php artisan cache:clear
   php artisan view:clear
   ```

4. **Verify**:
   - Customer logins work.
   - Recent orders display correctly.
   - Product inventory matches pre-backup state.

### Spatie Backup (VPS only)

The project includes `spatie/laravel-backup`. Configure destinations in `config/backup.php`. Run manually:

```bash
php artisan backup:run
php artisan backup:clean
php artisan backup:list
```

---

## Performance Notes

The platform is optimised for fast real-world page loads:

- **Server-rendered HTML** — no SPA overhead, instant first paint.
- **Minimal JavaScript** — Alpine.js only where interactivity matters (mobile menu, product gallery, add-to-cart).
- **Eager loading** — `Product::with(['images', 'variants'])` prevents N+1 on listing pages.
- **Pagination** — 24 products per page on listings, never the whole catalogue.
- **Indexes** — slug, status, price, category, created_at indexes on every filterable column.
- **Lazy loading** for below-the-fold images, eager loading for hero images.
- **WebP/AVIF support** via `Intervention\Image` (configure driver in `config/medialibrary.php`).
- **Versioned assets** — `vite build` outputs hashed filenames with long-lived `Cache-Control` headers.
- **HTTP/2-ready** — minimal external resources, CSS bundled, JS deferred.
- **Database cache** — store settings, role/permission checks cached with auto-invalidation.
- **Queueing** — emails, exports, refunds dispatched to `queue:work` when available.

---

## Testing

### Running Tests

```bash
# Install dev dependencies
composer install

# Run the test suite (Pest)
composer test
# Or directly:
./vendor/bin/pest

# Run with coverage
./vendor/bin/pest --coverage --min=80
```

### Test Coverage Includes

- Authentication (registration, login, logout, password reset)
- Authorization (role boundaries, permission gates)
- Product & variant management
- Price calculation (sale price logic, discount percentage)
- Cart persistence (add, update, remove, recalculate)
- Stock reservation (atomic locking, concurrent checkout)
- Duplicate checkout submission (idempotency key)
- Order creation and state machine transitions
- Payment webhook signature verification
- Payment event idempotency
- Coupon validation (expiry, usage limits, minimum order)
- Audit log immutability
- Inventory movement immutability
- File upload validation
- Review eligibility (verified purchase)

### Test Database

Tests use the `:memory:` SQLite driver by default (fast, isolated). For MySQL parity tests, set `DB_CONNECTION=mysql` in `phpunit.xml`.

---

## Completed Features

### Storefront (Phase 4)
✓ Homepage with 12+ database-configurable sections
✓ Shop with filters (category, price, sort, search)
✓ Category & collection pages
✓ Product detail with variants, gallery, reviews, related products
✓ Cart with coupon application and live recalculation
✓ Checkout with server-side price recompute, guest + registered
✓ Order confirmation page
✓ Customer login, register, password reset
✓ Customer account dashboard
✓ Order history & detail
✓ Wishlist
✓ Order tracking (token-protected)
✓ About, contact (with form), FAQ
✓ Store locations
✓ CMS page rendering (slug-based)
✓ Sitemap.xml (dynamic)
✓ Robots.txt
✓ 404 page

### Admin (Phase 8)
✓ Dashboard with KPIs (today/week/month revenue, AOV, top products, low stock)
✓ Product CRUD with variants
✓ Category & collection CRUD
✓ Inventory management with auditable ledger
✓ Order list & detail with status transitions
✓ Payment list
✓ Refund approval workflow
✓ Customer management
✓ Coupon CRUD
✓ Review moderation
✓ Homepage builder
✓ CMS pages, FAQs
✓ Banner management
✓ Store locations
✓ Staff management
✓ Role & permission management
✓ Reports (sales, inventory, customers, staff activity) with CSV export
✓ Store settings
✓ Payment gateway configuration
✓ Audit logs (immutable)
✓ Security events log
✓ Active sessions

### Security (Phase 8)
✓ CSRF on every form
✓ XSS protection via Blade escaping
✓ Mass-assignment protection (fillable)
✓ Parameterized queries (Eloquent)
✓ Secure password hashing (bcrypt, 12+ char minimum, strong-password rule)
✓ Session rotation on auth
✓ HttpOnly + SameSite cookies
✓ MFA enforcement for privileged roles
✓ Rate limiting (login, reset, reviews, checkout)
✓ File upload validation (mimes, size, dimensions)
✓ Immutable audit logs and inventory ledger
✓ Row-level locking for atomic stock reservation
✓ Idempotent checkout & payment webhook handling
✓ Payment signature verification (JazzCash, Easypaisa)
✓ SecureHeaders middleware (CSP, HSTS, X-Frame-Options, X-Content-Type-Options, Referrer-Policy)
✓ No hardcoded credentials
✓ No public admin registration
✓ No default shared passwords
✓ No public backup endpoints
✓ Sensitive fields auto-redacted in audit payloads

### SEO (Phase 8)
✓ Stable, clean URLs
✓ Unique page titles and meta descriptions
✓ Canonical URLs
✓ XML sitemap
✓ Robots.txt
✓ Breadcrumbs
✓ Product structured data (schema.org Product + Offer + AggregateRating)
✓ Open Graph metadata
✓ Twitter Card metadata
✓ Descriptive alt text
✓ Internal links between related products
✓ Custom 404 page
✓ Pagination indexation controls

---

## Known Limitations

The following features are scaffolded but require additional configuration or external service provisioning:

### Requires External Credentials
- **JazzCash production**: requires approved merchant account + signed agreement.
- **Easypaisa production**: requires approved merchant account.
- **Stripe international cards**: requires Stripe account + KYC.
- **WhatsApp click-to-chat**: configure `CONTACT_WHATSAPP` in `.env` (number only).
- **Email**: configure SMTP credentials in `.env`.

### Optional Enhancements (Not Implemented)
- **Algolia/Meilisearch** integration for typo-tolerant product search (currently using indexed DB search).
- **Real-time order tracking via courier APIs** (TCS, Leopards) — currently shows the courier tracking URL only.
- **Live chat widget** (Tawk.to, Crisp) — currently uses WhatsApp click-to-chat.
- **Customer product Q&A** — schema exists, controller pending.
- **Blog module** — schema exists, controller pending.
- **Loyalty programme** — not implemented.
- **Affiliate programme** — not implemented.
- **Multi-currency** — currently PKR only; the schema supports multi-currency, but display logic is PKR-focused.

### Hosting-Specific Limitations
- **cPanel without terminal**: cannot run `php artisan migrate`. Initial schema is imported via the SQL export file; subsequent updates require a temporary authenticated migration runner. Never expose a public endpoint that runs arbitrary migrations.
- **Shared hosting with PHP-FPM limits**: large exports (>50k rows) should be batched; the seeder uses chunked processing.
- **No Redis available**: database queue + database cache work fine for small-to-medium stores. Switch to Redis when available.

---

## Production Deployment Checklist

Before going live, verify every item below.

### Application
- [ ] `APP_ENV=production`, `APP_DEBUG=false` in `.env`.
- [ ] `APP_KEY` generated and not the dev default.
- [ ] `APP_URL` matches the public URL (with HTTPS).
- [ ] `APP_FORCE_HTTPS=true`.
- [ ] `ADMIN_PATH` set to something other than `admin` (obscure).
- [ ] `composer install --no-dev --optimize-autoloader` run.
- [ ] `npm run build` run; `public/build/` directory exists.
- [ ] `php artisan config:cache`, `route:cache`, `view:cache`, `event:cache` run.
- [ ] `storage:link` symlink created.

### Database
- [ ] Database user has minimum necessary privileges (not root).
- [ ] Database charset is `utf8mb4`, collation `utf8mb4_unicode_ci`.
- [ ] All migrations applied successfully.
- [ ] Super Admin created through `haarsingharr:install-super-admin` command (CLI) or `install.php` (web).
- [ ] No seed data (products, customers) imported into production database.

### Security
- [ ] HTTPS certificate installed and active.
- [ ] All rate limiters configured in `.env`.
- [ ] MFA enrolled for Super Admin and Store Admin accounts.
- [ ] `storage/`, `bootstrap/cache/` directories writable (775), not 777.
- [ ] `.env` not web-accessible (test by visiting `https://shop.domain/.env`).
- [ ] `vendor/`, `tests/`, `database/` directories blocked at web server.
- [ ] `install.php` deleted after Super Admin creation.
- [ ] No hardcoded credentials in git history.
- [ ] Backup script tested and scheduled.

### Performance
- [ ] OPcache enabled in PHP.
- [ ] `compression` (Brotli or gzip) enabled at web server.
- [ ] Long-lived cache headers for static assets (`Cache-Control: public, max-age=31536000, immutable`).
- [ ] Database indexes verified with `EXPLAIN` on slow queries.
- [ ] Image delivery optimised (WebP/AVIF when possible).
- [ ] CDN configured (optional — Cloudflare or similar).

### Functional Testing
- [ ] Customer registration → email verification → login.
- [ ] Product browsing → add to cart → checkout → COD order.
- [ ] Sandbox JazzCash / Easypaisa payment flow.
- [ ] Webhook delivery and signature verification.
- [ ] Order status transitions (pending → confirmed → packed → shipped → delivered).
- [ ] Refund workflow (approve → process → mark refunded).
- [ ] Customer review submission and moderation.
- [ ] Wishlist add/remove.
- [ ] Mobile responsiveness on real devices (iPhone, Android).
- [ ] 404, 500, 503 error pages.
- [ ] Backup restoration.

### Operational
- [ ] Error monitoring configured (Sentry, Bugsnag, or log review).
- [ ] Backup rotation set (retain 7 daily + 4 weekly minimum).
- [ ] Uptime monitoring configured (UptimeRobot, Pingdom).
- [ ] Documented restoration procedure.
- [ ] Staff trained on permission boundaries.
- [ ] Audit log review schedule defined.
- [ ] Disaster recovery runbook tested.

---

## License

Proprietary. © Haarsingharr Jewellers (Pvt) Ltd. All rights reserved.

## Acknowledgements

- Laravel framework by Taylor Otwell & contributors.
- Spatie packages (permission, backup, medialibrary, activitylog).
- Tailwind CSS by Adam Wathan & contributors.
- Pest PHP by Nuno Maduro & contributors.
- Intervention Image by Oliver Vogel.
- All product photography and brand identity is original to Haarsingharr.
