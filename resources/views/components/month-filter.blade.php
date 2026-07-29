@php
    $availableMonths = $availableMonths ?? [];
    $selectedMonth = $selectedMonth ?? now()->format('Y-m');
    $currentUrl = request()->url();
@endphp

<div class="bg-white rounded-lg shadow p-4 mb-6">
    <form method="GET" action="{{ $currentUrl }}" class="flex items-center space-x-4">
        <label class="text-sm font-medium text-gray-700">Filter by Month:</label>
        <select name="month" onchange="this.form.submit()"
            class="px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm">
            @foreach($availableMonths as $month)
                <option value="{{ $month['value'] }}" {{ $selectedMonth === $month['value'] ? 'selected' : '' }}>
                    {{ $month['label'] }}
                </option>
            @endforeach
        </select>
        @if($selectedMonth !== now()->format('Y-m'))
            <a href="{{ $currentUrl }}" class="text-sm text-blue-600 hover:text-blue-800 font-medium">Reset to Current Month</a>
        @endif
    </form>
</div>
