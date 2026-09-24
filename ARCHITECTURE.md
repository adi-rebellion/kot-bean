# KotBean — Restaurant Operating System

## Architecture & Implementation Plan

### Product Vision

KotBean is a multi-tenant Laravel SaaS for restaurant operations combining POS, KOT, inventory, billing, menu management, tables, reports, staff permissions, and AI product photography into one cohesive product.

---

## Tech Stack

| Layer | Technology |
|-------|------------|
| Backend | Laravel 13, PHP 8.4 |
| Database | MySQL/MariaDB |
| Frontend | Blade, Livewire 4, Alpine.js, Tailwind CSS 4, Vite |
| Auth | Laravel Breeze |
| Queues | Database driver (AI image jobs) |
| Real-time | Livewire polling + events for kitchen/AI |

---

## Multi-Tenant Architecture

**Strategy:** Single database, shared schema, `restaurant_id` on every tenant-scoped table.

```
┌─────────────────────────────────────────────────────────┐
│                    Global Scope Layer                    │
│  BelongsToRestaurant trait + RestaurantScope           │
│  SetRestaurantContext middleware                         │
└─────────────────────────────────────────────────────────┘
                              │
        ┌─────────────────────┼─────────────────────┐
        ▼                     ▼                     ▼
   Restaurant A          Restaurant B          Restaurant C
   (isolated data)        (isolated data)        (isolated data)
```

**Rules:**
- Every query on tenant models auto-scopes to `auth()->user()->restaurant_id`
- Policies verify tenant ownership before any action
- Super-admin (future) bypasses scope via explicit flag

---

## Database Schema

### Core Entities

```
restaurants
├── users (restaurant_id, role_id)
├── roles (restaurant_id, slug, name)
├── permissions (slug, name, group)
├── role_permission (role_id, permission_id)
├── categories
├── products (stock, min_stock, cost, price, tax_rate)
├── product_variants
├── inventory_transactions (audit trail)
├── restaurant_tables
├── orders (status lifecycle)
├── order_items
├── kots
├── kot_items
├── payments
├── customers
├── expenses
├── ai_image_generations
├── audit_logs
└── notifications (Laravel built-in)
```

### Order Lifecycle

```
DRAFT → CONFIRMED → KOT_CREATED → PREPARING → READY → SERVED
  → PAYMENT_PENDING → PAID → COMPLETED
                    ↘ CANCELLED (where appropriate)
```

### Inventory Flow

```
Product.stock is denormalized cache
inventory_transactions is source of truth for history

On sale: DB transaction + row lock on product
  → verify stock >= qty
  → create inventory_transaction (type: sale)
  → decrement product.stock
  → if fails, rollback entire order operation
```

### Future BOM Ready

`inventory_items` table (not implemented in MVP) will link:
- `product_id` (finished good)
- `ingredient_product_id` (raw material)
- `quantity_required`

Current `inventory_transactions.product_id` supports both finished goods and future ingredients.

---

## Service Layer

| Service | Responsibility |
|---------|----------------|
| `OrderService` | Create/update orders, add items, status transitions |
| `InventoryService` | Stock checks, deductions, adjustments, locking |
| `KotService` | Generate KOT from order, status updates |
| `PaymentService` | Record payments, complete orders |
| `ProductService` | CRUD, availability, variant management |
| `TableService` | Table states, merge/transfer (extensible) |
| `ReportService` | Sales, products, payments, hourly analytics |
| `AiImageService` | Queue generation, provider abstraction |
| `ExpenseService` | Expense CRUD and reporting |
| `AuditLogService` | Track all sensitive changes |

**Controllers and Livewire components delegate to services — no business logic in controllers.**

---

## AI Image Generation

```
ProductImageGenerator (Livewire)
        │
        ▼
AiImageService::requestGeneration()
        │
        ▼
GenerateProductImageJob (queued)
        │
        ▼
AiImageGeneratorInterface
        ├── OpenAiImageGenerator (default)
        ├── FakeImageGenerator (dev/testing)
        └── [Future: Replicate, Stability]
        │
        ▼
Store image → update product → notify user
```

Config: `config/ai.php` — provider, API keys, default prompts.

---

## Module Map

| Module | Route | Primary UI | Key Classes |
|--------|-------|------------|-------------|
| Dashboard | `/dashboard` | Livewire | `DashboardPage`, `ReportService` |
| POS | `/pos` | Livewire full-screen | `PosPage`, `OrderService` |
| Orders | `/orders` | Livewire + Blade | `OrdersIndex`, `OrderShow` |
| Kitchen | `/kitchen` | Livewire (poll 5s) | `KitchenDisplay`, `KotService` |
| Tables | `/tables` | Livewire grid | `TablesIndex`, `TableService` |
| Menu | `/menu` | Livewire CRUD | `MenuProducts`, `ProductService` |
| Inventory | `/inventory` | Livewire | `InventoryIndex`, `InventoryService` |
| Customers | `/customers` | Livewire | `CustomersIndex` |
| Reports | `/reports` | Livewire + charts | `ReportsIndex`, `ReportService` |
| Staff | `/staff` | Livewire | `StaffIndex` |
| Settings | `/settings` | Livewire tabs | `SettingsPage` |
| Expenses | `/expenses` | Livewire | `ExpensesIndex` |

---

## Permissions Matrix

| Permission | Owner | Manager | Cashier | Waiter | Kitchen |
|------------|-------|---------|---------|--------|---------|
| dashboard.view | ✓ | ✓ | ✓ | ✓ | ✗ |
| pos.access | ✓ | ✓ | ✓ | ✓ | ✗ |
| orders.view | ✓ | ✓ | ✓ | ✓ | ✓ |
| kitchen.access | ✓ | ✓ | ✗ | ✗ | ✓ |
| menu.manage | ✓ | ✓ | ✗ | ✗ | ✗ |
| inventory.manage | ✓ | ✓ | ✗ | view | ✗ |
| reports.view | ✓ | ✓ | ✗ | ✗ | ✗ |
| revenue.view | ✓ | ✓ | ✗ | ✗ | ✗ |
| staff.manage | ✓ | ✗ | ✗ | ✗ | ✗ |
| settings.manage | ✓ | ✓ | ✗ | ✗ | ✗ |
| payments.process | ✓ | ✓ | ✓ | ✓ | ✗ |

---

## UI Design System

- **Font:** DM Sans (headings + body)
- **Accent:** Amber/Orange gradient (restaurant warmth) + Slate neutrals
- **Layout:** Collapsible sidebar, full-width POS, card-based dashboard
- **Dark mode:** Class-based toggle, stored in localStorage
- **Components:** Blade anonymous components in `resources/views/components/`
- **Icons:** Heroicons (inline SVG)
- **Charts:** Chart.js via Alpine wrapper

---

## Implementation Phases

### Phase 1 — Foundation ✅ (current)
- [x] Laravel + Breeze + Livewire setup
- [ ] Database migrations (all entities)
- [ ] Models + relationships + enums
- [ ] Multi-tenant trait + middleware
- [ ] Roles & permissions seeding
- [ ] Base SaaS layout + navigation

### Phase 2 — Menu & Inventory
- [ ] Category & product CRUD
- [ ] Product variants
- [ ] Inventory transactions
- [ ] Stock badges & alerts
- [ ] AI image generation (async)

### Phase 3 — POS & Orders
- [ ] POS Livewire (3-column layout)
- [ ] Cart management
- [ ] Server-side stock validation
- [ ] Order types (dine-in, takeaway, delivery)
- [ ] Table selection

### Phase 4 — KOT & Kitchen
- [ ] KOT generation from orders
- [ ] Kitchen display (large cards, no pricing)
- [ ] Status workflow (pending → served)
- [ ] Print-friendly KOT layout

### Phase 5 — Billing & Payments
- [ ] Payment modal (cash, UPI, card, other)
- [ ] Invoice/receipt generation
- [ ] Print layouts

### Phase 6 — Tables
- [ ] Visual table grid
- [ ] Table states
- [ ] Open order from table

### Phase 7 — Dashboard & Reports
- [ ] Today's metrics (real data)
- [ ] Sales chart (Chart.js)
- [ ] Best sellers, low stock
- [ ] Sales/product/payment reports

### Phase 8 — Staff, Customers, Expenses, Audit
- [ ] Staff CRUD with roles
- [ ] Customer management
- [ ] Expense tracking
- [ ] Audit log viewer

### Phase 9 — Seed Data & Tests
- [ ] Bean & Brew Café demo seeder
- [ ] Historical orders/sales
- [ ] Feature tests for critical paths

---

## Demo Flow (End-to-End)

```
Login (owner@beanbrew.cafe / password)
  → Dashboard shows today's metrics
  → POS → Table 4 → Cold Coffee → Vietnamese Iced Coffee × 2
  → Send KOT
  → Kitchen → Start Preparing → Ready → Served
  → Payment → UPI
  → Order Completed
  → Inventory -2, Dashboard updates
```

---

## Security Checklist

- [x] CSRF (Laravel default)
- [ ] Form Requests for all mutations
- [ ] Policies on all resources
- [ ] Tenant isolation tests
- [ ] Row locking for inventory
- [ ] Rate limiting on AI generation
- [ ] Secure file uploads (MIME, size)
- [ ] Mass assignment protection

---

## Performance Notes

- Eager load categories+products on POS (single query)
- Cache menu for 5 min per restaurant (invalidated on product update)
- Dashboard metrics: aggregated SQL, not full table scans
- Kitchen: Livewire poll every 5s, not websocket (MVP)
- Indexes on: `restaurant_id`, `orders.status`, `orders.created_at`, `products.category_id`

---

## Future Extensions (Architecture Ready)

- QR ordering → `orders.source` enum
- Recipe/BOM → `inventory_items` + `InventoryService::deductRecipe()`
- Multi-branch → `branches` table, scope under restaurant
- Offline POS → Service layer returns JSON-ready DTOs
- Thermal printers → `PrintService` interface, separate from order logic
