#!/usr/bin/env bash
set -Eeuo pipefail

# CI-only full HTTP installer smoke test. Requires a fresh MySQL database
# supplied by the GitHub Actions service and built Composer/Vite artifacts.
PORT=8088
BASE="http://127.0.0.1:$PORT"
COOKIE="$(mktemp)"
PAGE="$(mktemp)"
LOG="$(mktemp)"
SERVER_PID=""
cleanup() {
  if [[ -n "$SERVER_PID" ]]; then kill "$SERVER_PID" 2>/dev/null || true; wait "$SERVER_PID" 2>/dev/null || true; fi
  rm -f "$COOKIE" "$PAGE" "$LOG"
}
trap cleanup EXIT

rm -f .env storage/app/.moramarket-installed storage/app/installer-access.key
php -S "127.0.0.1:$PORT" -t public >"$LOG" 2>&1 &
SERVER_PID="$!"
for i in $(seq 1 30); do
  if curl -sS -o /dev/null "$BASE/" 2>/dev/null; then break; fi
  sleep 1
done

STATUS="$(curl -sS -o /dev/null -w '%{http_code}' "$BASE/")"
[[ "$STATUS" == "302" ]] || { echo "Root did not redirect to installer: $STATUS"; cat "$LOG"; exit 1; }

curl -fsS -c "$COOKIE" -b "$COOKIE" "$BASE/installer/" > "$PAGE"
grep -q 'Secure Setup Access' "$PAGE"
test -s storage/app/installer-access.key

CODE="$(tr -d '\r\n' < storage/app/installer-access.key)"
CSRF="$(grep -o 'name="_csrf" value="[a-f0-9]*"' "$PAGE" | head -n1 | cut -d '"' -f4)"
[[ "$(printf %s "$CODE" | wc -c)" -eq 48 && "$(printf %s "$CSRF" | wc -c)" -eq 64 ]] || { echo "Installer credentials not provisioned"; exit 1; }

# Without the private access code, admin setup must remain inaccessible.
REJECTED="$(curl -sS -c "$COOKIE" -b "$COOKIE" -o "$PAGE" -w '%{http_code}' -X POST "$BASE/installer/?step=1" \
  --data-urlencode "_csrf=$CSRF" --data-urlencode 'access_code=wrong-code')"
[[ "$REJECTED" == "422" ]] || { echo "Installer failed to reject bad access code: $REJECTED"; exit 1; }

curl -sS -c "$COOKIE" -b "$COOKIE" -o /dev/null -X POST "$BASE/installer/" \
  --data-urlencode "_csrf=$CSRF" --data-urlencode "access_code=$CODE"
curl -fsS -c "$COOKIE" -b "$COOKIE" "$BASE/installer/?step=1" > "$PAGE"
grep -q 'Hosting Requirements' "$PAGE"
grep -q 'Web document root is public/' "$PAGE"

curl -sS -c "$COOKIE" -b "$COOKIE" -o /dev/null -X POST "$BASE/installer/?step=2" \
  --data-urlencode "_csrf=$CSRF" --data-urlencode 'host=127.0.0.1' \
  --data-urlencode 'port=3306' --data-urlencode 'database=moramarket_install_ci' \
  --data-urlencode 'username=moramarket_ci' --data-urlencode 'password=CI_DB_Password_891'
curl -fsS -c "$COOKIE" -b "$COOKIE" "$BASE/installer/?step=3" > "$PAGE"
grep -q 'Website Details' "$PAGE"

curl -sS -c "$COOKIE" -b "$COOKIE" -o /dev/null -X POST "$BASE/installer/?step=3" \
  --data-urlencode "_csrf=$CSRF" --data-urlencode 'name=Moramarket QA' \
  --data-urlencode "url=$BASE"
curl -fsS -c "$COOKIE" -b "$COOKIE" "$BASE/installer/?step=4" > "$PAGE"
grep -q 'Create Administrator' "$PAGE"

RESULT="$(curl -sS -c "$COOKIE" -b "$COOKIE" -o "$PAGE" -w '%{http_code}' -X POST "$BASE/installer/?step=4" \
  --data-urlencode "_csrf=$CSRF" --data-urlencode 'name=Moramarket Administrator' \
  --data-urlencode 'email=ci-admin@example.test' \
  --data-urlencode 'password=SafeInstallAdmin123' \
  --data-urlencode 'password_confirmation=SafeInstallAdmin123')"
if [[ "$RESULT" != "303" || ! -f storage/app/.moramarket-installed ]]; then
  echo "Installer did not complete. HTTP=$RESULT"
  sed 's/\(DB_PASSWORD=\).*/\1[redacted]/' "$LOG" | tail -45
  exit 1
fi

[[ ! -f storage/app/installer-access.key ]] || { echo "Installer key was not removed"; exit 1; }
curl -fsS "$BASE/login" > "$PAGE"
grep -q 'Sign In' "$PAGE"
STATUS="$(curl -sS -o /dev/null -w '%{http_code}' "$BASE/installer/")"
[[ "$STATUS" == "303" ]] || { echo "Installer not locked after completion: $STATUS"; exit 1; }

# Require a single admin; no public registration can create one.
php -r '
require "vendor/autoload.php";
$app = require "bootstrap/app.php";
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$count = App\Models\User::where("role", "admin")->count();
if ($count !== 1) { fwrite(STDERR, "Invalid admin count: $count\n"); exit(1); }
'
echo "Browser installer HTTP acceptance PASSED"
