@extends('security.layout')
@section('title', 'Security Alerts')

@section('security-content')
<div class="bg-white rounded-lg shadow p-6 mb-6">
    <div class="flex items-center justify-between mb-4">
        <h2 class="text-lg font-semibold text-gray-800">Security Alerts</h2>
        <span class="text-sm text-gray-500">{{ $alerts->total() }} total</span>
    </div>

    <form method="GET" action="{{ route('security.alerts.index') }}" class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-6 gap-3">
        <select name="type" class="border border-gray-300 rounded-md px-3 py-2 text-sm">
            <option value="all">All Types</option>
            @foreach($types as $type)
                <option value="{{ $type }}" {{ request('type') === $type ? 'selected' : '' }}>{{ $type }}</option>
            @endforeach
        </select>

        <select name="severity" class="border border-gray-300 rounded-md px-3 py-2 text-sm">
            <option value="all">All Severities</option>
            @foreach($severities as $severity)
                <option value="{{ $severity }}" {{ request('severity') === $severity ? 'selected' : '' }}>{{ ucfirst($severity) }}</option>
            @endforeach
        </select>

        <input type="text" name="ip" value="{{ request('ip') }}" placeholder="Source IP" class="border border-gray-300 rounded-md px-3 py-2 text-sm">
        <input type="date" name="date_from" value="{{ request('date_from') }}" class="border border-gray-300 rounded-md px-3 py-2 text-sm">
        <input type="date" name="date_to" value="{{ request('date_to') }}" class="border border-gray-300 rounded-md px-3 py-2 text-sm">

        <div class="flex space-x-2">
            <button type="submit" class="flex-1 bg-blue-600 text-white px-4 py-2 rounded-md text-sm font-medium hover:bg-blue-700">Filter</button>
            <a href="{{ route('security.alerts.index') }}" class="px-4 py-2 rounded-md text-sm font-medium bg-gray-200 text-gray-700 hover:bg-gray-300">Reset</a>
        </div>
    </form>
</div>

@if($alerts->isEmpty())
    <div class="bg-white rounded-lg shadow p-8 text-center">
        <p class="text-gray-500">No alerts match your filters.</p>
    </div>
@else
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50">
                    <tr class="text-left text-gray-500">
                        <th class="px-6 py-3 font-medium">Severity</th>
                        <th class="px-6 py-3 font-medium">Type</th>
                        <th class="px-6 py-3 font-medium">Source IP</th>
                        <th class="px-6 py-3 font-medium">Description</th>
                        <th class="px-6 py-3 font-medium">Action</th>
                        <th class="px-6 py-3 font-medium">Time</th>
                        <th class="px-6 py-3 font-medium">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($alerts as $alert)
                        <tr class="border-b last:border-0 hover:bg-gray-50">
                            <td class="px-6 py-3">
                                <span class="px-2 py-1 rounded-full text-xs text-white {{ $alert->severity_badge }}">{{ ucfirst($alert->severity) }}</span>
                            </td>
                            <td class="px-6 py-3 font-medium">{{ $alert->type }}</td>
                            <td class="px-6 py-3 font-mono text-purple-700">{{ $alert->source_ip ?? '-' }}</td>
                            <td class="px-6 py-3 max-w-xs truncate"><a href="{{ route('security.alerts.show', $alert) }}" class="text-blue-600 hover:text-blue-700">{{ $alert->description }}</a></td>
                            <td class="px-6 py-3">{{ $alert->action_taken ?? '-' }}</td>
                            <td class="px-6 py-3 text-gray-500 whitespace-nowrap">{{ $alert->occurred_at->format('Y-m-d H:i') }}</td>
                            <td class="px-6 py-3">
                                @if($alert->is_resolved)
                                    <span class="text-green-600 text-xs font-medium">Resolved</span>
                                @else
                                    <span class="text-yellow-600 text-xs font-medium">Open</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-6">
        {{ $alerts->links() }}
    </div>
@endif
@endsection
