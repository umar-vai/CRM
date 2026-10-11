<?php

namespace App\Http\Controllers;

use App\Modules\Configuration\ConfigurationService;
use App\Modules\CRM\Models\Customer;
use App\Modules\Tenancy\TenantContext;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CustomerController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $request->validate(['search' => ['nullable', 'string', 'max:100']]);
        $query = Customer::query()->orderByDesc('created_at')->orderByDesc('id');
        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($builder) use ($search): void {
                $builder->where('name', 'like', '%'.$search.'%')->orWhere('phone', 'like', '%'.$search.'%')->orWhere('email', 'like', '%'.$search.'%');
            });
        }
        $context = app(TenantContext::class);

        return Inertia::render('crm/customers', [
            'workspace' => $context->tenant(), 'customers' => $query->paginate(20)->withQueryString(), 'filters' => $filters,
            'terminology' => app(ConfigurationService::class)->current()['terminology'],
            'canConfigure' => $context->allows('configuration.publish'), 'canManageTeam' => $context->allows('team.manage'),
        ]);
    }
}
