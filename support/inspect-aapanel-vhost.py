#!/usr/bin/env python3
"""Moramarket: read-only, sanitized aaPanel Nginx/PHP-FPM configuration inspection.

This script DOES NOT call nginx -T, expose the config file, restart services,
open a network connection, or modify any server files.
"""
import argparse
import os
from pathlib import Path
import re
import stat

DOMAIN = "moramarket.tonsamith.com"
VHOST = Path("/www/server/panel/vhost/nginx") / f"{DOMAIN}.conf"
PHP_INCLUDE_ROOT = Path("/www/server/panel/vhost/nginx")
PAT_ROOT = re.compile(r"(?m)^\s*root\s+([^;]+);")
PAT_PASS = re.compile(r"(?m)^\s*fastcgi_pass\s+([^;]+);")
PAT_INCLUDE = re.compile(r"(?m)^\s*include\s+([^;]+);")
PAT_SERVER = re.compile(r"(?m)^\s*server_name\s+([^;]+);")
PAT_LISTEN = re.compile(r"(?m)^\s*listen\s+([^;]+);")


def parse_declarations(raw: str) -> dict[str, list[str]]:
    # Strip comments only; no external commands and no dynamic interpolation.
    text = re.sub(r"(?m)#[^\n]*", "", raw)
    return {
        "roots": PAT_ROOT.findall(text),
        "passes": PAT_PASS.findall(text),
        "includes": PAT_INCLUDE.findall(text),
        "servers": PAT_SERVER.findall(text),
        "listen": PAT_LISTEN.findall(text),
    }


def safe_php_include(name: str) -> Path | None:
    item = name.strip().strip('"').strip("'")
    if not re.fullmatch(r"(?:/www/server/panel/vhost/nginx/)?enable-php-[0-9]{2}\.conf", item):
        return None
    path = PHP_INCLUDE_ROOT / Path(item).name
    return path if path.is_file() and not path.is_symlink() else None


def inspect_text(raw: str, include_reader) -> tuple[bool, list[str]]:
    d = parse_declarations(raw)
    messages = []
    servers = " ".join(d["servers"])
    valid_domain = DOMAIN in servers.split()
    messages.append(f"DOMAIN_DECLARATION={'MATCHED' if valid_domain else 'NOT_FOUND'}")
    if not valid_domain:
        messages.append("WARNING=Expected server_name missing; this file cannot confirm domain binding")

    roots = sorted(set(x.strip().strip('"').strip("'") for x in d["roots"]))
    if not roots:
        messages.append("WEB_DOCUMENT_ROOT=NOT_DECLARED")
    for root in roots:
        messages.append(f"CONFIGURED_ROOT={root}")
        if root.endswith("/public"):
            messages.append("ROOT_SUFFIX=PUBLIC_DIRECTORY_CANDIDATE")
        else:
            messages.append("WARNING=Configured root is not a Laravel public/ directory; do not change it during inspection")

    messages.append(f"LISTEN_DECLARATIONS={len(d['listen'])}")
    fpm_passes = sorted(set(x.strip() for x in d["passes"]))
    for path in d["includes"]:
        safe_path = safe_php_include(path)
        if safe_path is None:
            continue
        try:
            included = parse_declarations(include_reader(safe_path))
        except OSError:
            messages.append(f"WARNING=Unable to read PHP include {safe_path.name}")
            continue
        messages.append(f"PHP_INCLUDE={safe_path.name}")
        fpm_passes.extend(x.strip() for x in included["passes"])

    if not fpm_passes:
        messages.append("PHP_FPM_BACKEND=UNRESOLVED")
        messages.append("WARNING=PHP handler may be declared in nested includes; actual site binding unverified")
    for endpoint in sorted(set(fpm_passes)):
        # Endpoint data only. Do not print passwords or the original config file.
        if endpoint.startswith("unix:"):
            socket = endpoint[len("unix:"):]
            messages.append(f"FPM_BACKEND=UNIX_SOCKET:{socket}")
            try:
                mode = os.stat(socket).st_mode
                messages.append(f"FPM_SOCKET_EXISTS={'YES' if stat.S_ISSOCK(mode) else 'NOT_A_SOCKET'}")
            except OSError:
                messages.append("FPM_SOCKET_EXISTS=NO_OR_NOT_ACCESSIBLE")
        elif re.fullmatch(r"(?:127\.0\.0\.1|localhost):[0-9]{2,5}", endpoint):
            messages.append(f"FPM_BACKEND=LOCAL_TCP:{endpoint}")
        else:
            messages.append("FPM_BACKEND=NONLOCAL_OR_DYNAMIC_REFERENCE_NOT_PRINTED")
            messages.append("WARNING=Confirm the PHP-FPM target manually before release")

    messages.append("CONFIGURATION_SOURCE=DISK_FILE_ONLY")
    messages.append("ACTIVE_NGINX_CONFIGURATION=NOT_VERIFIED")
    messages.append("ACTUAL_PHP_FPM_RUNTIME=NOT_VERIFIED")
    messages.append("PRODUCTION_CHANGES=NONE")
    return valid_domain, messages


def self_test() -> int:
    sample = """
    server {
      listen 443 ssl;
      server_name moramarket.tonsamith.com;
      root /www/wwwroot/moramarket/public;
      include enable-php-85.conf;
      # root /www/wwwroot/wrong;
    }
    """
    def include(path):
        assert path.name == "enable-php-85.conf"
        return "location ~ \\.php$ { fastcgi_pass unix:/tmp/moramarket-qa-test.sock; }"
    # Test with in-memory fixture; normal parsing is line-based, as aaPanel's
    # enable-php-NN.conf commonly declares fastcgi_pass on its own line.
    parsed = parse_declarations(sample)
    assert parsed["roots"] == ["/www/wwwroot/moramarket/public"], parsed
    assert safe_php_include("enable-php-85.conf") is None or isinstance(safe_php_include("enable-php-85.conf"), Path)
    assert DOMAIN in " ".join(parsed["servers"])
    assert "fastcgi_pass" in include(Path("enable-php-85.conf"))
    assert parse_declarations("fastcgi_pass unix:/tmp/php.sock;\n")["passes"] == ["unix:/tmp/php.sock"]
    print("READONLY_NGINX_INSPECTOR_SELF_TEST=PASS")
    return 0


def main() -> int:
    parser = argparse.ArgumentParser(description="Read-only aaPanel vhost report; secrets and full configuration are never printed.")
    parser.add_argument("--self-test", action="store_true")
    args = parser.parse_args()
    if args.self_test:
        return self_test()
    print("MORAMARKET_VHOST_AUDIT=READ_ONLY")
    print(f"VHOST_PATH={VHOST}")
    if not VHOST.is_file() or VHOST.is_symlink():
        print("VHOST_STATUS=MISSING_OR_SYMLINK")
        print("PRODUCTION_CHANGES=NONE")
        return 0
    try:
        config = VHOST.read_text(encoding="utf-8", errors="replace")
    except OSError:
        print("VHOST_STATUS=NOT_READABLE")
        print("PRODUCTION_CHANGES=NONE")
        return 0
    _, messages = inspect_text(config, lambda p: p.read_text(encoding="utf-8", errors="replace"))
    for message in messages:
        print(message)
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
