@extends('layouts.app')

@section('title', 'Notifications')

@section('content')
    <div class="flex flex-wrap items-center justify-between gap-3 mb-6">
        <h1 class="text-2xl font-bold text-gray-800">Notifications</h1>

        <div class="flex items-center gap-2">
            @foreach ($categories as $category)
                @php $count = $notifications->where('data.category', $category['key'])->count(); @endphp
                <span class="px-2 py-1 text-xs rounded-full bg-{{ $category['color'] }}-100 text-{{ $category['color'] }}-800">
                    {{ $category['label'] }}{{ $count ? ' ('.$count.')' : '' }}
                </span>
            @endforeach
        </div>
    </div>

    <div class="bg-white rounded-lg shadow overflow-hidden">
        @forelse ($notifications as $notification)
            @php
                $data = $notification->data;
                $category = $data['category'] ?? 'job';
                $config = collect($categories)->firstWhere('key', $category) ?? ['label' => ucfirst($category), 'color' => 'blue'];
            @endphp

            <div class="flex items-start gap-3 px-4 py-3 border-b border-gray-100 {{ $notification->read_at ? 'bg-white' : 'bg-indigo-50/40' }}">
                <span class="px-2 py-0.5 mt-0.5 text-[10px] font-semibold rounded bg-{{ $config['color'] }}-100 text-{{ $config['color'] }}-800 shrink-0">
                    {{ $config['label'] }}
                </span>

                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-gray-800">{{ $data['title'] ?? '' }}</p>
                    <p class="text-sm text-gray-600">{{ $data['body'] ?? '' }}</p>
                    <p class="text-xs text-gray-400 mt-1">{{ $notification->created_at?->format('d M Y, H:i') }}</p>
                </div>

                <div class="flex items-center gap-2 shrink-0">
                    @unless ($notification->read_at)
                        <span class="h-2 w-2 rounded-full bg-indigo-500" title="Unread"></span>
                    @endunless

                    @if (! empty($data['url']))
                        <a href="{{ $data['url'] }}"
                           class="text-xs px-2 py-1 rounded border border-gray-300 text-gray-700 hover:bg-gray-50">Open</a>
                    @endif
                </div>
            </div>
        @empty
            <p class="px-4 py-12 text-center text-sm text-gray-400">You have no notifications yet.</p>
        @endforelse
    </div>

    @if ($notifications->hasPages())
        <div class="mt-6">
            {{ $notifications->links() }}
        </div>
    @endif
@endsection
