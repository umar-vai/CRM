<?php

namespace Tests\Postgres;

use App\Models\User;
use App\Modules\CRM\LeadService;
use App\Modules\CRM\Models\Lead;
use App\Modules\Tenancy\Models\Tenant;
use App\Modules\Tenancy\TenantContext;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class CrmConcurrencyTest extends TestCase
{
    use DatabaseMigrations;

    private string $primary;

    private User $owner;

    private Tenant $tenant;

    private Lead $lead;

    protected function setUp(): void
    {
        parent::setUp();
        $this->primary = DB::getDefaultConnection();
        if (DB::connection()->getDriverName() !== 'pgsql') {
            $this->markTestSkipped('Requires PostgreSQL independent connections; executed by CI pgsql job.');
        }
        config(['database.connections.crm_competitor' => config('database.connections.'.$this->primary)]);
        DB::connection('crm_competitor')->statement("SET lock_timeout = '100ms'");
        $this->owner = User::factory()->create();
        $this->tenant = Tenant::create(['name' => 'Synthetic concurrency workspace']);
        DB::table('tenant_memberships')->insert(['tenant_id' => $this->tenant->id, 'user_id' => $this->owner->id, 'role' => 'owner', 'created_at' => now(), 'updated_at' => now()]);
        app(TenantContext::class)->set($this->tenant, 'owner');
        $this->lead = Lead::create(['name' => 'Synthetic lead', 'email' => 'shared@example.test', 'source' => 'direct', 'created_by' => $this->owner->id]);
    }

    protected function tearDown(): void
    {
        if (isset($this->primary)) {
            DB::setDefaultConnection($this->primary);
            while (DB::connection()->transactionLevel() > 0) {
                DB::connection()->rollBack();
            }
            DB::purge('crm_competitor');
            app(TenantContext::class)->clear();
        }
        parent::tearDown();
    }

    private function compete(callable $operation): mixed
    {
        DB::setDefaultConnection('crm_competitor');
        app(TenantContext::class)->set($this->tenant, 'owner');
        try {
            return $operation();
        } finally {
            DB::setDefaultConnection($this->primary);
            app(TenantContext::class)->set($this->tenant, 'owner');
        }
    }

    private function assertLocked(callable $operation): void
    {
        try {
            $this->compete($operation);
            $this->fail('Competing CRM mutation must wait for the real service lock.');
        } catch (QueryException $exception) {
            $this->assertSame('55P03', $exception->errorInfo[0]);
        }
    }

    public function test_uncommitted_conversion_blocks_competitor_then_retry_returns_same_customer(): void
    {
        $service = app(LeadService::class);
        DB::beginTransaction();
        $customer = $service->convert($this->owner->id, $this->lead->id, 1);
        $this->assertLocked(fn () => $service->convert($this->owner->id, $this->lead->id, 1));
        $this->assertSame(0, DB::connection('crm_competitor')->table('customers')->where('tenant_id', $this->tenant->id)->count());
        DB::commit();
        $retry = $this->compete(fn () => $service->convert($this->owner->id, $this->lead->id, 1));
        $this->assertSame($customer->id, $retry->id);
        $this->assertSame(1, DB::table('customers')->where('tenant_id', $this->tenant->id)->count());
        $this->assertSame(1, DB::table('audit_logs')->where('tenant_id', $this->tenant->id)->where('action', 'lead.converted')->count());
    }

    public function test_edit_lock_blocks_second_connection_and_stale_retry_is_rejected(): void
    {
        $service = app(LeadService::class);
        DB::beginTransaction();
        $service->update($this->owner->id, $this->lead->id, 1, ['name' => 'Winner']);
        $this->assertLocked(fn () => $service->update($this->owner->id, $this->lead->id, 1, ['name' => 'Loser']));
        DB::commit();
        try {
            $this->compete(fn () => $service->update($this->owner->id, $this->lead->id, 1, ['name' => 'Loser']));
            $this->fail('Stale retry must be rejected.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('version', $exception->errors());
        }
        $this->assertSame('Winner', Lead::findOrFail($this->lead->id)->getAttribute('name'));
    }

    public function test_two_different_leads_with_same_contact_cannot_race_duplicate_customers(): void
    {
        $other = Lead::create(['name' => 'Possible duplicate', 'email' => 'shared@example.test', 'source' => 'website', 'created_by' => $this->owner->id]);
        $service = app(LeadService::class);
        DB::beginTransaction();
        $service->convert($this->owner->id, $this->lead->id, 1);
        $this->assertLocked(fn () => $service->convert($this->owner->id, $other->id, 1));
        DB::commit();
        try {
            $this->compete(fn () => $service->convert($this->owner->id, $other->id, 1));
            $this->fail('Duplicate customer must require review.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('customer', $exception->errors());
        }
        $this->assertSame(1, DB::table('customers')->where('tenant_id', $this->tenant->id)->count());
        $this->assertSame('new', Lead::findOrFail($other->id)->getAttribute('status'));
    }
}
