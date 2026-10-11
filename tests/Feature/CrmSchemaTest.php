<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\CRM\Models\Lead;
use App\Modules\Tenancy\Models\Tenant;
use App\Modules\Tenancy\TenantContext;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

class CrmSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_composite_foreign_keys_reject_cross_tenant_customer_and_activity(): void
    {
        $user = User::factory()->create();
        $one = Tenant::create(['name' => 'One']);
        $two = Tenant::create(['name' => 'Two']);
        app(TenantContext::class)->set($one, 'owner');
        try {
            $lead = Lead::create(['name' => 'Synthetic', 'source' => 'direct', 'created_by' => $user->id]);
        } finally {
            app(TenantContext::class)->clear();
        }
        $invalidRows = [
            'customers' => ['id' => (string) Str::ulid(), 'tenant_id' => $two->id, 'lead_id' => $lead->id, 'name' => 'Invalid', 'source' => 'direct', 'created_by' => $user->id, 'created_at' => now(), 'updated_at' => now()],
            'lead_activities' => ['id' => (string) Str::ulid(), 'tenant_id' => $two->id, 'lead_id' => $lead->id, 'actor_id' => $user->id, 'kind' => 'note', 'metadata' => '{}', 'created_at' => now()],
        ];
        foreach ($invalidRows as $table => $row) {
            try {
                DB::transaction(fn () => DB::table($table)->insert($row));
                $this->fail('Composite foreign key must reject a foreign lead.');
            } catch (QueryException $exception) {
                $this->assertContains($exception->errorInfo[0], ['23000', '23503']);
            }
            $this->assertDatabaseCount($table, 0);
        }
    }

    public function test_crm_migration_down_up_preserves_existing_lead(): void
    {
        $user = User::factory()->create();
        $tenant = Tenant::create(['name' => 'Synthetic']);
        app(TenantContext::class)->set($tenant, 'owner');
        try {
            $lead = Lead::create(['name' => 'Preserved', 'source' => 'website', 'created_by' => $user->id]);
        } finally {
            app(TenantContext::class)->clear();
        }
        $migration = require database_path('migrations/2026_10_12_000001_extend_crm_workflows.php');
        $migration->down();
        $this->assertFalse(Schema::hasTable('customers'));
        $this->assertFalse(Schema::hasColumn('leads', 'version'));
        $this->assertDatabaseHas('leads', ['id' => $lead->id, 'name' => 'Preserved']);
        $migration->up();
        $this->assertTrue(Schema::hasTable('lead_activities'));
        $this->assertDatabaseHas('leads', ['id' => $lead->id, 'version' => 1]);
    }
}
