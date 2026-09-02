@extends('security.layout')
@section('title', 'Server Status')

@section('security-content')
<div class="bg-white rounded-lg shadow p-6 mb-6">
    <div class="flex items-center justify-between">
        <h2 class="text-lg font-semibold text-gray-800">Server Metrics</h2>
        <form method="GET" action="{{ route('security.server-status') }}" class="flex items-center space-x-2">
            <select name="server" onchange="this.form.submit()" class="border border-gray-300 rounded-md px-3 py-2 text-sm">
                @foreach($servers as $server)
                    <option value="{{ $server }}" {{ $serverName === $server ? 'selected' : '' }}>{{ $server }}</option>
                @endforeach
            </select>
        </form>
    </div>

    @if($current)
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-6">
            <div class="p-4 bg-gray-50 rounded-lg">
                <p class="text-xs text-gray-500 uppercase">CPU Usage</p>
                <p class="text-xl font-bold {{ $current->cpu_status === 'critical' ? 'text-red-600' : ($current->cpu_status === 'warning' ? 'text-yellow-600' : 'text-green-600') }}">{{ $current->cpu_usage }}%</p>
            </div>
            <div class="p-4 bg-gray-50 rounded-lg">
                <p class="text-xs text-gray-500 uppercase">RAM Usage</p>
                <p class="text-xl font-bold {{ $current->ram_status === 'critical' ? 'text-red-600' : ($current->ram_status === 'warning' ? 'text-yellow-600' : 'text-green-600') }}">{{ $current->ram_usage }}%</p>
            </div>
            <div class="p-4 bg-gray-50 rounded-lg">
                <p class="text-xs text-gray-500 uppercase">Disk Usage</p>
                <p class="text-xl font-bold {{ $current->disk_status === 'critical' ? 'text-red-600' : ($current->disk_status === 'warning' ? 'text-yellow-600' : 'text-green-600') }}">{{ $current->disk_usage }}%</p>
            </div>
            <div class="p-4 bg-gray-50 rounded-lg">
                <p class="text-xs text-gray-500 uppercase">Uptime</p>
                <p class="text-xl font-bold text-gray-800">{{ $current->uptime_formatted }}</p>
            </div>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-4">
            <div class="p-4 bg-gray-50 rounded-lg">
                <p class="text-xs text-gray-500 uppercase">Load (1/5/15)</p>
                <p class="text-sm font-medium mt-1">{{ $current->load_average_1 }} / {{ $current->load_average_5 }} / {{ $current->load_average_15 }}</p>
            </div>
            <div class="p-4 bg-gray-50 rounded-lg">
                <p class="text-xs text-gray-500 uppercase">Connections</p>
                <p class="text-sm font-medium mt-1">{{ $current->active_connections }}</p>
            </div>
            <div class="p-4 bg-gray-50 rounded-lg">
                <p class="text-xs text-gray-500 uppercase">Processes</p>
                <p class="text-sm font-medium mt-1">{{ $current->total_processes }}</p>
            </div>
            <div class="p-4 bg-gray-50 rounded-lg">
                <p class="text-xs text-gray-500 uppercase">Last Check</p>
                <p class="text-sm font-medium mt-1">{{ $current->checked_at->diffForHumans() }}</p>
            </div>
        </div>

        @if(!empty($current->services_status))
            <div class="mt-6">
                <p class="text-sm font-semibold text-gray-700 mb-2">Running Services</p>
                <div class="flex flex-wrap gap-2">
                    @foreach($current->services_status as $service => $status)
                        <span class="px-3 py-1 rounded-full text-xs font-medium {{ $status === 'running' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">{{ $service }}: {{ $status }}</span>
                    @endforeach
                </div>
            </div>
        @endif

        <div class="mt-8">
            <h3 class="text-sm font-semibold text-gray-700 mb-4">CPU Usage History (last 48 checks)</h3>
            <div class="flex items-end space-x-1 h-32">
                @foreach($history as $entry)
                    <div class="flex-1 bg-blue-500 rounded-t {{ $entry->cpu_usage >= 90 ? 'bg-red-500' : ($entry->cpu_usage >= 70 ? 'bg-yellow-500' : 'bg-green-500') }}"
                         title="{{ $entry->checked_at->format('Y-m-d H:i') }} - CPU {{ $entry->cpu_usage }}%"
                         style="height: {{ max($entry->cpu_usage, 2) }}%"></div>
                @endforeach
            </div>
        </div>
    @else
        <p class="text-gray-500 mt-4">No server data available yet. The agent will report here once connected.</p>
    @endif
</div>
@endsection
