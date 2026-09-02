#!/usr/bin/env python3
"""
SSH Guard Module
================
Detects SSH brute-force attacks by monitoring /var/log/auth.log and
automatically blocks offending IP addresses.

Uses a stateful approach that tracks failed attempts per IP within a
time window to avoid blocking legitimate users who make a few mistakes.
"""

import configparser
import os
import re
import subprocess
import time
from collections import defaultdict


class SSHGuard:
    def __init__(self, config, reporter, ip_blocker, logger):
        self.config = config
        self.reporter = reporter
        self.ip_blocker = ip_blocker
        self.logger = logger

        self.max_failed_attempts = self.config.getint('ssh_guard', 'max_failed_attempts', fallback=5)
        self.attempt_window = self.config.getint('ssh_guard', 'attempt_window', fallback=300)
        self.block_duration = self.config.getint('ssh_guard', 'block_duration', fallback=86400)

        self.auth_log = self._find_auth_log()
        self._last_position = 0
        self._attempts = defaultdict(list)  # ip -> list of timestamps

        self._ssh_failed_re = re.compile(
            r'Failed password for (?:invalid user )?(\S+) from (\d+\.\d+\.\d+\.\d+)',
        )
        self._invalid_re = re.compile(
            r'Invalid user (\S+) from (\d+\.\d+\.\d+\.\d+)',
        )
        self._connection_closed_re = re.compile(
            r'Connection (?:closed|reset) by (?:authenticating user \S+ )?(\d+\.\d+\.\d+\.\d+)',
        )

    def _find_auth_log(self) -> str:
        candidates = [
            '/var/log/auth.log',
            '/var/log/secure',
            '/var/log/messages',
        ]
        for path in candidates:
            if os.path.exists(path):
                return path
        return candidates[0]

    def check(self):
        """Check SSH logs for brute-force attempts.
        Returns a list of alert dicts.
        """
        findings = []
        now = time.time()

        # Prune old attempts
        for ip in list(self._attempts.keys()):
            self._attempts[ip] = [
                t for t in self._attempts[ip]
                if now - t < self.attempt_window
            ]
            if not self._attempts[ip]:
                del self._attempts[ip]

        # Read new log entries since last check
        try:
            if not os.path.exists(self.auth_log):
                return findings

            with open(self.auth_log, 'r', errors='ignore') as f:
                f.seek(self._last_position)
                new_lines = f.readlines()
                self._last_position = f.tell()

            for line in new_lines:
                self._process_line(line.rstrip('\n'), findings, now)

        except Exception as e:
            self.logger.error(f"SSH guard error reading log: {e}")

        return findings

    def _process_line(self, line: str, findings: list, now: float):
        ip = None
        match = self._ssh_failed_re.search(line) or self._invalid_re.search(line)
        if match:
            ip = match.group(2)
        else:
            match = self._connection_closed_re.search(line)
            if match:
                ip = match.group(1)

        if not ip:
            return

        # Track attempt
        self._attempts[ip].append(now)

        # Count within the window
        recent = [t for t in self._attempts[ip] if now - t < self.attempt_window]

        if len(recent) >= self.max_failed_attempts:
            # Clear attempts so we only alert/block once per burst
            self._attempts[ip] = []

            # Only trigger if not already blocked
            if self.ip_blocker.is_blocked(ip):
                return

            blocked = self.ip_blocker.block_ip(
                ip,
                reason=f"SSH brute-force: {len(recent)} failed attempts in {self.attempt_window}s",
                duration=self.block_duration,
            )

            findings.append({
                'type': 'ssh_bruteforce',
                'severity': 'critical',
                'source_ip': ip,
                'description': f"SSH brute-force detected: {len(recent)} failed login attempts in {self.attempt_window} seconds",
                'raw_log': line,
                'action_taken': f"IP {ip} blocked" if blocked else "Block failed - check firewall",
            })
