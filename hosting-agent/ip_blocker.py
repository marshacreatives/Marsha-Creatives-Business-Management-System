#!/usr/bin/env python3
"""
IP Blocker Module
=================
Handles blocking and unblocking of IP addresses using:
  - csf (recommended for cPanel) via `csf -d IP "reason"`
  - firewalld via `firewall-cmd`
  - iptables as fallback
  - Whitelist enforcement in cPanel/WHM via whmapi1

Safe guards:
  - Never blocks the local/loopback addresses
  - Never blocks private/reserved IP ranges
  - Never blocks whitelisted IPs
  - Avoids duplicate blocks
"""

import configparser
import ipaddress
import os
import subprocess
import time


class IPBlocker:
    def __init__(self, config, reporter, logger=None):
        self.config = config
        self.reporter = reporter
        self.logger = logger

        self.block_method = self.config.get('ip_blocker', 'block_method', fallback='csf')
        self.whitelist_file = self.config.get('ip_blocker', 'whitelist_file', fallback='/etc/hosting-agent/whitelist.txt')
        self.sync_with_whm = self.config.getboolean('ip_blocker', 'sync_with_whm', fallback=True)

        self._whitelist = set()
        self._load_whitelist()

    def _log(self, msg, level='info'):
        if self.logger:
            getattr(self.logger, level)(msg)

    def _load_whitelist(self):
        """Load IPs that must never be blocked."""
        try:
            with open(self.whitelist_file, 'r') as f:
                for line in f:
                    line = line.strip()
                    if line and not line.startswith('#'):
                        self._whitelist.add(line)
            self._log(f"Loaded {len(self._whitelist)} whitelisted IPs")
        except FileNotFoundError:
            os.makedirs(os.path.dirname(self.whitelist_file), exist_ok=True)
            with open(self.whitelist_file, 'w') as f:
                f.write("# Whitelisted IPs - one per line. These IPs will never be blocked.\n")
                f.write("# 127.0.0.1\n# 192.168.1.10\n")
            self._log(f"Created empty whitelist at {self.whitelist_file}")

    def add_to_whitelist(self, ip: str):
        """Permanently whitelist an IP."""
        self._whitelist.add(ip)
        with open(self.whitelist_file, 'a') as f:
            f.write(f"{ip}\n")
        self._log(f"Added {ip} to whitelist")
        return True

    def remove_from_whitelist(self, ip: str):
        """Remove an IP from the whitelist."""
        self._whitelist.discard(ip)
        with open(self.whitelist_file, 'r') as f:
            lines = f.readlines()
        with open(self.whitelist_file, 'w') as f:
            for line in lines:
                if line.strip() != ip:
                    f.write(line)
        self._log(f"Removed {ip} from whitelist")
        return True

    def is_whitelisted(self, ip: str) -> bool:
        if ip in self._whitelist:
            return True
        # Check if IP is in any whitelisted CIDR
        for entry in self._whitelist:
            if '/' in entry:
                try:
                    network = ipaddress.ip_network(entry, strict=False)
                    if ipaddress.ip_address(ip) in network:
                        return True
                except ValueError:
                    continue
        return False

    def _is_safe_to_block(self, ip: str) -> bool:
        """Prevent blocking critical / infrastructure IPs."""
        # Always allow local addresses
        if ip in ('127.0.0.1', '::1', 'localhost', '0.0.0.0'):
            return False

        try:
            addr = ipaddress.ip_address(ip)
        except ValueError:
            # Not a valid IP - don't block
            return False

        # Never block private/reserved ranges
        if addr.is_private or addr.is_loopback or addr.is_link_local or \
           addr.is_multicast or addr.is_reserved or addr.is_unspecified:
            return False

        # Never block whitelisted
        if self.is_whitelisted(ip):
            return False

        # Common network infrastructure - you can expand this
        known_safe = {
            '8.8.8.8', '8.8.4.4',       # Google DNS
            '1.1.1.1', '1.0.0.1',       # Cloudflare
            '208.67.222.222',            # OpenDNS
        }
        if ip in known_safe:
            return False

        return True

    def is_blocked(self, ip: str) -> bool:
        """Check if an IP is currently blocked by the firewall."""
        try:
            if self.block_method == 'csf':
                result = subprocess.run(
                    ['/usr/sbin/csf', '-g', ip],
                    capture_output=True, text=True, timeout=10
                )
                return result.returncode == 1 and 'DENY' in result.stdout
            elif self.block_method == 'firewalld':
                result = subprocess.run(
                    ['firewall-cmd', '--list-rich-rules'],
                    capture_output=True, text=True, timeout=10
                )
                return ip in result.stdout
            else:  # iptables
                result = subprocess.run(
                    ['iptables', '-L', 'INPUT', '-n'],
                    capture_output=True, text=True, timeout=10
                )
                return ip in result.stdout and 'DROP' in result.stdout
        except Exception as e:
            self._log(f"Error checking block status for {ip}: {e}", 'error')
            return False

    def block_ip(self, ip: str, reason: str, duration: int = 86400) -> bool:
        """
        Block an IP address at the firewall level.
        Returns True if blocked, False if already blocked or unsafe to block.
        """
        if not self._is_safe_to_block(ip):
            self._log(f"Refusing to block {ip} (whitelisted or unsafe)")
            return False

        if self.is_blocked(ip):
            self._log(f"IP {ip} already blocked")
            return False

        self._log(f"Blocking IP {ip}: {reason} (duration={duration}s)")

        success = False
        try:
            if self.block_method == 'csf':
                # Add to csf deny list (temporary by default)
                result = subprocess.run(
                    ['/usr/sbin/csf', '-d', ip, f'"{reason}"'],
                    capture_output=True, text=True, timeout=30
                )
                success = result.returncode == 0
                if not success:
                    self._log(f"csf -d failed: {result.stderr}", 'error')
            elif self.block_method == 'firewalld':
                result = subprocess.run(
                    ['firewall-cmd', '--permanent', '--add-rich-rule',
                     f"rule family='ipv4' source address='{ip}' reject"],
                    capture_output=True, text=True, timeout=15
                )
                if result.returncode == 0:
                    subprocess.run(['firewall-cmd', '--reload'], timeout=15)
                    success = True
                else:
                    self._log(f"firewalld block failed: {result.stderr}", 'error')
            else:  # iptables
                result = subprocess.run(
                    ['iptables', '-I', 'INPUT', '-s', ip, '-j', 'DROP'],
                    capture_output=True, text=True, timeout=15
                )
                success = result.returncode == 0
                if not success:
                    self._log(f"iptables block failed: {result.stderr}", 'error')

            # Also sync with cPanel/WHM if requested
            if success and self.sync_with_whm:
                self._sync_block_whm(ip)

        except Exception as e:
            self._log(f"Error blocking {ip}: {e}", 'error')
            return False

        if success:
            # Report to dashboard
            self.reporter.report_block(ip, reason, duration)
            self.reporter.report_action(
                action='block_ip',
                resource=ip,
                severity='high',
                description=f"Blocked IP {ip}: {reason}",
                details=f"duration={duration}s method={self.block_method}",
            )

        return success

    def unblock_ip(self, ip: str) -> bool:
        """Unblock an IP address."""
        self._log(f"Unblocking IP {ip}")
        success = False
        try:
            if self.block_method == 'csf':
                result = subprocess.run(
                    ['/usr/sbin/csf', '-dr', ip],
                    capture_output=True, text=True, timeout=30
                )
                success = result.returncode == 0
            elif self.block_method == 'firewalld':
                result = subprocess.run(
                    ['firewall-cmd', '--permanent', '--remove-rich-rule',
                     f"rule family='ipv4' source address='{ip}' reject"],
                    capture_output=True, text=True, timeout=15
                )
                if result.returncode == 0:
                    subprocess.run(['firewall-cmd', '--reload'], timeout=15)
                    success = True
            else:  # iptables
                result = subprocess.run(
                    ['iptables', '-D', 'INPUT', '-s', ip, '-j', 'DROP'],
                    capture_output=True, text=True, timeout=15
                )
                success = result.returncode == 0

            if self.sync_with_whm and success:
                self._sync_unblock_whm(ip)

        except Exception as e:
            self._log(f"Error unblocking {ip}: {e}", 'error')
            return False

        if success:
            self.reporter.report_unblock(ip, 'manual/expiry')
            self.reporter.report_action(
                action='unblock_ip',
                resource=ip,
                severity='info',
                description=f"Unblocked IP {ip}",
                details=f"method={self.block_method}",
            )

        return success

    def _sync_block_whm(self, ip: str, expire_ts: int = 0):
        """Add IP to cPanel/WHM firewall deny via whmapi1."""
        try:
            subprocess.run(
                ['whmapi1', 'add_ip_in_deny', f'ip={ip}'],
                capture_output=True, text=True, timeout=30
            )
        except Exception as e:
            self._log(f"WHM sync block failed for {ip}: {e}", 'error')

    def _sync_unblock_whm(self, ip: str):
        """Remove IP from cPanel/WHM firewall deny via whmapi1."""
        try:
            subprocess.run(
                ['whmapi1', 'remove_ip_from_deny', f'ip={ip}'],
                capture_output=True, text=True, timeout=30
            )
        except Exception as e:
            self._log(f"WHM sync unblock failed for {ip}: {e}", 'error')

    def cleanup_expired(self):
        """Remove blocks that have exceeded their duration.
        This requires tracking block times - for simplicity we rely on the
        dashboard's BlockedIP model to handle expiration and notify the agent.
        """
        self._log("Cleanup check complete")
