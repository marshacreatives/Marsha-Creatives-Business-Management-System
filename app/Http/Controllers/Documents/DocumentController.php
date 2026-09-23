<?php

namespace App\Http\Controllers\Documents;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\Document;
use App\Models\DocumentItem;
use App\Models\Item;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DocumentController extends Controller
{
    protected function base(): string
    {
        return request()->routeIs('admin.*') ? 'admin' : 'employee';
    }

    public function index(Request $request): View
    {
        $type = in_array($request->get('type'), ['invoice', 'quote', 'receipt'], true)
            ? $request->get('type')
            : 'invoice';

        $query = Document::with(['items', 'creator'])->where('type', $type);

        if (! auth()->user()->isAdmin()) {
            $query->where('created_by', auth()->id());
        }

        $documents = $query->latest()->get();
        $items = Item::orderBy('title')->get();
        $base = $this->base();

        return view('documents.documents.index', compact('documents', 'items', 'type', 'base'));
    }

    public function create(Request $request): View
    {
        $type = in_array($request->get('type'), ['invoice', 'quote', 'receipt'], true)
            ? $request->get('type')
            : 'invoice';

        $items = Item::orderBy('title')->get();
        $base = $this->base();

        return view('documents.documents.form', compact('type', 'items', 'base'))->with('document', null);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateDocument($request);

        $document = DB::transaction(function () use ($data) {
            $document = Document::create([
                'type' => $data['type'],
                'number' => Document::nextNumber($data['type']),
                'client_name' => $data['client_name'],
                'issue_date' => $data['issue_date'],
                'discount' => $data['discount'],
                'notes' => $data['notes'],
                'created_by' => auth()->id(),
            ]);

            $this->saveItems($document, $data['items']);

            return $document;
        });

        Activity::create([
            'user_id' => auth()->id(),
            'type' => 'document_created',
            'description' => "Created {$document->type_label} {$document->number} for {$document->client_name}",
            'subject_id' => $document->id,
            'subject_type' => Document::class,
        ]);

        return redirect()->route($this->base().'.documents.index', ['type' => $document->type])
            ->with('success', "{$document->type_label} {$document->number} created successfully.");
    }

    public function edit(Document $document): View
    {
        $this->authorizeDocument($document);

        $document->load('items');
        $items = Item::orderBy('title')->get();
        $base = $this->base();

        return view('documents.documents.form', compact('document', 'items', 'base'));
    }

    public function update(Request $request, Document $document): RedirectResponse
    {
        $this->authorizeDocument($document);

        $data = $this->validateDocument($request);

        DB::transaction(function () use ($document, $data) {
            $document->update([
                'client_name' => $data['client_name'],
                'issue_date' => $data['issue_date'],
                'discount' => $data['discount'],
                'notes' => $data['notes'],
            ]);

            $document->items()->delete();
            $this->saveItems($document, $data['items']);
        });

        Activity::create([
            'user_id' => auth()->id(),
            'type' => 'document_updated',
            'description' => "Updated {$document->type_label} {$document->number} for {$document->client_name}",
            'subject_id' => $document->id,
            'subject_type' => Document::class,
        ]);

        return redirect()->route($this->base().'.documents.index', ['type' => $document->type])
            ->with('success', "{$document->type_label} {$document->number} updated successfully.");
    }

    public function destroy(Document $document): RedirectResponse
    {
        $this->authorizeDocument($document);

        $label = "{$document->type_label} {$document->number}";

        Activity::create([
            'user_id' => auth()->id(),
            'type' => 'document_deleted',
            'description' => "Deleted {$label} for {$document->client_name}",
        ]);

        $document->delete();

        return redirect()->route($this->base().'.documents.index', ['type' => $document->type])
            ->with('success', "{$label} deleted successfully.");
    }

    public function downloadPdf(Document $document)
    {
        $this->authorizeDocument($document);

        $document->load('items');

        @ini_set('memory_limit', '512M');
        @set_time_limit(120);

        $pdf = Pdf::loadView('documents.documents.pdf', compact('document'))->setPaper('a4');

        return $pdf->download($document->number.'.pdf');
    }

    public function quickStore(Request $request): JsonResponse
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
        ]);

        $item = Item::create($data);

        Activity::create([
            'user_id' => auth()->id(),
            'type' => 'item_created',
            'description' => "Created item '{$item->title}' (Price: KSh ".number_format((float) $item->price, 2).')',
            'subject_id' => $item->id,
            'subject_type' => Item::class,
        ]);

        return response()->json([
            'id' => $item->id,
            'title' => $item->title,
            'description' => $item->description,
            'price' => (float) $item->price,
        ]);
    }

    private function validateDocument(Request $request): array
    {
        return $request->validate([
            'type' => 'required|in:invoice,quote,receipt',
            'client_name' => 'required|string|max:255',
            'issue_date' => 'required|date',
            'discount' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.item_id' => 'nullable|exists:items,id',
            'items.*.title' => 'required|string|max:255',
            'items.*.description' => 'nullable|string',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.quantity' => 'required|integer|min:1',
        ]);
    }

    private function saveItems(Document $document, array $items): void
    {
        $subtotal = 0.0;

        foreach ($items as $line) {
            $quantity = (int) $line['quantity'];
            $unitPrice = (float) $line['unit_price'];
            $lineTotal = round($unitPrice * $quantity, 2);
            $subtotal += $lineTotal;

            DocumentItem::create([
                'document_id' => $document->id,
                'item_id' => $line['item_id'] ?: null,
                'title' => $line['title'],
                'description' => $line['description'],
                'unit_price' => $unitPrice,
                'quantity' => $quantity,
                'line_total' => $lineTotal,
            ]);
        }

        $document->update([
            'total' => round($subtotal - (float) $document->discount, 2),
        ]);
    }

    private function authorizeDocument(Document $document): void
    {
        if (! auth()->user()->isAdmin() && (int) $document->created_by !== auth()->id()) {
            abort(403, 'You do not have access to this document.');
        }
    }
}
