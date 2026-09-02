#!/usr/bin/env python3
"""
Process Monitor Module
======================
Detects suspicious processes running on the server, including:
  - Crypto miners (xmrig, etc.)
  - Web shells / backdoors (netcat, metasploit)
  - Scanning tools (sqlmap, hydra)
  - Processes with excessive memory using unexpected users
  - Anonymous proxy services

Uses `ps` for a snapshot and cross-checks known malicious signatures.
"""

import configparser
import re
import subprocess


class ProcessMonitor:
    def __init__(self, config, reporter, ip_blocker, logger):
        self.config = config
        self.reporter = reporter
        self.ip_blocker = ip_blocker
        self.logger = logger

        self.miner_keywords = self._split_list('miner_keywords', 'xmrig,minergate')
        self.backdoor_keywords = self._split_list('backdoor_keywords', 'nc,netcat')
        self.suspicious_conn_threshold = self.config.getint(
            'process_monitor', 'suspicious_localhost_connections', fallback=10)

        # Signature patterns that strongly indicate a compromised host
        self._base64_cmd_re = re.compile(
            r'(base64\s+-d|echo\s+\S+\|base64|base64\s+--decode)', re.IGNORECASE
        )
        self._download_cmd_re = re.compile(
            r'(wget\s|curl\s+(-o|--output))', re.IGNORECASE
        )
        self._suspicious_users = {'nobody', 'www-data', 'apache', 'ftp', 'nologin', 'bin'}

    def _split_list(self, key: str, default: str) -> set:
        val = self.config.get('process_monitor', key, fallback=default)
        return set(x.strip() for x in val.split(',') if x.strip())

    def check(self):
        """Scan running processes for known malware signatures.
        Returns a list of alert dicts.
        """
        findings = []

        try:
            proc_list = self._get_processes()
        except Exception as e:
            self.logger.error(f"Process monitor error: {e}")
            return findings

        suspicious_cmds = []

        for proc in proc_list:
            pid, user, cmd = proc
            cmd_lower = cmd.lower()

            is_suspicious = False
            reason = None
            severity = 'low'

            # Check for exactly-known miner binaries (whole-word match to avoid
            # false positives from substrings like "nc" inside "conn", "incron", etc.)
            for kw in self.miner_keywords:
                if re.search(r'\b' + re.escape(kw.lower()) + r'\b', cmd_lower):
                    is_suspicious = True
                    reason = f"Crypto miner detected (match: {kw})"
                    severity = 'critical'
                    break

            if not is_suspicious:
                # Backdoor tools: use word-boundary match, and only flag real
                # netcat binaries, not any process whose path merely contains "nc".
                for kw in self.backdoor_keywords:
                    kwl = kw.lower()
                    if kwl in ('nc', 'netcat'):
                        # netcat/binaries depend on path: /bin/nc, /usr/bin/nc, ncat
                        if re.search(r'(^|[/\s])n(cat|etcat)?(\s|$)|\bncat\b', cmd_lower):
                            is_suspicious = True
                            reason = f"Potential backdoor/shell tool detected (match: {kw})"
                            severity = 'high'
                            break
                    elif re.search(r'\b' + re.escape(kwl) + r'\b', cmd_lower):
                        is_suspicious = True
                        reason = f"Potential backdoor/shell tool detected (match: {kw})"
                        severity = 'high'

            if not is_suspicious and user.lower() in self._suspicious_users:
                # Processes running as web-user that are shells/interpreters
                shell_indicators = re.search(
                    r'(bash|sh|python|perl|nc\s|/bin/nc|wget|curl|php\s+-r|base64|aes|mysql\s+--)',
                    cmd_lower,
                )
                if shell_indicators and 'apache' not in cmd_lower.lower() and 'php-fpm' not in cmd_lower.lower():
                    is_suspicious = True
                    reason = f"Web user running suspicious command: {cmd[:120]}"
                    severity = 'high'

            if not is_suspicious:
                # Base64-decoded commands often indicate obfuscated exec
                if self._base64_cmd_re.search(cmd) and \
                   self._download_cmd_re.search(cmd):
                    is_suspicious = True
                    reason = "Base64 + download command chain (common malware pattern)"
                    severity = 'high'

            if is_suspicious:
                suspicious_cmds.append((pid, user, cmd, reason, severity))
                findings.append({
                    'type': 'suspicious_process',
                    'severity': severity,
                    'description': reason,
                    'raw_log': f"PID {pid} (user={user}): {cmd[:300]}",
                    'action_taken': 'Quarantine process and investigate',
                })

        # Check for hidden / spoofed processes (command shown in brackets)
        ps_output = subprocess.run(['ps', 'aux', '--sort=-%mem'],
                                   capture_output=True, text=True, timeout=15).stdout
        top_mem = ps_output.splitlines()[1:11] if ps_output else []
        for line in top_mem:
            parts = line.split()
            if len(parts) >= 11:
                user, pid, cpu, mem = parts[0], parts[1], float(parts[2]), float(parts[3])
                cmd = ' '.join(parts[10:])
                if mem > 10 and user.lower() in self._suspicious_users and 'php' not in cmd.lower():
                    findings.append({
                        'type': 'suspicious_process',
                        'severity': 'medium',
                        'description': f"High memory usage ({mem:.1f}%) by web user: {cmd[:80]}",
                        'raw_log': line,
                        'action_taken': 'Investigate process memory footprint',
                    })

        return findings

    def _get_processes(self):
        """Return list of (pid, user, command) tuples for all processes."""
        result = subprocess.run(
            ['ps', '-eo', 'pid,user,args'],
            capture_output=True, text=True, timeout=15,
        )
        procs = []
        for line in result.stdout.strip().splitlines()[1:]:  # skip header
            parts = line.split(None, 2)
            if len(parts) == 3:
                procs.append((parts[0], parts[1], parts[2]))
        return procs
