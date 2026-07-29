@php
    $availableMonths = $availableMonths ?? [];
    $selectedMonth = $selectedMonth ?? now()->format('Y-m');
    $currentUrl = request()->url();
    $statuses = $statuses ?? null;
    $employees = $employees ?? null;
    $employeeField = $employeeField ?? 'employee_id';
@endphp

<div class="bg-white rounded-lg shadow p-4 mb-6">
    <form method="GET" action="{{ $currentUrl }}" class="flex items-center space-x-4 flex-wrap gap-y-2">
        @if(count($availableMonths) > 0)
            <label class="text-sm font-medium text-gray-700">Month:</label>
            <select name="month" onchange="this.form.submit()"
                class="px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm">
                @foreach($availableMonths as $month)
                    <option value="{{ $month['value'] }}" {{ $selectedMonth === $month['value'] ? 'selected' : '' }}>
                        {{ $month['label'] }}
                    </option>
                @endforeach
            </select>
            @if($selectedMonth !== now()->format('Y-m'))
                <a href="{{ $currentUrl }}" class="text-sm text-blue-600 hover:text-blue-800 font-medium">Reset</a>
            @endif
        @endif

        @if($statuses)
            <label class="text-sm font-medium text-gray-700 ml-4">Status:</label>
            <select name="status" onchange="this.form.submit()"
                class="px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm">
                <option value="">All</option>
                @foreach($statuses as $val => $label)
                    <option value="{{ $val }}" {{ request('status') === $val ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
        @endif

        @if($employees)
            <label class="text-sm font-medium text-gray-700 ml-4">Employee:</label>
            <select name="{{ $employeeField }}" onchange="this.form.submit()"
                class="px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm">
                <option value="">All</option>
                @foreach($employees as $emp)
                    <option value="{{ $emp->id }}" {{ request($employeeField) == $emp->id ? 'selected' : '' }}>{{ $emp->name }}</option>
                @endforeach
            </select>
        @endif
    </form>
</div>
