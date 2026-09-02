<?php

namespace App\Http\Controllers\Security;

use App\Http\Controllers\Controller;
use App\Models\AgentAction;
use App\Models\AgentLog;
use App\Models\BlockedIP;
use App\Models\SecurityAlert;
use App\Models\ServerStatus;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $now = now();

        $stats = [
            'total_alerts' => SecurityAlert::count(),
            'alerts_today' => SecurityAlert::whereDate('occurred_at', today())->count(),
            'alerts_24h' => SecurityAlert::where('occurred_at', '>=', $now->subHours(24))->count(),
            'critical_alerts' => SecurityAlert::where('severity', 'critical')->count(),
            'high_alerts' => SecurityAlert::where('severity', 'high')->count(),
            'unresolved' => SecurityAlert::where('is_resolved', false)->count(),
            'active_blocks' => BlockedIP::where('is_active', true)->count(),
            'blocks_24h' => BlockedIP::where('blocked_at', '>=', $now->subHours(24))->count(),
        ];

        $recentAlerts = SecurityAlert::latest('occurred_at')->limit(10)->get();
        $recentBlocks = BlockedIP::where('is_active', true)->latest('blocked_at')->limit(10)->get();
        $latestStatus = ServerStatus::latest('checked_at')->first();
        $agentLogs = AgentLog::latest('started_at')->limit(8)->get();
        $recentActions = AgentAction::latest('performed_at')->limit(8)->get();

        return view('security.index', compact(
            'stats',
            'recentAlerts',
            'recentBlocks',
            'latestStatus',
            'agentLogs',
            'recentActions',
        ));
    }
}
