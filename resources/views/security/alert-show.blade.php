@extends('security.layout')
@section('title', 'Alert Detail')

@section('security-content')
<div class="flex items-center justify-between mb-6">
    <div>
        <a href="{{ route('security.alerts.index') }}" class="text-sm text-blue-600 hover:text-blue-700">&larr; Back to Alerts</a>
        <h2 class="text-xl font-semibold text-gray-800 mt-2">Alert #{{ $alert->id }}</h2>
    </div>
    <div class="flex space-x-2">
        @if($alert->is_resolved)
            <form method="POST" action="{{ route('security.alerts.reopen', $alert) }}">
                @csrf
                <button type="submit" class="px-4 py-2 bg-yellow-600 text-white text-sm rounded-md hover:bg-yellow-700">Reopen</button>
            </form>
        @else
            <form method="POST" action="{{ route('security.alerts.resolve', $alert) }}">
                @csrf
                <button type="submit" class="px-4 py-2 bg-green-600 text-white text-sm rounded-md hover:bg-green-700">Mark Resolved</button>
            </form>
        @endif
        <form method="POST" action="{{ route('security.alerts.destroy', $alert) }}" onsubmit="return confirm('Delete this alert?')">
            @csrf
            @method('DELETE')
            <button type="submit" class="px-4 py-2 bg-red-600 text-white text-sm rounded-md hover:bg-red-700">Delete</button>
        </form>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-sm font-semibold text-gray-500 uppercase mb-4">Alert Details</h3>
        <dl class="space-y-3">
            <div class="flex justify-between">
                <dt class="text-sm text-gray-500">Severity</dt>
                <dd><span class="px-2 py-1 rounded-full text-xs text-white {{ $alert->severity_badge }}">{{ ucfirst($alert->severity) }}</span></dd>
            </div>
            <div class="flex justify-between">
                <dt class="text-sm text-gray-500">Type</dt>
                <dd class="text-sm font-medium">{{ $alert->type }}</dd>
            </div>
            <div class="flex justify-between">
                <dt class="text-sm text-gray-500">Source IP</dt>
                <dd class="text-sm font-mono text-purple-700">{{ $alert->source_ip ?? '-' }}</dd>
            </div>
            <div class="flex justify-between">
                <dt class="text-sm text-gray-500">Source Port</dt>
                <dd class="text-sm">{{ $alert->source_port ?? '-' }}</dd>
            </div>
            <div class="flex justify-between">
                <dt class="text-sm text-gray-500">Dest Port</dt>
                <dd class="text-sm">{{ $alert->destination_port ?? '-' }}</dd>
            </div>
            <div class="flex justify-between">
                <dt class="text-sm text-gray-500">Occurred</dt>
                <dd class="text-sm">{{ $alert->occurred_at->format('Y-m-d H:i:s') }}</dd>
            </div>
            <div class="flex justify-between">
                <dt class="text-sm text-gray-500">Status</dt>
                <dd class="text-sm {{ $alert->is_resolved ? 'text-green-600' : 'text-yellow-600' }}">{{ $alert->is_resolved ? 'Resolved' : 'Open' }}</dd>
            </div>
        </dl>
    </div>

    <div class="bg-white rounded-lg shadow p-6 lg:col-span-2">
        <h3 class="text-sm font-semibold text-gray-500 uppercase mb-4">Description</h3>
        <p class="text-gray-800 mb-4">{{ $alert->description }}</p>

        <h3 class="text-sm font-semibold text-gray-500 uppercase mb-2">Action Taken</h3>
        <p class="text-gray-700 {{ empty($alert->action_taken) ? 'text-gray-400' : '' }}">{{ $alert->action_taken ?? 'No action recorded' }}</p>

        @if($alert->raw_log)
            <h3 class="text-sm font-semibold text-gray-500 uppercase mt-6 mb-2">Raw Log</h3>
            <pre class="bg-gray-900 text-gray-100 text-xs rounded-lg p-4 overflow-x-auto whitespace-pre-wrap max-h-96">{{ $alert->raw_log }}</pre>
        @endif
    </div>
</div>
@endsection
