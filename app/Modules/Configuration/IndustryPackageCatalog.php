<?php

namespace App\Modules\Configuration;

use App\Modules\Tenancy\TenantContext;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class IndustryPackageCatalog
{
    /** @return list<array<string, mixed>> */
    public function all(): array
    {
        $result = [];
        $seen = [];
        foreach (config('industry_packages.manifests', []) as $manifest) {
            $manifest = $this->validateManifest($manifest);
            $key = $manifest['id'].'@'.$manifest['version'];
            if (in_array($key, $seen, true)) {
                throw ValidationException::withMessages(['package' => 'Duplicate package version.']);
            }
            $seen[] = $key;
            $result[] = [...$manifest, 'digest' => hash('sha256', json_encode($manifest, JSON_THROW_ON_ERROR))];
        }

        return $result;
    }

    /** @param array<string, mixed> $manifest
     * @return array<string, mixed>
     */
    public function validateManifest(array $manifest): array
    {
        $data = Validator::make(['manifest' => $manifest], [
            'manifest' => ['required', 'array:schema_version,id,version,name,core_min,core_max,modules,permissions,terminology,fields'],
            'manifest.schema_version' => ['required', 'integer', 'in:1'],
            'manifest.id' => ['required', 'string', 'regex:/^[a-z][a-z0-9-]{2,39}$/'],
            'manifest.version' => ['required', 'string', 'regex:/^\d+\.\d+\.\d+$/D', 'max:30'],
            'manifest.core_min' => ['required', 'string', 'regex:/^\d+\.\d+\.\d+$/D', 'max:30'],
            'manifest.core_max' => ['required', 'string', 'regex:/^\d+\.\d+\.\d+$/D', 'max:30'],
            'manifest.name' => ['required', 'array:en,bn'],
            'manifest.name.*' => ['required', 'string', 'max:80'],
            'manifest.name.en' => ['required'], 'manifest.name.bn' => ['required'],
            'manifest.modules' => ['required', 'array', 'list', 'min:1', 'max:10'],
            'manifest.modules.*' => ['required', 'string', 'distinct:strict'],
            'manifest.permissions' => ['required', 'array', 'list', 'min:1', 'max:20'],
            'manifest.permissions.*' => ['required', 'string', 'distinct:strict'],
            'manifest.terminology' => ['required', 'array:lead,customer'],
            'manifest.terminology.lead' => ['required', 'string', 'max:40'],
            'manifest.terminology.customer' => ['required', 'string', 'max:40'],
            'manifest.fields' => ['present', 'array'],
        ])->validate()['manifest'];
        app(CustomFieldSchema::class)->definitions($data['fields']);
        $registry = config('platform.modules');
        $knownPermissions = array_unique(array_merge(...array_values(config('platform.roles'))));
        foreach ($data['modules'] as $module) {
            if (! isset($registry[$module])) {
                throw ValidationException::withMessages(['package' => 'Unknown module.']);
            }
            foreach ($registry[$module]['dependencies'] as $dependency) {
                if (! in_array($dependency, $data['modules'], true)) {
                    throw ValidationException::withMessages(['package' => 'Missing module dependency.']);
                }
            }
        }
        foreach ($data['permissions'] as $permission) {
            if (! in_array($permission, $knownPermissions, true)) {
                throw ValidationException::withMessages(['package' => 'Unknown permission.']);
            }
        }
        $visited = [];
        $visiting = [];
        $walk = function (string $module) use (&$walk, &$visited, &$visiting, $registry): void {
            if (isset($visiting[$module])) {
                throw ValidationException::withMessages(['package' => 'Cyclic module dependency.']);
            }
            if (isset($visited[$module])) {
                return;
            }
            $visiting[$module] = true;
            foreach ($registry[$module]['dependencies'] as $dependency) {
                $walk($dependency);
            }
            unset($visiting[$module]);
            $visited[$module] = true;
        };
        foreach ($data['modules'] as $module) {
            $walk($module);
        }
        if ($data['fields'] !== [] && ! in_array('crm', $data['modules'], true)) {
            throw ValidationException::withMessages(['package' => 'Lead custom fields require CRM.']);
        }
        if (version_compare($data['core_min'], $data['core_max'], '>')) {
            throw ValidationException::withMessages(['package' => 'Invalid core compatibility range.']);
        }

        return $data;
    }

    /** Read-only impact preview. No installation, published-version change or data migration.
     * @return array<string, mixed>
     */
    public function preview(string $id, string $version, int $expectedVersion): array
    {
        $ctx = app(TenantContext::class);
        $ctx->tenant(); // Fail closed before catalog lookup.
        abort_unless($ctx->allows('configuration.preview'), 403);
        $current = app(ConfigurationService::class)->current();
        if ($current['version'] !== $expectedVersion) {
            throw ValidationException::withMessages(['version' => 'Configuration changed. Reload before previewing.']);
        }
        $manifest = collect($this->all())->first(fn (array $item): bool => $item['id'] === $id && $item['version'] === $version);
        abort_if($manifest === null, 404);
        $blockers = [];
        $core = config('industry_packages.core_version');
        if (version_compare($core, $manifest['core_min'], '<') || version_compare($core, $manifest['core_max'], '>')) {
            $blockers[] = 'Incompatible core version.';
        }
        foreach ($manifest['modules'] as $module) {
            if (config('platform.modules.'.$module.'.status') !== 'available') {
                $blockers[] = 'Module unavailable: '.$module;
            }
            if (! in_array($module, config('industry_packages.preview_entitlements', []), true)) {
                $blockers[] = 'Module outside server preview allowance: '.$module;
            }
        }
        foreach ($manifest['permissions'] as $permission) {
            if (! $ctx->allows($permission)) {
                $blockers[] = 'Permission required: '.$permission;
            }
        }
        // Current terminology is an immutable tenant override only if a published version exists.
        $effective = $current['version'] > 0 ? $current['terminology'] : $manifest['terminology'];

        return ['manifest' => $manifest, 'base_version' => $current['version'], 'terminology' => $effective,
            'preserved_override' => $current['version'] > 0, 'blockers' => $blockers, 'preview_only' => true];
    }
}
