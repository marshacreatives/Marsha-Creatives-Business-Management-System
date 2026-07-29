@extends('layouts.app')
@section('title', 'Edit Job')

@section('content')
<div class="mb-8">
    <h1 class="text-3xl font-bold text-gray-800">Edit Job</h1>
    <p class="text-gray-500 mt-1">Update project details</p>
</div>

<div class="bg-white rounded-lg shadow p-6 max-w-2xl">
    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
        <p class="text-sm text-blue-800"><strong>Available Balance:</strong> KSh {{ number_format($balance, 2) }}</p>
    </div>

    <form method="POST" action="{{ route('admin.jobs.update', $job) }}">
        @csrf
        @method('PUT')

        <div class="mb-4">
            <label class="block text-gray-700 text-sm font-medium mb-2">Project Name</label>
            <input type="text" name="project_name" value="{{ old('project_name', $job->project_name) }}" required
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
            @error('project_name') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
            <div>
                <label class="block text-gray-700 text-sm font-medium mb-2">Expense (KSh)</label>
                <input type="number" name="expense" step="0.01" min="0" value="{{ old('expense', $job->expense) }}" required
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                @error('expense') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-gray-700 text-sm font-medium mb-2">Cost (KSh)</label>
                <input type="number" name="cost" step="0.01" min="0" value="{{ old('cost', $job->cost) }}" required
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                @error('cost') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
            <div>
                <label class="block text-gray-700 text-sm font-medium mb-2">Status</label>
                <select name="status" required
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="in_progress" {{ old('status', $job->status) === 'in_progress' ? 'selected' : '' }}>In Progress</option>
                    <option value="completed" {{ old('status', $job->status) === 'completed' ? 'selected' : '' }}>Completed</option>
                    <option value="cancelled" {{ old('status', $job->status) === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                </select>
                @error('status') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-gray-700 text-sm font-medium mb-2">Assign To</label>
                <select name="assigned_to" required
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">Select Employee</option>
                    @foreach($employees as $employee)
                        <option value="{{ $employee->id }}" {{ old('assigned_to', $job->assigned_to) == $employee->id ? 'selected' : '' }}>{{ $employee->name }}</option>
                    @endforeach
                </select>
                @error('assigned_to') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
            </div>
        </div>

        <div class="flex space-x-3">
            <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-md hover:bg-blue-700 transition font-medium">Update Job</button>
            <a href="{{ route('admin.jobs.index') }}" class="bg-gray-300 text-gray-700 px-6 py-2 rounded-md hover:bg-gray-400 transition font-medium">Cancel</a>
        </div>
    </form>
</div>
@endsection
