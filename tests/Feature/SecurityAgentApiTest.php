<?php

namespace Tests\Feature;

use App\Models\SecurityAlert;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SecurityAgentApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_ping_requires_valid_api_key(): void
    {
        $response = $this->getJson('/api/agent/ping');
        $response->assertStatus(401);
    }

    public function test_ping_with_valid_api_key(): void
    {
        config(['security.agent_api_key' => 'test-secret']);

        $response = $this->withHeaders(['X-Agent-Key' => 'test-secret'])
            ->getJson('/api/agent/ping');
        $response->assertOk();
    }

    public function test_store_alert(): void
    {
        config(['security.agent_api_key' => 'test-secret']);

        $response = $this->withHeaders(['X-Agent-Key' => 'test-secret'])
            ->postJson('/api/agent/alert', [
                'server_name' => 'server1',
                'type' => 'ssh_bruteforce',
                'severity' => 'critical',
                'source_ip' => '203.0.113.99',
                'description' => 'SSH brute force from IP',
                'occurred_at' => now()->toIso8601String(),
            ]);

        $response->assertOk();
        $response->assertJson(['success' => true]);

        $this->assertDatabaseCount('security_alerts', 1);
        $this->assertDatabaseHas('security_alerts', [
            'type' => 'ssh_bruteforce',
            'severity' => 'critical',
            'source_ip' => '203.0.113.99',
        ]);
    }

    public function test_store_block(): void
    {
        config(['security.agent_api_key' => 'test-secret']);

        $response = $this->withHeaders(['X-Agent-Key' => 'test-secret'])
            ->postJson('/api/agent/block', [
                'ip' => '203.0.113.50',
                'reason' => 'Brute force',
                'duration' => 86400,
            ]);

        $response->assertOk();

        $this->assertDatabaseHas('blocked_ips', [
            'ip' => '203.0.113.50',
            'is_active' => true,
        ]);
    }

    public function test_store_status(): void
    {
        config(['security.agent_api_key' => 'test-secret']);

        $response = $this->withHeaders(['X-Agent-Key' => 'test-secret'])
            ->postJson('/api/agent/status', [
                'server_name' => 'server1',
                'cpu_usage' => 45,
                'ram_usage' => 60,
                'ram_total_gb' => 16,
                'ram_used_gb' => 9.6,
                'disk_usage' => 70,
                'check' => true,
            ]);

        $response->assertOk();

        $this->assertDatabaseCount('server_statuses', 1);
    }
}
