@extends('layouts.app')
@section('title', 'Employee Dashboard')

@section('content')
<div class="mb-8">
    <h1 class="text-3xl font-bold text-gray-800">My Dashboard</h1>
    <p class="text-gray-500 mt-1">Welcome back, {{ auth()->user()->name }}</p>
</div>

<x-month-filter :availableMonths="$availableMonths" :selectedMonth="$selectedMonth" />

<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-6 mb-8">
    <div class="bg-white rounded-lg shadow p-6 card-hover">
        <div class="flex items-center">
            <div class="p-3 bg-green-100 rounded-full">
                <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <div class="ml-4">
                <p class="text-sm text-gray-500">Company Balance</p>
                <p class="text-xl font-bold text-green-600">KSh {{ number_format($balance, 2) }}</p>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow p-6 card-hover">
        <div class="flex items-center">
            <div class="p-3 bg-blue-100 rounded-full">
                <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
            </div>
            <div class="ml-4">
                <p class="text-sm text-gray-500">Total Jobs</p>
                <p class="text-2xl font-bold text-gray-800">{{ $stats['total'] }}</p>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow p-6 card-hover">
        <div class="flex items-center">
            <div class="p-3 bg-yellow-100 rounded-full">
                <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <div class="ml-4">
                <p class="text-sm text-gray-500">In Progress</p>
                <p class="text-2xl font-bold text-gray-800">{{ $stats['in_progress'] }}</p>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow p-6 card-hover">
        <div class="flex items-center">
            <div class="p-3 bg-green-100 rounded-full">
                <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <div class="ml-4">
                <p class="text-sm text-gray-500">Completed</p>
                <p class="text-2xl font-bold text-gray-800">{{ $stats['completed'] }}</p>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow p-6 card-hover">
        <div class="flex items-center">
            <div class="p-3 bg-purple-100 rounded-full">
                <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <div class="ml-4">
                <p class="text-sm text-gray-500">Total Earnings</p>
                <p class="text-xl font-bold text-green-600">KSh {{ number_format($totalEarnings, 2) }}</p>
            </div>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
    <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">My Earnings Summary</h3>
        <div class="space-y-3">
            <div class="flex justify-between">
                <span class="text-gray-600">Total Revenue (Completed)</span>
                <span class="font-semibold text-blue-600">KSh {{ number_format($totalEarnings, 2) }}</span>
            </div>
            <div class="flex justify-between">
                <span class="text-gray-600">Total Expenses (Completed)</span>
                <span class="font-semibold text-red-600">KSh {{ number_format($totalExpenses, 2) }}</span>
            </div>
            <hr>
            <div class="flex justify-between">
                <span class="text-gray-600 font-medium">Net Profit Contributed</span>
                <span class="font-bold {{ ($totalEarnings - $totalExpenses) >= 0 ? 'text-green-600' : 'text-red-600' }}">KSh {{ number_format($totalEarnings - $totalExpenses, 2) }}</span>
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
                            {{ $activity->type === 'job_status_updated' ? 'bg-green-500' : '' }}"></div>
                        <div class="flex-1">
                            <p class="text-sm text-gray-800">{{ $activity->description }}</p>
                            <p class="text-xs text-gray-400">{{ $activity->created_at->diffForHumans() }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>

<div class="bg-white rounded-lg shadow p-6">
    <div class="flex justify-between items-center mb-4">
        <h3 class="text-lg font-semibold text-gray-800">My Assigned Jobs</h3>
        <a href="{{ route('employee.jobs.create') }}" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 transition text-sm font-medium">+ Log Job</a>
    </div>
    @if($assignedJobs->isEmpty())
        <p class="text-gray-500">No jobs assigned to you yet.</p>
    @else
        <div class="space-y-3">
            @foreach($assignedJobs->take(5) as $job)
                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                    <div>
                        <p class="font-medium text-gray-800">{{ $job->project_name }}</p>
                        <p class="text-sm text-gray-500">Created by {{ $job->creator->name }} &middot; {{ $job->created_at->diffForHumans() }}</p>
                    </div>
                    <div class="flex items-center space-x-3">
                        <span class="px-2 py-1 text-xs rounded-full font-medium
                            {{ $job->status === 'completed' ? 'bg-green-100 text-green-800' : '' }}
                            {{ $job->status === 'in_progress' ? 'bg-yellow-100 text-yellow-800' : '' }}
                            {{ $job->status === 'cancelled' ? 'bg-red-100 text-red-800' : '' }}">
                            {{ $job->status_label }}
                        </span>
                        <form method="POST" action="{{ route('employee.jobs.update-status', $job) }}">
                            @csrf
                            <select name="status" onchange="this.form.submit()"
                                class="text-xs border border-gray-300 rounded px-2 py-1 focus:outline-none focus:ring-1 focus:ring-blue-500">
                                <option value="in_progress" {{ $job->status === 'in_progress' ? 'selected' : '' }}>In Progress</option>
                                <option value="completed" {{ $job->status === 'completed' ? 'selected' : '' }}>Completed</option>
                                <option value="cancelled" {{ $job->status === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                            </select>
                        </form>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
@endsection
