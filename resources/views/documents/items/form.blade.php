@extends('layouts.app')
@section('title', $item ? 'Edit Item' : 'New Item')

@section('content')
@php
    $labels = ['invoice' => 'Invoices', 'quote' => 'Quotes', 'receipt' => 'Receipts'];
    $isAdmin = auth()->user()->isAdmin();
@endphp

<div class="mb-6">
    <div class="flex space-x-2 border-b border-gray-200">
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
</div>

<div class="mb-8">
    <h1 class="text-3xl font-bold text-gray-800">{{ $item ? 'Edit Item' : 'New Item' }}</h1>
    <p class="text-gray-500 mt-1">Items are used to quickly build invoices, quotations and receipts</p>
</div>

<div class="bg-white rounded-lg shadow p-6 max-w-2xl">
    <form method="POST" action="{{ $item ? route($base.'.items.update', $item) : route($base.'.items.store') }}">
        @csrf
        @if($item) @method('PUT') @endif

        <div class="mb-4">
            <label class="block text-gray-700 text-sm font-medium mb-2">Item Title</label>
            <input type="text" name="title" value="{{ old('title', $item->title ?? '') }}" required
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
            @error('title') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
        </div>

        <div class="mb-4">
            <label class="block text-gray-700 text-sm font-medium mb-2">Description</label>
            <textarea name="description" rows="3"
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">{{ old('description', $item->description ?? '') }}</textarea>
            @error('description') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
        </div>

        <div class="mb-6">
            <label class="block text-gray-700 text-sm font-medium mb-2">Price (KSh)</label>
            <input type="number" name="price" step="0.01" min="0" value="{{ old('price', $item->price ?? '0') }}" required
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
            @error('price') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
        </div>

        <div class="flex space-x-3">
            <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-md hover:bg-blue-700 transition font-medium">{{ $item ? 'Update Item' : 'Save Item' }}</button>
            <a href="{{ route($base.'.items.index') }}" class="bg-gray-300 text-gray-700 px-6 py-2 rounded-md hover:bg-gray-400 transition font-medium">Cancel</a>
        </div>
    </form>
</div>
@endsection