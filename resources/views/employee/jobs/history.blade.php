@extends('layouts.app')
@section('title', 'Job History')

@section('content')
<div class="mb-8">
    <h1 class="text-3xl font-bold text-gray-800">Job History</h1>
    <p class="text-gray-500 mt-1">Your complete job history and earnings summary</p>
</div>

<x-month-filter :availableMonths="$availableMonths" :selectedMonth="$selectedMonth" />

<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
    <div class="bg-white rounded-lg shadow p-6 card-hover">
        <p class="text-sm text-gray-500">Total Jobs</p>
        <p class="text-2xl font-bold text-gray-800">{{ $jobs->count() }}</p>
    </div>
    <div class="bg-white rounded-lg shadow p-6 card-hover">
        <p class="text-sm text-gray-500">Completed Jobs</p>
        <p class="text-2xl font-bold text-green-600">{{ $completedJobs->count() }}</p>
    </div>
    <div class="bg-white rounded-lg shadow p-6 card-hover">
        <p class="text-sm text-gray-500">Total Earnings</p>
        <p class="text-2xl font-bold text-green-600">KSh {{ number_format($totalEarnings, 2) }}</p>
    </div>
</div>

<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
    <div class="bg-white rounded-lg shadow p-6 card-hover">
        <p class="text-sm text-gray-500">Total Expenses</p>
        <p class="text-2xl font-bold text-red-600">KSh {{ number_format($totalExpenses, 2) }}</p>
    </div>
    <div class="bg-white rounded-lg shadow p-6 card-hover">
        <p class="text-sm text-gray-500">Net Profit Contributed</p>
        <p class="text-2xl font-bold {{ $totalProfit >= 0 ? 'text-green-600' : 'text-red-600' }}">KSh {{ number_format($totalProfit, 2) }}</p>
    </div>
    <div class="bg-white rounded-lg shadow p-6 card-hover">
        <p class="text-sm text-gray-500">Average Profit / Job</p>
        <p class="text-2xl font-bold text-gray-800">KSh {{ $completedJobs->count() > 0 ? number_format($totalProfit / $completedJobs->count(), 2) : '0.00' }}</p>
    </div>
</div>

<div class="bg-white rounded-lg shadow overflow-hidden">
    <div class="px-6 py-4 border-b border-gray-200">
        <h3 class="text-lg font-semibold text-gray-800">All Jobs</h3>
    </div>
    <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Project Name</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Created By</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Expense</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Cost</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Profit</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-200">
            @forelse($jobs as $job)
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4 font-medium text-gray-800">{{ $job->project_name }}</td>
                    <td class="px-6 py-4 text-gray-600">{{ $job->creator->name }}</td>
                    <td class="px-6 py-4 text-gray-600">{{ $job->created_at->format('M d, Y') }}</td>
                    <td class="px-6 py-4 text-red-600">KSh {{ number_format($job->expense, 2) }}</td>
                    <td class="px-6 py-4 text-blue-600">KSh {{ number_format($job->cost, 2) }}</td>
                    <td class="px-6 py-4 font-semibold {{ $job->profit >= 0 ? 'text-green-600' : 'text-red-600' }}">KSh {{ number_format($job->profit, 2) }}</td>
                    <td class="px-6 py-4">
                        <span class="px-2 py-1 text-xs rounded-full font-medium
                            {{ $job->status === 'completed' ? 'bg-green-100 text-green-800' : '' }}
                            {{ $job->status === 'in_progress' ? 'bg-yellow-100 text-yellow-800' : '' }}
                            {{ $job->status === 'cancelled' ? 'bg-red-100 text-red-800' : '' }}">
                            {{ $job->status_label }}
                        </span>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="px-6 py-8 text-center text-gray-500">No job history found.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
