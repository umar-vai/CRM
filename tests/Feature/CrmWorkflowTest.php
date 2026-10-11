<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Audit\AuditRecorder;
use App\Modules\CRM\LeadService;
use App\Modules\CRM\Models\Customer;
use App\Modules\CRM\Models\Lead;
use App\Modules\CRM\Models\LeadActivity;
use App\Modules\Tenancy\Models\Tenant;
use App\Modules\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use LogicException;
use Mockery\MockInterface;
use RuntimeException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class CrmWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private Tenant $tenant;

    private Lead $lead;

    private string $url;

    protected function setUp(): void
    {
        parent::setUp();
        $this->owner = User::factory()->create();
        $this->tenant = Tenant::create(['name' => 'Synthetic Business', 'locale' => 'bn']);
        $this->membership($this->owner, $this->tenant, 'owner');
        $context = app(TenantContext::class);
        $context->set($this->tenant, 'owner');
        try {
            $this->lead = Lead::create(['name' => 'Synthetic Lead', 'email' => 'lead@example.test', 'phone' => '+8801700000000', 'source' => 'website', 'notes' => 'Private initial note', 'created_by' => $this->owner->id]);
        } finally {
            $context->clear();
        }
        $this->url = "/workspaces/{$this->tenant->id}/leads/{$this->lead->id}";
        $this->actingAs($this->owner);
    }

    private function membership(User $user, Tenant $tenant, string $role): void
    {
        DB::table('tenant_memberships')->insert(['tenant_id' => $tenant->id, 'user_id' => $user->id, 'role' => $role, 'created_at' => now(), 'updated_at' => now()]);
    }

    public function test_detail_and_edit_normalize_contacts_ignore_ownership_and_redact_audit(): void
    {
        $this->patch($this->url, ['version' => 1, 'name' => 'Private changed name', 'email' => 'Changed@Example.test', 'phone' => '+880 1700-000001', 'notes' => 'Private changed note', 'tenant_id' => 'forged', 'created_by' => 999])->assertRedirect();
        $this->assertDatabaseHas('leads', ['id' => $this->lead->id, 'tenant_id' => $this->tenant->id, 'email' => 'changed@example.test', 'phone' => '+8801700000001', 'version' => 2, 'created_by' => $this->owner->id]);
        $this->get($this->url)->assertInertia(fn (Assert $page) => $page->component('crm/lead-detail')->where('lead.version', 2)->has('activities.data', 1)->where('activities.data.0.kind', 'updated'));
        $metadata = DB::table('audit_logs')->where('tenant_id', $this->tenant->id)->where('subject_id', $this->lead->id)->value('metadata');
        foreach (['Private', 'changed@example.test', '+8801700000001'] as $sensitive) {
            $this->assertStringNotContainsString($sensitive, $metadata);
        }
    }

    public function test_stale_and_missing_version_rejected_without_history(): void
    {
        $this->patch($this->url, ['version' => 1, 'status' => 'qualified'])->assertRedirect();
        $this->patchJson($this->url, ['version' => 1, 'name' => 'Stale overwrite'])->assertUnprocessable()->assertJsonValidationErrors('version');
        $this->patchJson($this->url, ['status' => 'lost'])->assertUnprocessable()->assertJsonValidationErrors('version');
        $this->assertDatabaseHas('leads', ['id' => $this->lead->id, 'status' => 'qualified', 'version' => 2, 'name' => 'Synthetic Lead']);
        $this->assertDatabaseCount('lead_activities', 1);
        $this->assertDatabaseCount('audit_logs', 1);
    }

    public function test_unchanged_edit_is_noop(): void
    {
        $this->patch($this->url, ['version' => 1, 'name' => 'Synthetic Lead'])->assertRedirect();
        $this->assertDatabaseCount('audit_logs', 0);
        $this->assertDatabaseCount('lead_activities', 0);
        $this->assertDatabaseHas('leads', ['id' => $this->lead->id, 'version' => 1]);
    }

    public function test_activity_retry_is_idempotent_and_body_not_in_audit(): void
    {
        $payload = ['version' => 1, 'kind' => 'call', 'body' => 'Private call details', 'request_key' => (string) Str::uuid(), 'tenant_id' => 'forged'];
        $this->post($this->url.'/activities', $payload)->assertRedirect();
        $this->post($this->url.'/activities', $payload)->assertRedirect();
        $this->assertDatabaseCount('lead_activities', 1);
        $this->assertDatabaseCount('audit_logs', 1);
        $this->assertDatabaseHas('leads', ['id' => $this->lead->id, 'version' => 2]);
        $this->assertDatabaseHas('lead_activities', ['tenant_id' => $this->tenant->id, 'lead_id' => $this->lead->id, 'body' => 'Private call details']);
        $audit = DB::table('audit_logs')->where('tenant_id', $this->tenant->id)->first();
        $this->assertStringNotContainsString('Private call details', $audit->metadata);
        $this->postJson($this->url.'/activities', [...$payload, 'body' => 'Different intent'])->assertUnprocessable()->assertJsonValidationErrors('request_key');
    }

    public function test_stale_or_invalid_activity_does_not_mutate(): void
    {
        $this->postJson($this->url.'/activities', ['version' => 99, 'kind' => 'note', 'body' => 'Stale', 'request_key' => (string) Str::uuid()])->assertUnprocessable()->assertJsonValidationErrors('version');
        $this->postJson($this->url.'/activities', ['version' => 1, 'kind' => 'converted', 'body' => 'Forged system event', 'request_key' => 'invalid'])->assertUnprocessable()->assertJsonValidationErrors(['kind', 'request_key']);
        $this->assertDatabaseCount('lead_activities', 0);
        $this->assertDatabaseCount('audit_logs', 0);
    }

    public function test_conversion_retry_creates_one_snapshot_history_and_audit(): void
    {
        $this->post($this->url.'/convert', ['version' => 1])->assertRedirect($this->url);
        $this->post($this->url.'/convert', ['version' => 1])->assertRedirect($this->url);
        $this->assertDatabaseCount('customers', 1);
        $this->assertDatabaseCount('lead_activities', 1);
        $this->assertDatabaseCount('audit_logs', 1);
        $this->assertDatabaseHas('customers', ['tenant_id' => $this->tenant->id, 'lead_id' => $this->lead->id, 'name' => 'Synthetic Lead', 'source' => 'website']);
        $this->assertDatabaseHas('leads', ['id' => $this->lead->id, 'status' => 'won', 'version' => 2]);
        $this->patchJson($this->url, ['version' => 2, 'name' => 'Overwrite converted'])->assertUnprocessable()->assertJsonValidationErrors('lead');
        $this->get("/workspaces/{$this->tenant->id}/customers?search=Synthetic")->assertInertia(fn (Assert $page) => $page->component('crm/customers')->has('customers.data', 1));
    }

    public function test_duplicate_suggestions_are_scoped_and_second_conversion_is_blocked(): void
    {
        $this->post("/workspaces/{$this->tenant->id}/leads", ['name' => 'Possible duplicate', 'email' => 'LEAD@EXAMPLE.TEST', 'source' => 'direct'])->assertRedirect();
        $this->get($this->url)->assertInertia(fn (Assert $page) => $page->has('duplicates', 1)->where('duplicates.0.name', 'Possible duplicate'));
        $this->post($this->url.'/convert', ['version' => 1])->assertRedirect();
        $second = DB::table('leads')->where('tenant_id', $this->tenant->id)->where('name', 'Possible duplicate')->value('id');
        $this->postJson("/workspaces/{$this->tenant->id}/leads/{$second}/convert", ['version' => 1])->assertUnprocessable()->assertJsonValidationErrors('customer');
        $this->assertDatabaseCount('customers', 1);
        $this->assertDatabaseHas('leads', ['id' => $second, 'status' => 'new', 'version' => 1]);
    }

    public function test_foreign_lead_detail_edit_activity_conversion_return_404(): void
    {
        $foreign = Tenant::create(['name' => 'Other workspace']);
        $this->membership($this->owner, $foreign, 'owner');
        $wrongUrl = "/workspaces/{$foreign->id}/leads/{$this->lead->id}";
        $this->get($wrongUrl)->assertNotFound();
        $this->patchJson($wrongUrl, ['version' => 1, 'name' => 'Intrusion'])->assertNotFound();
        $this->postJson($wrongUrl.'/activities', ['version' => 1, 'kind' => 'note', 'body' => 'Intrusion', 'request_key' => (string) Str::uuid()])->assertNotFound();
        $this->postJson($wrongUrl.'/convert', ['version' => 1])->assertNotFound();
        $this->assertDatabaseCount('customers', 0);
        $this->assertDatabaseCount('lead_activities', 0);
    }

    public function test_customer_search_and_duplicates_do_not_leak_foreign_workspace(): void
    {
        $this->post($this->url.'/convert', ['version' => 1])->assertRedirect();
        $other = Tenant::create(['name' => 'Second workspace']);
        $this->membership($this->owner, $other, 'owner');
        $this->post("/workspaces/{$other->id}/leads", ['name' => 'Second lead', 'email' => 'lead@example.test', 'source' => 'direct'])->assertRedirect();
        $id = DB::table('leads')->where('tenant_id', $other->id)->value('id');
        $this->get("/workspaces/{$other->id}/leads/{$id}")->assertInertia(fn (Assert $page) => $page->has('duplicates', 0)->where('customer', null));
        $this->get("/workspaces/{$other->id}/customers?search=Synthetic")->assertInertia(fn (Assert $page) => $page->has('customers.data', 0));
        $this->post("/workspaces/{$other->id}/leads/{$id}/convert", ['version' => 1])->assertRedirect();
        $this->assertDatabaseCount('customers', 2);
    }

    public function test_auditor_read_only_agent_cannot_convert_manager_can(): void
    {
        $auditor = User::factory()->create();
        $this->membership($auditor, $this->tenant, 'auditor');
        $this->actingAs($auditor)->get($this->url)->assertInertia(fn (Assert $page) => $page->where('canUpdate', false)->where('canConvert', false)->where('canAddActivity', false));
        $this->patchJson($this->url, ['version' => 1, 'name' => 'Blocked'])->assertForbidden();
        $this->postJson($this->url.'/activities', [])->assertForbidden();
        $this->postJson($this->url.'/convert', [])->assertForbidden();
        $agent = User::factory()->create();
        $this->membership($agent, $this->tenant, 'agent');
        $this->actingAs($agent)->postJson($this->url.'/convert', ['version' => 1])->assertForbidden();
        $manager = User::factory()->create();
        $this->membership($manager, $this->tenant, 'manager');
        $this->actingAs($manager)->post($this->url.'/convert', ['version' => 1])->assertRedirect();
    }

    public function test_nonmember_and_unknown_role_denied(): void
    {
        $this->actingAs(User::factory()->create())->get($this->url)->assertNotFound();
        $this->get("/workspaces/{$this->tenant->id}/customers")->assertNotFound();
        $unknown = User::factory()->create();
        $this->membership($unknown, $this->tenant, 'unknown');
        $this->actingAs($unknown)->get($this->url)->assertForbidden();
        $this->get("/workspaces/{$this->tenant->id}/customers")->assertForbidden();
    }

    public function test_cached_role_cannot_bypass_current_membership(): void
    {
        DB::table('tenant_memberships')->where('tenant_id', $this->tenant->id)->where('user_id', $this->owner->id)->update(['role' => 'auditor']);
        app(TenantContext::class)->set($this->tenant, 'owner');
        try {
            app(LeadService::class)->convert($this->owner->id, $this->lead->id, 1);
            $this->fail('Current membership must be rechecked.');
        } catch (HttpException $exception) {
            $this->assertSame(403, $exception->getStatusCode());
        } finally {
            app(TenantContext::class)->clear();
        }
        $this->assertDatabaseCount('customers', 0);
    }

    public function test_audit_failure_rolls_back_edit_activity_conversion_and_creation(): void
    {
        $this->mock(AuditRecorder::class, function (MockInterface $mock): void {
            $mock->shouldReceive('record')->andThrow(new RuntimeException('Synthetic audit failure'));
        });
        $this->withoutExceptionHandling();
        $operations = [
            fn () => $this->patch($this->url, ['version' => 1, 'name' => 'Rollback']),
            fn () => $this->post($this->url.'/activities', ['version' => 1, 'kind' => 'note', 'body' => 'Rollback', 'request_key' => (string) Str::uuid()]),
            fn () => $this->post($this->url.'/convert', ['version' => 1]),
            fn () => $this->post("/workspaces/{$this->tenant->id}/leads", ['name' => 'Rollback', 'source' => 'direct']),
        ];
        foreach ($operations as $operation) {
            try {
                $operation();
                $this->fail('Audit failure should abort the mutation.');
            } catch (RuntimeException $exception) {
                $this->assertSame('Synthetic audit failure', $exception->getMessage());
            }
            $this->assertDatabaseCount('leads', 1);
            $this->assertDatabaseHas('leads', ['id' => $this->lead->id, 'name' => 'Synthetic Lead', 'version' => 1, 'status' => 'new']);
            $this->assertDatabaseCount('customers', 0);
            $this->assertDatabaseCount('lead_activities', 0);
            try {
                Lead::count();
                $this->fail('Context must clear after exception.');
            } catch (LogicException $exception) {
                $this->assertSame('Tenant context is required.', $exception->getMessage());
            }
        }
    }

    public function test_new_models_fail_closed_without_tenant_context(): void
    {
        foreach ([Customer::class, LeadActivity::class] as $model) {
            try {
                $model::count();
                $this->fail('Missing context must fail closed.');
            } catch (LogicException $exception) {
                $this->assertSame('Tenant context is required.', $exception->getMessage());
            }
        }
    }

    public function test_timeline_pagination_and_custom_terminology(): void
    {
        $this->post("/workspaces/{$this->tenant->id}/configuration", ['version' => 0, 'terminology' => ['lead' => 'Inquiry', 'customer' => 'Client']])->assertRedirect();
        $context = app(TenantContext::class);
        $context->set($this->tenant, 'owner');
        try {
            for ($index = 0; $index < 21; $index++) {
                LeadActivity::create(['lead_id' => $this->lead->id, 'actor_id' => $this->owner->id, 'kind' => 'note', 'body' => "Synthetic {$index}", 'metadata' => [], 'created_at' => now()->addSeconds($index)]);
            }
        } finally {
            $context->clear();
        }
        $this->get($this->url)->assertInertia(fn (Assert $page) => $page->where('terminology.customer', 'Client')->where('activities.total', 21)->has('activities.data', 20)->where('activities.data.0.body', 'Synthetic 20'));
        $this->get($this->url.'?page=2')->assertInertia(fn (Assert $page) => $page->has('activities.data', 1));
    }
}
