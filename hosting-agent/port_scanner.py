#!/usr/bin/env python3
"""
Port Scanner Module
===================
Detects unauthorized ports listening on the server that may indicate a
backdoor, crypto miner, or malware C2 beacon. Monitors the full listening
port list and alerts when:
  - A port not in the allowed list opens
  - A known-dangerous port is found
  - Too many unexpected ports are listening simultaneously
"""

import configparser
import re
import subprocess
import time


class PortScanner:
    def __init__(self, config, reporter, ip_blocker, logger):
        self.config = config
        self.reporter = reporter
        self.ip_blocker = ip_blocker
        self.logger = logger

        self.allowed_ports = set(
            int(p.strip())
            for p in self.config.get('port_scan', 'allowed_ports', fallback='').split(',')
            if p.strip()
        )
        self.dangerous_ports = set(
            int(p.strip())
            for p in self.config.get('port_scan', 'dangerous_ports', fallback='').split(',')
            if p.strip()
        )
        self.scan_interval = self.config.getint('port_scan', 'scan_interval', fallback=300)

        self._last_scan = 0
        self._known_unexpected = set()
        self._alerted = set()

    def check(self):
        """Verify listening ports. Run full scan at interval by default.
        Returns a list of alert dicts.
        """
        now = time.time()

        # Only run the full (potentially slow) scan on the scan_interval
        if now - self._last_scan < self.scan_interval:
            return []

        self._last_scan = now
        findings = []

        try:
            listening = self._get_listening_ports()
            unexpected = listening - self.allowed_ports

            # Danger: a known-dangerous port is open
            dangerous_open = unexpected & self.dangerous_ports
            for port in dangerous_open:
                if port in self._alerted:
                    continue
                self._alerted.add(port)
                findings.append({
                    'type': 'dangerous_port',
                    'severity': 'critical',
                    'destination_port': str(port),
                    'description': f"Dangerous port {port} is listening - possible malware/C2/miner",
                    'action_taken': 'Investigate process on this port',
                })

            # New unexpected ports (not previously known and not alerted recently)
            new_unexpected = (unexpected - self._known_unexpected) - self._alerted
            if new_unexpected:
                # Exclude already-alerted permanently to avoid spam
                for port in list(new_unexpected):
                    if port in self._alerted:
                        continue
                    self._alerted.add(port)
                    findings.append({
                        'type': 'unauthorized_port',
                        'severity': 'high',
                        'destination_port': str(port),
                        'description': f"Unauthorized port {port} is now listening",
                        'action_taken': 'Investigate total allowed vs unexpected ports',
                    })

            self._known_unexpected = unexpected

        except Exception as e:
            self.logger.error(f"Port scanner error: {e}")

        # If many unexpected ports are open at once, flag once
        if len(self._known_unexpected) > 10:
            findings.append({
                'type': 'port_anomaly',
                'severity': 'high',
                'description': f"{len(self._known_unexpected)} unexpected ports are currently listening",
                'action_taken': 'Review recent port changes',
            })

        return findings

    def _get_listening_ports(self) -> set:
        """Return set of all TCP/UDP listening ports."""
        ports = set()
        try:
            ss = subprocess.run(
                ['ss', '-ltnp'],
                capture_output=True, text=True, timeout=15,
            )
            if ss.returncode == 0:
                for line in ss.stdout.splitlines():
                    m = re.search(r':(\d+)\s', line)
                    if m:
                        ports.add(int(m.group(1)))
                return ports
        except FileNotFoundError:
            pass

        # Fallback to netstat
        try:
            netstat = subprocess.run(
                ['netstat', '-ltnp'],
                capture_output=True, text=True, timeout=15,
            )
            if netstat.returncode == 0:
                for line in netstat.stdout.splitlines():
                    m = re.search(r'[.:](\d+)\s', line[0:80]) if line else None
                    # netstat format: tcp 0 0 0.0.0.0:80 0.0.0.0:* LISTEN
                    if 'LISTEN' in line:
                        parts = line.split()
                        if len(parts) >= 4:
                            addr = parts[3]
                            port_part = addr.rsplit(':', 1)[-1]
                            try:
                                ports.add(int(port_part))
                            except ValueError:
                                pass
        except Exception:
            pass

        return ports
