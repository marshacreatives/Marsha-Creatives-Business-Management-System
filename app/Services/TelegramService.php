<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelegramService
{
    protected ?string $token = null;
    protected ?string $chatId = null;

    public function __construct()
    {
        $this->token = config('security.telegram.bot_token');
        $this->chatId = config('security.telegram.chat_id');
    }

    public function isEnabled(): bool
    {
        return !empty($this->token) && !empty($this->chatId);
    }

    public function sendMessage(string $message, string $parseMode = 'Markdown'): bool
    {
        if (!$this->isEnabled()) {
            return false;
        }

        try {
            $response = Http::timeout(10)
                ->post("https://api.telegram.org/bot{$this->token}/sendMessage", [
                    'chat_id' => $this->chatId,
                    'text' => $message,
                    'parse_mode' => $parseMode,
                    'disable_web_page_preview' => true,
                ]);

            return $response->successful();
        } catch (\Exception $e) {
            Log::error("Telegram send failed: {$e->getMessage()}");
            return false;
        }
    }

    public function sendAlert(array $alert): bool
    {
        if (!$this->isEnabled()) {
            return false;
        }

        $severity = $alert['severity'] ?? 'info';
        $emoji = match ($severity) {
            'critical' => '🔴',
            'high' => '🟠',
            'medium' => '🟡',
            'low' => '🔵',
            default => '⚪',
        };

        $message = "{$emoji} *SECURITY ALERT: " . strtoupper($alert['type'] ?? 'unknown') . "*\n";
        $message .= "*Severity:* " . strtoupper($severity) . "\n";

        if (!empty($alert['source_ip'])) {
            $message .= "*Source IP:* `{$alert['source_ip']}`\n";
        }

        $message .= "*Description:* " . ($alert['description'] ?? '') . "\n";

        if (!empty($alert['action_taken'])) {
            $message .= "*Action:* {$alert['action_taken']}\n";
        }

        if (!empty($alert['server_name'])) {
            $message .= "*Server:* {$alert['server_name']}\n";
        }

        $message .= "*Time:* " . now()->format('Y-m-d H:i:s');

        return $this->sendMessage($message);
    }

    public function sendBlocked(string $ip, string $reason, ?string $serverName = null): bool
    {
        $server = $serverName ? "\n*Server:* {$serverName}" : '';
        return $this->sendMessage(
            "🚫 *IP BLOCKED:* `{$ip}`\n*Reason:* {$reason}\n*Time:* " . now()->format('Y-m-d H:i:s') . $server
        );
    }

    public function sendUnblocked(string $ip, ?string $serverName = null): bool
    {
        $server = $serverName ? "\n*Server:* {$serverName}" : '';
        return $this->sendMessage(
            "✅ *IP UNBLOCKED:* `{$ip}`\n*Time:* " . now()->format('Y-m-d H:i:s') . $server
        );
    }

    public function sendDailySummary(array $stats): bool
    {
        if (!$this->isEnabled()) {
            return false;
        }

        $message = "📊 *DAILY SECURITY SUMMARY*\n";
        $message .= "*Date:* " . now()->format('Y-m-d') . "\n\n";
        $message .= "*Total Alerts (24h):* {$stats['total_alerts']}\n";
        $message .= "*Critical:* {$stats['critical']}\n";
        $message .= "*High:* {$stats['high']}\n";
        $message .= "*Medium:* {$stats['medium']}\n";
        $message .= "*IPS Blocked (Active):* {$stats['active_blocks']}\n\n";
        $message .= "Full report available on the dashboard.";

        return $this->sendMessage($message);
    }
}
