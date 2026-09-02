<?php

namespace App\Http\Controllers\Security;

use App\Http\Controllers\Controller;
use App\Models\SecurityAlert;
use Illuminate\Http\Request;

class AlertController extends Controller
{
    public function index(Request $request)
    {
        $query = SecurityAlert::query();

        if ($request->filled('type') && $request->input('type') !== 'all') {
            $query->where('type', $request->input('type'));
        }

        if ($request->filled('severity') && $request->input('severity') !== 'all') {
            $query->where('severity', $request->input('severity'));
        }

        if ($request->filled('ip')) {
            $query->where('source_ip', $request->input('ip'));
        }

        if ($request->boolean('unresolved_only')) {
            $query->where('is_resolved', false);
        }

        if ($request->filled('date_from')) {
            $query->where('occurred_at', '>=', $request->input('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->where('occurred_at', '<=', $request->input('date_to') . ' 23:59:59');
        }

        $alerts = $query->latest('occurred_at')->paginate(25)->withQueryString();

        $types = SecurityAlert::distinct('type')->pluck('type')->sort();
        $severities = ['critical', 'high', 'medium', 'low', 'info'];

        return view('security.alerts', compact('alerts', 'types', 'severities'));
    }

    public function show(SecurityAlert $alert)
    {
        return view('security.alert-show', compact('alert'));
    }

    public function resolve(SecurityAlert $alert)
    {
        $alert->update(['is_resolved' => true]);
        return back()->with('success', 'Alert marked as resolved.');
    }

    public function reopen(SecurityAlert $alert)
    {
        $alert->update(['is_resolved' => false]);
        return back()->with('success', 'Alert reopened.');
    }

    public function destroy(SecurityAlert $alert)
    {
        $alert->delete();
        return redirect()->route('security.alerts.index')->with('success', 'Alert deleted.');
    }
}
