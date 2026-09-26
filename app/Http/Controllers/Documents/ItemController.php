<?php

namespace App\Http\Controllers\Documents;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\Item;
use App\Services\NotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ItemController extends Controller
{
    protected function base(): string
    {
        return request()->routeIs('admin.*') ? 'admin' : 'employee';
    }

    public function index(): View
    {
        $items = Item::latest()->get();
        $base = $this->base();

        return view('documents.items.index', compact('items', 'base'));
    }

    public function create(): View
    {
        $base = $this->base();

        return view('documents.items.form', compact('base'))->with('item', null);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateItem($request);

        $item = Item::create($data);

        Activity::create([
            'user_id' => auth()->id(),
            'type' => 'item_created',
            'description' => "Created item '{$item->title}' (Price: KSh ".number_format((float) $item->price, 2).')',
            'subject_id' => $item->id,
            'subject_type' => Item::class,
        ]);

        $this->notifyAdminsAboutItem($item);

        return redirect()->route($this->base().'.items.index')->with('success', 'Item created successfully.');
    }

    public function edit(Item $item): View
    {
        $base = $this->base();

        return view('documents.items.form', compact('item', 'base'));
    }

    public function update(Request $request, Item $item): RedirectResponse
    {
        $data = $this->validateItem($request);

        $item->update($data);

        Activity::create([
            'user_id' => auth()->id(),
            'type' => 'item_updated',
            'description' => "Updated item '{$item->title}'",
            'subject_id' => $item->id,
            'subject_type' => Item::class,
        ]);

        return redirect()->route($this->base().'.items.index')->with('success', 'Item updated successfully.');
    }

    public function destroy(Item $item): RedirectResponse
    {
        Activity::create([
            'user_id' => auth()->id(),
            'type' => 'item_deleted',
            'description' => "Deleted item '{$item->title}'",
        ]);

        $item->delete();

        return redirect()->route($this->base().'.items.index')->with('success', 'Item deleted successfully.');
    }

    private function validateItem(Request $request): array
    {
        return $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
        ]);
    }

    /**
     * Items are shared across the whole agency, so a new one added by an
     * employee is worth surfacing to the admin.
     */
    private function notifyAdminsAboutItem(Item $item): void
    {
        if ($this->base() !== 'employee') {
            return;
        }

        NotificationService::notifyAdmins(
            'New item from '.auth()->user()->name,
            "'{$item->title}' was added at KSh ".number_format((float) $item->price, 2).'.',
            route('admin.items.index'),
            'document',
        );
    }
}
