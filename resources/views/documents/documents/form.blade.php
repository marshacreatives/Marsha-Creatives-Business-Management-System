@extends('layouts.app')
@section('title', $document ? 'Edit ' . $document->type_label : 'New ' . ucfirst($type) . ' Document')

@section('content')
@php
    $docType = $document ? $document->type : $type;
    $single = ['invoice' => 'Invoice', 'quote' => 'Quotation', 'receipt' => 'Receipt'];
    $catalog = $items->map(fn ($i) => ['id' => $i->id, 'title' => $i->title, 'description' => $i->description, 'price' => (float) $i->price])->values();

    $initialLines = [];
    if (old('items')) {
        foreach (old('items') as $line) {
            $initialLines[] = [
                'item_id' => $line['item_id'] ?? '',
                'title' => $line['title'] ?? '',
                'description' => $line['description'] ?? '',
                'unit_price' => (float) ($line['unit_price'] ?? 0),
                'quantity' => (int) ($line['quantity'] ?? 1),
            ];
        }
    } elseif ($document) {
        foreach ($document->items as $line) {
            $initialLines[] = [
                'item_id' => $line->item_id,
                'title' => $line->title,
                'description' => $line->description ?? '',
                'unit_price' => (float) $line->unit_price,
                'quantity' => $line->quantity,
            ];
        }
    }
@endphp

<div class="mb-8">
    <h1 class="text-3xl font-bold text-gray-800">{{ $document ? 'Edit ' . $single[$docType] : 'New ' . $single[$docType] }}</h1>
    @if($document)
        <p class="text-gray-500 mt-1">{{ $document->number }} · {{ $document->issue_date->format('M d, Y') }}</p>
    @else
        <p class="text-gray-500 mt-1">Number is assigned automatically on save</p>
    @endif
</div>

@if($errors->any())
    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
        <ul class="list-disc list-inside text-sm">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<form method="POST" action="{{ $document ? route($base.'.documents.update', $document) : route($base.'.documents.store') }}" id="document-form">
    @csrf
    @if($document) @method('PUT') @endif
    <input type="hidden" name="type" value="{{ $docType }}">

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div>
            <label class="block text-gray-700 text-sm font-medium mb-2">Client Name / Company</label>
            <input type="text" name="client_name" value="{{ old('client_name', $document->client_name ?? '') }}" required
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="e.g. Acme Corp">
            @error('client_name') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="block text-gray-700 text-sm font-medium mb-2">Date</label>
            <input type="date" name="issue_date" value="{{ old('issue_date', $document ? $document->issue_date->format('Y-m-d') : now()->format('Y-m-d')) }}" required
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
            @error('issue_date') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="block text-gray-700 text-sm font-medium mb-2">Discount (KSh)</label>
            <input type="number" name="discount" step="0.01" min="0" id="discount-input" value="{{ old('discount', $document->discount ?? '0') }}"
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
            @error('discount') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
        </div>
    </div>

    <div class="mb-6">
        <label class="block text-gray-700 text-sm font-medium mb-2">Add Item</label>
        <input type="text" id="item-search" list="catalog-options" placeholder="Type or select an item from the list…" autocomplete="off"
            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" value="{{ old('search') }}">
        <datalist id="catalog-options">
            @foreach($items as $item)
                <option value="{{ $item->title }}">{{ $item->description ? Str::limit($item->description, 60) : 'KSh ' . number_format((float) $item->price, 2) }}</option>
            @endforeach
        </datalist>
        <button type="button" id="add-item-btn" class="hidden mt-2 bg-green-600 text-white px-3 py-2 rounded-md hover:bg-green-700 transition text-sm font-medium"></button>
        <p class="text-xs text-gray-400 mt-1">Search the catalogue. Not listed? The green button lets you add it instantly.</p>
    </div>

    <div class="bg-white rounded-lg shadow overflow-hidden mb-6">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase w-10">#</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Item</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Description</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase w-24">Qty</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase w-32">Price</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase w-32">Total</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase w-10"></th>
                    </tr>
                </thead>
                <tbody id="line-items" class="divide-y divide-gray-200"></tbody>
            </table>
        </div>
        <div id="no-items-msg" class="px-4 py-8 text-center text-gray-500">No items added yet — select from the list above.</div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
        <div>
            <label class="block text-gray-700 text-sm font-medium mb-2">Notes</label>
            <textarea name="notes" rows="4" placeholder="Optional notes shown on the document…"
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">{{ old('notes', $document->notes ?? '') }}</textarea>
            @error('notes') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
        </div>
        <div class="bg-gray-50 rounded-lg p-5 flex flex-col justify-end">
            <div class="flex justify-between items-center py-1 text-sm text-gray-600">
                <span>Subtotal</span>
                <span id="subtotal-display" class="font-medium">KSh 0.00</span>
            </div>
            <div class="flex justify-between items-center py-1 text-sm text-gray-600">
                <span>Discount</span>
                <span id="discount-display" class="font-medium">− KSh 0.00</span>
            </div>
            <div class="flex justify-between items-center pt-3 mt-1 border-t border-gray-200 text-base font-semibold text-gray-800">
                <span>Grand Total</span>
                <span id="grand-total-display">KSh 0.00</span>
            </div>
        </div>
    </div>

    <div class="flex space-x-3">
        <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-md hover:bg-blue-700 transition font-medium">Save {{ $single[$docType] }}</button>
        <a href="{{ route($base.'.documents.index', ['type' => $docType]) }}" class="bg-gray-300 text-gray-700 px-6 py-2 rounded-md hover:bg-gray-400 transition font-medium">Cancel</a>
    </div>
</form>

<div id="add-item-modal" class="hidden fixed inset-0 z-50 bg-black/40 flex items-center justify-center">
    <div class="bg-white rounded-lg shadow-xl w-full max-w-md mx-4 p-6">
        <h3 class="text-lg font-bold text-gray-800 mb-4">Add Item to Catalogue</h3>
        <div class="mb-3">
            <label class="block text-gray-700 text-sm font-medium mb-1">Item Title</label>
            <input type="text" id="aq-title" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required>
        </div>
        <div class="mb-3">
            <label class="block text-gray-700 text-sm font-medium mb-1">Description</label>
            <textarea id="aq-description" rows="2" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
        </div>
        <div class="mb-4">
            <label class="block text-gray-700 text-sm font-medium mb-1">Price (KSh)</label>
            <input type="number" id="aq-price" step="0.01" min="0" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required>
        </div>
        <div class="flex justify-end space-x-2">
            <button type="button" id="aq-cancel" class="bg-gray-300 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-400 transition text-sm font-medium">Cancel</button>
            <button type="button" id="aq-save" class="bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700 transition text-sm font-medium">Add Item</button>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
(function () {
    'use strict';

    const CSRF = '{{ csrf_token() }}';
    const QUICK_URL = '{{ route($base.'.documents.items.quick') }}';

    const catalog = @json($catalog);
    const catalogByTitle = {};
    catalog.forEach(function (it) { catalogByTitle[it.title] = it; });

    const form = document.getElementById('document-form');
    const tbody = document.getElementById('line-items');
    const noItemsMsg = document.getElementById('no-items-msg');
    const searchInput = document.getElementById('item-search');
    const addItemBtn = document.getElementById('add-item-btn');
    const discountInput = document.getElementById('discount-input');

    let selected = @json($initialLines);

    function money(n) {
        return 'KSh ' + Number(n).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function esc(str) {
        if (str == null) return '';
        return String(str).replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
    }

    function render() {
        let html = '';
        selected.forEach(function (line, i) {
            const lineTotal = ((parseFloat(line.unit_price) || 0) * (parseInt(line.quantity, 10) || 0)).toFixed(2);
            html += '<tr data-index="' + i + '">'
                + '<td class="px-4 py-3 text-gray-500 text-sm">' + (i + 1) + '</td>'
                + '<td class="px-4 py-3">'
                    + '<input type="hidden" name="items[' + i + '][item_id]" value="' + esc(line.item_id) + '">'
                    + '<input type="hidden" name="items[' + i + '][title]" value="' + esc(line.title) + '">'
                    + '<span class="font-medium text-gray-800 text-sm">' + esc(line.title) + '</span>'
                + '</td>'
                + '<td class="px-4 py-3">'
                    + '<input type="text" name="items[' + i + '][description]" value="' + esc(line.description) + '" placeholder="Description…" class="w-full px-2 py-1 text-sm border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-blue-500">'
                + '</td>'
                + '<td class="px-4 py-3">'
                    + '<input type="number" name="items[' + i + '][quantity]" min="1" step="1" value="' + (line.quantity || 1) + '" class="w-full px-2 py-1 text-sm border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-blue-500">'
                + '</td>'
                + '<td class="px-4 py-3">'
                    + '<input type="number" name="items[' + i + '][unit_price]" min="0" step="0.01" value="' + (line.unit_price || 0) + '" readonly class="w-full px-2 py-1 text-sm bg-gray-100 border border-gray-200 rounded text-gray-700">'
                + '</td>'
                + '<td class="px-4 py-3 text-sm font-medium text-gray-800 text-right line-total">' + money(lineTotal) + '</td>'
                + '<td class="px-4 py-3 text-right"><button type="button" class="remove-line text-red-600 hover:text-red-800 text-sm font-medium" data-index="' + i + '">✕</button></td>'
                + '</tr>';
        });
        tbody.innerHTML = html;
        noItemsMsg.style.display = selected.length ? 'none' : '';
        updateTotals();
    }

    function findLine(itemId) {
        return selected.findIndex(function (l) { return l.item_id && String(l.item_id) === String(itemId); });
    }

    function addLine(item, qty) {
        const idx = findLine(item.id);
        if (idx >= 0) {
            selected[idx].quantity = (parseInt(selected[idx].quantity, 10) || 0) + (qty || 1);
        } else {
            selected.push({
                item_id: item.id,
                title: item.title,
                description: item.description || '',
                unit_price: item.price,
                quantity: qty || 1
            });
        }
        render();
    }

    function updateTotals() {
        let subtotal = 0;
        selected.forEach(function (line, i) {
            const qty = parseInt(tbody.querySelector('input[name="items[' + i + '][quantity]"]').value, 10) || 0;
            const price = parseFloat(tbody.querySelector('input[name="items[' + i + '][unit_price]"]').value) || 0;
            const total = qty * price;
            line.quantity = qty;
            line.unit_price = price;
            rowTotalEl(i).textContent = money(total.toFixed(2));
            subtotal += total;
        });
        const discount = parseFloat(discountInput.value) || 0;
        const grand = Math.max(0, subtotal - discount);
        document.getElementById('subtotal-display').textContent = money(subtotal.toFixed(2));
        document.getElementById('discount-display').textContent = '− ' + money(discount.toFixed(2));
        document.getElementById('grand-total-display').textContent = money(grand.toFixed(2));
    }

    function rowTotalEl(i) {
        const row = tbody.querySelector('tr[data-index="' + i + '"]');
        return row ? row.querySelector('.line-total') : null;
    }

    tbody.addEventListener('input', function (e) {
        const row = e.target.closest('tr[data-index]');
        if (!row) return;
        const i = parseInt(row.dataset.index, 10);
        const name = e.target.getAttribute('name');
        if (name && name.indexOf('[description]') !== -1) {
            selected[i].description = e.target.value;
        }
        updateTotals();
    });

    tbody.addEventListener('click', function (e) {
        const btn = e.target.closest('.remove-line');
        if (!btn) return;
        const i = parseInt(btn.dataset.index, 10);
        selected.splice(i, 1);
        render();
    });

    searchInput.addEventListener('input', function () {
        const val = searchInput.value.trim();
        if (val && !catalogByTitle[val]) {
            addItemBtn.textContent = '+ Add Item "' + val + '" to catalogue';
            addItemBtn.classList.remove('hidden');
            addItemBtn.classList.add('inline-block');
        } else {
            addItemBtn.classList.add('hidden');
        }
    });

    searchInput.addEventListener('change', function () {
        const val = searchInput.value.trim();
        const item = catalogByTitle[val];
        if (item) {
            addLine(item, 1);
            searchInput.value = '';
            addItemBtn.classList.add('hidden');
        }
    });

    searchInput.addEventListener('keydown', function (e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            const item = catalogByTitle[searchInput.value.trim()];
            if (item) {
                addLine(item, 1);
                searchInput.value = '';
                addItemBtn.classList.add('hidden');
            }
        }
    });

    discountInput.addEventListener('input', updateTotals);

    const modal = document.getElementById('add-item-modal');
    addItemBtn.addEventListener('click', function () {
        document.getElementById('aq-title').value = searchInput.value.trim();
        document.getElementById('aq-description').value = '';
        document.getElementById('aq-price').value = '';
        modal.classList.remove('hidden');
    });
    document.getElementById('aq-cancel').addEventListener('click', function () {
        modal.classList.add('hidden');
    });

    document.getElementById('aq-save').addEventListener('click', function () {
        const title = document.getElementById('aq-title').value.trim();
        const description = document.getElementById('aq-description').value.trim();
        const price = document.getElementById('aq-price').value;
        if (!title || price === '' || isNaN(parseFloat(price))) return;

        const body = new FormData();
        body.append('title', title);
        body.append('description', description);
        body.append('price', price);

        fetch(QUICK_URL, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': CSRF, 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
            body: body
        }).then(function (res) {
            return res.json();
        }).then(function (item) {
            catalogByTitle[item.title] = item;
            addLine(item, 1);
            searchInput.value = '';
            addItemBtn.classList.add('hidden');
            modal.classList.add('hidden');
        }).catch(function () {
            alert('Could not add the item. Please try again.');
        });
    });

    render();
})();
</script>
@endsection