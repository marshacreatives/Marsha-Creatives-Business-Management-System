<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\RunWordPressSetup;
use App\Models\WordPressSite;
use App\Services\WordPress\WordPressClient;
use App\Services\WordPress\WordPressException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\View\View;

class WordPressSetupController extends Controller
{
    public function index(): View
    {
        $sites = WordPressSite::with('creator')->latest()->get();

        return view('admin.wordpress.index', compact('sites'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'site_url' => 'required|string|max:255',
            'username' => 'required|string|max:255',
            'password' => 'required|string|max:255',
        ]);

        try {
            $siteUrl = WordPressClient::normalizeUrl($validated['site_url']);
        } catch (WordPressException $e) {
            return back()
                ->withInput()
                ->withErrors(['site_url' => $e->getMessage()]);
        }

        $site = WordPressSite::create([
            'site_url' => $siteUrl,
            'status' => 'pending',
            'steps_log' => [],
            'created_by' => auth()->id(),
        ]);

        RunWordPressSetup::dispatch($site, $validated['username'], Crypt::encryptString($validated['password']));

        return redirect()
            ->route('admin.wordpress.show', $site)
            ->with('success', 'WordPress setup queued. The page will follow its progress automatically.');
    }

    public function show(WordPressSite $site): View
    {
        return view('admin.wordpress.show', compact('site'));
    }

    public function destroy(WordPressSite $site): RedirectResponse
    {
        $site->delete();

        return redirect()
            ->route('admin.wordpress.index')
            ->with('success', 'Setup record deleted.');
    }
}
