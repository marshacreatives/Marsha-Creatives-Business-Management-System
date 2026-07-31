<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Marsha Creatives') - Business Management</title>
    <link rel="icon" type="image/png" href="{{ asset('images/favicon.png') }}">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .gradient-bg { background: linear-gradient(135deg, #1e3a5f 0%, #2d5a87 100%); }
        .card-hover { transition: transform 0.2s, box-shadow 0.2s; }
        .card-hover:hover { transform: translateY(-2px); box-shadow: 0 10px 25px -5px rgba(0,0,0,0.1); }
    </style>
</head>
<body class="bg-gray-100 min-h-screen">
    <nav class="gradient-bg shadow-lg">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <img src="{{ asset('images/header_logo.png') }}" alt="Marsha Creatives" class="h-12">
                    <div class="hidden md:flex ml-10 space-x-1">
                        @if(auth()->user()->isAdmin())
                            <a href="{{ route('admin.dashboard') }}" class="text-gray-300 hover:text-white hover:bg-white/10 px-3 py-2 rounded-md text-sm font-medium {{ request()->routeIs('admin.dashboard') ? 'bg-white/20 text-white' : '' }}">Dashboard</a>
                            <a href="{{ route('admin.balance') }}" class="text-gray-300 hover:text-white hover:bg-white/10 px-3 py-2 rounded-md text-sm font-medium {{ request()->routeIs('admin.balance*') ? 'bg-white/20 text-white' : '' }}">Balance</a>
                            <a href="{{ route('admin.jobs.index') }}" class="text-gray-300 hover:text-white hover:bg-white/10 px-3 py-2 rounded-md text-sm font-medium {{ request()->routeIs('admin.jobs*') ? 'bg-white/20 text-white' : '' }}">Jobs</a>
                            <a href="{{ route('admin.users.index') }}" class="text-gray-300 hover:text-white hover:bg-white/10 px-3 py-2 rounded-md text-sm font-medium {{ request()->routeIs('admin.users*') ? 'bg-white/20 text-white' : '' }}">Employees</a>
                            <a href="{{ route('admin.financials') }}" class="text-gray-300 hover:text-white hover:bg-white/10 px-3 py-2 rounded-md text-sm font-medium {{ request()->routeIs('admin.financials') ? 'bg-white/20 text-white' : '' }}">Financials</a>
                        @else
                            <a href="{{ route('employee.dashboard') }}" class="text-gray-300 hover:text-white hover:bg-white/10 px-3 py-2 rounded-md text-sm font-medium {{ request()->routeIs('employee.dashboard') ? 'bg-white/20 text-white' : '' }}">Dashboard</a>
                            <a href="{{ route('employee.jobs.index') }}" class="text-gray-300 hover:text-white hover:bg-white/10 px-3 py-2 rounded-md text-sm font-medium {{ request()->routeIs('employee.jobs*') ? 'bg-white/20 text-white' : '' }}">My Jobs</a>
                            <a href="{{ route('employee.jobs.create') }}" class="text-gray-300 hover:text-white hover:bg-white/10 px-3 py-2 rounded-md text-sm font-medium {{ request()->routeIs('employee.jobs.create') ? 'bg-white/20 text-white' : '' }}">Log Job</a>
                            <a href="{{ route('employee.jobs.history') }}" class="text-gray-300 hover:text-white hover:bg-white/10 px-3 py-2 rounded-md text-sm font-medium {{ request()->routeIs('employee.jobs.history') ? 'bg-white/20 text-white' : '' }}">History</a>
                        @endif
                    </div>
                </div>
                <div class="flex items-center space-x-4">
                    <span class="text-gray-300 text-sm">{{ auth()->user()->name }}</span>
                    <span class="px-2 py-1 text-xs rounded-full {{ auth()->user()->isAdmin() ? 'bg-yellow-500 text-white' : 'bg-green-500 text-white' }}">{{ ucfirst(auth()->user()->role) }}</span>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="text-gray-300 hover:text-white text-sm">Logout</button>
                    </form>
                </div>
            </div>
        </div>
    </nav>

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        @if(session('success'))
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
                {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
                {{ session('error') }}
            </div>
        @endif

        @yield('content')
    </main>
</body>
</html>
