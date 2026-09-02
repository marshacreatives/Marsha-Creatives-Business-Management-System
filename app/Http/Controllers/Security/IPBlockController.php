<?php

namespace App\Http\Controllers\Security;

use App\Http\Controllers\Controller;
use App\Models\BlockedIP;
use App\Services\TelegramService;
use Illuminate\Http\Request;

class IPBlockController extends Controller
{
    public function __construct(
        protected TelegramService $telegram,
    ) {}

    public function index(Request $request)
    {
        $query = BlockedIP::query();

        if ($request->filled('status') && $request->input('status') !== 'all') {
            $active = $request->input('status') === 'active';
            $query->where('is_active', $active);
        }

        if ($request->filled('ip')) {
            $query->where('ip', 'like', "%{$request->input('ip')}%");
        }

        $blocks = $query->latest('blocked_at')->paginate(25)->withQueryString();

        return view('security.blocked-ips', compact('blocks'));
    }

    public function block(Request $request)
    {
        $request->validate([
            'ip' => 'required|ip',
            'reason' => 'required|string|max:500',
        ]);

        $blocked = BlockedIP::firstOrNew(['ip' => $request->ip]);

        if ($blocked->is_active) {
            return redirect()->route('security.blocks.index')->with('error', 'IP is already blocked.');
        }

        $blocked->ip = $request->ip;
        $blocked->reason = $request->reason;
        $blocked->blocked_by = auth()->user()->name;
        $blocked->is_permanent = true;
        $blocked->blocked_at = now();
        $blocked->is_active = true;
        $blocked->save();

        // Note: firewall-level blocking must be done on the server. This records
        // the block in the dashboard and notifies via Telegram. For automatic
        // firewall sync, use the agent's ip_blocker module.
        $this->telegram->sendBlocked($request->ip, $request->reason);

        return redirect()->route('security.blocks.index')->with('success', "IP {$request->ip} blocked.");
    }

    public function unblock(BlockedIP $block)
    {
        $block->unblock();
        $this->telegram->sendUnblocked($block->ip);
        return redirect()->route('security.blocks.index')->with('success', "IP {$block->ip} unblocked.");
    }

    public function destroy(BlockedIP $block)
    {
        $block->delete();
        return redirect()->route('security.blocks.index')->with('success', 'Block record deleted.');
    }
}
