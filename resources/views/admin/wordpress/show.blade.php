@extends('layouts.app')
@section('title', 'WordPress Setup')
@section('content')
<div class="mb-6">
    <a href="{{ route('admin.wordpress.index') }}" class="text-blue-600 hover:text-blue-800 text-sm font-medium">&larr; Back to WordPress Setup</a>
</div>

@if(in_array($site->status, ['pending', 'in_progress']))
<script>
    setTimeout(function () { window.location.reload(); }, 5000);
</script>
@endif

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
    <div class="bg-white rounded-lg shadow p-6 lg:col-span-2">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">Setup Progress</h1>
                <p class="text-gray-500 text-sm mt-1">{{ $site->site_url }}</p>
            </div>
            <span class="px-3 py-1 text-sm rounded-full font-medium {{ $site->status_badge_class }}">{{ $site->status_label }}</span>
        </div>

        @if($site->status === 'failed')
            <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-6">
                <p class="text-sm text-red-800 font-medium">The setup could not be completed.</p>
                @if($site->error_message)
                    <p class="text-xs text-red-600 mt-1">{{ $site->error_message }}</p>
                @endif
            </div>
        @endif

        @if($site->steps_log)
            <ol class="space-y-3">
                @foreach($site->steps_log as $step)
                    <li class="flex items-start space-x-3">
                        @if($step['status'] === 'completed')
                            <span class="mt-0.5 w-6 h-6 rounded-full bg-green-100 text-green-600 flex items-center justify-center">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            </span>
                        @elseif($step['status'] === 'failed')
                            <span class="mt-0.5 w-6 h-6 rounded-full bg-red-100 text-red-600 flex items-center justify-center">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </span>
                        @elseif($step['status'] === 'in_progress')
                            <span class="mt-0.5 w-6 h-6 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center">
                                <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                            </span>
                        @else
                            <span class="mt-0.5 w-6 h-6 rounded-full bg-gray-100 text-gray-500 flex items-center justify-center">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3"/><circle cx="12" cy="12" r="9"/></svg>
                            </span>
                        @endif
                        <div class="flex-1">
                            <p class="text-sm font-medium text-gray-800">{{ $step['step'] }}</p>
                            @if(!empty($step['detail']))
                                <p class="text-xs text-gray-500">{{ $step['detail'] }}</p>
                            @endif
                            @if(!empty($step['at']))
                                <p class="text-xs text-gray-400">{{ \Illuminate\Support\Carbon::parse($step['at'])->format('g:i:s A') }}</p>
                            @endif
                        </div>
                    </li>
                @endforeach
            </ol>
        @else
            <p class="text-gray-500">Waiting for the queued job to start...</p>
        @endif
    </div>

    <div class="space-y-6">
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Summary</h3>
            <div class="space-y-3">
                <div>
                    <p class="text-xs text-gray-500 uppercase">Active Theme</p>
                    <p class="font-medium text-gray-800">{{ $site->active_theme ?? 'Not set yet' }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-500 uppercase">Installed Plugins</p>
                    @if($site->installed_plugins)
                        <div class="flex flex-wrap gap-2 mt-1">
                            @foreach($site->installed_plugins as $plugin)
                                <span class="px-2 py-1 text-xs rounded-full bg-green-100 text-green-800">{{ $plugin }}</span>
                            @endforeach
                        </div>
                    @else
                        <p class="text-gray-500 text-sm">-</p>
                    @endif
                </div>
                <div>
                    <p class="text-xs text-gray-500 uppercase">Started By</p>
                    <p class="font-medium text-gray-800">{{ $site->creator?->name ?? '-' }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-2">What gets installed</h3>
            <ul class="text-sm text-gray-600 space-y-2">
                <li>&bull; Hello Elementor theme (activated)</li>
                <li>&bull; Google Site Kit plugin (activated)</li>
                <li>&bull; Every <code class="text-gray-800">.zip</code> in the <code class="text-gray-800">plugins/</code> folder (activated)</li>
            </ul>
        </div>
    </div>
</div>
@endsection