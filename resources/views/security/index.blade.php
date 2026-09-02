@extends('security.layout')
@section('title', 'Security Overview')

@section('security-content')
@php
    $statusColor = match($latestStatus->cpu_status ?? 'healthy') {
        'critical' => 'bg-red-500',
        'warning' => 'bg-yellow-500',
        default => 'bg-green-500',
    };
@endphp

<!-- Stat Cards -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    <div class="bg-white rounded-lg shadow p-6 card-hover">
        <div class="flex items-center">
            <div class="p-3 bg-red-100 rounded-full">
                <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/></svg>
            </div>
            <div class="ml-4">
                <p class="text-sm text-gray-500">Critical Alerts</p>
                <p class="text-2xl font-bold {{ $stats['critical_alerts'] > 0 ? 'text-red-600' : 'text-gray-800' }}">{{ $stats['critical_alerts'] }}</p>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow p-6 card-hover">
        <div class="flex items-center">
            <div class="p-3 bg-orange-100 rounded-full">
                <svg class="w-6 h-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <div class="ml-4">
                <p class="text-sm text-gray-500">High Alerts</p>
                <p class="text-2xl font-bold {{ $stats['high_alerts'] > 0 ? 'text-orange-600' : 'text-gray-800' }}">{{ $stats['high_alerts'] }}</p>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow p-6 card-hover">
        <div class="flex items-center">
            <div class="p-3 bg-purple-100 rounded-full">
                <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <div class="ml-4">
                <p class="text-sm text-gray-500">Active IP Blocks</p>
                <p class="text-2xl font-bold {{ $stats['active_blocks'] > 0 ? 'text-purple-600' : 'text-gray-800' }}">{{ $stats['active_blocks'] }}</p>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow p-6 card-hover">
        <div class="flex items-center">
            <div class="p-3 bg-blue-100 rounded-full">
                <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 010 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h16a1 1 0 010 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h16a1 1 0 010 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h16a1 1 0 010 2H4a1 1 0 01-1-1z"/></svg>
            </div>
            <div class="ml-4">
                <p class="text-sm text-gray-500">Alerts (24h)</p>
                <p class="text-2xl font-bold text-gray-800">{{ $stats['alerts_24h'] }}</p>
            </div>
        </div>
    </div>
</div>

<!-- Server health bar -->
@if($latestStatus)
<div class="bg-white rounded-lg shadow p-6 mb-8">
    <div class="flex items-center justify-between mb-4">
        <h3 class="text-lg font-semibold text-gray-800">Server Health: {{ $latestStatus->server_name }}</h3>
        <span class="text-sm text-gray-500">Last check: {{ $latestStatus->checked_at->diffForHumans() }}</span>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div>
            <div class="flex items-center justify-between mb-2">
                <span class="text-sm text-gray-600">CPU Usage</span>
                <span class="text-sm font-medium {{ $latestStatus->cpu_status === 'critical' ? 'text-red-600' : ($latestStatus->cpu_status === 'warning' ? 'text-yellow-600' : 'text-green-600') }}">
                    {{ $latestStatus->cpu_usage }}%
                </span>
            </div>
            <div class="w-full bg-gray-200 rounded-full h-2.5">
                <div class="h-2.5 rounded-full {{ $latestStatus->cpu_status === 'critical' ? 'bg-red-500' : ($latestStatus->cpu_status === 'warning' ? 'bg-yellow-500' : 'bg-green-500') }}"
                     style="width: {{ min($latestStatus->cpu_usage, 100) }}%"></div>
            </div>
        </div>
        <div>
            <div class="flex items-center justify-between mb-2">
                <span class="text-sm text-gray-600">RAM Usage</span>
                <span class="text-sm font-medium {{ $latestStatus->ram_status === 'critical' ? 'text-red-600' : ($latestStatus->ram_status === 'warning' ? 'text-yellow-600' : 'text-green-600') }}">
                    {{ $latestStatus->ram_usage }}% ({{ $latestStatus->ram_used_gb }} / {{ $latestStatus->ram_total_gb }} GB)
                </span>
            </div>
            <div class="w-full bg-gray-200 rounded-full h-2.5">
                <div class="h-2.5 rounded-full {{ $latestStatus->ram_status === 'critical' ? 'bg-red-500' : ($latestStatus->ram_status === 'warning' ? 'bg-yellow-500' : 'bg-green-500') }}"
                     style="width: {{ min($latestStatus->ram_usage, 100) }}%"></div>
            </div>
        </div>
        <div>
            <div class="flex items-center justify-between mb-2">
                <span class="text-sm text-gray-600">Disk Usage</span>
                <span class="text-sm font-medium {{ $latestStatus->disk_status === 'critical' ? 'text-red-600' : ($latestStatus->disk_status === 'warning' ? 'text-yellow-600' : 'text-green-600') }}">
                    {{ $latestStatus->disk_usage }}% ({{ $latestStatus->disk_used_gb }} / {{ $latestStatus->disk_total_gb }} GB)
                </span>
            </div>
            <div class="w-full bg-gray-200 rounded-full h-2.5">
                <div class="h-2.5 rounded-full {{ $latestStatus->disk_status === 'critical' ? 'bg-red-500' : ($latestStatus->disk_status === 'warning' ? 'bg-yellow-500' : 'bg-green-500') }}"
                     style="width: {{ min($latestStatus->disk_usage, 100) }}%"></div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-6 pt-4 border-t">
        <div><p class="text-xs text-gray-500">Load Average (1/5/15)</p><p class="font-medium">{{ $latestStatus->load_average_1 }} / {{ $latestStatus->load_average_5 }} / {{ $latestStatus->load_average_15 }}</p></div>
        <div><p class="text-xs text-gray-500">Active Connections</p><p class="font-medium">{{ $latestStatus->active_connections }}</p></div>
        <div><p class="text-xs text-gray-500">Processes</p><p class="font-medium">{{ $latestStatus->total_processes }}</p></div>
        <div><p class="text-xs text-gray-500">Uptime</p><p class="font-medium">{{ $latestStatus->uptime_formatted }}</p></div>
    </div>
</div>
@endif

<!-- Recent alerts and blocks -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold text-gray-800">Recent Alerts</h3>
            <a href="{{ route('security.alerts.index') }}" class="text-sm text-blue-600 hover:text-blue-700">View All</a>
        </div>
        @if($recentAlerts->isEmpty())
            <p class="text-gray-500 text-sm">No alerts recorded yet.</p>
        @else
            <div class="space-y-3">
                @foreach($recentAlerts as $alert)
                    <a href="{{ route('security.alerts.show', $alert) }}" class="block p-3 rounded-lg border border-gray-100 hover:bg-gray-50 transition">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center space-x-2">
                                <span class="w-2 h-2 rounded-full {{ $alert->severity_badge }}"></span>
                                <span class="text-sm font-medium text-gray-800">{{ $alert->type }}</span>
                            </div>
                            <span class="text-xs text-gray-400">{{ $alert->occurred_at->diffForHumans() }}</span>
                        </div>
                        <p class="text-sm text-gray-600 mt-1">{{ $alert->description }}</p>
                        @if($alert->source_ip)
                            <p class="text-xs text-gray-400 mt-1">IP: {{ $alert->source_ip }}</p>
                        @endif
                    </a>
                @endforeach
            </div>
        @endif
    </div>

    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold text-gray-800">Recently Blocked IPs</h3>
            <a href="{{ route('security.blocks.index') }}" class="text-sm text-blue-600 hover:text-blue-700">Manage</a>
        </div>
        @if($recentBlocks->isEmpty())
            <p class="text-gray-500 text-sm">No IPs currently blocked.</p>
        @else
            <div class="space-y-3">
                @foreach($recentBlocks as $block)
                    <div class="p-3 rounded-lg border border-gray-100">
                        <div class="flex items-center justify-between">
                            <span class="font-mono text-sm text-purple-700">{{ $block->ip }}</span>
                            <form method="POST" action="{{ route('security.blocks.unblock', $block) }}">
                                @csrf
                                <button type="submit" class="text-xs text-blue-600 hover:text-blue-700 font-medium">Unblock</button>
                            </form>
                        </div>
                        <p class="text-sm text-gray-600 mt-1">{{ $block->reason }}</p>
                        <p class="text-xs text-gray-400 mt-1">Blocked {{ $block->blocked_at->diffForHumans() }}</p>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>

<!-- Agent activity log -->
<div class="bg-white rounded-lg shadow p-6">
    <h3 class="text-lg font-semibold text-gray-800 mb-4">Agent Activity</h3>
    @if($agentLogs->isEmpty())
        <p class="text-gray-500 text-sm">No agent activity recorded yet. The agent will report here once running.</p>
    @else
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="text-left text-gray-500 border-b">
                        <th class="py-2 pr-4">Check Type</th>
                        <th class="py-2 pr-4">Status</th>
                        <th class="py-2 pr-4">Alerts</th>
                        <th class="py-2 pr-4">Blocks</th>
                        <th class="py-2 pr-4">Time</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($agentLogs as $log)
                        <tr class="border-b last:border-0">
                            <td class="py-2 pr-4 font-medium">{{ $log->check_type }}</td>
                            <td class="py-2 pr-4">
                                <span class="px-2 py-0.5 rounded-full text-xs {{ $log->status === 'success' ? 'bg-green-100 text-green-700' : ($log->status === 'failed' ? 'bg-red-100 text-red-700' : 'bg-yellow-100 text-yellow-700') }}">{{ $log->status }}</span>
                            </td>
                            <td class="py-2 pr-4">{{ $log->alerts_found }}</td>
                            <td class="py-2 pr-4">{{ $log->ips_blocked }}</td>
                            <td class="py-2 pr-4 text-gray-500">{{ $log->started_at->diffForHumans() }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>

<!-- Agent action audit trail -->
<div class="bg-white rounded-lg shadow p-6 mt-6">
    <div class="flex items-center justify-between mb-4">
        <h3 class="text-lg font-semibold text-gray-800">Agent Action Trail</h3>
        <a href="{{ route('security.actions.index') }}" class="text-sm text-blue-600 hover:text-blue-700">View All</a>
    </div>
    @if($recentActions->isEmpty())
        <p class="text-gray-500 text-sm">No agent actions recorded yet.</p>
    @else
        <div class="space-y-3">
            @foreach($recentActions as $action)
                <div class="flex items-start justify-between p-3 rounded-lg border border-gray-100">
                    <div class="flex items-start space-x-3">
                        <span class="w-2 h-2 rounded-full mt-1.5 {{ $action->severity_badge }}"></span>
                        <div>
                            <p class="text-sm">
                                <span class="font-medium text-gray-800">{{ $action->action }}</span>
                                @if($action->resource)
                                    <span class="text-purple-700 font-mono text-xs ml-2">{{ $action->resource }}</span>
                                @endif
                            </p>
                            @if($action->description)
                                <p class="text-xs text-gray-600 mt-0.5">{{ $action->description }}</p>
                            @endif
                        </div>
                    </div>
                    <span class="text-xs text-gray-400 whitespace-nowrap ml-4">{{ $action->performed_at?->diffForHumans() }}</span>
                </div>
            @endforeach
        </div>
    @endif
</div>
@endsection
