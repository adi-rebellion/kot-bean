#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT"

echo "=== KotBean server diagnostics ==="
echo "Path: $ROOT"
echo "Date: $(date -Is)"
echo

section() { echo; echo "--- $1 ---"; }

section "Docker Compose status"
docker compose ps 2>&1 || docker-compose ps 2>&1 || echo "docker compose failed"

section "Recent container logs (kotbean-web)"
docker compose logs --tail=30 kotbean-web 2>&1 || true

section "Recent container logs (kotbean-app)"
docker compose logs --tail=30 kotbean-app 2>&1 || true

section "Recent container logs (kotbean-db)"
docker compose logs --tail=20 kotbean-db 2>&1 || true

section "Network: nginx_rproxynet"
docker network inspect nginx_rproxynet 2>&1 | head -40 || echo "MISSING: nginx_rproxynet external network"

section "Local HTTP check (127.0.0.1:27082)"
curl -sI --connect-timeout 5 http://127.0.0.1:27082 2>&1 | head -15 || echo "Cannot reach app on 127.0.0.1:27082"

section "Required files"
for f in .env vendor/autoload.php public/build/manifest.json storage/framework/sessions; do
  if [[ -e "$f" ]]; then echo "OK  $f"; else echo "MISSING  $f"; fi
done

section "PHP in app container"
docker compose exec -T kotbean-app php -v 2>&1 || true
docker compose exec -T kotbean-app php artisan about --only=environment 2>&1 || true

section "Laravel log (last 20 lines)"
tail -20 storage/logs/laravel.log 2>&1 || echo "No laravel.log"

echo
echo "=== Done ==="
