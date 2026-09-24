# KotBean — Restaurant Operating System

Modern Laravel POS + KOT + Inventory + Billing platform for cafés and restaurants.

## Requirements

- PHP 8.4+
- Composer
- Node.js 20+
- MySQL/MariaDB (Docker recommended)

## Quick Start

### 1. Start Docker (app + MySQL + Redis + nginx)

```bash
docker compose up -d --build
```

| Service | URL / access |
|---------|----------------|
| **KotBean app** | http://127.0.0.1:27082 |
| **phpMyAdmin** | http://127.0.0.1:8080 |
| **MySQL** | `kotbean-db:3306` inside Docker (`kotbean` / `kotbean`) |

Data is stored at `/opt/data_volumes/aditya/kotbean/mysql_data` (same pattern as snookeradda).

> **Note:** phpMyAdmin (`8080`) and the web port (`27082`) match snookeradda — stop the other stack if those ports are already in use.

#### Docker permission denied?

If you see `permission denied while trying to connect to the docker API at unix:///var/run/docker.sock`, your user is not in the `docker` group. Fix it once:

```bash
sudo usermod -aG docker $USER
```

Then **log out and log back in** (or reboot), or run:

```bash
newgrp docker
```

Verify:

```bash
docker ps
docker compose up -d
```

**Temporary workaround** (requires your sudo password each time):

```bash
sudo docker compose up -d
```

#### Without Docker

If you prefer local MySQL on port `3306`, create a database and user, then update `.env`:

```bash
# In MySQL as root:
CREATE DATABASE kotbean;
CREATE USER 'kotbean'@'localhost' IDENTIFIED BY 'kotbean';
GRANT ALL ON kotbean.* TO 'kotbean'@'localhost';
FLUSH PRIVILEGES;
```

```env
DB_PORT=3306
DB_USERNAME=kotbean
DB_PASSWORD=kotbean
```

### 2. Install dependencies

```bash
composer install
npm install
npm run build
```

### 3. Configure environment

```bash
cp .env.example .env
php artisan key:generate
```

Ensure database settings match `docker-compose.yml` (see `.env.example`).

### 4. Migrate and seed demo data

```bash
docker compose exec kotbean-app php artisan key:generate
docker compose exec kotbean-app php artisan migrate --seed
docker compose exec kotbean-app php artisan storage:link
```

### 5. Run the application

With Docker running, visit **http://127.0.0.1:27082**

Optional queue worker:

```bash
docker compose exec kotbean-app php artisan queue:work
```

#### Without Docker (local PHP)

Use `DB_HOST=127.0.0.1` and expose MySQL if needed, then:

```bash
php artisan serve
php artisan queue:work
```

## Demo Login

| Role | Email | Password |
|------|-------|----------|
| Owner | owner@beanbrew.cafe | password |
| Waiter | waiter@beanbrew.cafe | password |
| Kitchen | kitchen@beanbrew.cafe | password |
| Cashier | cashier@beanbrew.cafe | password |

## Demo Flow

1. Login as **owner@beanbrew.cafe**
2. View **Dashboard** — today's sales, best sellers, low stock
3. Open **POS** → select Table 4 → add Vietnamese Iced Coffee × 2
4. **Send to Kitchen**
5. Open **Kitchen** (as kitchen@beanbrew.cafe) → Start Preparing → Ready → Served
6. Back to POS → **Pay** → UPI
7. Inventory decreases, dashboard updates

## Modules

- **Dashboard** — Real-time metrics, sales chart, best sellers, inventory alerts
- **POS** — Fast 3-column order taking with stock-aware products
- **Kitchen** — KOT display without financial data
- **Orders** — Order history and status tracking
- **Tables** — Visual table management
- **Menu** — Products, categories, AI image generation
- **Inventory** — Transaction history, stock adjustments
- **Reports** — Sales, products, payments, hourly analytics
- **Billing** — Invoices and print-friendly receipts
- **Staff** — Role-based permissions
- **Expenses** — Simple expense tracking for profit view

## AI Product Images

Set in `.env`:

```
AI_IMAGE_DRIVER=fake          # Use "openai" in production
OPENAI_API_KEY=sk-...
```

Images generate asynchronously via queue. Use `php artisan queue:work`.

## Architecture

See [ARCHITECTURE.md](ARCHITECTURE.md) for full system design.

## Testing

```bash
# Create test database
docker compose exec kotbean-db mysql -u root -pkotbean -e "CREATE DATABASE IF NOT EXISTS kotbean_test;"

php artisan test
```

## Tech Stack

- Laravel 13 · PHP 8.4
- Livewire 4 · Alpine.js · Tailwind CSS
- MySQL/MariaDB
- Chart.js
