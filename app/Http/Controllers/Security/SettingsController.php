<?php

namespace App\Http\Controllers\Security;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function index()
    {
        $settings = Setting::all()->keyBy('key');

        return view('security.settings', compact('settings'));
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'agent_api_key' => 'nullable|string',
            'telegram_enabled' => 'nullable|boolean',
            'telegram_min_level' => 'nullable|in:critical,high,medium,low,info',
            'alert_retention_days' => 'nullable|integer|min:1|max:365',
            'block_duration_hours' => 'nullable|integer|min:1|max:720',
        ]);

        $updates = [
            'alert_retention_days' => $request->input('alert_retention_days', 30),
            'block_duration_hours' => $request->input('block_duration_hours', 24),
            'telegram_min_level' => $request->input('telegram_min_level', 'medium'),
        ];

        foreach ($updates as $key => $value) {
            Setting::setValue($key, $value, 'integer');
        }

        // Telegram enabled is handled via env config on most setups; store a flag too
        Setting::setValue('telegram_enabled', $request->boolean('telegram_enabled'), 'boolean');

        if ($request->filled('agent_api_key')) {
            Setting::setValue('agent_api_key', $request->input('agent_api_key'));
        }

        return redirect()->route('security.settings')->with('success', 'Settings updated successfully.');
    }
}
