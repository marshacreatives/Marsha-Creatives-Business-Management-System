<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AgentAction;
use App\Models\AgentLog;
use App\Models\BlockedIP;
use App\Models\SecurityAlert;
use App\Models\ServerStatus;
use App\Services\TelegramService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AgentController extends Controller
{
    public function __construct(
        protected TelegramService $telegram,
    ) {}

    /**
     * Receive a security alert from the agent.
     */
    public function storeAlert(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'server_name' => 'required|string',
            'type' => 'required|string',
            'severity' => 'required|in:critical,high,medium,low,info',
            'source_ip' => 'nullable|ip',
            'source_port' => 'nullable|int',
            'destination_port' => 'nullable|int',
            'description' => 'required|string',
            'raw_log' => 'nullable|string',
            'action_taken' => 'nullable|string',
            'occurred_at' => 'required|date',
        ]);

        try {
            $alert = SecurityAlert::create([
                'type' => $validated['type'],
                'severity' => $validated['severity'],
                'source_ip' => $validated['source_ip'] ?? null,
                'source_port' => $validated['source_port'] ?? null,
                'destination_port' => $validated['destination_port'] ?? null,
                'description' => $validated['description'],
                'raw_log' => $validated['raw_log'] ?? null,
                'action_taken' => $validated['action_taken'] ?? null,
                'occurred_at' => $validated['occurred_at'],
            ]);

            // NOTE: Telegram notifications are handled by the agent itself
            // (hosting-agent/reporter.py), which sends alerts directly to
            // Telegram. We deliberately do NOT block this request on a
            // synchronous outbound Telegram call here so the agent API stays
            // fast and reliable even if Telegram is slow or unreachable.

            return response()->json([
                'success' => true,
                'alert_id' => $alert->id,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Receive a server status snapshot from the agent.
     */
    public function storeStatus(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'server_name' => 'required|string',
            'cpu_usage' => 'nullable|numeric',
            'ram_usage' => 'nullable|numeric',
            'ram_total_gb' => 'nullable|numeric',
            'ram_used_gb' => 'nullable|numeric',
            'disk_usage' => 'nullable|numeric',
            'disk_total_gb' => 'nullable|numeric',
            'disk_used_gb' => 'nullable|numeric',
            'load_average_1' => 'nullable|numeric',
            'load_average_5' => 'nullable|numeric',
            'load_average_15' => 'nullable|numeric',
            'active_connections' => 'nullable|int',
            'total_processes' => 'nullable|int',
            'uptime_seconds' => 'nullable|int',
            'services_status' => 'nullable|array',
        ]);

        try {
            ServerStatus::create([
                'server_name' => $validated['server_name'],
                'cpu_usage' => $validated['cpu_usage'] ?? 0,
                'ram_usage' => $validated['ram_usage'] ?? 0,
                'ram_total_gb' => $validated['ram_total_gb'] ?? 0,
                'ram_used_gb' => $validated['ram_used_gb'] ?? 0,
                'disk_usage' => $validated['disk_usage'] ?? 0,
                'disk_total_gb' => $validated['disk_total_gb'] ?? 0,
                'disk_used_gb' => $validated['disk_used_gb'] ?? 0,
                'load_average_1' => $validated['load_average_1'] ?? 0,
                'load_average_5' => $validated['load_average_5'] ?? 0,
                'load_average_15' => $validated['load_average_15'] ?? 0,
                'active_connections' => $validated['active_connections'] ?? 0,
                'total_processes' => $validated['total_processes'] ?? 0,
                'uptime_seconds' => $validated['uptime_seconds'] ?? 0,
                'services_status' => $validated['services_status'] ?? [],
                'checked_at' => now(),
            ]);

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Record that an IP was blocked at the firewall level.
     */
    public function storeBlock(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'ip' => 'required|ip',
            'reason' => 'required|string',
            'duration' => 'nullable|int',
            'blocked_by' => 'nullable|string',
        ]);

        try {
            $blocked = BlockedIP::firstOrNew(['ip' => $validated['ip']]);
            $blocked->ip = $validated['ip'];
            $blocked->reason = $validated['reason'];
            $blocked->blocked_by = $validated['blocked_by'] ?? 'agent';
            $blocked->is_permanent = (($validated['duration'] ?? 0) === 0);
            $blocked->blocked_at = now();
            $blocked->unblocked_at = null;
            $blocked->is_active = true;
            $blocked->save();

            $this->telegram->sendBlocked($validated['ip'], $validated['reason']);

            return response()->json(['success' => true, 'block_id' => $blocked->id]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Record that an IP was unblocked.
     */
    public function storeUnblock(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'ip' => 'required|ip',
        ]);

        try {
            $blocked = BlockedIP::where('ip', $validated['ip'])->where('is_active', true)->first();

            if ($blocked) {
                $blocked->unblock();
                $this->telegram->sendUnblocked($validated['ip']);
            }

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Receive an agent execution log entry.
     */
    public function storeLog(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'check_type' => 'required|string',
            'status' => 'required|string|in:success,failed,warning',
            'alerts_found' => 'nullable|int',
            'ips_blocked' => 'nullable|int',
            'details' => 'nullable|string',
            'execution_time_ms' => 'nullable|numeric',
            'started_at' => 'required|date',
        ]);

        try {
            AgentLog::create([
                'check_type' => $validated['check_type'],
                'status' => $validated['status'],
                'alerts_found' => $validated['alerts_found'] ?? 0,
                'ips_blocked' => $validated['ips_blocked'] ?? 0,
                'details' => $validated['details'] ?? null,
                'execution_time_ms' => $validated['execution_time_ms'] ?? null,
                'started_at' => $validated['started_at'],
            ]);

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Test endpoint to verify the agent can reach the dashboard.
     */
    public function ping(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'time' => now()->toIso8601String(),
        ]);
    }

    /**
     * Record a discrete agent action (block_ip, unblock_ip, kill_process,
     * quarantine_file, etc.) for the audit trail. Every action the agent
     * takes is posted here so the dashboard has a complete record.
     */
    public function storeAction(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'server_name' => 'nullable|string',
            'action' => 'required|string',
            'resource' => 'nullable|string',
            'severity' => 'nullable|string|in:critical,high,medium,low,info',
            'status' => 'nullable|string',
            'description' => 'nullable|string',
            'details' => 'nullable|string',
            'performed_at' => 'required|date',
        ]);

        try {
            AgentAction::create([
                'server_name' => $validated['server_name'] ?? null,
                'action' => $validated['action'],
                'resource' => $validated['resource'] ?? null,
                'severity' => $validated['severity'] ?? 'info',
                'status' => $validated['status'] ?? 'completed',
                'description' => $validated['description'] ?? null,
                'details' => $validated['details'] ?? null,
                'performed_at' => $validated['performed_at'],
            ]);

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
