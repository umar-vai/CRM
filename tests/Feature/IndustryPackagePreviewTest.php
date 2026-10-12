<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Configuration\ConfigurationService;
use App\Modules\Configuration\CustomFieldSchema;
use App\Modules\Configuration\IndustryPackageCatalog;
use App\Modules\Tenancy\Models\Tenant;
use App\Modules\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class IndustryPackagePreviewTest extends TestCase
{
    use RefreshDatabase;

    private function workspace(User $user, string $role = 'owner'): Tenant
    {
        $tenant = Tenant::create(['name' => 'Synthetic package workspace', 'industry' => 'service', 'locale' => 'bn']);
        DB::table('tenant_memberships')->insert(['tenant_id' => $tenant->id, 'user_id' => $user->id, 'role' => $role, 'created_at' => now(), 'updated_at' => now()]);

        return $tenant;
    }

    private function previewQuery(int $version = 0): string
    {
        return '?package_id=clinic-crm&package_version=1.0.0&version='.$version;
    }

    public function test_owner_catalog_and_preview_are_read_only_no_store_and_not_installed(): void
    {
        $user = User::factory()->create();
        $tenant = $this->workspace($user);
        $url = '/workspaces/'.$tenant->id.'/configuration/packages';
        $this->actingAs($user)->get($url)->assertInertia(fn (Assert $page) => $page->component('configuration/packages')->has('packages', 2)->where('preview', null));
        $response = $this->get($url.$this->previewQuery().'&tenant_id=forged&entitlements[]=appointments');
        $response->assertInertia(fn (Assert $page) => $page->where('workspace.id', $tenant->id)->where('preview.preview_only', true)->where('preview.terminology.customer', 'Patient')->where('preview.preserved_override', false)->has('preview.blockers', 0)->has('preview.manifest.fields', 2));
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
        $this->assertDatabaseCount('configuration_versions', 0);
        $this->assertDatabaseCount('audit_logs', 0);
        $this->assertDatabaseCount('leads', 0);
        $this->post($url, ['package_id' => 'clinic-crm'])->assertStatus(405);
        try {
            app(TenantContext::class)->tenant();
            $this->fail('Context leaked.');
        } catch (\LogicException) {
            $this->assertTrue(true);
        }
    }

    public function test_preview_preserves_existing_terminology_and_rejects_stale_version(): void
    {
        $user = User::factory()->create();
        $tenant = $this->workspace($user);
        $ctx = app(TenantContext::class);
        $ctx->set($tenant, 'owner');
        try {
            app(ConfigurationService::class)->publish($user->id, 0, ['lead' => 'My inquiry', 'customer' => 'My client']);
        } finally {
            $ctx->clear();
        }
        $url = '/workspaces/'.$tenant->id.'/configuration/packages';
        $this->actingAs($user)->get($url.$this->previewQuery(1))->assertInertia(fn (Assert $page) => $page->where('preview.terminology.customer', 'My client')->where('preview.preserved_override', true));
        $this->getJson($url.$this->previewQuery())->assertUnprocessable()->assertJsonValidationErrors('version');
        $this->assertDatabaseCount('configuration_versions', 1);
        $this->assertDatabaseCount('audit_logs', 1);
    }

    public function test_nonowner_nonmember_foreign_workspace_unknown_package_and_forged_version_denied(): void
    {
        $user = User::factory()->create();
        $other = $this->workspace(User::factory()->create());
        foreach (['manager', 'agent', 'auditor', 'unknown'] as $role) {
            $tenant = $this->workspace($user, $role);
            $this->actingAs($user)->get('/workspaces/'.$tenant->id.'/configuration/packages'.$this->previewQuery())->assertForbidden();
        }
        $this->get('/workspaces/'.$other->id.'/configuration/packages'.$this->previewQuery())->assertNotFound();
        $tenant = $this->workspace($user);
        $url = '/workspaces/'.$tenant->id.'/configuration/packages';
        $this->get($url.'?package_id=unknown&package_version=1.0.0&version=0')->assertNotFound();
        $this->get($url.'?package_id=clinic-crm&package_version=99.0.0&version=0')->assertNotFound();
        $this->getJson($url.'?package_id=clinic-crm')->assertUnprocessable()->assertJsonValidationErrors(['package_version', 'version']);
        $this->assertDatabaseCount('configuration_versions', 0);
    }

    public function test_own_preview_does_not_read_other_workspace_overrides(): void
    {
        $user = User::factory()->create();
        $own = $this->workspace($user);
        $other = $this->workspace($user);
        $ctx = app(TenantContext::class);
        $ctx->set($other, 'owner');
        try {
            app(ConfigurationService::class)->publish($user->id, 0, ['lead' => 'Other private label', 'customer' => 'Other private customer']);
        } finally {
            $ctx->clear();
        }
        $this->actingAs($user)->get('/workspaces/'.$own->id.'/configuration/packages'.$this->previewQuery())->assertInertia(fn (Assert $page) => $page->where('preview.base_version', 0)->where('preview.terminology.customer', 'Patient'));
    }

    public function test_manifest_unknown_keys_hooks_schema_types_dependencies_and_permissions_fail_closed(): void
    {
        $catalog = app(IndustryPackageCatalog::class);
        $manifest = config('industry_packages.manifests.1');
        $invalid = [
            [...$manifest, 'schema_version' => 2], [...$manifest, 'hook' => 'eval arbitrary code'],
            [...$manifest, 'modules' => ['unknown']], [...$manifest, 'modules' => ['appointments']],
            [...$manifest, 'permissions' => ['superuser']], [...$manifest, 'core_min' => '9.0.0', 'core_max' => '0.1.0'],
            [...$manifest, 'fields' => [[...$manifest['fields'][0], 'type' => 'javascript']]],
            [...$manifest, 'fields' => [[...$manifest['fields'][0], 'key' => 'tenant_id']]],
            [...$manifest, 'fields' => [$manifest['fields'][0], $manifest['fields'][0]]],
            [...$manifest, 'modules' => ['configuration']],
        ];
        foreach ($invalid as $input) {
            try {
                $catalog->validateManifest($input);
                $this->fail('Invalid manifest accepted.');
            } catch (ValidationException) {
                $this->assertTrue(true);
            }
        }
        config(['platform.modules.crm.dependencies' => ['configuration'], 'platform.modules.configuration.dependencies' => ['crm']]);
        try {
            $catalog->validateManifest($manifest);
            $this->fail('Cycle accepted.');
        } catch (ValidationException $error) {
            $this->assertArrayHasKey('package', $error->errors());
        }
    }

    public function test_server_preview_checks_core_entitlements_module_availability_and_permissions(): void
    {
        $user = User::factory()->create();
        $tenant = $this->workspace($user);
        $manifest = config('industry_packages.manifests.1');
        config(['industry_packages.manifests' => [[...$manifest, 'modules' => ['crm', 'configuration', 'appointments'], 'permissions' => ['leads.view', 'configuration.preview', 'leads.convert']]], 'industry_packages.core_version' => '9.0.0', 'industry_packages.preview_entitlements' => ['crm']]);
        $this->actingAs($user)->get('/workspaces/'.$tenant->id.'/configuration/packages'.$this->previewQuery())->assertInertia(fn (Assert $page) => $page->where('preview.preview_only', true)->has('preview.blockers', 4));
        config(['platform.roles.owner' => ['configuration.preview']]);
        $this->get('/workspaces/'.$tenant->id.'/configuration/packages'.$this->previewQuery())->assertInertia(fn (Assert $page) => $page->has('preview.blockers', 6));
        $this->assertDatabaseCount('configuration_versions', 0);
    }

    public function test_catalog_versions_unique_digest_stable_and_context_required_for_preview(): void
    {
        $catalog = app(IndustryPackageCatalog::class);
        $this->assertSame($catalog->all(), $catalog->all());
        // Released definitions are pinned. Changes must add a new version, not rewrite 1.0.0.
        $this->assertSame([
            'service-crm' => '8e6c216baa859f38fe5f75c6a4c19dafa1f5fb7b856b59d18a0d1f371a575e26',
            'clinic-crm' => 'ffdfdd145bb35d4ebf2fbca8b87f8388b305dc8aa04a0c03ce6aea0859abc351',
        ], array_column($catalog->all(), 'digest', 'id'));
        foreach ($catalog->all() as $item) {
            $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $item['digest']);
        }
        try {
            $catalog->preview('clinic-crm', '1.0.0', 0);
            $this->fail('Missing context accepted.');
        } catch (\LogicException) {
            $this->assertTrue(true);
        }
        $manifest = config('industry_packages.manifests.0');
        config(['industry_packages.manifests' => [$manifest, $manifest]]);
        try {
            $catalog->all();
            $this->fail('Duplicate version accepted.');
        } catch (ValidationException) {
            $this->assertTrue(true);
        }
    }

    public function test_typed_field_values_strict_types_dates_limits_required_and_unknown_keys(): void
    {
        $schema = app(CustomFieldSchema::class);
        $definitions = [];
        foreach (CustomFieldSchema::TYPES as $type) {
            $field = ['key' => 'sample_'.$type, 'entity' => 'lead', 'type' => $type, 'label' => ['en' => 'Sample', 'bn' => 'নমুনা'], 'required' => true];
            if ($type === 'select') {
                $field['options'] = ['consultation', 'wellness'];
            }
            $definitions[] = $field;
        }
        $valid = ['sample_text' => 'Synthetic', 'sample_integer' => 0, 'sample_boolean' => false, 'sample_date' => '2028-02-29', 'sample_select' => 'wellness'];
        $this->assertSame($valid, $schema->values($definitions, $valid));
        $invalid = [['sample_integer' => '12'], ['sample_integer' => 1.5], ['sample_integer' => 2147483648], ['sample_boolean' => 'false'], ['sample_date' => '2026-02-29'], ['sample_date' => '2026-01-01<script>'], ['sample_select' => 'other'], ['sample_text' => str_repeat('x', 501)], ['sample_text' => null], ['unknown' => 'value']];
        foreach ($invalid as $change) {
            try {
                $schema->values($definitions, [...$valid, ...$change]);
                $this->fail('Bad typed value accepted.');
            } catch (ValidationException) {
                $this->assertTrue(true);
            }
        }
        foreach ([['required' => 'false'], ['options' => ['wellness', 'wellness']], ['default' => 'arbitrary'], ['entity' => 'customer']] as $change) {
            try {
                $schema->definitions([[...$definitions[4], ...$change]]);
                $this->fail('Bad field schema accepted.');
            } catch (ValidationException) {
                $this->assertTrue(true);
            }
        }
        $this->assertSame([], $schema->values([[...$definitions[0], 'required' => false]], []));
    }
}
