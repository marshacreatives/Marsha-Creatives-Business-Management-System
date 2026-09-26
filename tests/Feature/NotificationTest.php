<?php

namespace Tests\Feature;

use App\Models\CompanyBalance;
use App\Models\FundRequest;
use App\Models\ProjectJob;
use App\Models\PushSubscription;
use App\Models\User;
use App\Notifications\AppNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    /*
    |--------------------------------------------------------------------------
    | Employee actions notify the admin
    |--------------------------------------------------------------------------
    */

    public function test_employee_logging_a_job_notifies_admins(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $employee = User::factory()->create(['role' => 'employee']);

        CompanyBalance::updateBalance(500000);

        $this->actingAs($employee)
            ->post(route('employee.jobs.store'), [
                'project_name' => 'Wedding Shoot',
                'expense' => '25000',
                'cost' => '60000',
                'status' => 'in_progress',
            ])
            ->assertRedirect(route('employee.jobs.index'));

        $this->assertSame(1, $admin->notifications()->count());

        $notification = $admin->notifications()->first();
        $this->assertSame('New job logged by '.$employee->name, $notification->data['title']);
        $this->assertStringContainsString('Wedding Shoot', $notification->data['body']);
        $this->assertSame('job', $notification->data['category']);
        $this->assertNull($notification->read_at);
    }

    public function test_employee_logging_a_job_does_not_notify_the_employee(): void
    {
        $employee = User::factory()->create(['role' => 'employee']);

        CompanyBalance::updateBalance(500000);

        $this->actingAs($employee)->post(route('employee.jobs.store'), [
            'project_name' => 'Wedding Shoot',
            'expense' => '1000',
            'cost' => '2000',
            'status' => 'in_progress',
        ]);

        $this->assertSame(0, $employee->notifications()->count());
    }

    public function test_every_admin_is_notified(): void
    {
        $first = User::factory()->create(['role' => 'admin']);
        $second = User::factory()->create(['role' => 'admin']);
        $employee = User::factory()->create(['role' => 'employee']);

        CompanyBalance::updateBalance(500000);

        $this->actingAs($employee)->post(route('employee.jobs.store'), [
            'project_name' => 'Brand Refresh',
            'expense' => '5000',
            'cost' => '9000',
            'status' => 'in_progress',
        ]);

        $this->assertSame(1, $first->notifications()->count());
        $this->assertSame(1, $second->notifications()->count());
    }

    public function test_employee_requesting_funds_notifies_admins(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $employee = User::factory()->create(['role' => 'employee']);

        CompanyBalance::updateBalance(1000);

        $this->actingAs($employee)
            ->post(route('employee.jobs.store'), [
                'project_name' => 'Big Campaign',
                'expense' => '90000',
                'cost' => '120000',
                'status' => 'in_progress',
            ])
            ->assertRedirect(route('employee.jobs.create'));

        $this->assertDatabaseHas('fund_requests', ['user_id' => $employee->id, 'status' => 'pending']);
        $this->assertSame(1, $admin->notifications()->count());
        $this->assertSame('fund', $admin->notifications()->first()->data['category']);
    }

    public function test_employee_changing_a_job_status_notifies_admins(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $employee = User::factory()->create(['role' => 'employee']);

        $job = ProjectJob::create([
            'project_name' => 'Album Cover',
            'expense' => 5000,
            'cost' => 12000,
            'status' => 'in_progress',
            'assigned_to' => $employee->id,
            'created_by' => $employee->id,
        ]);

        $this->actingAs($employee)
            ->post(route('employee.jobs.update-status', $job), ['status' => 'completed'])
            ->assertRedirect();

        $this->assertSame(1, $admin->notifications()->count());
        $this->assertSame(
            'Job status updated by '.$employee->name,
            $admin->notifications()->first()->data['title']
        );
    }

    public function test_employee_creating_a_document_notifies_admins(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $employee = User::factory()->create(['role' => 'employee']);

        $this->actingAs($employee)->post(route('employee.documents.store'), [
            'type' => 'invoice',
            'client_name' => 'Acme Ltd',
            'issue_date' => '2026-01-15',
            'discount' => '0',
            'notes' => null,
            'items' => [
                ['item_id' => null, 'title' => 'Design', 'description' => null, 'unit_price' => '10000', 'quantity' => '1'],
            ],
        ])->assertRedirect();

        $this->assertDatabaseHas('documents', ['client_name' => 'Acme Ltd']);
        $this->assertSame(1, $admin->notifications()->count());
        $this->assertSame('document', $admin->notifications()->first()->data['category']);
    }

    public function test_admin_creating_a_document_does_not_notify_themselves(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->post(route('admin.documents.store'), [
            'type' => 'invoice',
            'client_name' => 'Acme Ltd',
            'issue_date' => '2026-01-15',
            'discount' => '0',
            'notes' => null,
            'items' => [
                ['item_id' => null, 'title' => 'Design', 'description' => null, 'unit_price' => '10000', 'quantity' => '1'],
            ],
        ])->assertRedirect();

        $this->assertSame(0, $admin->notifications()->count());
    }

    public function test_employee_creating_an_item_notifies_admins(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $employee = User::factory()->create(['role' => 'employee']);

        $this->actingAs($employee)->post(route('employee.items.store'), [
            'title' => 'Logo Pack',
            'description' => null,
            'price' => '15000',
        ])->assertRedirect(route('employee.items.index'));

        $this->assertSame(1, $admin->notifications()->count());
    }

    public function test_employee_quick_creating_an_item_notifies_admins(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $employee = User::factory()->create(['role' => 'employee']);

        $this->actingAs($employee)->postJson(route('employee.documents.items.quick'), [
            'title' => 'Poster',
            'description' => null,
            'price' => '2500',
        ])->assertOk();

        $this->assertSame(1, $admin->notifications()->count());
        $this->assertDatabaseHas('items', ['title' => 'Poster']);
    }

    /*
    |--------------------------------------------------------------------------
    | Admin actions notify the employee
    |--------------------------------------------------------------------------
    */

    public function test_admin_assigning_a_job_notifies_the_assignee(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $employee = User::factory()->create(['role' => 'employee']);

        CompanyBalance::updateBalance(500000);

        $this->actingAs($admin)->post(route('admin.jobs.store'), [
            'project_name' => 'Product Video',
            'expense' => '40000',
            'cost' => '90000',
            'status' => 'in_progress',
            'assigned_to' => $employee->id,
        ])->assertRedirect(route('admin.jobs.index'));

        $this->assertSame(1, $employee->notifications()->count());

        $notification = $employee->notifications()->first();
        $this->assertSame('New job assigned to you', $notification->data['title']);
        $this->assertStringContainsString('Product Video', $notification->data['body']);
    }

    public function test_admin_assigning_a_job_does_not_notify_themselves(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $employee = User::factory()->create(['role' => 'employee']);

        CompanyBalance::updateBalance(500000);

        $this->actingAs($admin)->post(route('admin.jobs.store'), [
            'project_name' => 'Product Video',
            'expense' => '40000',
            'cost' => '90000',
            'status' => 'in_progress',
            'assigned_to' => $employee->id,
        ]);

        $this->assertSame(0, $admin->notifications()->count());
    }

    public function test_reassigning_a_job_notifies_both_employees(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $first = User::factory()->create(['role' => 'employee']);
        $second = User::factory()->create(['role' => 'employee']);

        $job = ProjectJob::create([
            'project_name' => 'Launch Video',
            'expense' => 10000,
            'cost' => 25000,
            'status' => 'in_progress',
            'assigned_to' => $first->id,
            'created_by' => $admin->id,
        ]);

        $this->actingAs($admin)->put(route('admin.jobs.update', $job), [
            'project_name' => 'Launch Video',
            'expense' => '10000',
            'cost' => '25000',
            'status' => 'in_progress',
            'assigned_to' => $second->id,
        ])->assertRedirect(route('admin.jobs.index'));

        $this->assertSame(1, $second->notifications()->count());
        $this->assertSame('Job assigned to you', $second->notifications()->first()->data['title']);

        $this->assertSame(1, $first->notifications()->count());
        $this->assertSame('Job reassigned', $first->notifications()->first()->data['title']);
    }

    public function test_updating_a_job_notifies_the_assignee(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $employee = User::factory()->create(['role' => 'employee']);

        $job = ProjectJob::create([
            'project_name' => 'Launch Video',
            'expense' => 10000,
            'cost' => 25000,
            'status' => 'in_progress',
            'assigned_to' => $employee->id,
            'created_by' => $admin->id,
        ]);

        $this->actingAs($admin)->put(route('admin.jobs.update', $job), [
            'project_name' => 'Launch Video',
            'expense' => '10000',
            'cost' => '25000',
            'status' => 'completed',
            'assigned_to' => $employee->id,
        ])->assertRedirect();

        $this->assertSame(1, $employee->notifications()->count());
        $this->assertSame('Your job was updated', $employee->notifications()->first()->data['title']);
    }

    public function test_deleting_a_job_notifies_the_assignee(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $employee = User::factory()->create(['role' => 'employee']);

        $job = ProjectJob::create([
            'project_name' => 'Cancelled Shoot',
            'expense' => 5000,
            'cost' => 9000,
            'status' => 'in_progress',
            'assigned_to' => $employee->id,
            'created_by' => $admin->id,
        ]);

        $this->actingAs($admin)
            ->delete(route('admin.jobs.destroy', $job))
            ->assertRedirect(route('admin.jobs.index'));

        $this->assertSame(1, $employee->notifications()->count());
        $this->assertSame('Job removed', $employee->notifications()->first()->data['title']);
    }

    public function test_approving_a_fund_request_notifies_the_employee(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $employee = User::factory()->create(['role' => 'employee']);

        $request = FundRequest::create([
            'user_id' => $employee->id,
            'amount' => '50000',
            'message' => 'Need funds',
            'status' => 'pending',
        ]);

        $this->actingAs($admin)
            ->post(route('admin.fund-requests.approve', $request))
            ->assertRedirect();

        $this->assertSame(1, $employee->notifications()->count());

        $notification = $employee->notifications()->first();
        $this->assertSame('Fund request approved', $notification->data['title']);
        $this->assertSame('fund', $notification->data['category']);
    }

    public function test_dismissing_a_fund_request_notifies_the_employee(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $employee = User::factory()->create(['role' => 'employee']);

        $request = FundRequest::create([
            'user_id' => $employee->id,
            'amount' => '50000',
            'message' => 'Need funds',
            'status' => 'pending',
        ]);

        $this->actingAs($admin)
            ->post(route('admin.fund-requests.dismiss', $request))
            ->assertRedirect();

        $this->assertSame(1, $employee->notifications()->count());
        $this->assertSame('Fund request declined', $employee->notifications()->first()->data['title']);
    }

    /*
    |--------------------------------------------------------------------------
    | The bell endpoints
    |--------------------------------------------------------------------------
    */

    public function test_index_returns_only_the_callers_notifications(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $employee = User::factory()->create(['role' => 'employee']);
        $other = User::factory()->create(['role' => 'employee']);

        $admin->notify(new AppNotification('For admin', 'Body', 'job', '/admin/jobs'));
        $employee->notify(new AppNotification('For employee', 'Body', 'job', '/employee/jobs'));
        $other->notify(new AppNotification('For someone else', 'Body', 'job', null));

        $response = $this->actingAs($employee)->getJson(route('notifications.index'));

        $response->assertOk()
            ->assertJsonPath('unread_count', 1)
            ->assertJsonPath('notifications.0.title', 'For employee')
            ->assertJsonPath('notifications.0.url', '/employee/jobs')
            ->assertJsonPath('notifications.0.color', 'blue');

        $this->assertCount(1, $response->json('notifications'));
    }

    public function test_index_counts_only_unread(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $admin->notify(new AppNotification('One', 'Body'));
        $second = new AppNotification('Two', 'Body');
        $admin->notify($second);

        $stored = $admin->notifications()->first();
        $stored->markAsRead();

        $this->actingAs($admin)
            ->getJson(route('notifications.index'))
            ->assertOk()
            ->assertJsonPath('unread_count', 1);
    }

    public function test_a_user_cannot_read_another_users_notification(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $employee = User::factory()->create(['role' => 'employee']);

        $admin->notify(new AppNotification('Secret', 'Body'));
        $id = $admin->notifications()->first()->id;

        $this->actingAs($employee)
            ->postJson(route('notifications.read', $id))
            ->assertNotFound();

        $this->assertNull($admin->notifications()->first()->read_at);
    }

    public function test_marking_a_notification_as_read(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $admin->notify(new AppNotification('Hello', 'Body'));
        $id = $admin->notifications()->first()->id;

        $this->actingAs($admin)->postJson(route('notifications.read', $id))->assertOk();

        $this->assertNotNull($admin->notifications()->first()->read_at);
    }

    public function test_mark_all_read_only_touches_the_caller(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $employee = User::factory()->create(['role' => 'employee']);

        $admin->notify(new AppNotification('A', 'Body'));
        $admin->notify(new AppNotification('B', 'Body'));
        $employee->notify(new AppNotification('C', 'Body'));

        $this->actingAs($admin)->postJson(route('notifications.read-all'))->assertOk();

        $this->assertSame(0, $admin->unreadNotifications()->count());
        $this->assertSame(1, $employee->unreadNotifications()->count());
    }

    public function test_notifications_page_requires_authentication(): void
    {
        // auth.custom redirects rather than returning 401, even for XHR, so the
        // poll endpoint's failure mode is a redirect the JS must tolerate.
        $this->get(route('notifications.page'))->assertRedirect(route('login'));
        $this->getJson(route('notifications.index'))->assertRedirect(route('login'));
        $this->postJson(route('notifications.read-all'))->assertRedirect(route('login'));
    }

    public function test_the_notifications_page_renders(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $admin->notify(new AppNotification('Visible', 'A body'));

        $this->actingAs($admin)
            ->get(route('notifications.page'))
            ->assertOk()
            ->assertSee('Visible')
            ->assertSee('A body');
    }

    /*
    |--------------------------------------------------------------------------
    | Push subscriptions
    |--------------------------------------------------------------------------
    */

    public function test_a_user_can_subscribe_to_push(): void
    {
        $employee = User::factory()->create(['role' => 'employee']);

        $this->actingAs($employee)->postJson(route('push.subscribe'), [
            'endpoint' => 'https://fcm.googleapis.com/fcm/send/abc123',
            'keys' => ['p256dh' => 'public-key', 'auth' => 'auth-token'],
        ])->assertOk();

        $this->assertDatabaseHas('push_subscriptions', [
            'user_id' => $employee->id,
            'endpoint' => 'https://fcm.googleapis.com/fcm/send/abc123',
            'public_key' => 'public-key',
            'auth_token' => 'auth-token',
        ]);
    }

    public function test_resubscribing_repoints_the_endpoint_rather_than_duplicating(): void
    {
        $first = User::factory()->create(['role' => 'employee']);
        $second = User::factory()->create(['role' => 'employee']);

        $payload = [
            'endpoint' => 'https://fcm.googleapis.com/fcm/send/shared',
            'keys' => ['p256dh' => 'k', 'auth' => 'a'],
        ];

        $this->actingAs($first)->postJson(route('push.subscribe'), $payload)->assertOk();
        $this->actingAs($second)->postJson(route('push.subscribe'), $payload)->assertOk();

        $this->assertSame(1, PushSubscription::count());
        $this->assertSame($second->id, PushSubscription::first()->user_id);
    }

    public function test_a_user_can_only_remove_their_own_subscription(): void
    {
        $owner = User::factory()->create(['role' => 'employee']);
        $other = User::factory()->create(['role' => 'employee']);

        $endpoint = 'https://fcm.googleapis.com/fcm/send/owned';
        $this->actingAs($owner)->postJson(route('push.subscribe'), [
            'endpoint' => $endpoint,
            'keys' => ['p256dh' => 'k', 'auth' => 'a'],
        ])->assertOk();

        $this->actingAs($other)->postJson(route('push.unsubscribe'), ['endpoint' => $endpoint])->assertOk();

        $this->assertDatabaseHas('push_subscriptions', ['endpoint' => $endpoint]);

        $this->actingAs($owner)->postJson(route('push.unsubscribe'), ['endpoint' => $endpoint])->assertOk();

        $this->assertDatabaseMissing('push_subscriptions', ['endpoint' => $endpoint]);
    }

    public function test_subscribe_validates_the_payload(): void
    {
        $employee = User::factory()->create(['role' => 'employee']);

        // push.* is registered in bootstrap/app.php as a JSON route, so the
        // browser gets real field errors instead of a redirect to login.
        $this->actingAs($employee)
            ->postJson(route('push.subscribe'), ['endpoint' => 'https://example.test/x'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['keys.p256dh', 'keys.auth']);

        $this->assertDatabaseCount('push_subscriptions', 0);
    }

    public function test_a_missing_notification_returns_json_not_an_html_error_page(): void
    {
        $employee = User::factory()->create(['role' => 'employee']);

        $response = $this->actingAs($employee)
            ->postJson(route('notifications.read', '11111111-1111-1111-1111-111111111111'));

        $response->assertNotFound();
        $this->assertStringContainsString(
            'application/json',
            (string) $response->headers->get('content-type')
        );
    }

    public function test_deleting_a_missing_notification_also_returns_json(): void
    {
        $employee = User::factory()->create(['role' => 'employee']);

        $response = $this->actingAs($employee)
            ->deleteJson(route('notifications.destroy', '11111111-1111-1111-1111-111111111111'));

        $response->assertNotFound();
        $this->assertStringContainsString(
            'application/json',
            (string) $response->headers->get('content-type')
        );
    }

    public function test_the_notifications_page_still_renders_html(): void
    {
        $employee = User::factory()->create(['role' => 'employee']);

        $response = $this->actingAs($employee)->get(route('notifications.page'));

        $response->assertOk();
        $this->assertStringContainsString(
            'text/html',
            (string) $response->headers->get('content-type')
        );
    }

    public function test_the_public_key_endpoint_never_leaks_the_private_key(): void
    {
        config([
            'services.vapid.public_key' => 'public-key-value',
            'services.vapid.private_key' => 'super-secret-private-key',
        ]);

        $employee = User::factory()->create(['role' => 'employee']);

        $response = $this->actingAs($employee)->getJson(route('push.key'));

        $response->assertOk()
            ->assertJsonPath('public_key', 'public-key-value')
            ->assertJsonPath('enabled', true);

        $this->assertStringNotContainsString(
            'super-secret-private-key',
            $response->getContent()
        );
    }

    public function test_push_reports_itself_disabled_without_vapid_keys(): void
    {
        config([
            'services.vapid.public_key' => null,
            'services.vapid.private_key' => null,
        ]);

        $employee = User::factory()->create(['role' => 'employee']);

        $this->actingAs($employee)
            ->getJson(route('push.key'))
            ->assertOk()
            ->assertJsonPath('enabled', false);
    }

    public function test_a_failed_push_does_not_prevent_the_job_being_saved(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $employee = User::factory()->create(['role' => 'employee']);

        // Configured but pointed at an unroutable host, so delivery must fail
        // in the background while the in-app notification still lands.
        config([
            'services.vapid.public_key' => 'invalid-public-key',
            'services.vapid.private_key' => 'invalid-private-key',
            'services.vapid.subject' => 'mailto:test@example.com',
            'notifications.push.timeout' => 1,
        ]);

        PushSubscription::create([
            'user_id' => $employee->id,
            'endpoint' => 'https://127.0.0.1:1/never-listens',
            'public_key' => 'k',
            'auth_token' => 'a',
        ]);

        CompanyBalance::updateBalance(500000);

        $this->actingAs($employee)
            ->post(route('employee.jobs.store'), [
                'project_name' => 'Resilient Job',
                'expense' => '1000',
                'cost' => '2000',
                'status' => 'in_progress',
            ])
            ->assertRedirect(route('employee.jobs.index'));

        $this->assertDatabaseHas('project_jobs', ['project_name' => 'Resilient Job']);
        $this->assertSame(1, $admin->notifications()->count());
    }
}
