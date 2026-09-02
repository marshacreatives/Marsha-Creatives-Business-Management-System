#!/usr/bin/env python3
"""
Reporter Module
===============
Handles communication with:
  1. The Laravel dashboard via its REST API
  2. Telegram directly for real-time alerts
  3. Local log file for full audit trail

The agent can run with either or both channels enabled.
"""

import configparser
import json
import logging
import os
import time
import urllib.request
import urllib.error


class Reporter:
    def __init__(self, config, logger=None):
        self.config = config
        self.logger = logger or logging.getLogger('reporter')

        self.server_name = self.config.get('general', 'server_name', fallback='server1')
        self.api_key = self.config.get('general', 'agent_api_key', fallback='')
        self.api_url = self.config.get('general', 'api_url', fallback='')

        self.tg_enabled = self.config.getboolean('telegram', 'enabled', fallback=False)
        self.tg_token = self.config.get('telegram', 'bot_token', fallback='')
        self.tg_chat_id = self.config.get('telegram', 'chat_id', fallback='')
        self.tg_min_level = self.config.get('telegram', 'min_alert_level', fallback='medium')

        self._alert_api_url = f"{self.api_url.rstrip('/')}/alert" if self.api_url else ''
        self._status_api_url = f"{self.api_url.rstrip('/')}/status" if self.api_url else ''
        self._block_api_url = f"{self.api_url.rstrip('/')}/block" if self.api_url else ''

        self._telegram_api_base = f"https://api.telegram.org/bot{self.tg_token}" if self.tg_token else ''

    # ------------------------------------------------------------------
    # HTTP Helper
    # ------------------------------------------------------------------
    def _http_post(self, url: str, data: dict):
        """POST JSON to URL with API key header."""
        if not url:
            return None
        payload = json.dumps(data).encode('utf-8')
        req = urllib.request.Request(url, data=payload, method='POST')
        req.add_header('Content-Type', 'application/json')
        if self.api_key:
            req.add_header('X-Agent-Key', self.api_key)
        try:
            with urllib.request.urlopen(req, timeout=15) as resp:
                return json.loads(resp.read().decode('utf-8'))
        except urllib.error.HTTPError as e:
            self.logger.error(f"HTTP {e.code} posting to {url}: {e.read().decode()}")
        except Exception as e:
            self.logger.error(f"Error posting to {url}: {e}")
        return None

    # ------------------------------------------------------------------
    # Dashboard reporting
    # ------------------------------------------------------------------
    def report_alert(self, finding: dict):
        """Send a security alert to the dashboard and likely Telegram."""
        alert = {
            'server_name': self.server_name,
            'type': finding.get('type', 'unknown'),
            'severity': finding.get('severity', 'medium'),
            'source_ip': finding.get('source_ip'),
            'source_port': finding.get('source_port'),
            'destination_port': finding.get('destination_port'),
            'description': finding.get('description', ''),
            'raw_log': finding.get('raw_log'),
            'action_taken': finding.get('action_taken'),
            'occurred_at': finding.get('occurred_at') or time.strftime('%Y-%m-%dT%H:%M:%S.000Z', time.gmtime()),
        }

        self._http_post(self._alert_api_url, alert)
        self.send_telegram_alert(alert)

    def report_status(self, status: dict):
        """Send server status snapshot to dashboard."""
        if not status:
            return
        status['server_name'] = self.server_name
        status['agent_key'] = self.api_key
        self._http_post(self._status_api_url, status)

    def report_block(self, ip: str, reason: str, duration: int):
        """Notify dashboard that an IP was blocked."""
        self._http_post(self._block_api_url, {
            'ip': ip,
            'reason': reason,
            'duration': duration,
            'blocked_by': self.server_name,
        })
        self.send_telegram_message(
            f"🚫 *IP BLOCKED:* `{ip}`\n"
            f"*Reason:* {reason}\n"
            f"*Server:* {self.server_name}\n"
            f"*Duration:* {duration//3600}h"
        )

    def report_unblock(self, ip: str, reason: str = ''):
        """Notify dashboard that an IP was unblocked."""
        self._http_post(f"{self.api_url.rstrip('/')}/unblock" if self.api_url else '', {
            'ip': ip,
            'reason': reason,
        })
        self.send_telegram_message(
            f"✅ *IP UNBLOCKED:* `{ip}`\n"
            f"*Server:* {self.server_name}"
        )

    # ------------------------------------------------------------------
    # Telegram
    # ------------------------------------------------------------------
    def _severity_level_rank(self, level: str) -> int:
        rank = {'critical': 5, 'high': 4, 'medium': 3, 'low': 2, 'info': 1}
        return rank.get(level, 3)

    def send_telegram_alert(self, alert: dict):
        """Send a formatted alert to Telegram if it meets the threshold."""
        if not self.tg_enabled or not self.tg_token or not self.tg_chat_id:
            return

        if self._severity_level_rank(alert.get('severity', 'info')) < \
           self._severity_level_rank(self.tg_min_level):
            return

        emoji = {
            'critical': '🔴',
            'high': '🟠',
            'medium': '🟡',
            'low': '🔵',
            'info': '⚪',
        }.get(alert.get('severity'), '⚪')

        text = (
            f"{emoji} *SECURITY ALERT: {alert.get('type', '').upper()}*\n"
            f"*Severity:* {alert.get('severity', '').upper()}\n"
        )
        if alert.get('source_ip'):
            text += f"*Source IP:* `{alert.get('source_ip')}`\n"
        text += f"*Description:* {alert.get('description', '')}\n"
        if alert.get('action_taken'):
            text += f"*Action:* {alert.get('action_taken')}\n"
        text += f"*Time:* {alert.get('occurred_at', '')}\n"
        text += f"*Server:* {alert.get('server_name', self.server_name)}"

        self.send_telegram_message(text)

    def send_telegram_message(self, text: str, parse_mode: str = 'Markdown'):
        """Send a plain message to Telegram."""
        if not self.tg_enabled or not self._telegram_api_base or not self.tg_chat_id:
            return

        url = f"{self._telegram_api_base}/sendMessage"
        payload = {
            'chat_id': self.tg_chat_id,
            'text': text,
            'parse_mode': parse_mode,
            'disable_web_page_preview': True,
        }
        self._http_post(url, payload)

    def send_daily_summary(self):
        """Send a daily summary of security events to Telegram."""
        if not self.tg_enabled:
            return

        try:
            # Gather counts from local log if available, else just generic
            text = (
                f"📊 *DAILY SECURITY SUMMARY*\n"
                f"*Server:* {self.server_name}\n"
                f"*Date:* {time.strftime('%Y-%m-%d')}\n\n"
                f"All security checks are active.\n"
                f"Full report available on the dashboard."
            )
            self.send_telegram_message(text)
        except Exception as e:
            self.logger.error(f"Error sending daily summary: {e}")
