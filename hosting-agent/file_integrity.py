#!/usr/bin/env python3
"""
File Integrity / Webshell Module
================================
Scans cPanel user home directories for common webshell and malware
signatures in PHP files. This is CPU/IO intensive, so it runs on a
longer interval (default 1 hour) and uses several heuristics:
  - Dangerous function calls (eval, base64_decode, shell_exec, etc.)
  - Obfuscated/encoded payloads
  - .htaccess redirects / injects into non-default locations
  - Suspicious files in web-accessible directories
"""

import configparser
import os
import re
import time
from datetime import datetime


class FileIntegrity:
    def __init__(self, config, reporter, ip_blocker, logger):
        self.config = config
        self.reporter = reporter
        self.ip_blocker = ip_blocker
        self.logger = logger

        self.home_base = self.config.get('file_integrity', 'home_base', fallback='/home')
        self.max_scan_size = self.config.getint('file_integrity', 'max_scan_size', fallback=5242880)
        self.exclude_dirs = set(
            x.strip()
            for x in self.config.get('file_integrity', 'exclude_dirs',
                                     fallback='vendor,node_modules,wp-admin,wp-includes,mail,etc,cache,tmp,logs')
            .split(',')
            if x.strip()
        )
        self.dangerous_functions = set(
            x.strip()
            for x in self.config.get('file_integrity', 'dangerous_functions',
                                     fallback='eval,base64_decode,shell_exec,system,exec,passthru,proc_open,popen,assert,create_function')
            .split(',')
            if x.strip()
        )

        self._scan_interval = 3600  # 1 hour
        self._last_scan = 0

        # Regex for dangerous function call
        fn_pattern = '|'.join(re.escape(f) for f in self.dangerous_functions)
        self._dangerous_fn_re = re.compile(rf'\b({fn_pattern})\s*\(')
        # Encoded payload heuristics
        self._obfuscated_re = re.compile(
            r'(gzinflate|gzuncompress|str_rot13|chr\(\d+\)\.chr\(|'
            r'base64_decode\(\s*["\'][A-Za-z0-9+/=]{100,})',
            re.IGNORECASE,
        )
        self._global_htaccess_re = re.compile(
            r'(Options\s+.*ExecCGI|AddType\s+application/x-httpd-php|'
            r'AddHandler.*\.php|php_value\s+auto_prepend)' , re.IGNORECASE
        )

    def check(self):
        """Scan for webshells and file integrity issues.
        Returns a list of alert dicts.
        """
        now = time.time()
        # Only run this expensive scan on the hourly interval
        if now - self._last_scan < self._scan_interval:
            return []
        self._last_scan = now

        findings = []
        if not os.path.isdir(self.home_base):
            return findings

        for account_dir in os.listdir(self.home_base):
            # Skip non-user/system directories (quota, lost+found, etc.)
            if account_dir in ('lost+found', 'nobody', 'root', '.wh..wh..opq'):
                continue

            user_home = os.path.join(self.home_base, account_dir)
            if not os.path.isdir(user_home):
                continue

            # Public-HTML / web-accessible roots under cPanel
            web_roots = [
                os.path.join(user_home, 'public_html'),
                os.path.join(user_home, 'public_ftp'),
            ]
            for root in web_roots:
                if not os.path.isdir(root):
                    continue
                self._scan_directory(root, account_dir, findings)

            # Check for injected .htaccess files
            self._scan_htaccess(user_home, account_dir, findings)

        return findings

    def _scan_directory(self, root: str, account: str, findings: list):
        """Recursively scan a directory for suspicious PHP files."""
        scanned = 0
        for dirpath, dirnames, filenames in os.walk(root):
            # Prune excluded dirs
            dirnames[:] = [d for d in dirnames if d not in self.exclude_dirs]

            for fname in filenames:
                if not fname.endswith(('.php', '.php5', '.phtml', '.php7', '.pht')):
                    continue

                filepath = os.path.join(dirpath, fname)
                try:
                    size = os.path.getsize(filepath)
                except OSError:
                    continue
                if size == 0 or size > self.max_scan_size:
                    continue

                scanned += 1
                self._inspect_php_file(filepath, account, findings)

        if scanned == 0:
            return

    def _inspect_php_file(self, filepath: str, account: str, findings: list):
        """Check a single PHP file for webshell signatures."""
        try:
            with open(filepath, 'r', errors='ignore') as f:
                content = f.read()
        except (OSError, PermissionError):
            return

        # Skip huge content
        if len(content) > 200000:
            return

        rel_path = os.path.relpath(filepath, os.path.join(self.home_base, account))

        # 1. Dangerous function calls
        if self._dangerous_fn_re.search(content):
            # Check if it looks truly malicious (encoded payload + dangerous fn)
            if self._obfuscated_re.search(content):
                findings.append({
                    'type': 'webshell',
                    'severity': 'critical',
                    'description': f"Obfuscated webshell detected in {account}: {rel_path}",
                    'raw_log': f"File: {filepath}\nSnippet:\n{self._snippet(content)}",
                    'action_taken': 'Quarantine account and scan thoroughly',
                })
            else:
                # Still worth noting (could be legit framework, but on web hosting
                # eval+base64 combination is almost always suspicious)
                findings.append({
                    'type': 'webshell',
                    'severity': 'high',
                    'description': f"Suspicious dangerous function in {account}: {rel_path}",
                    'raw_log': f"File: {filepath}\nSnippet:\n{self._snippet(content)}",
                    'action_taken': 'Review file for legitimacy',
                })

        # 2. Forged PHP with .htaccess combo already covered separately

    def _scan_htaccess(self, user_home: str, account: str, findings: list):
        """Look for injected .htaccess files in unexpected locations or dirs."""
        for dirpath, dirnames, filenames in os.walk(user_home):
            if 'public_html' in dirpath:
                continue
            if 'htaccess' not in ' '.join(filenames).lower():
                continue

            for fname in filenames:
                if fname.lower() != '.htaccess':
                    continue
                ht_path = os.path.join(dirpath, fname)
                try:
                    with open(ht_path, 'r', errors='ignore') as f:
                        content = f.read()
                except Exception:
                    continue
                if self._global_htaccess_re.search(content):
                    findings.append({
                        'type': 'htaccess_injection',
                        'severity': 'high',
                        'description': f"Suspicious .htaccess in {account}: {dirpath}",
                        'raw_log': content[:1000],
                        'action_taken': 'Review .htaccess for malicious directives',
                    })

    def _snippet(self, content: str, length: int = 500) -> str:
        """Extract a short readable snippet around the first suspicious token."""
        idx = 0
        fn_match = self._dangerous_fn_re.search(content)
        ob_match = self._obfuscated_re.search(content)
        if fn_match:
            idx = fn_match.start()
        elif ob_match:
            idx = ob_match.start()
        return content[max(0, idx - 50): idx + 250]
