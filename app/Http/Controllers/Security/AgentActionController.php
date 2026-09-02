<?php

namespace App\Http\Controllers\Security;

use App\Http\Controllers\Controller;
use App\Models\AgentAction;
use Illuminate\Http\Request;

class AgentActionController extends Controller
{
    public function index(Request $request)
    {
        $query = AgentAction::query();

        if ($request->filled('action') && $request->input('action') !== 'all') {
            $query->where('action', $request->input('action'));
        }

        if ($request->filled('severity') && $request->input('severity') !== 'all') {
            $query->where('severity', $request->input('severity'));
        }

        if ($request->filled('resource')) {
            $query->where('resource', 'like', '%' . $request->input('resource') . '%');
        }

        if ($request->filled('date_from')) {
            $query->where('performed_at', '>=', $request->input('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->where('performed_at', '<=', $request->input('date_to') . ' 23:59:59');
        }

        $actions = $query->latest('performed_at')->paginate(25)->withQueryString();

        $actionTypes = AgentAction::distinct('action')->pluck('action')->sort();
        $severities = ['critical', 'high', 'medium', 'low', 'info'];

        return view('security.agent-actions', compact('actions', 'actionTypes', 'severities'));
    }
}
