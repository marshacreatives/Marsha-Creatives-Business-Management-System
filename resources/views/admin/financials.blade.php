@extends('layouts.app')
@section('title', 'Financial Overview')

@section('content')
<div class="mb-8">
    <h1 class="text-3xl font-bold text-gray-800">Financial Overview</h1>
    <p class="text-gray-500 mt-1">Aggregated financial data across all jobs and employees</p>
</div>

<x-month-filter :availableMonths="$availableMonths" :selectedMonth="$selectedMonth" />

<div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
    <div class="bg-white rounded-lg shadow p-6 card-hover">
        <p class="text-sm text-gray-500">Company Balance</p>
        <p class="text-2xl font-bold text-green-600">KSh {{ number_format($balance, 2) }}</p>
    </div>
    <div class="bg-white rounded-lg shadow p-6 card-hover">
        <p class="text-sm text-gray-500">Total Revenue</p>
        <p class="text-2xl font-bold text-blue-600">KSh {{ number_format($businessTotals['total_revenue'], 2) }}</p>
    </div>
    <div class="bg-white rounded-lg shadow p-6 card-hover">
        <p class="text-sm text-gray-500">Total Expenses</p>
        <p class="text-2xl font-bold text-red-600">KSh {{ number_format($businessTotals['total_expenses'], 2) }}</p>
    </div>
    <div class="bg-white rounded-lg shadow p-6 card-hover">
        <p class="text-sm text-gray-500">Total Profit</p>
        <p class="text-2xl font-bold {{ $businessTotals['total_profit'] >= 0 ? 'text-green-600' : 'text-red-600' }}">KSh {{ number_format($businessTotals['total_profit'], 2) }}</p>
    </div>
</div>

<div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
    <div class="bg-white rounded-lg shadow p-4 text-center">
        <p class="text-3xl font-bold text-gray-800">{{ $businessTotals['total_jobs'] }}</p>
        <p class="text-sm text-gray-500">Total Jobs</p>
    </div>
    <div class="bg-white rounded-lg shadow p-4 text-center">
        <p class="text-3xl font-bold text-green-600">{{ $businessTotals['completed_jobs'] }}</p>
        <p class="text-sm text-gray-500">Completed</p>
    </div>
    <div class="bg-white rounded-lg shadow p-4 text-center">
        <p class="text-3xl font-bold text-yellow-600">{{ $businessTotals['in_progress_jobs'] }}</p>
        <p class="text-sm text-gray-500">In Progress</p>
    </div>
    <div class="bg-white rounded-lg shadow p-4 text-center">
        <p class="text-3xl font-bold text-red-600">{{ $businessTotals['cancelled_jobs'] }}</p>
        <p class="text-sm text-gray-500">Cancelled</p>
    </div>
</div>

<div class="bg-white rounded-lg shadow overflow-hidden">
    <div class="px-6 py-4 border-b border-gray-200">
        <h3 class="text-lg font-semibold text-gray-800">Per-Employee Breakdown</h3>
    </div>
    <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Employee</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Total Jobs</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Completed</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Expenses</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Revenue</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Profit</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-200">
            @forelse($employees as $employee)
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4 font-medium text-gray-800">{{ $employee->name }}</td>
                    <td class="px-6 py-4 text-gray-600">{{ $employee->total_jobs }}</td>
                    <td class="px-6 py-4 text-gray-600">{{ $employee->completed_jobs }}</td>
                    <td class="px-6 py-4 text-red-600">KSh {{ number_format($employee->total_expenses ?? 0, 2) }}</td>
                    <td class="px-6 py-4 text-blue-600">KSh {{ number_format($employee->total_revenue ?? 0, 2) }}</td>
                    <td class="px-6 py-4 font-semibold {{ $employee->total_profit >= 0 ? 'text-green-600' : 'text-red-600' }}">KSh {{ number_format($employee->total_profit, 2) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="px-6 py-8 text-center text-gray-500">No employee data available.</td>
                </tr>
            @endforelse

            <tr class="bg-gray-50 font-bold">
                <td class="px-6 py-4 text-gray-800">TOTAL</td>
                <td class="px-6 py-4 text-gray-800">{{ $businessTotals['total_jobs'] }}</td>
                <td class="px-6 py-4 text-gray-800">{{ $businessTotals['completed_jobs'] }}</td>
                <td class="px-6 py-4 text-red-700">KSh {{ number_format($businessTotals['total_expenses'], 2) }}</td>
                <td class="px-6 py-4 text-blue-700">KSh {{ number_format($businessTotals['total_revenue'], 2) }}</td>
                <td class="px-6 py-4 {{ $businessTotals['total_profit'] >= 0 ? 'text-green-700' : 'text-red-700' }}">KSh {{ number_format($businessTotals['total_profit'], 2) }}</td>
            </tr>
        </tbody>
    </table>
</div>
@endsection
