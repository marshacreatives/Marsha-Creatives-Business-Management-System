@extends('layouts.app')
@section('title', 'Log Job')

@section('content')
<div class="mb-8">
    <h1 class="text-3xl font-bold text-gray-800">Log New Job</h1>
    <p class="text-gray-500 mt-1">Create a new job entry and assign it to yourself</p>
</div>

@if(session('error'))
    <div class="bg-red-50 border border-red-300 rounded-lg p-6 mb-6">
        <div class="flex items-start">
            <div class="flex-shrink-0">
                <svg class="h-6 w-6 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                </svg>
            </div>
            <div class="ml-3 flex-1">
                <h3 class="text-sm font-medium text-red-800">Insufficient Funds</h3>
                <p class="text-sm text-red-700 mt-1">{{ session('error') }}</p>
                <div class="mt-4">
                    <form method="POST" action="{{ route('employee.jobs.store') }}" id="requestFundsForm">
                        @csrf
                        <input type="hidden" name="_method" value="PATCH">
                    </form>
                    <button onclick="document.getElementById('requestFundsInline').submit()"
                        class="inline-flex items-center px-4 py-2 bg-red-600 text-white text-sm font-medium rounded-md hover:bg-red-700 transition">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/></svg>
                        Send Request to Admin
                    </button>
                    <p class="text-xs text-gray-500 mt-2">A fund request has already been automatically sent to the admin.</p>
                </div>
            </div>
        </div>
    </div>
@endif

<div class="bg-white rounded-lg shadow p-6 max-w-2xl">
    <div class="bg-green-50 border border-green-200 rounded-lg p-4 mb-6">
        <p class="text-sm text-green-800"><strong>Available Company Balance:</strong> KSh {{ number_format($balance, 2) }}</p>
    </div>

    <form method="POST" action="{{ route('employee.jobs.store') }}">
        @csrf

        <div class="mb-4">
            <label class="block text-gray-700 text-sm font-medium mb-2">Project Name</label>
            <input type="text" name="project_name" value="{{ old('project_name') }}" required
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
            @error('project_name') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
            <div>
                <label class="block text-gray-700 text-sm font-medium mb-2">Expense (KSh)</label>
                <input type="number" name="expense" step="0.01" min="0" value="{{ old('expense') }}" required
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                <p class="text-xs text-gray-400 mt-1">Deducted from company balance</p>
                @error('expense') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-gray-700 text-sm font-medium mb-2">Cost (KSh)</label>
                <input type="number" name="cost" step="0.01" min="0" value="{{ old('cost') }}" required
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                <p class="text-xs text-gray-400 mt-1">Amount the client pays</p>
                @error('cost') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
            </div>
        </div>

        <div class="mb-6">
            <label class="block text-gray-700 text-sm font-medium mb-2">Status</label>
            <select name="status" required
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                <option value="in_progress" {{ old('status') === 'in_progress' ? 'selected' : '' }}>In Progress</option>
                <option value="completed" {{ old('status') === 'completed' ? 'selected' : '' }}>Completed</option>
                <option value="cancelled" {{ old('status') === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
            </select>
            @error('status') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
        </div>

        <div class="bg-gray-50 rounded-lg p-4 mb-6">
            <p class="text-sm text-gray-600"><strong>Note:</strong> The expense amount will be deducted from the company balance when you log this job.</p>
        </div>

        <div class="flex space-x-3">
            <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-md hover:bg-blue-700 transition font-medium">Log Job</button>
            <a href="{{ route('employee.jobs.index') }}" class="bg-gray-300 text-gray-700 px-6 py-2 rounded-md hover:bg-gray-400 transition font-medium">Cancel</a>
        </div>
    </form>
</div>
@endsection
