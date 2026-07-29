@extends('layouts.app')
@section('title', 'Company Balance')

@section('content')
<div class="mb-8">
    <h1 class="text-3xl font-bold text-gray-800">Company Balance</h1>
    <p class="text-gray-500 mt-1">Manage the company account balance</p>
</div>

<div class="bg-white rounded-lg shadow p-6 mb-6">
    <h3 class="text-lg font-semibold text-gray-800 mb-4">Current Balance</h3>
    <p class="text-4xl font-bold text-green-600">KSh {{ number_format($balance, 2) }}</p>
    <p class="text-sm text-gray-500 mt-2">Last updated: {{ \App\Models\CompanyBalance::latest()->first()?->updated_at?->diffForHumans() ?? 'Never' }}</p>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
    <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-semibold text-gray-800 mb-2">Set Balance</h3>
        <p class="text-sm text-gray-500 mb-4">Replace the current balance with a new amount</p>
        <form method="POST" action="{{ route('admin.balance.set') }}">
            @csrf
            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-medium mb-2">New Balance (KSh)</label>
                <input type="number" name="balance" step="0.01" min="0" value="{{ $balance }}" required
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                @error('balance')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>
            <button type="submit" class="w-full bg-blue-600 text-white py-2 rounded-md hover:bg-blue-700 transition font-medium">
                Set Balance
            </button>
        </form>
    </div>

    <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-semibold text-gray-800 mb-2">Add Funds</h3>
        <p class="text-sm text-gray-500 mb-4">Add money on top of the existing balance</p>
        <form method="POST" action="{{ route('admin.balance.add') }}">
            @csrf
            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-medium mb-2">Amount to Add (KSh)</label>
                <input type="number" name="amount" step="0.01" min="0.01" value="0" required
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500">
                @error('amount')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>
            <button type="submit" class="w-full bg-green-600 text-white py-2 rounded-md hover:bg-green-700 transition font-medium">
                Add to Balance
            </button>
        </form>
    </div>
</div>

<x-month-filter :availableMonths="$availableMonths" :selectedMonth="$selectedMonth" />

<div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
    <div class="bg-white rounded-lg shadow p-6 card-hover">
        <p class="text-sm text-gray-500">Total Top-ups This Month</p>
        <p class="text-2xl font-bold text-green-600">KSh {{ number_format($totalTopups, 2) }}</p>
    </div>
    <div class="bg-white rounded-lg shadow p-6 card-hover">
        <p class="text-sm text-gray-500">Balance Updates This Month</p>
        <p class="text-2xl font-bold text-blue-600">{{ $totalSets + $balanceLogs->count() }}</p>
    </div>
</div>

<div class="bg-white rounded-lg shadow overflow-hidden">
    <div class="px-6 py-4 border-b border-gray-200">
        <h3 class="text-lg font-semibold text-gray-800">Balance Activity</h3>
    </div>
    <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Action</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Amount</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Previous Balance</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">New Balance</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Done By</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-200">
            @forelse($balanceLogs as $log)
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4 text-sm text-gray-600">{{ $log->created_at->format('M d, Y H:i') }}</td>
                    <td class="px-6 py-4">
                        <span class="px-2 py-1 text-xs rounded-full font-medium
                            {{ $log->type === 'add' ? 'bg-green-100 text-green-800' : 'bg-blue-100 text-blue-800' }}">
                            {{ $log->type === 'add' ? 'Top-up' : 'Set' }}
                        </span>
                    </td>
                    <td class="px-6 py-4 font-semibold {{ $log->type === 'add' ? 'text-green-600' : 'text-blue-600' }}">
                        {{ $log->type === 'add' ? '+' : '' }}KSh {{ number_format($log->amount, 2) }}
                    </td>
                    <td class="px-6 py-4 text-gray-600">KSh {{ number_format($log->previous_balance, 2) }}</td>
                    <td class="px-6 py-4 font-semibold text-gray-800">KSh {{ number_format($log->new_balance, 2) }}</td>
                    <td class="px-6 py-4 text-gray-600">{{ $log->user->name }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="px-6 py-8 text-center text-gray-500">No balance activity for this month.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
