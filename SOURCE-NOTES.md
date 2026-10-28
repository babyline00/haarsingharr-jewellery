# haarsingharr-source — setup notes

This folder was created from:

    /home/musaddiq/Downloads/haarsingharr-jewellery-source (1).zip

The archive was a **source-only, older and incomplete cut** of the app. This
note records exactly what was added or replaced to make it boot, migrate, seed
and serve. The original archive is untouched and can be re-extracted at any
time; the working project at `~/Desktop/laravel` was **not modified**.

## What the archive was missing

- **104 files** that the code references but the archive did not contain —
  most notably `app/Http/Controllers/Controller.php` (the base controller every
  controller extends), `app/Models/{Faq,StoreLocation,InventoryAlert,
  PasswordResetToken,Redirect}.php`, 8 policies, `app/Support/*`,
  `app/Traits/GeneratesSlug.php`, the whole `resources/views/admin/**` tree,
  and migrations `000008`, `000009` and `*_add_sort_order_to_product_variants`.
  These were copied from `~/Desktop/laravel`.
- No `bootstrap/providers.php`, no `vendor/`, `node_modules/`, `.env`,
  `composer.lock` or built assets.

## Files replaced with fixed versions (83)

The archive also shipped broken versions of files that already exist in the
working project. Every replacement below is the same file with a bug fix
(config boot errors, duplicate SQLite indexes, trait-method collisions,
model/enum bugs, broken view helpers, etc.).

See `git`-less diff: `diff -rq <archive extraction> .`

## Deliberate additions

- `database/seeders/DatabaseSeeder.php` — **did not exist in either project**,
  so `php artisan migrate --seed` / `composer fresh-setup` could not work.
  It calls `RolesAndPermissionsSeeder` then `DevelopmentSeeder`.
- `app/ZipArchivePolyfill.php` + `require_once` in `bootstrap/app.php` — the
  host PHP has no `zip` extension and `spatie/laravel-backup`'s config needs
  the `ZipArchive` constants.
- `.env` copied from the working project, with
  `APP_URL=http://127.0.0.1:8001` so it does not collide with the app on 8000.
- `vendor/`, `node_modules/`, `composer.json`, `composer.lock` and
  `public/build` copied from the working project, because the archive's own
  `composer.json` omitted `laravel/sanctum`, `laravel/ui` and
  `spatie/laravel-sluggable`, which its own models require.

## Running

    cd ~/Desktop/haarsingharr-source
    php artisan serve --host=127.0.0.1 --port=8001

- Storefront: http://127.0.0.1:8001
- Admin:      http://127.0.0.1:8001/admin
- Login:      admin@haarsingharr.local / Str0ngAdmin@2025

Verified: `php artisan test` → 39 passed (140 assertions); all 79
parameter-free GET routes return < 500 with no server errors.

## Code bugs found while exercising the app, fixed here

1. `app/Services/CheckoutService.php` — `new \App\Enums\OrderStatus(...)` on a
   pure enum caused a fatal `Cannot instantiate enum` whenever an order was
   cancelled from the account area (and on payment confirmation). Replaced with
   the enum cases `OrderStatus::Cancelled` / `OrderStatus::Confirmed`.
2. `app/Http/Controllers/Checkout/CheckoutController.php` — choosing Cash on
   Delivery for a cart above the provider limit threw an unhandled
   `DomainException` **after** the order was created: the customer got a 500,
   an orphan `pending` order stayed in the database and the cart was emptied.
   The limit is now checked before the order is placed, and a gateway
   `DomainException` is caught and cancels the order. The form now shows
   "Cash on Delivery is not available for orders over Rs. 200,000…".

Both bugs also exist in `~/Desktop/laravel` (identical code at the time of
writing) and were intentionally **not** changed there.

## Not fixed

- PHP extensions `zip` and `bcmath` are still absent from this machine, so
  spatie backups and any bcmath-based money maths will not work until they are
  installed. This affects the working project too.
