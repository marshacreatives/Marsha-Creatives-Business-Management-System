@extends('layouts.app')
@section('title', 'My Jobs')

@section('content')
<div class="flex justify-between items-center mb-8">
    <div>
        <h1 class="text-3xl font-bold text-gray-800">My Jobs</h1>
        <p class="text-gray-500 mt-1">All your assigned and logged jobs</p>
    </div>
    <a href="{{ route('employee.jobs.create') }}" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 transition font-medium">+ Log Job</a>
</div>

<x-month-filter :availableMonths="$availableMonths" :selectedMonth="$selectedMonth" :statuses="$statuses" />

<div class="bg-white rounded-lg shadow overflow-hidden">
    <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Project Name</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Expense</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Cost</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Profit</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Update</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-200">
            @forelse($jobs as $job)
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4">
                        <span class="font-medium text-gray-800">{{ $job->project_name }}</span>
                        <p class="text-xs text-gray-400">by {{ $job->creator->name }} &middot; {{ $job->created_at->diffForHumans() }}</p>
                    </td>
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
                    <td class="px-6 py-4">
                        <form method="POST" action="{{ route('employee.jobs.update-status', $job) }}">
                            @csrf
                            <select name="status" onchange="this.form.submit()"
                                class="text-sm border border-gray-300 rounded px-2 py-1 focus:outline-none focus:ring-1 focus:ring-blue-500">
                                <option value="in_progress" {{ $job->status === 'in_progress' ? 'selected' : '' }}>In Progress</option>
                                <option value="completed" {{ $job->status === 'completed' ? 'selected' : '' }}>Completed</option>
                                <option value="cancelled" {{ $job->status === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                            </select>
                        </form>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="px-6 py-8 text-center text-gray-500">No jobs found. <a href="{{ route('employee.jobs.create') }}" class="text-blue-600 hover:underline">Log one</a></td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
