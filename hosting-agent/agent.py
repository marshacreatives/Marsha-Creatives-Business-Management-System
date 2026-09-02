#!/usr/bin/env python3
"""
Web Hosting Security Agent - Main Daemon
=========================================
Runs security checks on a web hosting server and reports findings to the
Laravel dashboard and Telegram bot. Detects and blocks suspicious IPs.

Run as root or with sudo:
    python3 agent.py --foreground     # run in foreground (debug)
    python3 agent.py --check          # run a single round of checks, then exit
    python3 agent.py --daemon         # run as background daemon (default)

Install as a service:
    sudo bash install.sh
"""

import argparse
import configparser
import logging
import os
import signal
import subprocess
import sys
import threading
import time

BASE_DIR = os.path.dirname(os.path.abspath(__file__))
sys.path.insert(0, BASE_DIR)

from ip_blocker import IPBlocker
from reporter import Reporter


class HostingAgent:
    """Main security agent that orchestrates all checks."""

    def __init__(self, config_path: str, foreground: bool = False):
        self.config_path = config_path
        self.config = configparser.ConfigParser()
        self.config.read(config_path)

        self.server_name = self.config.get('general', 'server_name', fallback='server1')
        self.check_interval = self.config.getint('general', 'check_interval', fallback=60)
        self.status_interval = self.config.getint('general', 'status_interval', fallback=300)
        self.foreground = foreground
        self.running = True

        self._setup_logging()

        self.reporter = Reporter(config=self.config, logger=self.logger)
        self.ip_blocker = IPBlocker(config=self.config, reporter=self.reporter, logger=self.logger)

        self.modules = self._load_modules()

        # State tracking
        self._last_status_report = 0
        self._last_daily_summary = None

        self.logger.info(f"Hosting Agent started on {self.server_name} (foreground={foreground})")

    def _setup_logging(self):
        log_dir = os.path.dirname(self.config.get('general', 'log_file', fallback='/var/log/hosting-agent/agent.log'))
        os.makedirs(log_dir, exist_ok=True)

        level = logging.DEBUG if self.foreground else logging.INFO
        self.logger = logging.getLogger('hosting-agent')
        self.logger.setLevel(level)

        log_file = self.config.get('general', 'log_file', fallback='/var/log/hosting-agent/agent.log')
        fh = logging.FileHandler(log_file)
        fh.setLevel(level)
        formatter = logging.Formatter('%(asctime)s [%(levelname)s] %(name)s: %(message)s')
        fh.setFormatter(formatter)
        self.logger.addHandler(fh)

        if self.foreground:
            ch = logging.StreamHandler()
            ch.setLevel(level)
            ch.setFormatter(formatter)
            self.logger.addHandler(ch)

    def _load_modules(self):
        """Load enabled security check modules dynamically."""
        from ssh_guard import SSHGuard
        from apache_guard import ApacheGuard
        from port_scanner import PortScanner
        from process_monitor import ProcessMonitor
        from file_integrity import FileIntegrity
        from server_shield import ServerShield

        modules = []
        if self.config.getboolean('general', 'enable_ssh_guard', fallback=True):
            modules.append(SSHGuard(self.config, self.reporter, self.ip_blocker, self.logger))
        if self.config.getboolean('general', 'enable_apache_guard', fallback=True):
            modules.append(ApacheGuard(self.config, self.reporter, self.ip_blocker, self.logger))
        if self.config.getboolean('general', 'enable_port_scan', fallback=True):
            modules.append(PortScanner(self.config, self.reporter, self.ip_blocker, self.logger))
        if self.config.getboolean('general', 'enable_process_monitor', fallback=True):
            modules.append(ProcessMonitor(self.config, self.reporter, self.ip_blocker, self.logger))
        if self.config.getboolean('general', 'enable_file_integrity', fallback=True):
            modules.append(FileIntegrity(self.config, self.reporter, self.ip_blocker, self.logger))
        if self.config.getboolean('general', 'enable_server_shield', fallback=True):
            modules.append(ServerShield(self.config, self.reporter, self.ip_blocker, self.logger))

        return modules

    def run_checks(self):
        """Run a single round of all security checks."""
        self.logger.info("=== Starting security check round ===")

        for module in self.modules:
            module_name = module.__class__.__name__
            self.logger.info(f"Running {module_name}...")
            start = time.time()
            try:
                findings = module.check()
                elapsed = (time.time() - start) * 1000
                self.logger.info(f"{module_name} complete: {len(findings)} alerts, {elapsed:.0f}ms")

                for finding in findings:
                    self.logger.warning(f"{module_name} alert: {finding.get('description')} src={finding.get('source_ip')}")

                    # Record the alert via reporter
                    self.reporter.report_alert(finding)

                    # Block if a source IP is flagged and blocking is enabled
                    source_ip = finding.get('source_ip')
                    self.logger.warning(f"{module_name} alert: {finding.get('description')} src={source_ip}")

            except Exception as e:
                self.logger.error(f"Error running {module_name}: {e}", exc_info=self.foreground)

        self.logger.info("=== Security check round complete ===")

    def report_status(self):
        """Report current server health metrics to dashboard."""
        self.logger.debug("Reporting server status...")
        try:
            status = self._gather_status()
            self.reporter.report_status(status)
        except Exception as e:
            self.logger.error(f"Error reporting status: {e}")

    def _gather_status(self):
        """Gather server metrics by running monitor.sh."""
        monitor_script = os.path.join(BASE_DIR, 'monitor.sh')
        try:
            result = subprocess.run(
                ['bash', monitor_script],
                capture_output=True,
                text=True,
                timeout=30,
            )
            if result.returncode == 0:
                import json
                return json.loads(result.stdout)
            else:
                self.logger.error(f"monitor.sh failed: {result.stderr}")
                return None
        except Exception as e:
            self.logger.error(f"Error running monitor.sh: {e}")
            return None

    def send_daily_summary(self):
        """Send the daily security summary to Telegram."""
        self.logger.info("Sending daily summary...")
        try:
            self.reporter.send_daily_summary()
        except Exception as e:
            self.logger.error(f"Error sending daily summary: {e}")

    def _should_send_daily_summary(self):
        if not self.config.getboolean('telegram', 'daily_summary', fallback=True):
            return False
        summary_hour = self.config.getint('telegram', 'daily_summary_hour', fallback=9)
        now = time.localtime()
        if now.tm_hour != summary_hour:
            return False
        today_str = time.strftime('%Y-%m-%d', now)
        if self._last_daily_summary == today_str:
            return False
        self._last_daily_summary = today_str
        return True

    def startup_tasks(self):
        """Tasks to run once at startup."""
        self.logger.info("Running startup tasks...")
        # Clean up expired blocks
        self.ip_blocker.cleanup_expired()
        # Report initial status
        self.report_status()

    def run(self):
        """Main daemon loop."""
        self.startup_tasks()
        last_check = 0

        while self.running:
            now = time.time()

            # Run security checks on interval
            if now - last_check >= self.check_interval:
                self.run_checks()
                last_check = now

            # Report status on interval
            if now - self._last_status_report >= self.status_interval:
                self.report_status()
                self._last_status_report = now

            # Send daily summary
            if self._should_send_daily_summary():
                self.send_daily_summary()

            time.sleep(5)

        self.logger.info("Agent stopped.")

    def stop(self, signum=None, frame=None):
        self.logger.info("Received stop signal, shutting down...")
        self.running = False


def parse_config_path() -> str:
    """Resolve config path from env or default."""
    env_path = os.environ.get('HOSTING_AGENT_CONFIG')
    if env_path:
        return env_path

    candidates = [
        os.path.join(BASE_DIR, 'config.ini'),
        '/etc/hosting-agent/config.ini',
    ]
    for path in candidates:
        if os.path.exists(path):
            return path
    return candidates[0]


def main():
    parser = argparse.ArgumentParser(description='Web Hosting Security Agent')
    parser.add_argument('--foreground', action='store_true', help='Run in foreground with debug logging')
    parser.add_argument('--check', action='store_true', help='Run a single round of checks, then exit')
    parser.add_argument('--status', action='store_true', help='Report server status once, then exit')
    parser.add_argument('--daemon', action='store_true', help='Run as background daemon (default)')
    parser.add_argument('--config', help='Path to config.ini')
    args = parser.parse_args()

    config_path = args.config or parse_config_path()
    agent = HostingAgent(config_path=config_path, foreground=args.foreground)

    # Register signal handlers for graceful shutdown
    signal.signal(signal.SIGINT, agent.stop)
    signal.signal(signal.SIGTERM, agent.stop)

    if args.check:
        agent.run_checks()
        sys.exit(0)

    if args.status:
        agent.report_status()
        sys.exit(0)

    agent.run()


if __name__ == '__main__':
    main()
