@extends('layouts.app')
@section('title', 'Manage Employees')

@section('content')
<div class="flex justify-between items-center mb-8">
    <div>
        <h1 class="text-3xl font-bold text-gray-800">Employees</h1>
        <p class="text-gray-500 mt-1">Manage employee accounts</p>
    </div>
    <a href="{{ route('admin.users.create') }}" class="bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700 transition font-medium">+ Register Employee</a>
</div>

<div class="bg-white rounded-lg shadow overflow-hidden">
    <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Email</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Jobs Assigned</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Joined</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-200">
            @forelse($employees as $employee)
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4 font-medium text-gray-800">{{ $employee->name }}</td>
                    <td class="px-6 py-4 text-gray-600">{{ $employee->email }}</td>
                    <td class="px-6 py-4 text-gray-600">{{ $employee->assigned_jobs_count }}</td>
                    <td class="px-6 py-4 text-gray-600">{{ $employee->created_at->format('M d, Y') }}</td>
                    <td class="px-6 py-4">
                        <a href="{{ route('admin.users.edit', $employee) }}" class="text-blue-600 hover:text-blue-800 text-sm font-medium">Edit</a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="px-6 py-8 text-center text-gray-500">No employees found. <a href="{{ route('admin.users.create') }}" class="text-green-600 hover:underline">Register one</a></td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
