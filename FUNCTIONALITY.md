# KotBean — Functionality Document

**Version:** September 2026  
**Production URL:** https://go.snookeraddabhilai.top  
**Stack:** Laravel 13 · PHP 8.4 · Livewire 4 · Tailwind · MySQL · Redis · Docker

KotBean is a restaurant operating system for cafés and restaurants. It combines **Point of Sale (POS)**, **Kitchen Order Tickets (KOT)**, **inventory**, **billing**, **customer engagement**, **promotions**, and **financial tracking** in a single multi-user web app with optional PWA and Android (Capacitor) support.

---

## Table of Contents

1. [Overview](#overview)
2. [User Roles & Permissions](#user-roles--permissions)
3. [Module Guide](#module-guide)
4. [Order Lifecycle](#order-lifecycle)
5. [Integrations](#integrations)
6. [Mobile & PWA](#mobile--pwa)
7. [Deployment](#deployment)
8. [Known Gaps (Backend Exists, UI Missing)](#known-gaps-backend-exists-ui-missing)
9. [Suggested Enhancements (Roadmap)](#suggested-enhancements-roadmap)

---

## Overview

| Area | Status |
|------|--------|
| Dashboard & analytics | ✅ Live |
| Point of Sale (POS) | ✅ Live |
| Kitchen display (KOT) | ✅ Live |
| Orders history | ✅ Live |
| Table management | ✅ Live (optional per restaurant) |
| Menu / products | ✅ Live |
| AI product images | ✅ Live |
| Inventory tracking | ✅ Live (view + auto-deduct) |
| Customers | ✅ Live |
| WhatsApp messaging (Twilio) | ✅ Live |
| Discounts & promotions | ✅ Live |
| Reports & CSV export | ✅ Live |
| Expenses | ✅ Live |
| Vendor ledger | ✅ Live |
| Staff management | ✅ Live |
| Settings & branding | ✅ Live |
| PWA install | ✅ Live |
| Android app (Capacitor) | ✅ Shell ready |
| REST API | ❌ Not built |
| iOS app | ❌ Not built |

---

## User Roles & Permissions

Each user belongs to one **restaurant** and one **role**. Permissions are checked via middleware and inside Livewire actions.

### System Roles

| Role | Typical users | Access summary |
|------|---------------|----------------|
| **Owner** | Business owner | Full access to everything |
| **Manager** | Shift manager | All except staff management |
| **Cashier** | Billing counter | POS, orders, menu view, customers, promotions view |
| **Waiter** | Floor staff | Same as cashier + table management |
| **Kitchen** | Kitchen staff | Kitchen display + order view only |

### Permission Groups

| Group | Permissions |
|-------|-------------|
| Dashboard | `dashboard.view` |
| POS | `pos.access` |
| Orders | `orders.view`, `orders.manage` |
| Kitchen | `kitchen.access` |
| Tables | `tables.view`, `tables.manage` |
| Menu | `menu.view`, `menu.manage` |
| Inventory | `inventory.view`, `inventory.manage` |
| Customers | `customers.view`, `customers.manage` |
| Promotions | `promotions.view`, `promotions.manage` |
| Reports | `reports.view`, `revenue.view` |
| Staff | `staff.manage` |
| Settings | `settings.manage` |
| Payments | `payments.process` |
| Expenses | `expenses.view`, `expenses.manage` |

> **Note:** After adding new permissions, run `php artisan db:seed --class=PermissionSeeder --force` to sync them to system roles.

### Authentication

- Login with **email** or **10-digit mobile number**
- Staff accounts use auto-generated emails: `{phone}@staff.{restaurant-slug}.kotbean`
- Laravel Breeze auth (register, forgot password, email verification)
- Inactive users are blocked at login

---

## Module Guide

### 1. Dashboard (`/dashboard`)

Real-time business overview for the current day and recent trends.

- Today's sales, orders, average order value, discounts, expenses, gross profit
- Sales chart: today / yesterday (hourly) or last 7 / 30 days (daily)
- Best-selling products
- Low-stock alerts
- Payment method breakdown
- Recent orders list
- Top categories
- Active order status counts

---

### 2. Point of Sale — POS (`/pos`)

The core billing interface optimized for tablets and desktops.

**Order types:** Dine-in · Takeaway · Delivery

**Features:**
- Product grid with category filter and search
- Product variants (size/flavor) via modal when configured
- Table selection for dine-in (when tables enabled)
- Cart: add, increment, decrement, remove items
- Stock validation — blocks overselling for tracked inventory
- Customer capture (name + phone) — links order to customer record
- Order notes and delivery address
- **Promotions:** auto-apply best eligible discount; manual promo code entry
- Send to kitchen (creates KOT)
- Payment collection: Cash, UPI, Card, Other
- Draft orders can be saved and resumed via `/pos/{order}`

**Cart totals:** Subtotal → Tax → Discount (if applied) → Total

---

### 3. Kitchen Display (`/kitchen`)

Full-screen KOT board for kitchen staff.

- Columns: **New** → **Cooking** → **Ready**
- One-click status advance (Pending → Preparing → Ready → Served)
- Auto-refresh every 5 seconds
- **Sound alert** when new pending KOTs arrive (requires one-time "Enable Sound" click)
- Shows table number, order items, quantities, and notes
- Dedicated kitchen layout (no sidebar clutter)

**Print:** KOT ticket available at `/kots/{kot}/print` (route exists; no in-app button yet)

---

### 4. Orders (`/orders`)

Historical order list with filters.

- Filter by status and date range (defaults to today)
- Order number, type, status, total, date
- Tenant-scoped (each restaurant sees only its orders)

---

### 5. Tables (`/tables`)

Visual table management when enabled in Settings.

- Tables grouped by zone
- Status: Available · Occupied · Reserved · Cleaning
- Manual status updates
- Hidden from navigation when `tables_enabled = false`

---

### 6. Menu (`/menu`)

Product catalog management.

**Products list:**
- Search and category filter
- Availability indicator
- Delete product (soft delete, requires `menu.manage`)

**Create / Edit product:**
- Name, description, price, cost price, SKU
- Category assignment
- Tax rate override
- Stock tracking toggle, min stock, current stock
- Availability toggle
- Has variants flag
- **AI image generation** — queue OpenAI image job; polls until complete
- Sort order

> Categories exist in the database (seeded) but have **no management UI** yet.

---

### 7. Inventory (`/inventory`)

Stock visibility and transaction history.

- Low-stock product alerts
- Total stock value metric
- Paginated transaction log (sale, restock, wastage, cancellation, etc.)
- Stock is **automatically deducted** when an order is completed (not when sent to kitchen)

> Manual stock adjustment is supported in the backend service but has **no UI** yet.

---

### 8. Customers (`/customers`)

Customer relationship view built from POS order data.

**All Customers tab:**
- Search by name, phone, email
- Total orders, total spent, last order date

**WhatsApp tab:**
- Lists customers with phone + at least one order
- Message preview for last order recap
- Optional custom note appended to template
- Send via Twilio WhatsApp (requires `customers.manage`)
- Uses approved Twilio Content Template (`ContentSid`)

---

### 9. Discounts & Promotions (`/promotions`)

Create and manage promotional discounts.

| Field | Description |
|-------|-------------|
| Name | Display name (e.g. "Morning 10% Off") |
| Promo code | Optional — for manual entry at POS |
| Type | Percentage off or fixed ₹ amount |
| Value | Discount amount (% or ₹) |
| Auto apply | Automatically applied at POS when eligible |
| Expiry | Optional end date/time |
| Quantity | Max total uses (blank = unlimited) |
| Rule | See rules below |
| Min order amount | Optional minimum cart value |
| Active | Enable/disable without deleting |

**Promotion rules:**

| Rule | Behavior |
|------|----------|
| No special rule | Any eligible order qualifies |
| First customer of the day | Only the first completed order each day |
| Customer's first order ever | Only customers with zero prior orders |

**At POS:**
- Auto-apply picks the **best** (highest value) eligible promotion
- Manual codes applied under Customer & notes → Promo code
- Discount shown in cart and on printed invoice
- Usage tracked on order completion

---

### 10. Reports (`/reports`)

Business intelligence for any date range.

- Sales summary: orders, gross, tax, discounts, net
- Product performance table
- Payment method breakdown
- **CSV download** (`/reports/download?date_from=&date_to=`)

---

### 11. Expenses (`/expenses`)

Track business operating costs.

- Title, category, amount, date, notes
- Link to vendor (optional)
- Payment status: Paid · Pending · Partial
- Partial payments: paid amount, due date
- Create, edit, delete (requires `expenses.manage`)

---

### 12. Vendors & Ledger (`/vendors`, `/vendors/ledger`)

Supplier management and accounts payable.

**Vendors:**
- Name, phone, email, address, notes
- Expense count per vendor

**Vendor ledger:**
- Filter by vendor, payment status, date range
- Summary: total billed, paid, outstanding
- Record partial payment or mark expense as fully paid

---

### 13. Staff (`/staff`)

Add team members without email setup.

- Create staff: name, 10-digit phone, role
- Auto-generates login email and random password (shown once)
- Staff log in with phone number
- Audit log on creation
- Only owners can assign the owner role

> Edit, deactivate, or reset password UI **not built yet** (`is_active` field exists on users).

---

### 14. Settings (`/settings`)

Restaurant configuration and branding.

- Business name, address, phone, email, GSTIN
- Default tax rate, timezone
- Logo upload / remove
- Theme color (9 presets + custom hex) — applied across the app
- **Enable / disable table management** — affects POS and navigation

---

## Order Lifecycle

```
┌─────────┐    Add items     ┌───────────┐   Send to kitchen   ┌─────────────┐
│  DRAFT  │ ───────────────► │ CONFIRMED │ ──────────────────► │ KOT CREATED │
└─────────┘                  └───────────┘                     └──────┬──────┘
     │                                                                 │
     │ Apply promo                                                     │ Kitchen advances
     │ Attach customer                                                 ▼
     │                                                          ┌─────────────┐
     │                                                          │  PREPARING  │
     │                                                          └──────┬──────┘
     │                                                                 │
     │ Collect payment                                                 ▼
     ▼                                                          ┌─────────────┐
┌─────────────┐   completeOrder()   ┌───────────┐               │    READY    │
│    PAID     │ ◄────────────────── │  PAYMENT  │               └──────┬──────┘
└──────┬──────┘                     └───────────┘                      │
       │                                                                ▼
       │ Inventory deducted                                    ┌─────────────┐
       │ Customer stats updated                                │   SERVED    │
       │ Promotion redemption recorded                         └─────────────┘
       │ Table released
       ▼
┌───────────┐
│ COMPLETED │
└───────────┘
```

**Tax calculation:** Per-product `tax_rate`, falling back to restaurant `default_tax_rate`.

**Cancellation:** Supported in `OrderService` (restores inventory, releases table, cancels KOTs) — **no UI button yet**.

---

## Integrations

### Twilio WhatsApp

| Env variable | Purpose |
|--------------|---------|
| `TWILIO_SID` | Account SID |
| `TWILIO_AUTH_TOKEN` | Auth token |
| `TWILIO_WHATSAPP_FROM` | Sender number (`whatsapp:+...`) |
| `TWILIO_WHATSAPP_CONTENT_SID` | Approved template SID (`HX...`) |

Template variables: `{{1}}` name · `{{2}}` restaurant · `{{3}}` order # · `{{4}}` date · `{{5}}` items · `{{6}}` total · `{{7}}` note

### OpenAI (Product Images)

| Env variable | Purpose |
|--------------|---------|
| `AI_IMAGE_DRIVER` | `fake` or `openai` |
| `OPENAI_API_KEY` | API key |
| `OPENAI_IMAGE_MODEL` | Model name |
| `OPENAI_IMAGE_SIZE` | e.g. `1024x1024` |

Requires queue worker: `php artisan queue:work`

---

## Mobile & PWA

### Progressive Web App

- Installable on phones/tablets (Add to Home Screen)
- Offline fallback page
- Install banner component
- Dynamic manifest at `/manifest.webmanifest`

### Android (Capacitor)

- App ID: `com.kotbean.app`
- WebView shell pointing to Laravel server via `CAPACITOR_SERVER_URL`
- npm scripts: `mobile:sync`, `mobile:android`, `mobile:run:android`

---

## Deployment

### Docker services

| Service | Purpose |
|---------|---------|
| `kotbean-app` | PHP 8.4-FPM (Laravel) |
| `kotbean-web` | Nginx |
| `kotbean-db` | MySQL 8.0 |
| `kotbean-redis` | Redis |
| `phpmyadmin` | Database admin |

### Production checklist

```bash
# Deploy code
git pull

# Backend
composer install --no-dev
php artisan migrate --force
php artisan db:seed --class=PermissionSeeder --force
php artisan config:cache
php artisan view:cache

# Frontend (no Node on server — build locally and sync)
npm run build
rsync -avz public/build/ server:/path/to/kot-bean/public/build/

# Queue (for AI images)
php artisan queue:work --daemon
```

### Demo credentials (seeded)

| Role | Email | Password |
|------|-------|----------|
| Owner | owner@beanbrew.cafe | password |
| Manager | manager@beanbrew.cafe | password |
| Cashier | cashier@beanbrew.cafe | password |
| Waiter | waiter@beanbrew.cafe | password |
| Kitchen | kitchen@beanbrew.cafe | password |

---

## Known Gaps (Backend Exists, UI Missing)

These features have service-layer or route support but no user-facing UI yet:

| Feature | What exists | What's missing |
|---------|-------------|----------------|
| Order cancellation | `OrderService::cancel()` | Cancel button on Orders page |
| Invoice print | `/orders/{order}/invoice` route + Blade | Print button in Orders/POS |
| KOT print | `/kots/{kot}/print` route + Blade | Print button in Kitchen |
| Inventory adjustment | `InventoryService::adjustStock()` | Adjust stock form on Inventory page |
| Category management | `categories` table + seeder | CRUD page for categories |
| Product variants | DB schema + POS variant modal | Variant CRUD on product form |
| Staff edit/deactivate | `users.is_active` field | Edit staff UI |
| Profile page | Breeze profile views | Routes not registered in `web.php` |

---

## Suggested Enhancements (Roadmap)

Prioritized ideas based on what's already built and typical restaurant needs.

### High priority — completes existing gaps

| # | Feature | Why |
|---|---------|-----|
| 1 | **Print buttons** (invoice + KOT) | Routes exist; staff need one-click print |
| 2 | **Order cancel UI** | Service ready; managers need to void mistaken orders |
| 3 | **Category CRUD** | Products depend on categories; currently seed-only |
| 4 | **Product variant editor** | POS supports variants but can't create/edit them |
| 5 | **Inventory adjustment UI** | Restock/wastage without workarounds |
| 6 | **Staff edit & deactivate** | Disable ex-employees; reset passwords |

### Medium priority — revenue & operations

| # | Feature | Why |
|---|---------|-----|
| 7 | **Split bill / partial payments** | Groups often pay separately |
| 8 | **Table merge & transfer** | Move orders between tables mid-service |
| 9 | **Order hold / recall** | Pause an order and resume later |
| 10 | **Daily cash register close** | Opening float, expected vs actual cash |
| 11 | **Low-stock auto alerts** | Email/WhatsApp when stock hits minimum |
| 12 | **Customer loyalty points** | Reward repeat customers (stats already tracked) |
| 13 | **Scheduled promotions** | Start/end times, day-of-week rules |
| 14 | **Combo / meal deals** | Bundle products at fixed price |
| 15 | **GST invoice format** | HSN codes, CGST/SGST breakdown for India |

### Medium priority — customer engagement

| # | Feature | Why |
|---|---------|-----|
| 16 | **Auto WhatsApp on order complete** | Send thank-you without manual action |
| 17 | **SMS fallback** (Twilio SMS) | Reach customers without WhatsApp |
| 18 | **Feedback link after order** | Google review / rating collection |
| 19 | **Birthday / anniversary promos** | Customer DOB field + scheduled message |

### Lower priority — scale & platform

| # | Feature | Why |
|---|---------|-----|
| 20 | **Multi-location** | One owner, many branches (currently single-restaurant) |
| 21 | **REST API + mobile ordering** | Customer-facing order app |
| 22 | **Online ordering widget** | Embed menu on website |
| 23 | **Kitchen printer integration** | ESC/POS thermal printer auto-print on KOT |
| 24 | **Barcode / QR menu** | Scan-to-order at table |
| 25 | **iOS Capacitor target** | App Store presence |
| 26 | **Role permission editor UI** | Custom roles without database changes |
| 27 | **Audit log viewer** | See who changed what (logging exists) |
| 28 | **Backup & export** | Full data export for compliance |
| 29 | **Dark kitchen / cloud kitchen mode** | Delivery-only workflow |
| 30 | **Recipe / BOM costing** | Ingredient-level cost tracking |

### Quick wins (< 1 day each)

- Add print buttons to Orders and Kitchen pages
- Wire profile routes from Breeze
- Show promotion name on dashboard discount metric tooltip
- "Copy promo code" button on Promotions page
- Export customer list to CSV
- Order detail slide-over panel from Orders list
- Keyboard shortcuts on POS (number keys for quick pay)

---

## Test Coverage Summary

22 test files covering: auth, POS flow, payments, inventory, promotions, WhatsApp, vendor ledger, reports export, staff, settings, kitchen alerts, menu delete, and PWA manifest.

Run tests:

```bash
php artisan test --compact
```

---

## Related Documents

- [README.md](README.md) — Setup and quick start
- [ARCHITECTURE.md](ARCHITECTURE.md) — Technical architecture
- [.env.example](.env.example) — Environment variable reference

---

*Last updated: September 2026*
