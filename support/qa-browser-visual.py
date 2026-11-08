#!/usr/bin/env python3
"""Chromium desktop/mobile smoke + interaction and screenshot acceptance.
Runs ONLY against loopback CI server and isolated SQLite fixtures.
"""
import json
import os
from pathlib import Path
import sys
from urllib.parse import urlsplit

from playwright.sync_api import sync_playwright

BASE = "http://127.0.0.1:8096"
PWD = "VisualAcceptance123"
OUT = Path(os.environ["RUNNER_TEMP"]) / "moramarket-browser-qa"
OUT.mkdir(parents=True, exist_ok=True)

PUBLIC = [
    "/", "/en", "/en/products", "/en/category/electronics",
    "/en/product/qa-bluetooth-speaker", "/en/pages", "/en/page/qa-sample-page",
    "/en/blog", "/en/post/qa-sample-post", "/policies",
    "/policies/qa-sample-policy", "/support-center",
    "/support-center/article/qa-help-article", "/cart",
]
GUEST = ["/login", "/register", "/forgot-password", "/reset-password/sample"]
ADMIN = [
    "/dashboard", "/dashboard/categories", "/dashboard/vendors", "/dashboard/orders",
    "/dashboard/content/page", "/dashboard/content/post", "/dashboard/policies",
    "/dashboard/knowledge", "/dashboard/support", "/dashboard/settings",
    "/dashboard/appearance", "/dashboard/system-health", "/dashboard/security-center",
    "/dashboard/media", "/account/notifications", "/account",
]
VENDOR = [
    "/vendor", "/vendor/shipping", "/vendor/products", "/vendor/orders",
    "/vendor/media", "/account/notifications", "/account", "/account/orders",
    "/account/support",
]
CUSTOMER = [
    "/account", "/account/orders", "/account/support",
    "/account/support/1", "/account/notifications", "/cart",
    "/en/products", "/en/product/qa-bluetooth-speaker",
]
SCREENS = {
    "/en", "/en/products", "/en/product/qa-bluetooth-speaker", "/login",
    "/dashboard", "/dashboard/orders", "/dashboard/content/post",
    "/dashboard/media", "/dashboard/notifications", "/vendor",
    "/vendor/products", "/account", "/account/notifications",
}
issues = []
stats = {"pages_checked": 0, "screenshots": 0, "interaction_checks": 0, "by_role": {}}

def check(condition, msg):
    if not condition:
        issues.append(msg)

def visit(page, path, role, viewport, theme):
    page_errors = []
    failed_assets = []
    def err(exc):
        page_errors.append(str(exc))
    def response(r):
        if r.status >= 400 and (".css" in r.url or ".js" in r.url):
            failed_assets.append(f"{r.status} {r.url}")
    page.on("pageerror", err)
    page.on("response", response)
    try:
        r = page.goto(BASE + path, wait_until="networkidle", timeout=15000)
        code = r.status if r else 0
        # Root redirects to locale homepage, otherwise a 200 is expected.
        check(code == 200, f"{role}/{viewport}/{theme} {path}: HTTP {code}")
        page.locator("body").wait_for(timeout=8000)
        title = page.title()
        check(bool(title.strip()), f"{role}/{viewport}/{theme} {path}: empty title")
        check(not page.locator("text=Server Error").count(), f"{role}/{viewport}/{theme} {path}: server error body")
        scroll = page.evaluate("Math.ceil(document.documentElement.scrollWidth - window.innerWidth)")
        check(scroll <= 12, f"{role}/{viewport}/{theme} {path}: horizontal page overflow {scroll}px")
        if theme == "dark":
            check(page.locator("html").get_attribute("data-theme") == "dark",
                  f"{role}/{viewport}/{theme} {path}: dark theme not preserved")
        if role != "public" and role != "guest":
            check(page.locator(".workspace-shell").count() > 0 if path.startswith(
                ("/dashboard", "/vendor", "/account")) else True,
                f"{role}/{viewport}/{theme} {path}: missing workspace shell")
        if path in SCREENS and theme == "light":
            f = OUT / (role + "_" + viewport + "_" + path.strip("/").replace("/", "_") + ".png")
            page.screenshot(path=str(f), full_page=True, animations="disabled")
            stats["screenshots"] += 1
        stats["pages_checked"] += 1
        stats["by_role"][role] = stats["by_role"].get(role, 0) + 1
        if page_errors:
            issues.extend(f"{role}/{viewport}/{theme} {path}: JS {x}" for x in page_errors[:4])
        if failed_assets:
            issues.extend(f"{role}/{viewport}/{theme} {path}: asset {x}" for x in failed_assets[:4])
    except Exception as exc:
        issues.append(f"{role}/{viewport}/{theme} {path}: {type(exc).__name__}: {str(exc)[:300]}")
    finally:
        page.remove_listener("pageerror", err)
        page.remove_listener("response", response)

def login(page, role):
    page.goto(BASE + "/login", wait_until="domcontentloaded")
    page.locator('input[name="email"]').fill(role + "@visual-qa.test")
    page.locator('input[name="password"]').fill(PWD)
    page.locator('form[action$="/login"] button[type="submit"]').click()
    page.wait_for_url(
        lambda u: urlsplit(u).path in
        {"admin": ["/dashboard"], "vendor": ["/vendor"], "customer": ["/account"]}[role],
        timeout=15000
    )
    check(page.locator(".workspace-shell").count() == 1, f"{role}: login succeeded but dashboard missing")
    stats["interaction_checks"] += 1

def interactions(page, role, viewport):
    if role == "guest":
        page.goto(BASE + "/login", wait_until="networkidle")
        toggle = page.locator('[data-password-toggle="login-password"]')
        if toggle.count():
            toggle.click()
            check(page.locator('#login-password').get_attribute("type") == "text",
                  f"{viewport} guest password toggle")
            stats["interaction_checks"] += 1
        return
    if role == "public":
        page.goto(BASE + "/en/products", wait_until="networkidle")
        page.locator("#catalog-query").fill("Bluetooth")
        page.locator('form.panel button[type="submit"]').click()
        check("q=Bluetooth" in page.url, f"{viewport} catalog filter submission")
        stats["interaction_checks"] += 1
        return
    page.goto(BASE + ("/dashboard" if role == "admin" else "/vendor" if role == "vendor" else "/account"),
              wait_until="networkidle")
    toggle = page.locator('[data-theme-toggle]').first
    if toggle.count() and toggle.is_visible():
        toggle.click()
        check(page.locator("html").get_attribute("data-theme") == "dark",
              f"{role}/{viewport} theme toggle")
        toggle.click()
        stats["interaction_checks"] += 1
    if viewport == "mobile":
        mobile = page.locator("[data-sidebar-toggle]")
        if mobile.count() and mobile.is_visible():
            mobile.click()
            check(mobile.get_attribute("aria-expanded") == "true",
                  f"{role}/{viewport} sidebar expand")
            mobile.click()
            stats["interaction_checks"] += 1
    if role == "admin":
        page.goto(BASE + "/dashboard/content/post", wait_until="networkidle")
        modal = page.locator('[data-open-modal="content-create"]').first
        if modal.count():
            modal.click()
            check(page.locator("dialog#content-create").evaluate("(d) => d.open"),
                  f"{role}/{viewport}: post creation modal not opened")
            close = page.locator('dialog#content-create [data-close-modal]').first
            close.click()
            stats["interaction_checks"] += 1
        triggers = page.locator("[data-menu-id]")
        if triggers.count():
            triggers.first.click()
            check(triggers.first.get_attribute("aria-expanded") == "true",
                  f"{role}/{viewport}: three-dot action menu did not open")
            stats["interaction_checks"] += 1
    if role == "vendor":
        page.goto(BASE + "/vendor/products", wait_until="networkidle")
        button = page.locator('[data-open-modal="product-create"]')
        if button.count():
            button.click()
            check(page.locator("dialog#product-create").evaluate("(d) => d.open"),
                  f"{role}/{viewport}: product modal not opened")
            stats["interaction_checks"] += 1
    if role == "customer":
        page.goto(BASE + "/account/notifications", wait_until="networkidle")
        check("QA customer notice" in page.content(),
              f"{role}/{viewport}: real in-app notice missing")
        stats["interaction_checks"] += 1

if __name__ == "__main__":
    if os.getenv("CI") != "true" or os.getenv("GITHUB_ACTIONS") != "true":
        sys.exit("BLOCKED: visual QA runs only in isolated GitHub Actions")
    with sync_playwright() as pw:
        browser = pw.chromium.launch(headless=True, args=["--no-sandbox"])
        for viewport, width, height in (("desktop", 1440, 900), ("mobile", 390, 844)):
            for role, paths in (("public", PUBLIC), ("guest", GUEST),
                                ("admin", ADMIN), ("vendor", VENDOR), ("customer", CUSTOMER)):
                ctx = browser.new_context(viewport={"width": width, "height": height},
                                          color_scheme="light")
                page = ctx.new_page()
                if role in ("admin", "vendor", "customer"):
                    try:
                        login(page, role)
                    except Exception as exc:
                        issues.append(f"{role}/{viewport}: login failure: {exc}")
                        ctx.close()
                        continue
                for theme in ("light", "dark"):
                    ctx.add_cookies([{"name": "mm-theme", "value": theme,
                                      "url": BASE, "sameSite": "Lax"}])
                    for path in paths:
                        visit(page, path, role, viewport, theme)
                ctx.add_cookies([{"name": "mm-theme", "value": "light",
                                  "url": BASE, "sameSite": "Lax"}])
                try:
                    interactions(page, role, viewport)
                except Exception as exc:
                    issues.append(f"{role}/{viewport}: interaction: {type(exc).__name__}: {str(exc)[:300]}")
                ctx.close()
        browser.close()
    stats["issues"] = issues
    (OUT / "report.json").write_text(json.dumps(stats, indent=2), encoding="utf-8")
    print(json.dumps({"pages_checked": stats["pages_checked"],
                      "screenshots": stats["screenshots"],
                      "interaction_checks": stats["interaction_checks"],
                      "issue_count": len(issues),
                      "issues_preview": issues[:25]}, indent=2))
    if issues:
        sys.exit(1)
    print("MORAMARKET_BROWSER_QA=PASS")
