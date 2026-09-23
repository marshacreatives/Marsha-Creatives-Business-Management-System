@extends('layouts.app')
@section('title', 'WordPress Setup')

@section('content')
<div class="mb-8">
    <h1 class="text-3xl font-bold text-gray-800">Quick WordPress Setup</h1>
    <p class="text-gray-500 mt-1">Automate a new site: log in, install Hello Elementor + Google Site Kit, and install plugins from the <code class="text-gray-700">plugins/</code> folder.</p>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
    <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-lg font-semibold text-gray-800 mb-4">Run Setup</h2>

        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
            <p class="text-sm text-blue-800">Credentials are used only for this run and are never stored.</p>
        </div>

        <form method="POST" action="{{ route('admin.wordpress.store') }}">
            @csrf

            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-medium mb-2">WordPress Login Link</label>
                <input type="text" name="site_url" value="{{ old('site_url') }}" required
                    placeholder="https://client-site.com"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                <p class="text-xs text-gray-400 mt-1">Site URL, wp-admin, or wp-login.php link all work.</p>
                @error('site_url') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
            </div>

            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-medium mb-2">Username</label>
                <input type="text" name="username" value="{{ old('username') }}" required autocomplete="off"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                @error('username') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
            </div>

            <div class="mb-6">
                <label class="block text-gray-700 text-sm font-medium mb-2">Password</label>
                <input type="password" name="password" required autocomplete="new-password"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                @error('password') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
            </div>

            <button type="submit" class="w-full bg-blue-600 text-white px-6 py-2 rounded-md hover:bg-blue-700 transition font-medium">Start Setup</button>
        </form>
    </div>

    <div class="lg:col-span-2 bg-white rounded-lg shadow overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-lg font-semibold text-gray-800">Setup History</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Site</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Started By</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">When</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($sites as $site)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4">
                                <a href="{{ route('admin.wordpress.show', $site) }}" class="font-medium text-blue-600 hover:text-blue-800">{{ $site->site_url }}</a>
                            </td>
                            <td class="px-6 py-4">
                                <span class="px-2 py-1 text-xs rounded-full font-medium {{ $site->status_badge_class }}">{{ $site->status_label }}</span>
                            </td>
                            <td class="px-6 py-4 text-gray-600">{{ $site->creator?->name ?? '-' }}</td>
                            <td class="px-6 py-4 text-gray-600">{{ $site->created_at->diffForHumans() }}</td>
                            <td class="px-6 py-4 space-x-2">
                                <a href="{{ route('admin.wordpress.show', $site) }}" class="text-blue-600 hover:text-blue-800 text-sm font-medium">View</a>
                                <form method="POST" action="{{ route('admin.wordpress.destroy', $site) }}" class="inline" onsubmit="return confirm('Delete this setup record?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:text-red-800 text-sm font-medium">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-8 text-center text-gray-500">No setup runs yet. Start one using the form.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection