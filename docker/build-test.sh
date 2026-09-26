#!/usr/bin/env bash
#
# MagicAI v11.20 — production image: build + self-test (one-shot)
#
# Usage:
#   ./docker/build-test.sh
#
# Optional overrides:
#   IMAGE=magicai:11.20 CONTAINER=magicai-test PORT=8080 ./docker/build-test.sh
#
# Reads host .env for credentials (never prints them). Requires docker + a
# reachable MySQL (DB_HOST=127.0.0.1 is auto-rewritten to host.docker.internal
# so macOS/Linux host DBs are reachable from the container).

set -euo pipefail
cd "$(dirname "$0")/.."

IMAGE="${IMAGE:-magicai:11.20}"
CONTAINER="${CONTAINER:-magicai-test}"
PORT="${PORT:-8080}"

pass=0; fail=0; skip=0
ok()  { echo "  [PASS] $1"; pass=$((pass+1)); }
ko()  { echo "  [FAIL] $1"; fail=$((fail+1)); }
sk()  { echo "  [SKIP] $1"; skip=$((skip+1)); }
section() { echo; echo "==> $1"; }

# ── 0) Preflight ─────────────────────────────────────────────────────────
section "0) Preflight"
if ! command -v docker >/dev/null 2>&1; then
    echo "ERROR: docker not found. Install Docker Desktop or OrbStack first."
    exit 1
fi
docker info >/dev/null 2>&1 || { echo "ERROR: docker daemon is not running."; exit 1; }
ok "docker daemon available"

[ -f .env ] || { echo "ERROR: .env not found in repo root."; exit 1; }
set -a; . ./.env; set +a

missing=""
for v in APP_KEY DB_CONNECTION DB_DATABASE DB_USERNAME; do
    eval "val=\${$v:-}"
    [ -n "$val" ] || missing="$missing $v"
done
if [ -n "$missing" ]; then
    echo "ERROR: missing required env vars:$missing"
    exit 1
fi
ok ".env loaded (required vars present)"

case "${DB_HOST:-127.0.0.1}" in
    127.0.0.1|localhost)
        echo "NOTE: DB_HOST was $DB_HOST — rewriting to host.docker.internal for container access"
        DB_HOST="host.docker.internal"
        ;;
esac

# ── 1) Build ─────────────────────────────────────────────────────────────
section "1) Build image $IMAGE"
if docker build -t "$IMAGE" .; then
    ok "image built"
else
    ko "image build failed"
    exit 1
fi

ENV_KEYS="APP_NAME APP_ENV APP_KEY APP_DEBUG APP_URL LOG_CHANNEL LOG_LEVEL
DB_CONNECTION DB_HOST DB_PORT DB_DATABASE DB_USERNAME DB_PASSWORD
CACHE_DRIVER SESSION_DRIVER QUEUE_CONNECTION FILESYSTEM_DISK
REDIS_HOST REDIS_PORT
CLOUDFLARE_R2_ACCESS_KEY_ID CLOUDFLARE_R2_SECRET_ACCESS_KEY
CLOUDFLARE_R2_DEFAULT_REGION CLOUDFLARE_R2_BUCKET CLOUDFLARE_R2_ENDPOINT
CLOUDFLARE_R2_URL CLOUDFLARE_R2_USE_PATH_STYLE_ENDPOINT"
ENV_ARGS=()
for k in $ENV_KEYS; do
    eval "v=\${$k:-}"
    [ -n "$v" ] && ENV_ARGS+=("-e" "$k=$v")
done

docker rm -f "$CONTAINER" >/dev/null 2>&1 || true
docker run -d --name "$CONTAINER" -p "$PORT:8080" \
    ${ENV_ARGS[@]+"${ENV_ARGS[@]}"} "$IMAGE" >/dev/null
ok "container started (port $PORT)"

# ── 2) /api/health ───────────────────────────────────────────────────────
section "2) Health endpoint GET /api/health"
healthy=0
for _ in $(seq 1 30); do
    if curl -fsS "http://127.0.0.1:$PORT/api/health" >/dev/null 2>&1; then
        healthy=1
        break
    fi
    sleep 2
done
if [ "$healthy" = 1 ]; then
    echo "  $(curl -fsS "http://127.0.0.1:$PORT/api/health")"
    ok "health endpoint responds"
else
    ko "health endpoint not responding — check: docker logs $CONTAINER"
fi

# ── 3) Supervisor programs ───────────────────────────────────────────────
section "3) Supervisor programs (php-fpm / nginx / queue / scheduler)"
supstat="$(docker exec "$CONTAINER" supervisorctl status 2>&1 || true)"
printf '%s\n' "$supstat" | sed 's/^/  /'
running="$(printf '%s\n' "$supstat" | grep -c RUNNING || true)"
if [ "$running" -ge 4 ]; then
    ok "all 4 programs RUNNING"
else
    ko "only $running/4 programs RUNNING"
fi

# ── 4) R2 read / write / delete (temporary object) ──────────────────────
section "4) R2 self-test (temporary object: put → get → delete)"
if [ -n "${CLOUDFLARE_R2_ACCESS_KEY_ID:-}" ] && [ -n "${CLOUDFLARE_R2_BUCKET:-}" ]; then
    r2out="$(docker exec "$CONTAINER" php artisan tinker --execute="
        \$k='magicai-selftest/'.bin2hex(random_bytes(8)).'.txt';
        \$r=Storage::disk('r2');
        \$ok=\$r->put(\$k,'magicai-r2-selftest')
          && \$r->get(\$k)==='magicai-r2-selftest'
          && \$r->delete(\$k)
          && !\$r->exists(\$k);
        echo \$ok ? 'R2_PASS' : 'R2_FAIL';" 2>&1 || true)"
    printf '%s\n' "$r2out" | tail -2 | sed 's/^/  /'
    if printf '%s' "$r2out" | grep -q R2_PASS; then
        ok "R2 put/get/delete/!exists"
    else
        ko "R2 self-test failed (credentials, bucket, or endpoint?)"
    fi
else
    sk "R2 credentials not set — skipped"
fi

# ── 5) Queue worker ──────────────────────────────────────────────────────
section "5) Queue worker (database driver, jobs table reachable)"
qout="$(docker exec "$CONTAINER" php artisan queue:work --once --stop-when-empty 2>&1 || true)"
printf '%s\n' "$qout" | tail -3 | sed 's/^/  /'
if printf '%s' "$qout" | grep -qiE "Processed 0 jobs|No jobs processed"; then
    ok "queue worker boots cleanly"
else
    ko "queue worker failed (DB unreachable or jobs table missing?)"
fi

# ── 6) FFmpeg ────────────────────────────────────────────────────────────
section "6) FFmpeg + poppler (extension/pdf pipeline deps)"
ffv="$(docker exec "$CONTAINER" ffmpeg -version 2>&1 | head -1 || true)"
echo "  $ffv"
if printf '%s' "$ffv" | grep -q "ffmpeg version"; then
    ok "ffmpeg present in image"
else
    ko "ffmpeg missing from image"
fi
ppv="$(docker exec "$CONTAINER" pdftotext -v 2>&1 | head -1 || true)"
echo "  $ppv"

# ── 7) App self-check ────────────────────────────────────────────────────
section "7) App self-check (about / routes / extensions)"
docker exec "$CONTAINER" php artisan about 2>&1 | head -20 | sed 's/^/  /' || ko "artisan about"
rt="$(docker exec "$CONTAINER" php artisan route:list --path=api/health 2>&1 | grep -c "health" || true)"
if [ "$rt" -ge 1 ]; then
    ok "route GET /api/health registered"
else
    ko "route /api/health not found"
fi
exts="$(docker exec "$CONTAINER" php -m | grep -cE '^(gd|zip|intl|redis|pdo_mysql|pdo_pgsql|mbstring|exif|bcmath|pcntl|xml)$' || true)"
if [ "$exts" -ge 10 ]; then
    ok "php extensions present ($exts/11)"
else
    ko "only $exts/11 required php extensions loaded"
fi

# ── Summary ───────────────────────────────────────────────────────────────
section "Summary"
echo "PASS=$pass  FAIL=$fail  SKIP=$skip"
echo
echo "Container left running: $CONTAINER (http://127.0.0.1:$PORT)"
echo "Stop it:  docker rm -f $CONTAINER"
[ "$fail" -eq 0 ]
