@extends('layouts.app')
@section('title', 'Manage Jobs')

@section('content')
<div class="flex justify-between items-center mb-8">
    <div>
        <h1 class="text-3xl font-bold text-gray-800">Jobs</h1>
        <p class="text-gray-500 mt-1">Manage all company jobs</p>
    </div>
    <a href="{{ route('admin.jobs.create') }}" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 transition font-medium">+ Create Job</a>
</div>

<div class="bg-white rounded-lg shadow overflow-hidden">
    <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Project Name</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Assigned To</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Expense</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Cost</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Profit</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-200">
            @forelse($jobs as $job)
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4 font-medium text-gray-800">{{ $job->project_name }}</td>
                    <td class="px-6 py-4 text-gray-600">{{ $job->assignee->name }}</td>
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
                    <td class="px-6 py-4 space-x-2">
                        <a href="{{ route('admin.jobs.edit', $job) }}" class="text-blue-600 hover:text-blue-800 text-sm font-medium">Edit</a>
                        <form method="POST" action="{{ route('admin.jobs.destroy', $job) }}" class="inline" onsubmit="return confirm('Are you sure you want to delete this job?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-red-600 hover:text-red-800 text-sm font-medium">Delete</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="px-6 py-8 text-center text-gray-500">No jobs found. <a href="{{ route('admin.jobs.create') }}" class="text-blue-600 hover:underline">Create one</a></td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
