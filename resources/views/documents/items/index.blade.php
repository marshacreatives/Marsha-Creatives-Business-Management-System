@extends('layouts.app')
@section('title', 'Items')

@section('content')
@php
    $labels = ['invoice' => 'Invoices', 'quote' => 'Quotes', 'receipt' => 'Receipts'];
    $isAdmin = auth()->user()->isAdmin();
@endphp

<div class="flex justify-between items-center mb-6">
    <div>
        <h1 class="text-3xl font-bold text-gray-800">Items</h1>
        <p class="text-gray-500 mt-1">Manage the product / service catalogue used on documents</p>
    </div>
    <a href="{{ route($base.'.items.create') }}" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 transition font-medium">+ New Item</a>
</div>

<div class="mb-6 flex space-x-2 border-b border-gray-200">
    @foreach($labels as $key => $label)
        <a href="{{ route($base.'.documents.index', ['type' => $key]) }}"
            class="px-4 py-2 text-sm font-medium rounded-t-md text-gray-500 hover:text-gray-700">
            {{ $label }}
        </a>
    @endforeach
    <a href="{{ route($base.'.items.index') }}"
        class="px-4 py-2 text-sm font-medium rounded-t-md bg-white text-blue-700 border border-b-0 border-gray-200 -mb-px">
        Items
    </a>
</div>

<div class="bg-white rounded-lg shadow overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Title</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Description</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Price</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse($items as $item)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 font-medium text-gray-800">{{ $item->title }}</td>
                        <td class="px-6 py-4 text-gray-600">{{ $item->description ?? '—' }}</td>
                        <td class="px-6 py-4 text-blue-600 font-medium">KSh {{ number_format((float) $item->price, 2) }}</td>
                        <td class="px-6 py-4 space-x-2">
                            <a href="{{ route($base.'.items.edit', $item) }}" class="text-blue-600 hover:text-blue-800 text-sm font-medium">Edit</a>
                            <form method="POST" action="{{ route($base.'.items.destroy', $item) }}" class="inline" onsubmit="return confirm('Are you sure you want to delete this item? Existing documents will keep their saved details.')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-600 hover:text-red-800 text-sm font-medium">Delete</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-6 py-8 text-center text-gray-500">No items found. <a href="{{ route($base.'.items.create') }}" class="text-blue-600 hover:underline">Create one</a></td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection