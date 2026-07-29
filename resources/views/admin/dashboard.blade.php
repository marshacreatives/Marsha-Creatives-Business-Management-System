@extends('layouts.app')
@section('title', 'Admin Dashboard')

@section('content')
<div class="mb-8">
    <h1 class="text-3xl font-bold text-gray-800">Admin Dashboard</h1>
    <p class="text-gray-500 mt-1">Welcome back, {{ auth()->user()->name }}</p>
</div>

<x-month-filter :availableMonths="$availableMonths" :selectedMonth="$selectedMonth" />

<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    <div class="bg-white rounded-lg shadow p-6 card-hover">
        <div class="flex items-center">
            <div class="p-3 bg-green-100 rounded-full"><svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg></div>
            <div class="ml-4">
                <p class="text-sm text-gray-500">Account Balance</p>
                <p class="text-2xl font-bold text-gray-800">KSh {{ number_format($balance, 2) }}</p>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow p-6 card-hover">
        <div class="flex items-center">
            <div class="p-3 bg-blue-100 rounded-full"><svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg></div>
            <div class="ml-4">
                <p class="text-sm text-gray-500">Total Jobs</p>
                <p class="text-2xl font-bold text-gray-800">{{ $totalJobs }}</p>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow p-6 card-hover">
        <div class="flex items-center">
            <div class="p-3 bg-purple-100 rounded-full"><svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg></div>
            <div class="ml-4">
                <p class="text-sm text-gray-500">Employees</p>
                <p class="text-2xl font-bold text-gray-800">{{ $totalEmployees }}</p>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow p-6 card-hover">
        <div class="flex items-center">
            <div class="p-3 {{ $totalProfit >= 0 ? 'bg-green-100' : 'bg-red-100' }} rounded-full">
                <svg class="w-6 h-6 {{ $totalProfit >= 0 ? 'text-green-600' : 'text-red-600' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
            </div>
            <div class="ml-4">
                <p class="text-sm text-gray-500">Total Profit</p>
                <p class="text-2xl font-bold {{ $totalProfit >= 0 ? 'text-green-600' : 'text-red-600' }}">KSh {{ number_format($totalProfit, 2) }}</p>
            </div>
        </div>
    </div>
</div>

@if($pendingFundRequests->isNotEmpty())
<div class="bg-red-50 border border-red-200 rounded-lg shadow p-6 mb-8">
    <div class="flex items-center mb-4">
        <div class="p-2 bg-red-100 rounded-full mr-3">
            <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/></svg>
        </div>
        <h3 class="text-lg font-semibold text-red-800">Pending Fund Requests ({{ $pendingFundRequests->count() }})</h3>
    </div>
    <div class="space-y-3">
        @foreach($pendingFundRequests as $request)
            <div class="bg-white rounded-lg p-4 flex items-center justify-between">
                <div>
                    <p class="font-medium text-gray-800">{{ $request->user->name }} is requesting additional funds</p>
                    <p class="text-sm text-gray-500">Amount needed: <span class="font-semibold text-red-600">KSh {{ number_format($request->amount, 2) }}</span></p>
                    @if($request->message)
                        <p class="text-xs text-gray-400 mt-1">{{ $request->message }}</p>
                    @endif
                    <p class="text-xs text-gray-400">{{ $request->created_at->diffForHumans() }}</p>
                </div>
                <div class="flex space-x-2">
                    <form method="POST" action="{{ route('admin.fund-requests.approve', $request) }}">
                        @csrf
                        <button type="submit" class="px-4 py-2 bg-green-600 text-white text-sm rounded-md hover:bg-green-700 transition font-medium">Approve</button>
                    </form>
                    <form method="POST" action="{{ route('admin.fund-requests.dismiss', $request) }}">
                        @csrf
                        <button type="submit" class="px-4 py-2 bg-gray-400 text-white text-sm rounded-md hover:bg-gray-500 transition font-medium">Dismiss</button>
                    </form>
                </div>
            </div>
        @endforeach
    </div>
</div>
@endif

<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
    <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">Job Status Breakdown</h3>
        <div class="space-y-3">
            <div class="flex justify-between items-center">
                <span class="text-gray-600">Completed</span>
                <span class="bg-green-100 text-green-800 px-3 py-1 rounded-full text-sm font-medium">{{ $completedJobs }}</span>
            </div>
            <div class="flex justify-between items-center">
                <span class="text-gray-600">In Progress</span>
                <span class="bg-yellow-100 text-yellow-800 px-3 py-1 rounded-full text-sm font-medium">{{ $inProgressJobs }}</span>
            </div>
            <div class="flex justify-between items-center">
                <span class="text-gray-600">Cancelled</span>
                <span class="bg-red-100 text-red-800 px-3 py-1 rounded-full text-sm font-medium">{{ $cancelledJobs }}</span>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">Revenue Overview</h3>
        <div class="space-y-3">
            <div class="flex justify-between">
                <span class="text-gray-600">Total Revenue</span>
                <span class="font-semibold text-blue-600">KSh {{ number_format($totalRevenue, 2) }}</span>
            </div>
            <div class="flex justify-between">
                <span class="text-gray-600">Total Expenses</span>
                <span class="font-semibold text-red-600">KSh {{ number_format($totalExpenses, 2) }}</span>
            </div>
            <hr>
            <div class="flex justify-between">
                <span class="text-gray-600 font-medium">Net Profit</span>
                <span class="font-bold {{ $totalProfit >= 0 ? 'text-green-600' : 'text-red-600' }}">KSh {{ number_format($totalProfit, 2) }}</span>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">Quick Actions</h3>
        <div class="space-y-3">
            <a href="{{ route('admin.jobs.create') }}" class="block w-full text-center bg-blue-600 text-white py-2 rounded-md hover:bg-blue-700 transition">Create New Job</a>
            <a href="{{ route('admin.users.create') }}" class="block w-full text-center bg-green-600 text-white py-2 rounded-md hover:bg-green-700 transition">Register Employee</a>
            <a href="{{ route('admin.balance') }}" class="block w-full text-center bg-purple-600 text-white py-2 rounded-md hover:bg-purple-700 transition">Update Balance</a>
            <a href="{{ route('admin.financials') }}" class="block w-full text-center bg-gray-600 text-white py-2 rounded-md hover:bg-gray-700 transition">View Financials</a>
        </div>
    </div>
</div>

<div class="bg-white rounded-lg shadow p-6">
    <h3 class="text-lg font-semibold text-gray-800 mb-4">Recent Activity</h3>
    @if($recentActivities->isEmpty())
        <p class="text-gray-500">No recent activities.</p>
    @else
        <div class="space-y-3">
            @foreach($recentActivities as $activity)
                <div class="flex items-start space-x-3 pb-3 border-b last:border-0">
                    <div class="w-2 h-2 mt-2 rounded-full
                        {{ $activity->type === 'job_created' ? 'bg-blue-500' : '' }}
                        {{ $activity->type === 'job_updated' ? 'bg-yellow-500' : '' }}
                        {{ $activity->type === 'job_status_updated' ? 'bg-green-500' : '' }}
                        {{ $activity->type === 'job_deleted' ? 'bg-red-500' : '' }}
                        {{ $activity->type === 'balance_updated' ? 'bg-purple-500' : '' }}
                        {{ $activity->type === 'user_registered' ? 'bg-teal-500' : '' }}
                        {{ $activity->type === 'user_updated' ? 'bg-orange-500' : '' }}
                        {{ $activity->type === 'fund_requested' ? 'bg-red-500' : '' }}
                        {{ $activity->type === 'fund_request_approved' ? 'bg-green-500' : '' }}
                        {{ $activity->type === 'fund_request_dismissed' ? 'bg-gray-500' : '' }}">
                    </div>
                    <div class="flex-1">
                        <p class="text-sm text-gray-800">{{ $activity->description }}</p>
                        <p class="text-xs text-gray-400">{{ $activity->user->name }} &middot; {{ $activity->created_at->diffForHumans() }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
@endsection
