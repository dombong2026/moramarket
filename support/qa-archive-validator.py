#!/usr/bin/env python3
"""Fail-closed, extraction-free Moramarket QA ZIP inspector."""
import argparse
import hashlib
import os
from pathlib import Path
import re
import stat
import sys
import tempfile
import zipfile

PREFIX = 'moramarket/'
CHECKED = {
    'composer.json', 'composer.lock', 'public/index.php',
    'public/installer/index.php', 'vendor/autoload.php',
    'public/build/manifest.json',
}
REQUIRED = {PREFIX + p for p in CHECKED} | {
    PREFIX + 'BUILD_INFORMATION.txt', PREFIX + 'PACKAGE_CHECKSUMS.txt',
}


class UnsafeArchive(Exception):
    pass


def reject(condition, message):
    if condition:
        raise UnsafeArchive(message)


def inspect(path):
    try:
        with zipfile.ZipFile(path) as z:
            info = z.infolist()
            reject(not info or len(info) > 30000, 'Archive file count exceeds safe bounds')
            seen = set()
            total = 0
            for item in info:
                name = item.filename
                reject(name in seen, 'Duplicate archive path')
                seen.add(name)
                reject(not name.startswith(PREFIX) or '\\' in name or
                       any(ord(c) < 32 or ord(c) == 127 for c in name),
                       'Archive entry outside expected root or containing unsafe characters')
                relative = name[len(PREFIX):]
                if not relative:
                    reject(not item.is_dir(), 'Invalid application root')
                    continue
                parts = relative.rstrip('/').split('/')
                reject(any(p in ('', '.', '..') for p in parts), 'Invalid archive path traversal')
                reject(any(p in ('.git', '.github', '.ssh') for p in parts),
                       'Private repository/SSH files in ZIP')
                reject(any(p == '.env' or p.startswith('.env.') for p in parts),
                       'Environment file in ZIP')
                reject(parts[0] in ('tests', 'support', 'docs', 'node_modules',
                                     'phpunit.xml', 'package.json', 'package-lock.json'),
                       'Developer-only package content')
                reject(parts[:2] in (['public', 'storage'], ['public', 'hot']),
                       'Unsafe public symlink/hot-reload path')
                reject(parts[:2] == ['storage', 'app'] and len(parts) > 2 and
                       parts[2] in ('installer-access.key', '.moramarket-installed',
                                    'moramarket-install.lock'), 'Private installer state in ZIP')
                mode = stat.S_IFMT(item.external_attr >> 16)
                reject(mode not in (0, stat.S_IFDIR, stat.S_IFREG), 'Symlink/special file')
                reject((mode == stat.S_IFDIR and not item.is_dir()) or
                       (mode == stat.S_IFREG and item.is_dir()), 'Inconsistent file type')
                total += item.file_size
                reject(total > 1024 * 1024 * 1024, 'Archive uncompressed size too large')
            reject(not REQUIRED.issubset(seen), 'Missing Laravel runtime or build metadata')
            reject(any(z.getinfo(x).is_dir() for x in REQUIRED),
                   'Required application file is a directory')
            build = z.read(PREFIX + 'BUILD_INFORMATION.txt').decode('utf-8')
            m = re.search(r'(?m)^Source SHA: ([0-9a-f]{40})$', build)
            reject(m is None, 'No valid source SHA in build metadata')
            sha = m.group(1)
            reject('Status: QA ONLY; NOT PRODUCTION APPROVED' not in build,
                   'Build is missing QA-only marker')
            filename = re.fullmatch(r'moramarket-qa-([0-9a-f]{40})\.zip', path.name)
            reject(filename is not None and filename.group(1) != sha,
                   'Archive filename does not match source SHA')
            embedded = {}
            for row in z.read(PREFIX + 'PACKAGE_CHECKSUMS.txt').decode('utf-8').splitlines():
                x = re.fullmatch(r'([0-9a-f]{64})  ([^\s]+)', row)
                reject(x is None, 'Malformed embedded checksum line')
                digest, name = x.groups()
                reject(name in embedded, 'Duplicate embedded checksum entry')
                embedded[name] = digest
            reject(set(embedded) != CHECKED, 'Incorrect embedded checksum coverage')
            for name, expected in embedded.items():
                reject(hashlib.sha256(z.read(PREFIX + name)).hexdigest() != expected,
                       'Embedded checksum mismatch: ' + name)
            reject(z.testzip() is not None, 'Archive CRC validation failed')
            return len(info), total, sha
    except (zipfile.BadZipFile, OSError, UnicodeError, RuntimeError, ValueError) as exc:
        raise UnsafeArchive('Archive inspection failed: ' + type(exc).__name__) from exc


def self_test():
    sha = 'a' * 40
    data = {n: n.encode() for n in CHECKED}
    data['BUILD_INFORMATION.txt'] = (
        'Source SHA: ' + sha + '\nStatus: QA ONLY; NOT PRODUCTION APPROVED\n').encode()
    data['PACKAGE_CHECKSUMS.txt'] = ''.join(
        hashlib.sha256(data[n]).hexdigest() + '  ' + n + '\n'
        for n in sorted(CHECKED)).encode()
    with tempfile.TemporaryDirectory(prefix='moramarket-archive-test-') as d:
        path = Path(d) / ('moramarket-qa-' + sha + '.zip')
        def create(extra=None, altered=None):
            with zipfile.ZipFile(path, 'w', zipfile.ZIP_DEFLATED) as z:
                for n, content in data.items():
                    z.writestr(PREFIX + n, b'tampered' if n == altered else content)
                if extra:
                    name, body, permissions = extra
                    if permissions is None:
                        z.writestr(name, body)
                    else:
                        item = zipfile.ZipInfo(name)
                        item.create_system = 3
                        item.external_attr = permissions << 16
                        z.writestr(item, body)
        create()
        inspect(path)
        for bad in [
            ('moramarket/../escape.php', b'bad', None),
            ('moramarket/.env', b'APP_KEY=bad', None),
            ('moramarket/public/storage', b'bad', stat.S_IFLNK | 0o777),
            ('moramarket/support/injected.php', b'bad', None),
        ]:
            create(extra=bad)
            try:
                inspect(path)
            except UnsafeArchive:
                pass
            else:
                raise AssertionError('Unsafe fixture was accepted: ' + bad[0])
        create(altered='composer.lock')
        try:
            inspect(path)
        except UnsafeArchive:
            pass
        else:
            raise AssertionError('Tampered Composer lock was accepted')
    print('MORAMARKET_ARCHIVE_VALIDATOR_SELF_TEST=PASS')


def main():
    p = argparse.ArgumentParser()
    p.add_argument('archive', nargs='?')
    p.add_argument('--self-test', action='store_true')
    p.add_argument('--ci', action='store_true')
    a = p.parse_args()
    if a.self_test:
        self_test()
        return
    reject(not a.archive, 'Archive filename required')
    path = Path(a.archive)
    reject(not path.is_file() or path.is_symlink(), 'Archive must be a regular file')
    path = path.resolve(strict=True)
    if a.ci:
        reject(os.getenv('CI') != 'true' or os.getenv('GITHUB_ACTIONS') != 'true',
               'CI-only validation attempted outside GitHub Actions')
        temp = Path(os.environ.get('RUNNER_TEMP', '/nonexistent')).resolve()
        reject(not path.is_relative_to(temp), 'CI archive must be inside RUNNER_TEMP')
    else:
        reject(not path.is_relative_to(Path('/root')),
               'QA archive must stay under private /root')
    reject(path.suffix != '.zip', 'Expected ZIP format')
    count, size, sha = inspect(path)
    if a.ci:
        reject(sha != os.getenv('GITHUB_SHA'), 'Source SHA differs from CI commit')
    print('PACKAGE_SOURCE_SHA=' + sha)
    print('PACKAGE_ENTRIES=' + str(count))
    print('PACKAGE_UNCOMPRESSED_BYTES=' + str(size))
    print('PACKAGE_CONTENT_CHECKSUMS=VERIFIED')
    print('ARCHIVE_TRAVERSAL_AND_PRIVATE_FILES=BLOCKED')
    print('ARCHIVE_CRC=PASS')
    print('ARCHIVE_SECURITY_SCAN=PASS')


if __name__ == '__main__':
    try:
        main()
    except UnsafeArchive as error:
        print('ARCHIVE_SECURITY_SCAN=BLOCKED: ' + str(error), file=sys.stderr)
        sys.exit(1)
