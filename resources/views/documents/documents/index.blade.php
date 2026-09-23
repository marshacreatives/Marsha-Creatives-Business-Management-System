@extends('layouts.app')
@section('title', 'Documents')

@section('content')
@php
    $labels = ['invoice' => 'Invoices', 'quote' => 'Quotes', 'receipt' => 'Receipts'];
    $single = ['invoice' => 'Invoice', 'quote' => 'Quotation', 'receipt' => 'Receipt'];
    $isAdmin = auth()->user()->isAdmin();
@endphp

<div class="flex justify-between items-center mb-6">
    <div>
        <h1 class="text-3xl font-bold text-gray-800">Documents</h1>
        <p class="text-gray-500 mt-1">Create and manage invoices, quotations and receipts</p>
    </div>
    <a href="{{ route($base.'.documents.create', ['type' => $type]) }}" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 transition font-medium">+ New {{ $single[$type] }}</a>
</div>

<div class="mb-6 flex space-x-2 border-b border-gray-200">
    @foreach($labels as $key => $label)
        <a href="{{ route($base.'.documents.index', ['type' => $key]) }}"
            class="px-4 py-2 text-sm font-medium rounded-t-md {{ $type === $key ? 'bg-white text-blue-700 border border-b-0 border-gray-200 -mb-px' : 'text-gray-500 hover:text-gray-700' }}">
            {{ $label }}
        </a>
    @endforeach
    <a href="{{ route($base.'.items.index') }}"
        class="px-4 py-2 text-sm font-medium rounded-t-md {{ request()->routeIs(($isAdmin ? 'admin' : 'employee').'.items*') ? 'bg-white text-blue-700 border border-b-0 border-gray-200 -mb-px' : 'text-gray-500 hover:text-gray-700' }}">
        Items
    </a>
</div>

<div class="bg-white rounded-lg shadow overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Number</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Bill To</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Total</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Created By</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse($documents as $document)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 font-semibold text-gray-800">{{ $document->number }}</td>
                        <td class="px-6 py-4 text-gray-600">{{ $document->client_name }}</td>
                        <td class="px-6 py-4 text-gray-600">{{ $document->issue_date->format('M d, Y') }}</td>
                        <td class="px-6 py-4 text-blue-600 font-medium">KSh {{ number_format((float) $document->total, 2) }}</td>
                        <td class="px-6 py-4 text-gray-600">{{ $document->creator->name ?? '—' }}</td>
                        <td class="px-6 py-4 space-x-2 whitespace-nowrap">
                            <a href="{{ route($base.'.documents.pdf', $document) }}" class="text-green-600 hover:text-green-800 text-sm font-medium">PDF</a>
                            <a href="{{ route($base.'.documents.edit', $document) }}" class="text-blue-600 hover:text-blue-800 text-sm font-medium">Edit</a>
                            <form method="POST" action="{{ route($base.'.documents.destroy', $document) }}" class="inline" onsubmit="return confirm('Are you sure you want to delete this {{ strtolower($single[$type]) }}?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-600 hover:text-red-800 text-sm font-medium">Delete</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-6 py-8 text-center text-gray-500">
                            No {{ strtolower($labels[$type]) }} found.
                            <a href="{{ route($base.'.documents.create', ['type' => $type]) }}" class="text-blue-600 hover:underline">Create one</a>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection