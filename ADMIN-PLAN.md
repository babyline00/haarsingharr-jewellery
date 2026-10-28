# Admin Completion Plan — Haarsingharr.pk

**Scope:** every page under `/admin` — feature completeness, function, and UI/UX.
**Basis:** code audit + live route sweep + design-token review (2026-10-21).
**Status:** Phases 0–7 complete; Phase 8 (polish, accessibility, responsive behavior, and end-to-end review) in progress.

---

## 1. Measured baseline

| Item | Count |
|---|---|
| Admin routes | 138 (78 GET) |
| Admin controllers | 25 |
| Admin views | 75 |
| Feature view directories | 29 (3 empty: `layouts/`, `partials/`, `backup/`) |
| Admin permissions | 183, across 7 roles (super-admin has all) |
| Admin feature tests | 18 in `tests/Feature/AdminCrudTest.php` — all pass |
| Design tokens | `resources/css/app.css` (483 lines, Tailwind 4 `@theme`) |

Live sweep as `super-admin` against `:8000`: **66 × 2xx, 9 × 3xx, 3 × failure**.

### What is genuinely solid today
Dashboard, catalogue CRUD (products/variants/images, categories, collections), inventory adjustments + movement ledger, orders (list/detail/status/shipment/invoice/packing slip), payments (read-only), refunds (approve only), exchanges (status update), customers, reviews, coupons, banners, homepage sections, CMS pages, FAQs, staff, roles, 4 reports + CSV export, settings, SEO redirects, audit log, active sessions.

---

## 2. P0 — verified defects (fix before anything else)

| # | Defect | Status (Phase 0) |
|---|---|---|
| 1 | `GET /admin/orders/{id}/edit` → **500** | ✅ Route removed (`edit`/`update`/`destroy` excluded); no 500 |
| 2 | `GET /admin/coupons/{id}` → 500 | ✅ **Not a defect** — `show()` redirects to `edit`; the 404 was simply missing seed data |
| 3 | **Refunds cannot be created in the UI** | ✅ Request-refund form on payment detail; payments card on order detail; balance guard added |
| 4 | COD over limit → 500 + orphan order | ✅ Ported from `haarsingharr-source`; verified live |
| 5 | Order cancel fatal (`new OrderStatus`) | ✅ Ported; enum cases used |
| 6 | Missing `DatabaseSeeder` | ✅ Added (`RolesAndPermissionsSeeder` + `DevelopmentSeeder`) |
| 7 | Dead links in shell | ✅ Working search, notification centre, change-password page, shortcuts modal |
| 8 | 9 `show` routes redirect to `edit` | ⏸ Kept intentionally (fast-edit pattern) — documented |
| 9 | "Mark all read" is cosmetic | ✅ Persisted via `unreadNotifications()->update(['read_at' => now()])` |

**Phase 0 verification (2026-10-21):** 39/39 tests pass; admin sweep 66 × 2xx / 9 × 3xx / 0 × 5xx; COD 500 000 cart returns `302 → /checkout` with a validation message and creates no order; refund creation + over-refund guard verified.

**Prerequisites:** enable `ext-zip` and `ext-bcmath` (both are required by `composer.json`; backups and money maths will fail without them), then run `php artisan storage:link`, `config:cache`, and a Vite production build.

---

## 3. Data that exists with **no admin UI at all**

| Table(s) | Model | Current UI | Needed |
|---|---|---|---|
| `contact_enquiries` | `ContactEnquiry` | none (storefront form only) | Inbox + reply + status |
| `newsletter_subscriptions` | `NewsletterSubscription` | none | List, export, unsubscribe |
| `inventory_alerts` | `InventoryAlert` | none (dashboard count only) | Alerts page, acknowledge, auto-resolve |
| `product_questions` | — (no model) | none | Q&A moderation |
| `blog_posts`, `blog_tags`, `blog_post_tag` | — | none | Blog CRUD + editor + SEO |
| `suppliers`, `purchase_orders`, `purchase_order_items` | — | none | Supplier + PO + receiving |
| `navigation_menus` | — | none | Header/footer/mobile menu builder |
| `email_templates` | — | none | Template editor + preview + test send |
| `cms_sections` | `CmsSection` | partial (homepage builder) | Verify coverage in builder |
| `wishlists`, `wishlist_items` | `Wishlist`, `WishlistItem` | none | Read-only customer insight (optional) |

Also absent: backups (package installed, no route/view), queue/failed-job monitor, system health, CSV **import**, Excel export, global search, notification centre.

---

## 4. Design language (keep the brand, tighten the system)

Reuse the existing tokens — do **not** invent a second palette.

| Role | Token | Usage |
|---|---|---|
| Canvas | `--color-ivory-alt` @ 30% | page background |
| Surface | `--color-porcelain` | cards, tables, forms |
| Chrome | `--color-charcoal` | topbar, primary text |
| Muted text | `--color-muted` | hints, meta |
| Primary action | `--color-champagne` / `btn-gold` | exactly one per page |
| Secondary | `btn-outline` charcoal | adjacent actions |
| Danger | `--color-danger` / rose | destructive only |
| Status | success / warning / info / draft | pills, never body text |
| Radius | `--radius-xl` cards, `--radius-lg` controls, pill status | |
| Elevation | `--shadow-sm` cards; `--shadow-lg` overlays | no stacked shadows |
| Type | Cormorant Garamond → page titles; Inter 13–14px → UI | numbers tabular |
| Motion | 150 ms hover, 200 ms dropdown, 300 ms drawer, ease-out | honour `prefers-reduced-motion` |
| Focus | 2 px champagne ring, 2 px offset | never remove outline |
| Density | 44 px table rows, 40 px controls, 8 pt spacing scale | compact but calm |

Admin character: ivory canvas, white cards, charcoal chrome, champagne reserved for the primary action — jewellery-brand restraint, not a generic SaaS blue dashboard.

### Naming cleanup
`btn-primary` is defined but used **0×**; views use `btn-gold` (39 files). Pick one (`btn-primary` as alias mapping to champagne) and use it everywhere.

---

## 5. Page archetypes — every page conforms to one of these

### A. Dashboard
Breadcrumb → page title + date range → KPI row (4) → 2-col: sales chart + orders-by-status → recent orders table → low stock → quick actions. Widgets toggleable, range persisted.

### B. Index / list
Breadcrumb → **page header** (title, count, 1 primary + ≤2 secondary actions) → **filter bar** (search, 2–4 selects, date range, "Clear") → **bulk bar** (appears on selection) → **table** (sticky header, sortable columns, status pill, right-aligned money, row-action menu) → **footer** (range, per-page, pagination) → **empty state with CTA** → **skeleton** while loading. State in the URL.

### C. Record detail
Header (title, status pill, actions) → 2/3 main: summary, line items, timeline/activity; 1/3 side: customer, payment, shipping, notes. Every mutation logged in a visible activity feed.

### D. Create / edit form
Header (title, dirty indicator) → sections in cards → inline field errors + top error summary → **sticky action bar** (Save / Save & close / Cancel) → unsaved-changes guard → ⌘S save.

### E. Settings
Left group rail (General, Store, Tax, Checkout, Payments, Shipping, Email, Notifications, Legal, Integrations) → grouped fields → per-group save + "last updated by".

### F. Report
Period picker + compare-to-previous → KPI row → chart card (with accessible table fallback) → detail table → export (CSV/Excel) + scheduled email.

### G. Inbox (enquiries, notifications)
Two-pane: filterable list left, detail + reply/actions right; unread counts; keyboard `j/k`.

### H. Print (invoice, packing slip)
A4, no chrome, brand header, customer/ship-to, line items, totals, footer; `@media print` + "Download PDF".

### I. Auth / error
Login, 403, 404, 419, 500 rendered inside the admin shell (or a minimal branded card), with a route back.

---

## 6. Component library (Blade, `resources/views/components/admin/`)

**Built in Phase 1** (see `resources/views/components/admin/README.md`):

- **Layout:** `header`, `breadcrumbs`, `section`, `card`, `stat`, `tabs`, `empty`
- **Form:** `field`, `input`, `select`, `textarea`, `checkbox`, `error-summary`
- **Data:** `table`, `th` (sort + `aria-sort`), `pagination`, `filter-bar`, `status-pill`, `money`
- **Feedback:** `confirm` (accessible alert dialog), `modal`, `button`
- **Nav:** `icon` (single SVG set), `nav-group`, `nav-count`

The shell now uses these primitives, so new pages should compose them rather than
hand-rolling markup. Custom pagination (`resources/views/pagination/admin.blade.php`)
is registered globally, so every `->links()` call is consistent.

Still to add in later phases: `toggle`, `radio-group`, `file`, `bulk-bar`, `badge`,
`date`, `tooltip`, `toast`, `drawer`, `spinner`, `dropdown`, `skeleton`.


---

## 7. Shell & navigation

**Sidebar**
- Collapsible groups with persisted state; badges for pending orders, low stock, new enquiries, pending reviews, refunds.
- Keyboard navigable, `aria-current="page"`, roving tabindex.
- Add missing entries: **Users**, **Security Events**, **Enquiries**, **Newsletter**, **Alerts**, **Backups**.
- Reorganise: Dashboard · Catalogue · Sales · Customers · Marketing · Content · Reports · System.

**Topbar**
- Working global search / command palette (⌘K): orders by number, customers by email, products by SKU, pages by title.
- Notification centre: real `markAsRead`, "View all" page, unread badge from DB.
- User menu: profile, **working change password**, active sessions, sign out.
- Breadcrumbs rendered globally from route name.

**Responsive**
- Sidebar drawer + backdrop + focus trap + ESC + scroll lock under 1024 px (partly exists).
- Tables: horizontal scroll container with sticky first column at md; card layout at sm.
- Forms: single column under 768 px; sticky action bar above the fold.

**States**
- Full-page 403/404/419/500; offline/CSRF-expired toast; session-expiry redirect back.

---

### Phase 1 status (2026-10-21)

- **Components:** 25 under `resources/views/components/admin/` (24 Blade + the `icon` view) + `App\View\Components\Admin\Icon`; documented in `README.md`.
- **Shell:** skip link, global breadcrumbs, environment badge, collapsible/persisted sidebar groups, icons on every link, badges for orders in progress / low stock / pending reviews / pending refunds, mobile drawer fixed to `display:none` when closed.
- **Nav:** added **Users** (under Customers) and **Security Events**, **Active Sessions** (under System); `aria-current="page"` only on the active link.
- **Pagination:** one accessible pagination view registered via `Paginator::defaultView()`.
- **Error pages:** branded 403 / 404 / 419 / 429 / 500 / 503, independent of auth and DB.
- **Pages migrated as reference archetypes:** orders list, coupons list, audit log, notifications, store settings (+ settings fields).
- **Verification:** `view:cache` clean; 41/41 tests (147 assertions); admin GET sweep **66 × 2xx, 9 × 3xx, 3 × 404 (expected), 0 × 5xx**.

---

## 8. Module plans

### 8.1 Dashboard
**Now:** KPIs, 14-day sales trend (inline SVG), top products, low stock, orders by status, recent orders, quick actions.
**Gaps:** fixed date range; no comparisons; quick actions partly dead; no pending-work queue.
**Do:** range picker + previous-period deltas; "needs attention" queue (unpaid, unfulfilled, refunds, low stock, unanswered enquiries); real quick actions; widget visibility persisted; cache stats (60 s).

### 8.2 Products
**Now:** index/create/edit, variants, images, primary image, category/tags.
**Gaps:** no show page (redirects), no bulk actions, no duplicate, no import/export, no SEO fields surfaced, no variant matrix, no stock per variant inline, no related products, no activity history, no preview.
**Do:** bulk publish/draft/archive/delete/export; duplicate; CSV import with dry-run + error report; variant matrix generator (size × colour); image manager (reorder, alt text, crop, reuse); SEO panel; related/upsell; per-record timeline; storefront preview; low-stock badge.

### 8.3 Categories
**Now:** flat CRUD with parent select.
**Gaps:** no tree view/reorder, no image, no SEO, no counts.
**Do:** drag-and-drop tree, product counts, image, meta fields, bulk status, safe delete (reassign children/products).

### 8.4 Collections
**Now:** CRUD; product attach exists.
**Gaps:** sort order, rules (manual vs automatic), scheduling, cover image, SEO.
**Do:** product picker with search + drag order; rule builder (tag/price/stock); schedule; preview.

### 8.5 Inventory
**Now:** stock index, single adjust, movement ledger.
**Gaps:** no low-stock/out-of-stock dedicated page, no bulk adjust, no per-variant ledger, no alerts UI, no stocktake, no import.
**Do:** tabs All / Low / Out; bulk adjust with reason; per-variant drawer with ledger; acknowledge alerts; stocktake mode; CSV stock import; valuation.

### Phase 2 status (2026-10-21)

**Products**
- Bulk activate / draft / archive / soft-delete with permission-aware actions.
- Duplicate copies content, variants and image files; the copy starts as a draft with **zero stock** so inventory is never doubled.
- CSV import with a dry-run preview (row-level errors), confirm-to-import, 500-row cap, category resolution by name/slug, and opening stock posted through the ledger.
- CSV export honours the active filters.
- Variant matrix generator (up to 3 attributes, 100 combinations); existing combinations are skipped and unchanged.
- Variant create/edit guard against negative/derived stock; opening stock posts a ledger movement.
- Image manager (upload multiple, choose primary, remove), SEO panel with live search preview + canonical URL, related / upsell / cross-sell links, and a product activity timeline.
- Low-stock and out-of-stock filters, per-variant stock in the list, storefront preview generated from the product slug.
- Fixed `Product::availableStock()` to sum variants even when the historical `has_variants` flag is stale, and fixed the self-SKU `unique` rule on update.

**Categories**
- Tree-flattened index with depth, product counts (including subcategory products), stats, search (flat results) and up/down ordering using accessible buttons instead of drag-and-drop.
- Bulk activate / inactive / delete; deletion is blocked while a category has products or subcategories and reports exactly what is blocking it.
- Checkbox flags now clear correctly on update; image/SEO fields retained.

**Collections**
- Stats, pagination and storefront previews.
- Manual product picker with attach, detach and ordered move up/down.
- Rule-based preview count with `all` / `any` matching over price, category and featured flags; schedule, homepage flag and SEO fields added (migration `2026_10_21_000001_add_seo_to_collections_table`).

**Inventory**
- All / Low / Out tabs, search, retail and cost valuation, low/out counts.
- Unresolved alert card with acknowledge action; adjustments refresh alerts through `InventoryService`.
- Bulk set/adjust with reason + note and per-row adjust, each writing an immutable ledger movement.
- CSV stock export and import (`sku,new_stock,reason,notes`), with per-line errors and a 1,000-row cap.
- Movement ledger filters by SKU/product/type and supports a per-variant ledger view.

**Deferred to Phase 8 polish (deliberately)**
- Category/collection drag-and-drop (keyboard-accessible move buttons ship now).
- Tag/stock rule builder (price/category/featured rules ship now).
- Dedicated stocktake session UI (bulk adjust + CSV import cover the workflow).
- Image crop/reorder and variant-specific image assignment.

**Verification:** `view:cache` clean; `npm run build` clean; **53/53 tests (249 assertions)**, including 12 new `AdminCatalogueTest` cases; admin GET sweep **76 × 2xx, 9 × 3xx, 2 × 404 (expected — no coupon seed data), 0 × 5xx**.

### 8.6 Suppliers & purchase orders *(new)*
Tables exist, no UI. Supplier CRUD; PO create/receive/partial receive; cost updates on receive; expected-date tracking; audit.

### 8.7 Orders
**Now:** list, detail, status transition, shipment create, invoice, packing slip.
**Gaps:** `edit` → 500; no bulk status; no refund/exchange creation; no internal notes; no resend email; no cancel+restock button; no manual payment; no address edit; limited filters; no export.
**Do:** fix route; filter bar (date, status, payment, channel, amount); saved views; bulk status/print/export; order timeline (status, payments, shipments, refunds, notes); internal notes; resend confirmation/invoice; cancel with restock; manual payment record; edit addresses; partial fulfilment; tracking edit; batch print.

### 8.8 Payments
**Now:** read-only index/detail.
**Gaps:** no manual capture, no refund initiation, no reconciliation, no provider event log UI, no export.
**Do:** mark paid/failed (with reason); initiate refund (links to 8.9); provider events + webhook payload viewer; reconciliation report; export.

### 8.9 Refunds
**Now:** index, show, approve. **No create UI.**
**Do:** create from order/payment (amount, type, reason, restock toggle); approve/reject with notes; gateway refund call or manual reference; receipt PDF; status timeline; filters + export.

### 8.10 Exchanges / returns
**Now:** index, show, status update (no stock movement).
**Do:** create from order; approve/reject with restock; receive item; complete; return label; timeline; filters.

### 8.11 Shipping & fulfilment
**Now:** global settings + shipment list; create shipment from order.
**Gaps:** no edit/delete, no carrier/tracking update, no dispatched/delivered transitions, no zones/rates, no labels.
**Do:** shipment edit/cancel; tracking + carrier; status transitions; zones & rate table CRUD (replace single global flat/express fields); delivery estimate; label/print; COD reconciliation.

### Phase 3 status (2026-10-21)

**Orders**
- Filter bar for search, status, payment, channel, date range and value range, plus saved views (needs action, unpaid, today, high value).
- Bulk status, cancel + stock reconciliation, print packing slips and export selected orders.
- Order detail: status timeline, append-only internal notes, editable shipping/billing addresses, per-line fulfilment with automatic partial/fulfilled state, resend confirmation/invoice, manual payments and shipment controls.
- Cancellation reconciles stock once: unconsumed reservations are released, otherwise consumed stock is restocked only where a `sale_out` ledger movement exists.
- CSV export and batch packing-slip print.

**Payments**
- Filters (search, status, provider, date), captured/refunded totals, CSV export.
- Payment detail with reconciliation summary, provider event log and raw payload viewer.
- Manual capture and failure actions with notes; manual/provider events stay auditable.

**Refunds**
- Create from an order payment or payment detail, with full/partial amount, reason, internal notes and optional restock.
- Approve, reject, settle and receipt PDF. COD refunds complete immediately; JazzCash/Easypaisa remain processing until staff confirm settlement; manual providers complete with a reference.
- Totals are recomputed from completed refunds, so retries and duplicate events cannot double-count refunds.

**Exchanges / returns**
- Create from an order item, with request type, quantity, reason, replacement variant and return-label URL.
- Receive item (optional one-time restock), complete, internal notes, status timeline and CSV export.

**Shipping & fulfilment**
- Shipping zones and additive rate CRUD (method, thresholds, free-over, weight bands, ETA); legacy flat/express settings remain as fallback.
- Shipment edit/delete, courier/tracking updates, delivery estimates, status-to-order transitions, A6 label PDFs and filtered shipment search.
- COD reconciliation panel for delivered, unpaid COD orders.

**Deferred to later phases**
- Live checkout integration for zone/rate quoting (models and `ShippingRate::quote()` are ready).
- Courier API integrations and automated tracking sync.
- Customer-facing return portal (staff workflows ship now).

**Verification:** `view:cache` clean; `npm run build` clean; **65/65 tests (352 assertions)**, including 12 new `AdminSalesTest` cases (103 assertions); admin GET sweep **80 × 2xx, 9 × 3xx, 3 × 404 (expected — 2 missing coupon seeds, 1 receipt for a non-completed refund), 0 × 5xx**.

### 8.12 Customers
**Now:** index, show, update, delete.
**Gaps:** no segments, LTV, notes/tags, addresses, impersonation, export, GDPR.
**Do:** segment filters (new/repeat/VIP/blocked); LTV + order count; tabs orders/addresses/wishlist/reviews; notes + tags; impersonate (audited, banner); export; GDPR export/erase.

### 8.13 Users & staff
**Now:** staff CRUD; users index/show/edit (no sidebar link).
**Do:** unify Staff + Users or clearly split (staff = admin accounts, customers = shoppers); invite flow; 2FA; force reset; last login; deactivate; direct permissions; nav entry.

### 8.14 Reviews & Q&A
**Now:** reviews index/show/approve/reject; no reply.
**Do:** reply (customer notified); images moderation; bulk approve/reject; Q&A moderation queue (new).

### 8.15 Coupons
**Now:** index/create/edit/show missing.
**Gaps:** no show view, no usage report, no bulk codes, no restrictions UI.
**Do:** show page with redemptions; bulk generate; min spend/product/category/customer limits; schedule; usage chart; export.

### 8.16 Banners
**Now:** CRUD + image upload.
**Gaps:** scheduling, placement, mobile image, click tracking.
**Do:** placement (home hero, category, checkout), start/end, desktop/mobile, alt text, click/impression counts, reorder.

### 8.17 Homepage builder
**Now:** sections CRUD + reorder field.
**Do:** drag-reorder, live preview, publish/draft, per-section config forms, schedule, duplicate.

### 8.18 CMS pages
**Now:** CRUD.
**Gaps:** revisions, preview, scheduling, nav assignment, SEO fields.
**Do:** rich-text editor, revisions + restore, preview token, publish at, meta, menu assignment.

### 8.19 FAQs
**Now:** CRUD. **Do:** reorder, categories, publish toggle, search.

### 8.20 Blog *(new)*
Posts CRUD with editor, tags, categories, cover, SEO, scheduling, preview, related products.

### 8.21 Navigation menus *(new)*
Header/footer/mobile builders: nested items, link types (page/category/collection/URL), drag order, visibility.

### 8.22 Email templates *(new)*
List, edit with variables palette, preview with sample data, test send, enable/disable, per-event mapping.

### 8.23 Contact enquiries *(new)*
Inbox, status (new/read/replied/closed), assignment, reply (template), spam, export.

### 8.24 Newsletter *(new)*
Subscribers list, source, status, export, bulk unsubscribe, segment by consent.

### 8.25 Reports
**Now:** sales, inventory, customers, staff activity + CSV per period.
**Add:** products, coupons, tax, shipping, payments/reconciliation, refunds, exchanges, stock valuation, cohorts. Compare period, granularity, saved reports, Excel export, scheduled email. One shared chart component with table fallback.

### 8.26 Settings
**Now:** general, SEO redirects, payment toggles.
**Add groups:** store/contact, currency & tax, checkout rules (COD limits — surface the 200 000 threshold), shipping, email/SMTP + test send, notifications, legal pages, integrations/webhooks, maintenance mode. Per-group save + audit.

### 8.27 Roles & permissions
**Now:** role CRUD with permission checkboxes.
**Do:** permission matrix (module × action), clone role, search, "select all in group", direct user permissions, guard against removing your own super-admin.

### 8.28 Audit & security
**Now:** audit index/show, security events, active sessions.
**Do:** sidebar entries for security events; filters + export; per-record activity tab; retention setting; revoke session; failed-login alerts.

### 8.29 Database, backups, health
**Now:** database info page only.
**Do:** backup create/download/delete (needs `ext-zip`), schedule status, restore runbook link; table sizes + optimize; migration status; queue/failed jobs (retry/delete); health panel (PHP version, extensions, disk, cache, storage link, mail test); maintenance toggle.

### 8.30 Global search & notifications
Command palette (⌘K) across orders/customers/products/pages/actions; recent items; keyboard hints. Notification centre page with read/unread, bulk mark, deep links.

---

## 9. Accessibility (WCAG 2.2 AA)

Current: `aria-` appears in **1 of 75 views**. Target:

- Skip link; landmarks (`header/nav/main`); one `h1` per page.
- All inputs labelled; `aria-describedby` for hint + error; `aria-invalid`; error summary focused on submit.
- Menus/modals/tabs: roles, `aria-expanded`, `aria-controls`, focus trap + restore, ESC.
- Tables: `<caption>`, `scope`, `aria-sort`; row actions reachable without hover.
- Toasts `role="status"` / `aria-live="polite"`; errors `assertive`.
- Contrast audit — champagne `#C8A968` on ivory is likely **below 4.5:1** for text; use `champagne-deep` for text, champagne for fills only.
- 44 px touch targets; visible focus; 200% zoom; reduced motion.

---

## 10. Responsive & performance

- Breakpoints: 360 / 640 / 768 / 1024 / 1280 / 1440.
- List pages: table → cards under 768 px; filters collapse into a sheet.
- Forms: single column, sticky action bar; long forms sectioned.
- Performance: eager-load relations (kill N+1 on order/customer lists), indexes on filter columns, debounce search 300 ms, paginate everything, lazy images, cache dashboard aggregates, single Vite bundle < 200 KB JS, LCP < 2.5 s on 4G.

---

## 11. Testing & QA

- Pest feature tests per module: index/create/update/delete + validation + authorization.
- **Permission matrix test:** every role × every admin route → expected 200/403.
- Regression tests for the two P0 business bugs (COD limit, order cancel).
- CSV import tests (valid/invalid/dry-run).
- Browser smoke: 360/768/1440, keyboard-only pass, axe scan.
- Seed a demo dataset covering every module (orders in each status, refunds, exchanges, enquiries, alerts) — needs `DatabaseSeeder` here.

---

## 12. Phased roadmap

| Phase | Deliverable | Est. |
|---|---|---|
| **0. Stabilise** ✅ | P0 defects 1–9, port the 2 checkout fixes, add seeder, fix dead links — **done 2026-10-21** | 1–2 d |
| **1. Foundations** ✅ | Blade components, page archetypes, shell/nav/breadcrumbs, confirm, 403/404/419/429/500/503 — **done 2026-10-21** (token audit: existing scale reused, no ad-hoc values) | 4 d |
| **2. Catalogue** ✅ | products (bulk/import/SEO/variants/images), categories tree, collections, inventory tabs/bulk/ledger/alerts — **done 2026-10-21** | 5 d |
| **3. Sales** ✅ | orders (filters/bulk/timeline/notes/cancel/export), payments, refunds, exchanges, shipping zones/shipments — **done 2026-10-21** | 6 d |
| **4. Customers & marketing** ✅ | customers segments/tags/LTV/notes/impersonate/GDPR, reviews reply + bulk moderation, Q&A module, coupons show/bulk-generate/usage, banners tracking/reorder, homepage builder duplicate/reorder/preview — **done 2026-10-22** | 5 d |
| **5. Content & comms** ✅ | blog, navigation menus, email templates, enquiries inbox, newsletter, CMS revisions — **done 2026-10-22** | 5 d |
| **6. Reports** ✅ | 7 new reports (products, coupons, tax, shipping, payments/reconciliation, refunds, exchanges), compare periods, Excel export, scheduled weekly email + "Email me", shared SVG chart component — **done 2026-10-22** | 3 d |
| **7. System** ✅ | settings groups (checkout COD limit, email + SMTP test send, notifications, legal, audit retention), roles permission matrix + clone + search, audit/security exports + related activity + session revoke + failed-login alerts + retention prune, system panel (health, backups, migrations, failed jobs, schedule, maintenance toggle) — **done 2026-10-22** | 3 d |
| **8. Polish** | a11y, responsive, perf, full test matrix, demo seed, QA | 4 d |

**Total:** ~37 working days for one developer; phases 2–4 can run in parallel with a second pair of hands.

### Acceptance criteria (applies to every page)
1. Reachable from the sidebar or a record action; no dead links.
2. Uses the shared layout, page header, breadcrumb, and components — no ad-hoc colours/spacing.
3. List: search + filters + empty state + pagination + bulk actions where destructive/mass.
4. Form: inline validation, error summary, unsaved guard, sticky actions.
5. Every action authorized server-side **and** hidden client-side without permission.
6. Keyboard-only usable; axe clean; 360 px layout intact.
7. Mutation audited; success/error toast.
8. Feature test + permission-matrix entry.

---

## 13. Decisions needed before Phase 1

1. **New modules in scope?** Blog, suppliers/PO, product Q&A, email templates, navigation menus — all have schema but no code. Recommend: blog + enquiries + newsletter yes; suppliers/PO later; Q&A yes (cheap).
2. **Refund execution:** call JazzCash/Easypaisa refund APIs, or record manual refunds only? (Affects 8.9.)
3. **Redirect-style `show` routes:** keep (fast edit) or remove from routes?
4. **Staff vs Users:** merge into one "Team" module, or keep separate with a nav entry?
5. **Backups:** install `ext-zip` on the server, or implement SQLite file snapshots instead?
6. **Visual direction:** keep champagne/ivory brand (recommended) vs neutral admin theme?
7. **Multi-warehouse:** are `store_locations` fulfilment locations, or just showrooms? (Affects inventory + shipping.)

---

## Appendix — route/view gap register

| Route | Controller | View | State |
|---|---|---|---|
| `admin.orders.edit/update` | — | — | ✅ removed from routes |
| `admin.coupons.show` | redirect → edit | — | ✅ works (was data-dependent 404) |
| `admin.refunds.store` | present | form on payment detail | ✅ reachable |
| `admin.products.show` | redirect → edit | — | 302 (by design?) |
| `admin.categories.show` etc. (9 total) | redirect → edit | — | 302 |
| `admin.security-events.index` | present | present | no sidebar link |
| `admin.users.index` | present | present | no sidebar link |
| `admin/backup` | — | empty dir | **no route** |
| `admin.reports.export` | present | 1 link (`_period`) | partial |
| `admin.notifications.*` | present | present | ✅ new |
| `admin.search` | present | present | ✅ new |

---

## Phase 4 status (done 2026-10-22)

**Customers**
- Segments as index tabs: `all`, `recently_joined` (last 30 days), `repeat` (≥ 2 delivered orders), `vip` (≥ Rs. 250,000 lifetime spend — `CustomerController::VIP_THRESHOLD`), `blocked`. Stats strip (total/new/repeat/VIP/blocked) per tab.
- Per-customer tags (comma-separated, case-insensitive dedupe, cast to array), internal notes, marketing-consent toggle, LTV + order stats.
- Bulk actions: activate / suspend / deactivate / consent on / consent off / export (redirects to filtered CSV export) — permission-gated (`customers.update`/`customers.delete`).
- GDPR: JSON export download (route `admin.customers.gdpr`) + erase & anonymize (`…/gdpr-erase` POST, audits + `data_deleted_at`).
- Impersonation: `admin.customers.impersonate` (POST) logs in as customer with `impersonator_id` in session; audited (`customers.impersonate.start/end`); champagne storefront banner with "Return to admin"; public exit route `POST /impersonation/exit` (`impersonation.exit`). Staff/suspended customers are refused (403).

**Reviews**
- Index tabs (all/pending/published/featured/reported) + search/rating filters, bulk approve/reject/feature/unfeature/delete.
- Reply on show page saves `admin_response` + sends `ReviewReplyMail` to registered reviewers (guests silently skipped); audited.

**Product Q&A** *(new module — `questions` permission set incl. `questions.answer`)*
- Moderation index with tabs (all/pending/unanswered/published), search filter, bulk approve/unpublish/delete.
- Answer inline via `<details>` form; `answered_at`/`answerer` recorded; approve/reject/delete AND bulk; audited. Admin-only (no storefront ask form yet — **deferred**).

**Coupons**
- `show` page: redemption table (order/customer/discount/date), validity & restrictions sidebar, stats (unique customers, discount given, AOV, first/last redemption).
- Bulk generate: `admin.coupons.generate` form → `generate.store` creates N unique `PREFIX-<random>` codes sharing one discount config (unique check incl. trashed).
- Index rewritten: stats strip, status/type filters, bulk activate/disable/archive/export; CSV export with same filters.
- Fix: `usageStats()` now qualifies `coupon_redemptions.coupon_id` (ambiguous vs `orders.coupon_id` on join).

**Banners**
- New columns: `alt_text`, `impressions`, `clicks` (migration `2026_10_22_000001_add_customer_tags_and_banner_tracking`).
- Index rewritten: per-placement grouping, schedule columns, impressions→clicks + CTR, bulk publish/unpublish/delete, move up/down (POST forms) within placement, stats strip.
- Form gains alt_text (+ hint). Public click-through route `GET /banners/{banner}/click` (`banners.click`) — increments clicks, redirects to target (or shop fallback); unpublished 404. HomeController records impressions on home render.

**Homepage builder**
- Index rewritten: stats strip, per-row move up/down, publish/unpublish, duplicate (creates unpublished draft `copy-…{key}` with fresh uuid), bulk publish/unpublish/duplicate/delete, schedule "live window" badge.
- Storefront `?preview=1` (staff only) renders all sections incl. drafts, with a preview banner.

**Permissions**: `questions.*` module added to `RolesAndPermissionsSeeder` (+ `customers.impersonate`); marketing-manager gains questions module; customer-support gains `questions.view/update/answer`. **Seeded on dev DB** (run `php artisan db:seed --class=RolesAndPermissionsSeeder` on any other environment).

**Validation**
- Tests: 79 passed (455 assertions) incl. 14 new in `tests/Feature/AdminMarketingTest.php`.
- `php artisan view:cache` clean; `npm run build` clean; admin GET sweep 20/20 routes < 500 with seeded data.

**Deferred (parked)**
- Storefront Q&A display per product page (index + render approved Q&A).
- Per-section config forms for the homepage builder beyond key/title/subtitle/view/schedule.
- Rich moderation detail for review images (thumbs are currently listed as text/CDN paths on the show page).
- Newsletter double-opt-in flow touches Phase 5; customer-level "email preferences" UI (beyond consent flag) tagged for Phase 7 settings.

---

## Phase 5 status (done 2026-10-22)

**Blog**
- Posts CRUD with tag management (tags typed as comma-separated names, resolved case-insensitively; unknown tags auto-created); slug auto-generated via `GeneratesSlug` with uniqueness.
- Publishing semantics: "publish" with a **future date is normalised to `scheduled`** (controller `finalise()`); status filter tabs incl. a scheduled view; draft/published/scheduled/archived states.
- Per-post preview route (`admin.blog.preview`) renders outside the admin chrome; storefront `GET /blog` (`blog.index`) + `GET /blog/{post:slug}` (`blog.show`) with tag sidebar & per-post related tags.
- Storefront tag sidebar uses `withCount` + PHP `filter()` — **SQLite rejects `HAVING` without an aggregate**, do not "simplify" back.

**Navigation menus**
- Builders per location (`main` / `mobile` / `footer` locational records); one nesting level (top-level rows may carry a dropdown of children); link types `page` / `category` / `collection` / custom URL; per-item visibility toggle; move up/down + remove via Alpine editor, items serialized to JSON `items` column. Slug auto-generated server-side (`Str::slug(name)` + uniqueness loop — column is NOT NULL, never trust the client).
- Storefront: header (desktop + mobile drawer) and footer render `NavigationMenu::visibleItems()`; gracefully **fallback to the hardcoded links when no menu exists** for a location.
- Note: `visibleItems()` must resolve URLs via `resolveUrl()` for **both items and children** (stored key is `link`, not `url`).
- Blade gotcha: never pass Alpine runtime strings (`:name="'label-'+i"`) into `<x-*>` component tags — Blade compiles the attribute as PHP, so an undefined variable becomes "Undefined constant". Use static component names inside `x-for` templates and bind the dynamic data with `x-model` on plain inputs.

**Email templates**
- Editable transactional templates with event `key`, variables palette (order/customer tokens like `{{order_number}}` shown literally with HTML entities to survive Blade), live template preview route (`admin.emails.preview`), **test send** to the current staff member via `TemplateTestMail` Mailable, enable/disable toggle (`admin.emails.toggle`).
- Mailables used for *both* template test-send and enquiry replies (`TemplateTestMail` / `EnquiryReplyMail`) because `Mail::fake()` does not record raw `Mail::html()` sends — feature tests assert `Mail::assertSent(...)`.
- Mailable gotcha: the `Mailable` base owns a non-readonly `$subject` — a promoted constructor property must not be named `subject` (use `renderedSubject`/`renderedBody`); and `Content:view` must reference the actual Blade name (`emails.template-preview`, not `.blade.php`).

**Contact enquiries (inbox)**
- Index with status tabs (new/open/resolved/closed), search, deduped-unread badges; show page with assign-to-staff (`admin.enquiries.assign`), reply email to customer via `EnquiryReplyMail` with optional auto-resolve (`resolve` checkbox → `status: resolved`), internal notes.
- Bulk actions `open/resolve/close` route through a `statusMap` — **the action verbs (`resolve`, `close`) differ from the enum values (`resolved`, `closed`)**; writing the raw action breaks the status CHECK constraint.
- CSV export (`admin.enquiries.export`) honours the current filter.

**Newsletter**
- Subscriber list: subscribed/unsubscribed tabs + search; CSV export; bulk unsubscribe/delete; emails sourced from storefront footer signup + account opt-in.

**CMS revisions + preview**
- `cms_page_revisions` table (migration `2026_10_22_000002_create_cms_page_revisions`); `CmsPage::revisions()`.
- Snapshot recorded **pre-update** in `CmsPageController::update()`; `admin.pages.revisions` lists snapshots w/ author + diff summary; **restore snapshots the CURRENT state first**, so a restore is itself reversible; restore audited (`cms.restore`).
- Staff-only preview (`admin.pages.preview`) renders unpublished templates/drafts outside the admin chrome.

**Permissions**: new modules `blog`, `menus`, `email_templates` added to `RolesAndPermissionsSeeder` for super-admin **and marketing-manager** (`->parameter()` overrides on the `blog`/`emails` resources keep route params `{blog_post}`/`{email_template}` in sync with controller signatures). Enquiries continue on the existing `contacts.*` module (customer-support role) — no new module needed. **Seeded on dev DB** (run `php artisan db:seed --class=RolesAndPermissionsSeeder` on any other environment).

**Validation**
- Tests: 86 passed (531 assertions) incl. 7 new Phase 5 tests in `tests/Feature/AdminContentTest.php` covering blog scheduling, menus, email templates (variables, preview, test send, toggle), enquiry inbox (assign/reply/bulk), newsletter export/bulk, CMS revisions/restore.
- `php artisan view:cache` clean; `npm run build` clean; admin GET sweep **41/41 routes < 500** with seeded data (Phase 4 + Phase 5 + storefront blog).

**Deferred (parked)**
- Rich-text (WYSIWYG) editor for blog post content and CMS pages (plain textarea + preview today).
- Per-event email template mapping UI (auto-select template per checkout/lifecycle event rather than hardcoded keys).
- Auto-seeding of default email templates on deploy (currently manual `EmailTemplate::create`).
- Blog "related products" linking on showcase posts.
- Drag-and-drop menu item reordering (move up/down buttons today).

## Phase 6 status (done 2026-10-22)

**Seven new reports** (one over the roadmap's six — exchanges was cheap and §8.25 lists it explicitly)
- `ReportController` gained `products` (units/revenue/discount/COGS by product, joined to orders in selling statuses), `coupons` (redemptions, discount given, influenced revenue, avg coupon order), `tax` (collected by province via `order_addresses` + daily trend), `shipping` (per-courier funnel + top destination cities), `payments/reconciliation` (captured/refunded/net/failed by provider & method with computed success rate), `refunds` (funnel by status/type + top reasons + latest list), `exchanges` (type/status funnel + open work queue).
- `sellingStatuses()` = pending→delivered/partially_fulfilled (excludes cancelled/returned churn) — shared by aggregates and exports.
- All eleven report slugs live in `ReportController::TYPES` (used by both `whereIn` route constraints and the email endpoint).

**Compare periods**
- `compareWindow()` shifts the window back by `diffInDays(from,to)+1` (previous period of identical length); `deltas()` produces per-key `{text: "+x.x% vs previous" | "new this period" | "no change", trend}` rendered through the existing `x-admin.stat` delta/trend props via the new `_stats` partial.
- Wired into **sales, customers, staff-activity** and all seven new reports; inventory opts out (`hasCompare => false` hides the checkbox — window-based compare is meaningless for snapshot data).

**Excel export**
- `rowsFor()` returns `[headings, rows]` per slug, shared by streamed CSV, XLSX (`Maatwebsite\Excel\Facades\Excel` + generic `App\Exports\ReportExport` FromArray/WithHeadings/WithTitle) and the email attachment (`Excel::raw`, 500-row cap).
- Route accepts `?format=csv|xlsx`; buttons "CSV"/"Excel" live in the shared `_period` partial (wrapped in `@can('reports.export')`).

**Scheduled email**
- `SendWeeklyReportDigest` (`haarsingharr:weekly-report`) schedules **Mondays 07:00**, mails the past-7-days sales XLSX to all super-admins (`User::type=admin` filtered by `isSuperAdmin()`), mirroring `SendNewsletterDigest` conventions.
- **"Email me"** button on every report queues `ReportEmailMail` to the current staff member (audited `reports.email`).

**Chart component**
- `components/admin/chart.blade.php` — dependency-free **SVG** line/bar chart (`labels`, `series`, `type`, `money` props, legend, sparse axis labels, 4 lattice ticks) with an accessible `<details>` **table fallback**; figures render only when data exists (`@unless empty(labels)`). Used by sales (line + previous-period overlay), products, coupons, tax, payments (captured vs failed), refunds, exchanges, customers (new-per-day bars).

**Gotchas hit this phase (recur-proofing)**
- `Route::whereIn('type', ReportController::TYPES)` needs an **array** — string explodes at route registration.
- Class constants are **not** proxied by the Maatwebsite facade — `Excel::XLSX` on the facade is an undefined constant; use `Maatwebsite\Excel\Excel::XLSX` (imported with an alias) or the literal `'Xlsx'`.
- `Mailable` base owns non-readonly `$from`/`$subject` — promoted report props renamed to `$periodFrom`/`$periodTo` (same trap as `subject` in Phase 5).
- `ReportController::export()` return type must cover `StreamedResponse|BinaryFileResponse` — XLSX downloads are BinaryFileResponse.
- Plain `function ($r) use ($n)` closures do **not** auto-capture outer locals (arrow fns only) — cost one dev-DB sweep failure that tests missed because the payments table was empty in the test DB (export tests now seed a payment+refund so the rows actually build).
- Streamed download bodies are not capturable in Laravel's test client — assert headers only (charset suffix varies per stack: `text/csv; charset=utf-8` locally, `UTF-8` in AdminSalesTest precedent).
- Physical `created_at` in tests must fall **inside** the compared window: previous window for the default month view starts ≥ diff+1 days back (e.g. `subDays(35)` works, `subDays(20)` lands in the current window).

**Validation**
- Tests: **110 passed (605 assertions)** incl. 24 in `tests/Feature/AdminReportsTest.php` (7 page datasets, per-report aggregates/chart/province/provider/courier/coupon/funnel checks, compare deltas, CSV+XLSX export datasets, "email me" queue + `hasTo`/attachment, weekly digest assertSent).
- `php artisan view:cache` clean; `npm run build` clean; dev-DB GET sweep **65/65 routes < 500** (41 prior + 24 Phase 6 report pages incl. compare + both export formats) with a seeded order+item+address+payment+refund+shipment+exchange+coupon-redemption fixture.
- Scheduler verified via `routes/console.php` (`weeklyOn(1, '07:00')`).

**Deferred (parked under §8.25)**
- Saved reports (named/shareable), per-report permission split beyond `reports.view`/`reports.export`, reporting granularity switch (day/week/month roll-ups), customer cohorts/retention report.

## Phase 7 status (done 2026-10-22)

**Settings groups (§8.26)**
- `SettingsController::schema()` gained `checkout` (**COD max order value Rs. 200 000 surfaced**, min order value, max quantity/item, COD checkout note), `email` (from name/address + staff notification inbox), `notifications` (new-order / low-stock / failed-login booleans), `legal` (terms/privacy/returns URLs) and `system` (`audit_retention_days`, default 365).
- **SMTP test send**: POST `settings/email-test` → new `PlainNoticeMail` (generic text mailable, `plain-notice` text blade) to any address, audited `settings.email_test`; card on the settings index shows the active mailer/host/from read-only (credentials stay in `.env`/cPanel).
- Per-group save already existed (only submitted modules persist) — all new groups ride the same `update()` + audit.

**Roles & permissions (§8.27)**
- **Permission matrix**: `GET/POST roles/matrix` — every role a column, permissions grouped module × action rows, one POST re-syncs each submitted column (diff audited as `roles.matrix` with granted/revoked lists). Signed-in super-admin's own column is rendered read-only and skipped on save ("never silently strip your own access"), `update()` blocks editing your own super-admin role outright.
- **Clone role**: POST `roles/{role}/clone` → `-copy` slug with synced permissions, audited `roles.clone`. **Search** on roles index (`q` over title/slug/description).
- "Select all in group" + direct user-to-permission grants: deferred (see below).

**Audit & security (§8.28)**
- Filters refactored into shared `filteredLogs()`/`filteredEvents()` builders used by index pages **and** the new `audit-logs/export` + `security-events/export` streamed CSVs (5000-row cap).
- Audit **show** now renders a "Related activity" card (same target + same actor, recent 10 each). **Revoke session**: POST `active-sessions/{session}/revoke` (deletes the ActiveSession row + database-driver session row, audited `security.session.revoke`, confirm-gated button).
- **Failed-login alerts**: new `FailedLoginAlert` service — ≥5 `login.attempt/login.suspended` failures for the same email inside 15 min → `SecurityEvent::log('login.alert', …)` + `PlainNoticeMail` to all super-admins, gated by `notify_failed_login` setting (default on) and a `Cache::add` one-shot flag (one alert per email per window). Wired into `LoginController`'s failure branch.
- **Retention**: `haarsingharr:prune-audit` command (min window 30 days, reads `audit_retention_days`), scheduled daily 03:00.

**System panel (§8.29)** — `SystemController` at `admin.system.index`, all routes guarded `settings.view`/`settings.update`
- **Health grid**: PHP/Laravel versions, DB ping, cache probe, storage symlink, disk free %, extension checks (zip flagged as archive-only dependency), environment/debug, mailer, queue connection.
- **Backups**: spatie/laravel-backup enumerated through its supported `BackupDestination::create($disk, $name)->backups()` API (path/size/date/stream/delete); create = `backup:run` guarded by `class_exists('ZipArchive')` (missing locally → honest flash, no crash); download streams via `Backup::stream()`; delete via `Backup::delete()`. `config/backup.php` published: name `haarsingharr-backup`, tightened excludes. Schedule: `backup:clean` 02:15 + `backup:run --disable-notifications` 02:30 (`withoutOverlapping`); restore runbook text on the page.
- **Migrations & schedule**: `migrate:status` + `schedule:list` output rendered in `<pre>` panels.
- **Failed queue jobs**: list 25 most-recent, retry/forget wrapped in try/catch (bogus payloads flash an error, never 500), flush, audited.
- **Maintenance**: POST `system/maintenance` **requires typed confirmation `MAINTENANCE`**; `artisan down --secret=<random24> --render errors.503`, `up` to re-open, bypass URL flashed; custom `App\Http\Middleware\PreventRequestsDuringMaintenance` now **extends the framework one** so the secret path/bypass cookie work (it previously ignored the secret entirely) while keeping the `MAINTENANCE_ALLOWED_IPS` allowlist.
- Sidebar: System group now links "System Health" between Store Settings and Database.

**Gotchas hit this phase (recur-proofing)**
- `AuditService::record()` signature is `(action, target, ?array previous, ?array after, ?string module)` — passing a model as "previous" is a TypeError (cost one test failure).
- Custom `permissions` schema requires `slug` (unique) in addition to `name` — scaffolded permissions in tests must include it.
- `Route::resource('roles', …)` **swallows** `GET roles/matrix` (`{role}` matches "matrix") and `audit-logs/{audit_log}` swallows `audit-logs/export` — sub-routes must be registered **before** the resource.
- Laravel 12's `MailFake::raw()` is a **no-op** — `Mail::raw` sends/assert nothing under `Mail::fake()`. Converted both raw sends (SMTP test, login alert) to the `PlainNoticeMail` mailable and assert `assertSent(PlainNoticeMail::class, n)`.
- `AuditLog` uses `SoftDeletes` — retention pruning is a **hard delete** (`forceDelete()`, incl. trash older than cutoff); `AuditLog::create` ignores non-fillable `created_at`, so tests seed old rows via `DB::table('audit_logs')->insert`.
- Maintenance bypass in Laravel 12 is a **cookie** (`/{secret}` issues `laravel_maintenance`), not a path prefix — the test client does not persist response cookies, so tests must extract the cookie and replay it via `withUnencryptedCookies()`.
- `schedule:list` output *does* contain command signatures — assertable directly.

**Validation**
- Tests: **133 passed (701 assertions)** incl. 24 Phase 7 tests in `tests/Feature/AdminSystemTest.php` (matrix apply/guard, clone, search, exports, related activity, revoke, alert burst+silence+threshold+real login route, settings groups persistence, SMTP test, system panel, backups guard, failed-job lifecycle, maintenance down/bypass/up + confirmation, prune command) — up from 110/605 after Phase 6.
- `php artisan view:cache` clean; `npm run build` clean; dev-DB GET sweep **70/70 routes < 500** (65 prior + roles matrix, audited roles search, audit + security-events CSV exports, system panel).
- ext-zip absent on the dev box: backup create test self-skips when installed; download/delete/panel unaffected.

**Deferred (parked under §8.26–§8.29)**
- "Select all in group" client-side helper + direct user-level permission grants UI (`user_permissions` table exists but has no admin screen yet).
- Integrations/webhooks settings group; real "currency & tax" group beyond the general-tab fields.
- Database table **sizes** (information_schema data/index lengths) + per-table optimize buttons — current database page shows row counts.
- `spatie/laravel-health` package-grade checks (pingable endpoints, scheduled check runs).

## Phase 8 progress (2026-10-25)

- Product entry is now guided in four short steps (details, photos, price, store settings); product editing has section jump links and a persistent save bar so the long advanced editor is easier to move through.
- Storefront editorial hero artwork added in PNG and optimized WebP formats; product cards and mobile purchase actions refined, product variant selection is shared with the sticky mobile action, and simple products have an add-to-bag path.
- Shop mobile filter state, checkout choice states, address prefill route, FAQ disclosure semantics, and shared navigation behavior improved.
- Admin and account destructive actions moved from browser `confirm()` prompts to the reusable confirmation dialog. Dialogs now restore focus and trap keyboard focus; nested delete forms were removed from category, FAQ, coupon, and CMS edit forms.
- Admin modal and confirmation dialogs now share a reference-counted body scroll lock, so a closed dialog instance cannot re-enable background scrolling while another dialog is still open. Static audit found no remaining native `confirm()` calls in admin views; modal open/close event names were checked against the declared dialogs.
- Product creation now accepts real product photos before the first save, displays local previews, validates up to ten images (5 MB each), assigns the first upload as the cover, and rolls back stored files if the product transaction fails. The edit image manager previews selected uploads and links stored photos to their full-size originals.
- User search now groups OR conditions under the active filters. User and role assignment enforce the actor's existing permissions, preserve permissions the actor cannot manage, and protect administrator accounts from lower-privilege editing.
- Privileged-staff MFA now has the missing enrollment, challenge, and verification routes; TOTP validation, encrypted recovery codes, one-time recovery use, and throttled verification are implemented. The super-admin role is read-only to non-super-admins.
- A static scan across Blade views, controllers, services, and routes found the missing `account.addresses.fill` route; the existing authorized controller action is now reachable. The scan resolves all 306 unique route-helper references.
- `php artisan view:cache`, PHP syntax checks, `php artisan route:list` (352 registered routes), `npm run build`, and targeted `git diff --check` completed. Full Pest and browser/viewport sweeps remain outstanding.
