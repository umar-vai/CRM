<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\CRM\Models\Lead;
use App\Modules\Tenancy\Models\Tenant;
use App\Modules\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class WorkspaceTest extends TestCase
{
    use RefreshDatabase;

    private function workspace(User $user, string $role = 'owner'): Tenant
    {
        $tenant = Tenant::create(['name' => 'Example Business', 'industry' => 'service', 'locale' => 'bn']);
        DB::table('tenant_memberships')->insert(['tenant_id' => $tenant->id, 'user_id' => $user->id, 'role' => $role, 'created_at' => now(), 'updated_at' => now()]);

        return $tenant;
    }

    private function lead(Tenant $tenant, User $user, string $name): Lead
    {
        $context = app(TenantContext::class);
        $context->set($tenant, 'owner');
        try {
            return Lead::create(['name' => $name, 'source' => 'direct', 'status' => 'new', 'created_by' => $user->id]);
        } finally {
            $context->clear();
        }
    }

    public function test_creating_workspace_assigns_owner_membership(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->post('/workspaces', ['name' => 'My Company', 'industry' => 'retail', 'locale' => 'bn'])->assertRedirect();
        $this->assertDatabaseHas('tenant_memberships', ['user_id' => $user->id, 'role' => 'owner']);
        $this->assertDatabaseHas('tenants', ['name' => 'My Company', 'locale' => 'bn']);
    }

    public function test_non_member_cannot_access_workspace(): void
    {
        $tenant = $this->workspace(User::factory()->create());
        $this->actingAs(User::factory()->create())->get("/workspaces/{$tenant->id}/leads")->assertNotFound();
    }

    public function test_search_and_status_do_not_leak_other_tenants(): void
    {
        $user = User::factory()->create();
        $own = $this->workspace($user);
        $otherUser = User::factory()->create();
        $other = $this->workspace($otherUser);
        $this->lead($own, $user, 'Shared Search');
        $this->lead($other, $otherUser, 'Shared Search Secret');
        $this->actingAs($user)->get("/workspaces/{$own->id}/leads?search=Shared&status=new")
            ->assertInertia(fn (Assert $page) => $page->component('crm/leads')->has('leads.data', 1)->where('leads.data.0.name', 'Shared Search'));
    }

    public function test_cannot_update_lead_from_another_workspace(): void
    {
        $user = User::factory()->create();
        $own = $this->workspace($user);
        $otherUser = User::factory()->create();
        $secret = $this->lead($this->workspace($otherUser), $otherUser, 'Secret');
        $this->actingAs($user)->patch("/workspaces/{$own->id}/leads/{$secret->id}", ['status' => 'won'])->assertNotFound();
        $this->assertDatabaseHas('leads', ['id' => $secret->id, 'status' => 'new']);
    }

    public function test_auditor_can_read_but_not_write(): void
    {
        $user = User::factory()->create();
        $tenant = $this->workspace($user, 'auditor');
        $lead = $this->lead($tenant, $user, 'Read only');
        $this->actingAs($user)->get("/workspaces/{$tenant->id}/leads")->assertOk();
        $this->post("/workspaces/{$tenant->id}/leads", ['name' => 'Blocked', 'source' => 'direct'])->assertForbidden();
        $this->patch("/workspaces/{$tenant->id}/leads/{$lead->id}", ['status' => 'won'])->assertForbidden();
        $this->get("/workspaces/{$tenant->id}/configuration")->assertForbidden();
    }

    public function test_create_ignores_forged_tenant_and_records_redacted_audit(): void
    {
        $user = User::factory()->create();
        $own = $this->workspace($user);
        $other = $this->workspace(User::factory()->create());
        $this->actingAs($user)->post("/workspaces/{$own->id}/leads", ['name' => 'Sensitive Example', 'email' => 'private@example.test', 'phone' => '+880 1700-000000', 'source' => 'website', 'tenant_id' => $other->id])->assertRedirect();
        $this->assertDatabaseHas('leads', ['tenant_id' => $own->id, 'phone' => '+8801700000000', 'status' => 'new']);
        $audit = DB::table('audit_logs')->first();
        $this->assertSame($own->id, $audit->tenant_id);
        $this->assertStringNotContainsString('private@example.test', $audit->metadata);
        $this->assertStringNotContainsString('Sensitive Example', $audit->metadata);
    }

    public function test_status_change_is_audited(): void
    {
        $user = User::factory()->create();
        $tenant = $this->workspace($user);
        $lead = $this->lead($tenant, $user, 'Example');
        $this->actingAs($user)->patch("/workspaces/{$tenant->id}/leads/{$lead->id}", ['status' => 'qualified', 'version' => 1])->assertRedirect();
        $this->assertDatabaseHas('leads', ['id' => $lead->id, 'status' => 'qualified']);
        $this->assertDatabaseHas('audit_logs', ['subject_id' => $lead->id, 'action' => 'lead.status_changed']);
    }

    public function test_configuration_is_versioned_and_stale_publish_rejected(): void
    {
        $user = User::factory()->create();
        $tenant = $this->workspace($user);
        $payload = ['version' => 0, 'terminology' => ['lead' => 'Inquiry', 'customer' => 'Client']];
        $this->actingAs($user)->post("/workspaces/{$tenant->id}/configuration", $payload)->assertRedirect();
        $this->postJson("/workspaces/{$tenant->id}/configuration", $payload)->assertUnprocessable()->assertJsonValidationErrors('version');
        $this->assertDatabaseCount('configuration_versions', 1);
        $this->get("/workspaces/{$tenant->id}/leads")->assertInertia(fn (Assert $page) => $page->where('terminology.lead', 'Inquiry'));
    }

    public function test_configuration_does_not_cross_tenants(): void
    {
        $user = User::factory()->create();
        $one = $this->workspace($user);
        $two = $this->workspace($user);
        $this->actingAs($user)->post("/workspaces/{$one->id}/configuration", ['version' => 0, 'terminology' => ['lead' => 'Inquiry', 'customer' => 'Client']])->assertRedirect();
        $this->get("/workspaces/{$two->id}/leads")->assertInertia(fn (Assert $page) => $page->where('terminology.lead', 'Lead'));
    }

    public function test_tenant_context_is_cleared_after_request(): void
    {
        $user = User::factory()->create();
        $tenant = $this->workspace($user);
        $this->actingAs($user)->get("/workspaces/{$tenant->id}/leads")->assertOk();
        $this->expectException(\LogicException::class);
        Lead::count();
    }

    public function test_unknown_role_has_no_permissions(): void
    {
        $user = User::factory()->create();
        $tenant = $this->workspace($user, 'untrusted-role');
        $this->actingAs($user)->get("/workspaces/{$tenant->id}/leads")->assertForbidden();
    }

    public function test_business_member_cannot_delete_account_and_orphan_workspace(): void
    {
        $user = User::factory()->create();
        $tenant = $this->workspace($user);
        $this->actingAs($user)->deleteJson(route('profile.destroy'), ['password' => 'password'])
            ->assertUnprocessable()->assertJsonValidationErrors('password');
        $this->assertAuthenticatedAs($user);
        $this->assertDatabaseHas('tenant_memberships', ['tenant_id' => $tenant->id, 'user_id' => $user->id]);
    }

    public function test_invalid_status_does_not_write_or_audit(): void
    {
        $user = User::factory()->create();
        $tenant = $this->workspace($user);
        $lead = $this->lead($tenant, $user, 'Example');
        $this->actingAs($user)->patchJson("/workspaces/{$tenant->id}/leads/{$lead->id}", ['status' => 'arbitrary-code'])->assertUnprocessable();
        $this->assertDatabaseHas('leads', ['id' => $lead->id, 'status' => 'new']);
        $this->assertDatabaseCount('audit_logs', 0);
    }

    public function test_loaded_model_cannot_be_saved_in_other_tenant_context(): void
    {
        $user = User::factory()->create();
        $one = $this->workspace($user);
        $two = $this->workspace($user);
        $lead = $this->lead($one, $user, 'Original');
        app(TenantContext::class)->set($two, 'owner');
        try {
            $this->expectException(\LogicException::class);
            $lead->update(['name' => 'Cross tenant mutation']);
        } finally {
            app(TenantContext::class)->clear();
        }
    }
}
