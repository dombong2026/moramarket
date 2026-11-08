#!/usr/bin/env bash
# CI-only disposable MySQL snapshot/restore and rollback rehearsal.
set -Eeuo pipefail
die() { printf 'LIFECYCLE_QA=BLOCKED: %s\n' "$*" >&2; exit 1; }
[[ "$(printenv GITHUB_ACTIONS || :)" == true && "$(printenv CI || :)" == true ]] ||
    die 'Only isolated GitHub Actions runners are permitted.'
[[ -n "$(printenv RUNNER_TEMP || :)" && -n "$(printenv GITHUB_WORKSPACE || :)" ]] ||
    die 'Isolated CI runner environment is unavailable.'
[[ -f .env && -f storage/app/.moramarket-installed ]] ||
    die 'Run only after the freshly extracted package installer succeeds.'
grep -Eq '^DB_DATABASE="?moramarket_install_ci"?$' .env ||
    die 'The installed database is not the dedicated temporary CI schema.'
[[ "$PWD" != "$GITHUB_WORKSPACE" && "$PWD" == "$RUNNER_TEMP/"* ]] ||
    die 'Refusing to run outside the temporary extracted package.'
command -v mysql >/dev/null || die 'MySQL CLI is missing from the runner.'
command -v mysqldump >/dev/null || die 'mysqldump is missing from the runner.'
[[ "$(printenv MYSQL_PWD || :)" == 'CI_root_password_891' ]] ||
    die 'Require the disposable CI MySQL service password.'

mysql_ci() { mysql --protocol=tcp -h127.0.0.1 -P3306 -uroot --batch --skip-column-names "$@"; }
dump_ci() { mysqldump --protocol=tcp -h127.0.0.1 -P3306 -uroot --single-transaction --quick --skip-lock-tables --no-tablespaces "$@"; }

SOURCE_DB=moramarket_install_ci
RECOVERY_DB=moramarket_restore_ci
SNAPSHOT="$(mktemp "$RUNNER_TEMP/moramarket-db-backup.XXXXXXXX.sql")"
trap 'rm -f -- "$SNAPSHOT"' EXIT

[[ -z "$(mysql_ci -e "SHOW DATABASES LIKE '$RECOVERY_DB'")" ]] ||
    die 'Restore schema already exists; never overwrite it.'
SRC_USERS="$(mysql_ci "$SOURCE_DB" -e 'SELECT COUNT(*) FROM users')"
SRC_CATEGORIES="$(mysql_ci "$SOURCE_DB" -e 'SELECT COUNT(*) FROM categories')"
SRC_MIGRATIONS="$(mysql_ci "$SOURCE_DB" -e 'SELECT COUNT(*) FROM migrations')"
[[ "$SRC_USERS" -ge 1 && "$SRC_CATEGORIES" -ge 1 && "$SRC_MIGRATIONS" -ge 1 ]] ||
    die 'Installation missing expected seed data.'

dump_ci "$SOURCE_DB" > "$SNAPSHOT"
[[ -s "$SNAPSHOT" ]] || die 'Backup snapshot empty.'
sha256sum "$SNAPSHOT" | awk '{print "BACKUP_SHA256=" $1}'
mysql_ci -e "CREATE DATABASE $RECOVERY_DB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
mysql_ci "$RECOVERY_DB" < "$SNAPSHOT"

check_recovery() {
    [[ "$(mysql_ci "$RECOVERY_DB" -e 'SELECT COUNT(*) FROM users')" == "$SRC_USERS" ]] ||
       die 'User count differs from backup.'
    [[ "$(mysql_ci "$RECOVERY_DB" -e 'SELECT COUNT(*) FROM categories')" == "$SRC_CATEGORIES" ]] ||
       die 'Category count differs from backup.'
    [[ "$(mysql_ci "$RECOVERY_DB" -e 'SELECT COUNT(*) FROM migrations')" == "$SRC_MIGRATIONS" ]] ||
       die 'Migration journal count differs from backup.'
}
check_recovery
echo 'BACKUP_RESTORE=PASS'

# Actual Laravel migration command; same version only.
DB_DATABASE="$RECOVERY_DB" DB_USERNAME=root DB_PASSWORD="$MYSQL_PWD" \
    php artisan migrate --force --no-interaction >/dev/null
check_recovery
echo 'SAME_VERSION_MIGRATIONS=IDEMPOTENT'

# QA-only simulated schema upgrade and snapshot-based rollback in a
# disposable second DB. The original installed QA DB is not changed.
mysql_ci "$RECOVERY_DB" -e 'CREATE TABLE qa_upgrade_probe (id BIGINT PRIMARY KEY, applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP); INSERT INTO qa_upgrade_probe (id) VALUES (1)'
[[ "$(mysql_ci "$RECOVERY_DB" -e 'SELECT COUNT(*) FROM qa_upgrade_probe')" == 1 ]] ||
    die 'Simulated forward schema migration did not persist.'
mysql_ci -e "DROP DATABASE $RECOVERY_DB; CREATE DATABASE $RECOVERY_DB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
mysql_ci "$RECOVERY_DB" < "$SNAPSHOT"
check_recovery
[[ -z "$(mysql_ci "$RECOVERY_DB" -e "SHOW TABLES LIKE 'qa_upgrade_probe'")" ]] ||
    die 'Snapshot rollback did not remove the temporary schema.'
echo 'BACKUP_BASED_ROLLBACK=PASS'
echo 'FUTURE_VERSION_UPGRADE=UNVERIFIED'
echo 'SOURCE_QA_DATABASE=UNCHANGED'
echo 'PRODUCTION=NOT_ACCESSED'
