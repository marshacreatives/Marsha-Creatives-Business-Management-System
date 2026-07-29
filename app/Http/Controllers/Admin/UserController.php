<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function index()
    {
        $employees = User::where('role', 'employee')
            ->withCount('assignedJobs')
            ->latest()
            ->get();

        return view('admin.users.index', compact('employees'));
    }

    public function create()
    {
        return view('admin.users.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:8|confirmed',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => 'employee',
        ]);

        Activity::create([
            'user_id' => auth()->id(),
            'type' => 'user_registered',
            'description' => "Registered new employee '{$user->name}'",
            'subject_id' => $user->id,
            'subject_type' => User::class,
        ]);

        return redirect()->route('admin.users.index')->with('success', 'Employee registered successfully.');
    }

    public function edit(User $user)
    {
        return view('admin.users.edit', compact('user'));
    }

    public function update(Request $request, User $user)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'password' => 'nullable|min:8|confirmed',
        ]);

        $data = [
            'name' => $request->name,
            'email' => $request->email,
        ];

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $user->update($data);

        Activity::create([
            'user_id' => auth()->id(),
            'type' => 'user_updated',
            'description' => "Updated employee account for '{$user->name}'",
            'subject_id' => $user->id,
            'subject_type' => User::class,
        ]);

        return redirect()->route('admin.users.index')->with('success', 'Employee updated successfully.');
    }
}
