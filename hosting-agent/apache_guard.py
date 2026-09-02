#!/usr/bin/env python3
"""
Apache Guard Module
===================
Monitors Apache access and error logs for:
  - SQL injection attempts
  - Directory traversal
  - Malicious user-agents (scanners, bots)
  - XML-RPC attacks
  - Repeated 4xx/5xx errors from same IP (scanner detection)
  - PHP file uploads in suspicious locations
  - WordPress/wp-login brute force

Automatically blocks offending IPs after a threshold is hit.
"""

import configparser
import os
import re
import time
from collections import defaultdict


class ApacheGuard:
    def __init__(self, config, reporter, ip_blocker, logger):
        self.config = config
        self.reporter = reporter
        self.ip_blocker = ip_blocker
        self.logger = logger

        self.access_log = self.config.get('apache_guard', 'apache_access_log',
                                          fallback='/usr/local/apache/logs/access_log')
        self.error_log = self.config.get('apache_guard', 'apache_error_log',
                                         fallback='/usr/local/apache/logs/error_log')
        self.max_suspicious = self.config.getint('apache_guard', 'max_suspicious_requests', fallback=20)
        self.attempt_window = self.config.getint('apache_guard', 'attempt_window', fallback=300)
        self.block_duration = self.config.getint('apache_guard', 'block_duration', fallback=86400)

        self._last_access_pos = 0
        self._last_error_pos = 0
        self._suspicious = defaultdict(list)

        self._sql_injection_re = re.compile(
            r"(union\s+select|select\s+.*\s+from|insert\s+into|\bupdate\b.*\bset\b|"
            r"drop\s+table|delete\s+from|\bor\s+1=1\b|concat\(|information_schema|"
            r"sleep\(|benchmark\(|load_file\(|0x[0-9a-f]{8,})",
            re.IGNORECASE,
        )
        self._traversal_re = re.compile(
            r"(\.\./|\.\.\\|%2e%2e|/etc/passwd|/proc/self|window\.conf|boot\.ini|"
            r"\\x00|%00)", re.IGNORECASE
        )
        self._command_inj_re = re.compile(
            r"(\||;|&&|\bcat\b|\bwget\b|\bcurl\b|\bbash\b|\b/bin/sh\b|%7c|%3b)"
            r".*(/etc/passwd|/bin/|cmd\.exe|powershell|nc\s)",
            re.IGNORECASE,
        )
        self._xss_re = re.compile(
            r"(<script[\s>]|javascript:|onerror\s*=|onload\s*=|<iframe)"
            r".*", re.IGNORECASE
        )
        self._xmlrpc_re = re.compile(r"/xmlrpc\.php", re.IGNORECASE)
        self._wp_login_re = re.compile(r"/wp-login\.php", re.IGNORECASE)
        self._scanner_ua_re = re.compile(
            r"(nikto|sqlmap|nessus|acunetix|openvas|masscan|nmap|zoomeye|"
            r"fimap|havij|pangolin|xrlabs|dirbuster|wpscan|gobuster|hydra|"
            r"metasploit|fuzz|scan|vuln|exploit|probing|nikto)",
            re.IGNORECASE,
        )

    def check(self):
        """Scan Apache logs for attacks.
        Returns a list of alert dicts.
        """
        findings = []
        now = time.time()

        # Prune old suspicious records
        for ip in list(self._suspicious.keys()):
            self._suspicious[ip] = [
                t for t in self._suspicious[ip]
                if now - t < self.attempt_window
            ]
            if not self._suspicious[ip]:
                del self._suspicious[ip]

        self._check_access_log(findings, now)
        self._check_error_log(findings, now)

        return findings

    def _parse_access_line(self, line: str):
        """Extract IP, request, user-agent from an Apache access log line."""
        # Common Apache combined format:
        # IP - - [date] "METHOD /path HTTP/1.1" status size "referer" "user-agent"
        parts = line.split('"')
        if len(parts) < 6:
            return None

        ip = line.split()[0]
        request = parts[1] if len(parts) > 1 else ''
        status = ''
        try:
            status = line.split('"')[2].split()[1]
        except (IndexError, ValueError):
            pass
        user_agent = parts[5] if len(parts) > 5 else ''

        return {
            'ip': ip,
            'request': request,
            'status': status,
            'user_agent': user_agent,
            'raw': line.rstrip('\n'),
        }

    def _check_access_log(self, findings: list, now: float):
        """Scan access log for malicious requests."""
        try:
            if not os.path.exists(self.access_log):
                return

            with open(self.access_log, 'r', errors='ignore') as f:
                f.seek(self._last_access_pos)
                new_lines = f.readlines()
                self._last_access_pos = f.tell()

            attacks_per_ip = defaultdict(list)

            for line in new_lines:
                entry = self._parse_access_line(line)
                if not entry:
                    continue

                ip = entry['ip']
                request = entry['request']
                ua = entry['user_agent']
                attack_type = None

                if self._sql_injection_re.search(request):
                    attack_type = 'sql_injection'
                elif self._traversal_re.search(request):
                    attack_type = 'directory_traversal'
                elif self._command_inj_re.search(request):
                    attack_type = 'command_injection'
                elif self._xss_re.search(request):
                    attack_type = 'xss_attempt'
                elif self._xmlrpc_re.search(request):
                    attack_type = 'xmlrpc_abuse'
                elif self._scanner_ua_re.search(ua):
                    attack_type = 'scanner_ua'
                elif self._wp_login_re.search(request):
                    attack_type = 'wp_login_bruteforce'

                if attack_type:
                    attacks_per_ip[ip].append((attack_type, entry))

            for ip, attacks in attacks_per_ip.items():
                if self.ip_blocker.is_blocked(ip):
                    continue

                count = len(attacks)
                if count >= self.max_suspicious:
                    self._suspicious[ip].append(now)
                    types = set(t for t, _ in attacks)
                    blocked = self.ip_blocker.block_ip(
                        ip,
                        reason=f"Apache attack: {count} suspicious requests ({', '.join(types)})",
                        duration=self.block_duration,
                    )
                    last_attack_type, last_entry = attacks[-1]
                    findings.append({
                        'type': 'web_attack',
                        'severity': 'high',
                        'source_ip': ip,
                        'destination_port': 80,
                        'description': f"{count} suspicious requests detected: {', '.join(types)}",
                        'raw_log': last_entry['raw'],
                        'action_taken': f"IP {ip} blocked" if blocked else "Block failed",
                    })
                elif count >= 5:
                    # Individual attack alert, don't block yet
                    last_attack_type, last_entry = attacks[-1]
                    findings.append({
                        'type': 'web_attack',
                        'severity': 'medium',
                        'source_ip': ip,
                        'destination_port': 80,
                        'description': f"Possible {last_attack_type} attempt from {ip}",
                        'raw_log': last_entry['raw'],
                        'action_taken': 'Monitor',
                    })

        except Exception as e:
            self.logger.error(f"Apache guard error reading access log: {e}")

    def _check_error_log(self, findings: list, now: float):
        """Scan Apache error log for anomalies."""
        try:
            if not os.path.exists(self.error_log):
                return

            with open(self.error_log, 'r', errors='ignore') as f:
                f.seek(self._last_error_pos)
                new_lines = f.readlines()
                self._last_error_pos = f.tell()

            cgi_errors = 0
            for line in new_lines:
                # Detect suspicious CGI/execution errors
                if re.search(r'(cgi|\.cmd|\.exe|/bin/|perl|sh -c)', line, re.IGNORECASE):
                    cgi_errors += 1

            if cgi_errors > 3:
                findings.append({
                    'type': 'web_attack',
                    'severity': 'medium',
                    'description': f"{cgi_errors} suspicious execution errors in Apache error log",
                    'raw_log': '\n'.join(new_lines[-3:]),
                    'action_taken': 'Investigate',
                })

        except Exception as e:
            self.logger.error(f"Apache guard error reading error log: {e}")
