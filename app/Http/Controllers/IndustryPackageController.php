<?php

namespace App\Http\Controllers;

use App\Modules\Configuration\ConfigurationService;
use App\Modules\Configuration\IndustryPackageCatalog;
use App\Modules\Tenancy\TenantContext;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class IndustryPackageController extends Controller
{
    public function index(Request $request, IndustryPackageCatalog $catalog, ConfigurationService $configuration): Response
    {
        $data = $request->validate([
            'package_id' => ['sometimes', 'required', 'string', 'max:40'],
            'package_version' => ['required_with:package_id', 'string', 'max:30'],
            'version' => ['required_with:package_id', 'integer', 'min:0'],
        ]);

        return Inertia::render('configuration/packages', [
            'workspace' => app(TenantContext::class)->tenant(),
            'configurationVersion' => $configuration->current()['version'],
            'packages' => $catalog->all(),
            'preview' => isset($data['package_id']) ? $catalog->preview($data['package_id'], $data['package_version'], (int) $data['version']) : null,
            'canManageTeam' => app(TenantContext::class)->allows('team.manage'),
        ]);
    }
}
