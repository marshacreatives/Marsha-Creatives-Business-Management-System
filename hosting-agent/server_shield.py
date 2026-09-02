#!/usr/bin/env python3
"""
Server Resource Shield (SRS)
============================
Protects the host against resource exhaustion caused by runaway processes
(coin miners, compromised PHP scripts executing loops, mass-spawned shells).

This module is deliberately SAFE-BY-DEFAULT: it only acts on processes that
are both (a) consuming abnormal CPU and (b) NOT on the protected-services
allowlist (mysqld, httpd, php-fpm, cpanel core, etc.). A blanket "kill
everything above 50% CPU" would take down a healthy server, so the shield
always targets the *offending process* and never the control plane.

Configuration ([server_shield]):
  enabled            = true            # master switch
  cpu_threshold      = 50              # process %CPU to consider "runaway"
  load_threshold     = 4.0             # system load/1min multiplier to trigger guard
  min_duration       = 60              # how long process must persist before action (s)
  auto_kill          = false           # kill the offending process automatically
  protected          = comma list      # process basenames that must NEVER be killed
  exclude_users      = comma list      # owners that are exempt (system/service users)
"""

import os
import re
import shutil
import subprocess
import time


class ServerShield:
    def __init__(self, config, reporter, ip_blocker, logger):
        self.config = config
        self.reporter = reporter
        self.ip_blocker = ip_blocker
        self.logger = logger

        self.enabled = self.config.getboolean('server_shield', 'enabled', fallback=True)
        self.cpu_threshold = self.config.getfloat('server_shield', 'cpu_threshold', fallback=50)
        self.load_threshold = self.config.getfloat('server_shield', 'load_threshold', fallback=4.0)
        self.min_duration = self.config.getint('server_shield', 'min_duration', fallback=60)
        self.auto_kill = self.config.getboolean('server_shield', 'auto_kill', fallback=False)

        self.protected = set(
            x.strip().lower()
            for x in self.config.get('server_shield', 'protected', fallback='').split(',')
            if x.strip()
        ) or self._default_protected()

        self.exclude_users = set(
            x.strip().lower()
            for x in self.config.get('server_shield', 'exclude_users', fallback='').split(',')
            if x.strip()
        )

        # Track (pid, start_id) so a process must stay hot across multiple
        # samples before we consider it a real runaway (avoids killing on
        # a one-off CPU spike).
        self._watch = {}
        self._last_cleanup = 0

    @staticmethod
    def _default_protected():
        """Critical processes that must never be auto-killed."""
        return {
            'mysqld', 'mariadbd', 'httpd', 'apache2', 'nginx', 'php-fpm', 'php-cgi',
            'cpsrvd', 'cpdavd', 'tailwatchd', 'exim', 'dovecot', 'sshd', 'crond',
            'srs', 'qmail', 'imapd', 'xinetd', 'named', 'systemd', 'kerneloops',
            'logger', 'lxcfs', 'java', 'lsphp', 'lsapi', 'pure-ftpd', 'vsftpd',
            'cpanellogd', 'spamd', 'redis-server', 'memcached', 'postgres',
        }

    # ------------------------------------------------------------------
    def _system_load(self):
        """Return 1-minute load average."""
        try:
            with open('/proc/loadavg') as f:
                return float(f.read().split()[0])
        except Exception:
            return 0.0

    def _cpu_count(self):
        """Number of logical CPUs."""
        try:
            return os.cpu_count() or 1
        except Exception:
            return 1

    def _all_processes(self):
        """Return list of dicts with pid, user, cpu, cmd for every process."""
        # %CPU in `ps aux` is cumulative lifetime average, not current load.
        # Use `top -b -n1` style instantaneous but keep it cheap: ps %cpu is
        # good enough to rank offenders without adding meaningful overhead.
        out = subprocess.run(
            ['ps', 'aux'],
            capture_output=True, text=True, timeout=15,
        ).stdout
        procs = []
        for line in out.splitlines()[1:]:
            parts = line.split(None, 10)
            if len(parts) < 11:
                continue
            try:
                procs.append({
                    'pid': int(parts[1]),
                    'user': parts[0],
                    'cpu': float(parts[2]),
                    'cmd': parts[10],
                })
            except (ValueError, IndexError):
                continue
        return procs

    def _basename(self, cmd: str):
        """Return the executable basename from a command string."""
        first = cmd.split()[0] if cmd else ''
        base = os.path.basename(first)
        if not base:
            base = first
        return base.lower()

    def _ensure_alive(self, pid: int):
        """Return True if the process is still running."""
        return os.path.exists(f'/proc/{pid}')

    def _kill(self, pid: int, signal_nr: int = 15):
        """Send a signal to a process. Returns True on success."""
        try:
            os.kill(pid, signal_nr)
            return True
        except (ProcessLookupError, PermissionError, OSError) as e:
            self.logger.warning(f"server_shield: failed to signal PID {pid}: {e}")
            return False

    # ------------------------------------------------------------------
    def check(self):
        """Evaluate system load and find runaway processes.

        Returns a list of alert dicts. If auto_kill is enabled and the
        process is not protected, it is SIGTERM'd after min_duration of
        sustained high CPU.
        """
        findings = []
        if not self.enabled:
            return findings

        # Clean up stale watch entries periodically
        now = time.time()
        if now - self._last_cleanup > 300:
            self._watch = {k: v for k, v in self._watch.items() if self._ensure_alive(k)}
            self._last_cleanup = now

        load = self._system_load()
        nproc = self._cpu_count()
        # Normalize: load 1.0 on a 1-CPU box is "busy"; guard on load that
        # clearly exceeds available cores.
        load_busy = load > (self.load_threshold * max(1, nproc))

        if not load_busy:
            # No sustained high load -> nothing to defend against.
            return findings

        self.logger.info(
            f"server_shield: high load detected ({load:.2f} on {nproc} core(s))"
        )

        for proc in self._all_processes():
            pid = proc['pid']
            cpu = proc['cpu']
            user = proc['user'].lower()
            base = self._basename(proc['cmd'])

            if cpu < self.cpu_threshold:
                self._watch.pop(pid, None)
                continue
            if base in self.protected:
                continue
            if user in self.exclude_users:
                continue
            if user in ('root',) and base in ('python3', 'hosting-agent'):
                continue  # never kill ourselves or root system tasks
            if pid == os.getpid():
                continue

            # Track persistence
            entry = self._watch.get(pid)
            if entry is None:
                self._watch[pid] = {'first_seen': now, 'cmd': proc['cmd'], 'user': proc['user']}
                continue  # needs a second sample to confirm
            if now - entry['first_seen'] < self.min_duration:
                continue

            # Confirmed sustained runaway process
            cmd_snippet = proc['cmd'][:200]
            finding = {
                'type': 'runaway_process',
                'severity': 'high',
                'description': (
                    f"Runaway process detected: {base} (PID {pid}, user {proc['user']}) "
                    f"using {cpu:.1f}% CPU under high load ({load:.2f})"
                ),
                'raw_log': f"PID {pid} user={proc['user']} cpu={cpu:.1f}% cmd={cmd_snippet}",
                'action_taken': 'Auto-kill' if self.auto_kill else 'Flagged for review',
            }

            if self.auto_kill:
                # SIGTERM first, escalate to SIGKILL shortly after if needed
                if self._kill(pid, 15):
                    self.logger.warning(f"server_shield: SIGTERM sent to PID {pid} ({base})")
                    # Give it a moment, then force kill if still alive
                    time.sleep(1)
                    if self._ensure_alive(pid):
                        self._kill(pid, 9)
                        self.logger.warning(f"server_shield: SIGKILL sent to PID {pid} ({base})")
                    finding['action_taken'] = f"Killed runaway process {base} (PID {pid})"
                    # Record the kill action on the dashboard audit trail
                    self.reporter.report_action(
                        action='kill_process',
                        resource=str(pid),
                        severity='high',
                        description=f"Killed runaway process {base} (PID {pid}) under high load",
                        details=proc['cmd'][:400],
                    )

            findings.append(finding)
            self._watch.pop(pid, None)

        return findings


# Convenience basename allowlist for config docs
def protected_list():
    return ",".join(sorted(ServerShield._default_protected()))
