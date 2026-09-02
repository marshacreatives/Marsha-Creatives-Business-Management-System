@extends('security.layout')
@section('title', 'Security Settings')

@section('security-content')
@php
    $getSetting = fn($key, $default) => $settings->get($key)->value ?? $default;
@endphp

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">Agent Connection</h3>
        <form method="POST" action="{{ route('security.settings.update') }}">
            @csrf
            <div class="space-y-4">
                <div>
                    <label class="block text-sm text-gray-600 mb-1">Agent API Key</label>
                    <input type="text" name="agent_api_key" value="{{ $getSetting('agent_api_key', '') }}" placeholder="Set/change the shared agent API key" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm">
                    <p class="text-xs text-gray-400 mt-1">Must match the AGENT_API_KEY in your .env file and the agent's config.ini.</p>
                </div>

                <div class="border-t pt-4">
                    <h4 class="text-sm font-semibold text-gray-700 mb-3">Telegram Notifications</h4>
                    <div class="flex items-center mb-3">
                        <input type="checkbox" name="telegram_enabled" id="telegram_enabled" value="1" {{ $getSetting('telegram_enabled', false) ? 'checked' : '' }} class="w-4 h-4 text-blue-600">
                        <label for="telegram_enabled" class="ml-2 text-sm text-gray-600">Enable Telegram notifications</label>
                    </div>
                    <div>
                        <label class="block text-sm text-gray-600 mb-1">Minimum Alert Level</label>
                        <select name="telegram_min_level" class="border border-gray-300 rounded-md px-3 py-2 text-sm w-full">
                            @foreach(['critical', 'high', 'medium', 'low', 'info'] as $level)
                                <option value="{{ $level }}" {{ $getSetting('telegram_min_level', 'medium') === $level ? 'selected' : '' }}>{{ ucfirst($level) }}</option>
                            @endforeach
                        </select>
                        <p class="text-xs text-gray-400 mt-1">Alerts below this level will not be sent to Telegram. Bot token & chat ID are configured via .env (TELEGRAM_BOT_TOKEN, TELEGRAM_CHAT_ID).</p>
                    </div>
                </div>

                <div class="border-t pt-4">
                    <h4 class="text-sm font-semibold text-gray-700 mb-3">Data Retention & Blocking</h4>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-sm text-gray-600 mb-1">Alert Retention (days)</label>
                            <input type="number" name="alert_retention_days" value="{{ $getSetting('alert_retention_days', 30) }}" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm">
                        </div>
                        <div>
                            <label class="block text-sm text-gray-600 mb-1">Default Block Duration (hours)</label>
                            <input type="number" name="block_duration_hours" value="{{ $getSetting('block_duration_hours', 24) }}" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm">
                        </div>
                    </div>
                </div>

                <button type="submit" class="w-full bg-blue-600 text-white px-4 py-2 rounded-md text-sm font-medium hover:bg-blue-700">Save Settings</button>
            </div>
        </form>
    </div>

    <div class="space-y-6">
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Agent Deployment</h3>
            <p class="text-sm text-gray-600 mb-4">Deploy the security agent to each hosting server. The agent runs as a systemd service and automatically reports security events and server health to this dashboard.</p>
            <div class="bg-gray-900 text-gray-100 text-xs rounded-lg p-4 overflow-x-auto">
                <pre># On each hosting server:
sudo bash install.sh

# Verify agent is running:
sudo systemctl status hosting-agent</pre>
            </div>
            <div class="mt-4 bg-blue-50 border border-blue-200 rounded-lg p-4">
                <p class="text-xs text-blue-800">
                    <strong>Security note:</strong> The agent API key (X-Agent-Key header) authenticates agent-to-dashboard communication. Always rotate it if compromised, and restrict the dashboard's API route to agents only.
                </p>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Environment Variables</h3>
            <p class="text-sm text-gray-600 mb-3">Add these to your <code class="bg-gray-100 px-1 rounded">.env</code> file:</p>
            <div class="bg-gray-900 text-gray-100 text-xs rounded-lg p-4 overflow-x-auto">
                <pre># Security dashboard
AGENT_API_KEY=your-secure-random-key

# Telegram (optional)
TELEGRAM_BOT_TOKEN=your-bot-token
TELEGRAM_CHAT_ID=your-chat-id</pre>
            </div>
        </div>
    </div>
</div>
@endsection
