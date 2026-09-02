<?php

namespace App\Http\Controllers\Security;

use App\Http\Controllers\Controller;
use App\Models\ServerStatus;
use Illuminate\Http\Request;

class ServerStatusController extends Controller
{
    public function index(Request $request)
    {
        $serverName = $request->input('server', ServerStatus::latest('checked_at')->value('server_name'));

        $current = ServerStatus::forServer($serverName)->latest('checked_at')->first();

        $history = ServerStatus::forServer($serverName)
            ->latest('checked_at')
            ->limit(48)
            ->get()
            ->reverse()
            ->values();

        $servers = ServerStatus::distinct('server_name')->pluck('server_name');

        return view('security.server-status', compact('current', 'history', 'servers', 'serverName'));
    }
}
