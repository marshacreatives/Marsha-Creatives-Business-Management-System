@extends('layouts.app')

@section('title', 'Security Dashboard')

@section('content')
<div class="mb-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-800">Hosting Security</h1>
            <p class="text-gray-500 mt-1">Server monitoring, threat detection & IP blocking</p>
        </div>
        <a href="{{ route('security.dashboard') }}" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-md hover:bg-blue-700 transition">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
            Security Overview
        </a>
    </div>

    <nav class="mt-4 bg-white rounded-lg shadow-sm p-1 flex space-x-1 overflow-x-auto">
        <a href="{{ route('security.dashboard') }}" class="px-4 py-2 rounded-md text-sm font-medium whitespace-nowrap {{ request()->routeIs('security.dashboard') ? 'bg-blue-600 text-white' : 'text-gray-600 hover:bg-gray-100' }}">Overview</a>
        <a href="{{ route('security.alerts.index') }}" class="px-4 py-2 rounded-md text-sm font-medium whitespace-nowrap {{ request()->routeIs('security.alerts.*') ? 'bg-blue-600 text-white' : 'text-gray-600 hover:bg-gray-100' }}">Alerts</a>
        <a href="{{ route('security.blocks.index') }}" class="px-4 py-2 rounded-md text-sm font-medium whitespace-nowrap {{ request()->routeIs('security.blocks.*') ? 'bg-blue-600 text-white' : 'text-gray-600 hover:bg-gray-100' }}">Blocked IPs</a>
        <a href="{{ route('security.server-status') }}" class="px-4 py-2 rounded-md text-sm font-medium whitespace-nowrap {{ request()->routeIs('security.server-status') ? 'bg-blue-600 text-white' : 'text-gray-600 hover:bg-gray-100' }}">Server Status</a>
        <a href="{{ route('security.settings') }}" class="px-4 py-2 rounded-md text-sm font-medium whitespace-nowrap {{ request()->routeIs('security.settings') ? 'bg-blue-600 text-white' : 'text-gray-600 hover:bg-gray-100' }}">Settings</a>
    </nav>
</div>

@yield('security-content')
@endsection
