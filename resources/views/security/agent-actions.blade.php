@extends('security.layout')
@section('title', 'Agent Actions')

@section('security-content')
<div class="bg-white rounded-lg shadow p-6 mb-6">
    <div class="flex items-center justify-between mb-4">
        <h2 class="text-lg font-semibold text-gray-800">Agent Action Audit Trail</h2>
        <span class="text-sm text-gray-500">{{ $actions->total() }} total</span>
    </div>

    <form method="GET" action="{{ route('security.actions.index') }}" class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-5 gap-3">
        <select name="action" class="border border-gray-300 rounded-md px-3 py-2 text-sm">
            <option value="all">All Actions</option>
            @foreach($actionTypes as $actionType)
                <option value="{{ $actionType }}" {{ request('action') === $actionType ? 'selected' : '' }}>{{ $actionType }}</option>
            @endforeach
        </select>

        <select name="severity" class="border border-gray-300 rounded-md px-3 py-2 text-sm">
            <option value="all">All Severities</option>
            @foreach($severities as $severity)
                <option value="{{ $severity }}" {{ request('severity') === $severity ? 'selected' : '' }}>{{ ucfirst($severity) }}</option>
            @endforeach
        </select>

        <input type="text" name="resource" value="{{ request('resource') }}" placeholder="Resource (IP/PID/file)" class="border border-gray-300 rounded-md px-3 py-2 text-sm">
        <input type="date" name="date_from" value="{{ request('date_from') }}" class="border border-gray-300 rounded-md px-3 py-2 text-sm">
        <input type="date" name="date_to" value="{{ request('date_to') }}" class="border border-gray-300 rounded-md px-3 py-2 text-sm">

        <div class="flex space-x-2 col-span-1 md:col-span-3 lg:col-span-1">
            <button type="submit" class="flex-1 bg-blue-600 text-white px-4 py-2 rounded-md text-sm font-medium hover:bg-blue-700">Filter</button>
            <a href="{{ route('security.actions.index') }}" class="px-4 py-2 rounded-md text-sm font-medium bg-gray-200 text-gray-700 hover:bg-gray-300">Reset</a>
        </div>
    </form>
</div>

@if($actions->isEmpty())
    <div class="bg-white rounded-lg shadow p-8 text-center">
        <p class="text-gray-500">No agent actions recorded yet. Actions are posted here as the agent works.</p>
    </div>
@else
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50">
                    <tr class="text-left text-gray-500">
                        <th class="px-6 py-3 font-medium">Severity</th>
                        <th class="px-6 py-3 font-medium">Action</th>
                        <th class="px-6 py-3 font-medium">Resource</th>
                        <th class="px-6 py-3 font-medium">Description</th>
                        <th class="px-6 py-3 font-medium">Server</th>
                        <th class="px-6 py-3 font-medium">Time</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($actions as $action)
                        <tr class="border-b last:border-0 hover:bg-gray-50">
                            <td class="px-6 py-3">
                                <span class="px-2 py-1 rounded-full text-xs text-white {{ $action->severity_badge }}">{{ ucfirst($action->severity) }}</span>
                            </td>
                            <td class="px-6 py-3 font-medium">{{ $action->action }}</td>
                            <td class="px-6 py-3 font-mono text-purple-700 break-all">{{ $action->resource ?? '-' }}</td>
                            <td class="px-6 py-3 max-w-md">
                                <p class="truncate" title="{{ $action->description ?? '' }}">{{ $action->description ?? '-' }}</p>
                                @if($action->details)
                                    <p class="text-xs text-gray-400 truncate" title="{{ $action->details }}">{{ $action->details }}</p>
                                @endif
                            </td>
                            <td class="px-6 py-3">{{ $action->server_name ?? '-' }}</td>
                            <td class="px-6 py-3 text-gray-500 whitespace-nowrap">{{ $action->performed_at?->format('Y-m-d H:i:s') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-6">
        {{ $actions->links() }}
    </div>
@endif
@endsection
