<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\Item;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DocumentsTest extends TestCase
{
    use RefreshDatabase;

    public function test_employee_cannot_access_admin_documents_routes(): void
    {
        $employee = User::factory()->create(['role' => 'employee']);

        $this->actingAs($employee)->get(route('admin.documents.index'))->assertForbidden();
        $this->actingAs($employee)->get(route('admin.items.index'))->assertForbidden();
    }

    public function test_admin_can_create_items(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->post(route('admin.items.store'), [
            'title' => 'Website Design',
            'description' => 'Full design package',
            'price' => '25000.00',
        ]);

        $response->assertRedirect(route('admin.items.index'));

        $this->assertDatabaseHas('items', [
            'title' => 'Website Design',
            'price' => '25000.00',
        ]);
    }

    public function test_document_numbers_autoincrement_per_type(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $item = Item::create(['title' => 'Design', 'price' => 100]);

        $this->createDocument($admin, 'invoice', $item, 'Acme Corp');
        $this->createDocument($admin, 'quote', $item, 'Beta Ltd');
        $this->createDocument($admin, 'receipt', $item, 'Gamma Inc');
        $this->createDocument($admin, 'invoice', $item, 'Delta Co');

        $this->assertDatabaseHas('documents', ['type' => 'invoice', 'number' => 'INV-000001']);
        $this->assertDatabaseHas('documents', ['type' => 'quote', 'number' => 'QT-000001']);
        $this->assertDatabaseHas('documents', ['type' => 'receipt', 'number' => 'RC-000001']);
        $this->assertDatabaseHas('documents', ['type' => 'invoice', 'number' => 'INV-000002']);
        $this->assertDatabaseMissing('documents', ['type' => 'quote', 'number' => 'QT-000002']);
    }

    public function test_totals_are_computed_with_discount(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $item = Item::create(['title' => 'Consulting', 'price' => 100]);

        $response = $this->actingAs($admin)->post(route('admin.documents.store'), $this->payload(
            'invoice', 'Acme Corp', $item, 100, 2, 10
        ));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $document = Document::where('number', 'INV-000001')->firstOrFail();

        $this->assertSame('10.00', (string) $document->discount);
        $this->assertSame(190.0, (float) $document->total);
        $this->assertCount(1, $document->items);
        $this->assertSame(2, $document->items->first()->quantity);
    }

    public function test_invoice_line_totals_and_discount(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $item = Item::create(['title' => 'Hosting', 'price' => 50]);

        $this->createDocument($admin, 'invoice', $item, 'Acme Corp');

        $document = Document::where('number', 'INV-000001')->firstOrFail();

        $this->assertSame(50.0, (float) $document->subtotal);
        $this->assertSame(50.0, (float) $document->total);
        $this->assertSame('50.00', (string) $document->items->first()->line_total);
    }

    public function test_employee_only_sees_own_documents(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $empA = User::factory()->create(['role' => 'employee']);
        $empB = User::factory()->create(['role' => 'employee']);
        $item = Item::create(['title' => 'Design', 'price' => 100]);

        $docA = $this->createDocument($empA, 'invoice', $item, 'Alpha Client');
        $docB = $this->createDocument($empB, 'invoice', $item, 'Beta Client');

        $response = $this->actingAs($empA)->get(route('employee.documents.index', ['type' => 'invoice']));

        $response->assertOk();
        $response->assertSee('Alpha Client');
        $response->assertDontSee('Beta Client');

        $this->actingAs($empA)->get(route('employee.documents.edit', $docB))->assertForbidden();
        $this->actingAs($empA)->get(route('employee.documents.pdf', $docB))->assertForbidden();

        $response = $this->actingAs($admin)->get(route('admin.documents.index', ['type' => 'invoice']));
        $response->assertOk();
        $response->assertSee('Alpha Client');
        $response->assertSee('Beta Client');
    }

    public function test_pdf_download_returns_valid_pdf(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $item = Item::create(['title' => 'Design', 'price' => 100]);

        $document = $this->createDocument($admin, 'quote', $item, 'Acme Corp');

        $response = $this->actingAs($admin)->get(route('admin.documents.pdf', $document));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringContainsString('%PDF', $response->baseResponse->getContent());
    }

    public function test_update_preserves_number_and_updates_totals(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $item = Item::create(['title' => 'Design', 'price' => 100]);

        $document = $this->createDocument($admin, 'invoice', $item, 'Acme Corp');

        $response = $this->actingAs($admin)->put(route('admin.documents.update', $document), $this->payload(
            'invoice', 'Renamed Client', $item, 100, 3, 0
        ));

        $response->assertRedirect();

        $document->refresh();

        $this->assertSame('INV-000001', $document->number);
        $this->assertSame('Renamed Client', $document->client_name);
        $this->assertSame(300.0, (float) $document->total);
    }

    public function test_quick_add_item_returns_json(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->postJson(route('admin.documents.items.quick'), [
            'title' => 'Social Media Pack',
            'description' => 'Monthly management',
            'price' => '15000.50',
        ]);

        $response->assertOk()
            ->assertJsonPath('title', 'Social Media Pack')
            ->assertJsonPath('price', 15000.5);

        $this->assertDatabaseHas('items', ['title' => 'Social Media Pack']);
    }

    public function test_document_requires_at_least_one_item(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->post(route('admin.documents.store'), [
            'type' => 'invoice',
            'client_name' => 'Acme Corp',
            'issue_date' => '2026-09-23',
            'discount' => '0',
            'items' => [],
        ]);

        $response->assertSessionHasErrors('items');
        $this->assertDatabaseCount('documents', 0);
    }

    public function test_create_and_edit_pages_render(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $employee = User::factory()->create(['role' => 'employee']);
        $item = Item::create(['title' => 'Design', 'price' => 100]);

        foreach (['invoice', 'quote', 'receipt'] as $type) {
            $this->actingAs($admin)->get(route('admin.documents.create', ['type' => $type]))->assertOk();
            $this->actingAs($employee)->get(route('employee.documents.create', ['type' => $type]))->assertOk();
        }

        $this->actingAs($admin)->get(route('admin.items.create'))->assertOk();
        $this->actingAs($admin)->get(route('admin.items.index'))->assertOk();

        $document = $this->createDocument($admin, 'invoice', $item, 'Acme Corp');
        $this->actingAs($admin)->get(route('admin.documents.edit', $document))->assertOk()
            ->assertSee('INV-000001')
            ->assertSee('Acme Corp');

        $this->actingAs($employee)->get(route('employee.items.index'))->assertOk();
        $this->actingAs($employee)->get(route('employee.items.create'))->assertOk();
    }

    private function createDocument(User $user, string $type, Item $item, string $client): Document
    {
        $this->actingAs($user)
            ->post(route($user->isAdmin() ? 'admin.documents.store' : 'employee.documents.store'), $this->payload($type, $client, $item, (float) $item->price, 1, 0))
            ->assertRedirect();

        return Document::latest('id')->first();
    }

    private function payload(string $type, string $client, Item $item, float $price, int $quantity, float $discount): array
    {
        return [
            'type' => $type,
            'client_name' => $client,
            'issue_date' => '2026-09-23',
            'discount' => (string) $discount,
            'notes' => 'Thanks',
            'items' => [
                ['item_id' => (string) $item->id, 'title' => $item->title, 'description' => 'Line description', 'unit_price' => (string) $price, 'quantity' => (string) $quantity],
            ],
        ];
    }
}
