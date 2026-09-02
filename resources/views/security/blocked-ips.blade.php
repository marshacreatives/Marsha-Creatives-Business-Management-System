@extends('security.layout')
@section('title', 'Blocked IPs')

@section('security-content')
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
    <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">Block an IP</h3>
        <form method="POST" action="{{ route('security.blocks.store') }}">
            @csrf
            <div class="space-y-3">
                <div>
                    <label class="block text-sm text-gray-600 mb-1">IP Address</label>
                    <input type="text" name="ip" required placeholder="e.g. 203.0.113.50" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-sm text-gray-600 mb-1">Reason</label>
                    <textarea name="reason" required rows="3" placeholder="Why is this IP being blocked?" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm"></textarea>
                </div>
                <button type="submit" class="w-full bg-red-600 text-white px-4 py-2 rounded-md text-sm font-medium hover:bg-red-700">Block IP</button>
            </div>
        </form>
    </div>

    <div class="lg:col-span-2">
        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-gray-800">Blocked IP Records</h3>
                <span class="text-sm text-gray-500">{{ $blocks->total() }} records</span>
            </div>

            <form method="GET" action="{{ route('security.blocks.index') }}" class="flex space-x-3 mb-4">
                <select name="status" class="border border-gray-300 rounded-md px-3 py-2 text-sm">
                    <option value="all">All Statuses</option>
                    <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                    <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Unblocked</option>
                </select>
                <input type="text" name="ip" value="{{ request('ip') }}" placeholder="Search IP" class="border border-gray-300 rounded-md px-3 py-2 text-sm flex-1">
                <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-md text-sm font-medium hover:bg-blue-700">Search</button>
                <a href="{{ route('security.blocks.index') }}" class="px-4 py-2 rounded-md text-sm font-medium bg-gray-200 text-gray-700 hover:bg-gray-300">Reset</a>
            </form>
        </div>

        @if($blocks->isEmpty())
            <div class="bg-white rounded-lg shadow p-8 text-center">
                <p class="text-gray-500">No blocked IP records found.</p>
            </div>
        @else
            <div class="bg-white rounded-lg shadow overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-gray-50">
                            <tr class="text-left text-gray-500">
                                <th class="px-6 py-3 font-medium">IP Address</th>
                                <th class="px-6 py-3 font-medium">Reason</th>
                                <th class="px-6 py-3 font-medium">Blocked By</th>
                                <th class="px-6 py-3 font-medium">Status</th>
                                <th class="px-6 py-3 font-medium">Blocked At</th>
                                <th class="px-6 py-3 font-medium">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($blocks as $block)
                                <tr class="border-b last:border-0 hover:bg-gray-50">
                                    <td class="px-6 py-3 font-mono font-medium text-purple-700">{{ $block->ip }}</td>
                                    <td class="px-6 py-3">{{ $block->reason }}</td>
                                    <td class="px-6 py-3">{{ $block->blocked_by }}</td>
                                    <td class="px-6 py-3">
                                        @if($block->is_active)
                                            <span class="px-2 py-1 rounded-full text-xs bg-red-100 text-red-700">Active</span>
                                        @else
                                            <span class="px-2 py-1 rounded-full text-xs bg-gray-100 text-gray-600">Unblocked</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-3 text-gray-500 whitespace-nowrap">{{ $block->blocked_at->format('Y-m-d H:i') }}</td>
                                    <td class="px-6 py-3">
                                        <div class="flex space-x-2">
                                            @if($block->is_active)
                                                <form method="POST" action="{{ route('security.blocks.unblock', $block) }}">
                                                    @csrf
                                                    <button type="submit" class="text-xs text-blue-600 hover:text-blue-700 font-medium">Unblock</button>
                                                </form>
                                            @endif
                                            <form method="POST" action="{{ route('security.blocks.destroy', $block) }}" onsubmit="return confirm('Delete this record?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-xs text-red-600 hover:text-red-700 font-medium">Delete</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="mt-6">{{ $blocks->links() }}</div>
        @endif
    </div>
</div>
@endsection
