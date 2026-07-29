@extends('layouts.app')
@section('title', 'Register Employee')

@section('content')
<div class="mb-8">
    <h1 class="text-3xl font-bold text-gray-800">Register Employee</h1>
    <p class="text-gray-500 mt-1">Create a new employee account</p>
</div>

<div class="bg-white rounded-lg shadow p-6 max-w-2xl">
    <form method="POST" action="{{ route('admin.users.store') }}">
        @csrf

        <div class="mb-4">
            <label class="block text-gray-700 text-sm font-medium mb-2">Full Name</label>
            <input type="text" name="name" value="{{ old('name') }}" required
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
            @error('name') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
        </div>

        <div class="mb-4">
            <label class="block text-gray-700 text-sm font-medium mb-2">Email Address</label>
            <input type="email" name="email" value="{{ old('email') }}" required
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
            @error('email') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
        </div>

        <div class="mb-4">
            <label class="block text-gray-700 text-sm font-medium mb-2">Password</label>
            <input type="password" name="password" required
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
            @error('password') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
        </div>

        <div class="mb-6">
            <label class="block text-gray-700 text-sm font-medium mb-2">Confirm Password</label>
            <input type="password" name="password_confirmation" required
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>

        <div class="flex space-x-3">
            <button type="submit" class="bg-green-600 text-white px-6 py-2 rounded-md hover:bg-green-700 transition font-medium">Register Employee</button>
            <a href="{{ route('admin.users.index') }}" class="bg-gray-300 text-gray-700 px-6 py-2 rounded-md hover:bg-gray-400 transition font-medium">Cancel</a>
        </div>
    </form>
</div>
@endsection
