<?php

namespace App\Http\Controllers;

use App\Modules\Configuration\ConfigurationService;
use App\Modules\CRM\LeadService;
use App\Modules\CRM\Models\Customer;
use App\Modules\CRM\Models\Lead;
use App\Modules\CRM\Models\LeadActivity;
use App\Modules\Tenancy\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class LeadController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $request->validate(['search' => ['nullable', 'string', 'max:100'], 'status' => ['nullable', Rule::in(['new', 'contacted', 'qualified', 'won', 'lost'])]]);
        $query = Lead::query()->withExists('customer')->orderByDesc('created_at')->orderByDesc('id');
        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($builder) use ($search): void {
                $builder->where('name', 'like', '%'.$search.'%')->orWhere('phone', 'like', '%'.$search.'%')->orWhere('email', 'like', '%'.$search.'%');
            });
        }
        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        $context = app(TenantContext::class);

        return Inertia::render('crm/leads', [
            'workspace' => $context->tenant(), 'leads' => $query->paginate(20)->withQueryString(),
            'filters' => $filters, 'terminology' => app(ConfigurationService::class)->current()['terminology'],
            'canCreate' => $context->allows('leads.create'), 'canUpdate' => $context->allows('leads.update'),
            'canConfigure' => $context->allows('configuration.publish'),
            'canManageTeam' => $context->allows('team.manage'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate($this->contactRules());
        app(LeadService::class)->create($request->user()->id, $data);

        return back();
    }

    public function update(Request $request, string $tenant, string $lead): RedirectResponse
    {
        Lead::whereKey($lead)->firstOrFail();
        $data = $request->validate([...$this->contactRules(true), 'version' => ['required', 'integer', 'min:1'], 'status' => ['sometimes', Rule::in(LeadService::STATUSES)]]);
        $version = (int) $data['version'];
        unset($data['version']);
        app(LeadService::class)->update($request->user()->id, $lead, $version, $data);

        return back();
    }

    public function show(string $tenant, string $lead): Response
    {
        $record = Lead::whereKey($lead)->firstOrFail();
        $context = app(TenantContext::class);
        $phone = $record->getAttribute('phone');
        $email = $record->getAttribute('email');
        $duplicates = Lead::whereKeyNot($lead)->where(function ($query) use ($phone, $email): void {
            $query->whereRaw('1 = 0');
            if ($phone) {
                $query->orWhere('phone', $phone);
            }
            if ($email) {
                $query->orWhereRaw('LOWER(email) = ?', [strtolower($email)]);
            }
        })->orderBy('id')->limit(5)->get(['id', 'name']);

        return Inertia::render('crm/lead-detail', [
            'workspace' => $context->tenant(), 'lead' => $record,
            'activities' => LeadActivity::where('lead_id', $lead)->orderByDesc('created_at')->orderByDesc('id')->paginate(20),
            'duplicates' => $duplicates, 'customer' => Customer::where('lead_id', $lead)->first(['id', 'name']),
            'terminology' => app(ConfigurationService::class)->current()['terminology'],
            'canUpdate' => $context->allows('leads.update'), 'canConvert' => $context->allows('leads.convert'),
            'canAddActivity' => $context->allows('leads.activities.create'),
            'canConfigure' => $context->allows('configuration.publish'), 'canManageTeam' => $context->allows('team.manage'),
        ]);
    }

    public function activity(Request $request, string $tenant, string $lead): RedirectResponse
    {
        Lead::whereKey($lead)->firstOrFail();
        $data = $request->validate(['version' => ['required', 'integer', 'min:1'], 'kind' => ['required', Rule::in(['note', 'call', 'meeting'])], 'body' => ['required', 'string', 'max:3000'], 'request_key' => ['required', 'uuid']]);
        app(LeadService::class)->addActivity($request->user()->id, $lead, (int) $data['version'], $data['kind'], $data['body'], strtolower($data['request_key']));

        return back();
    }

    public function convert(Request $request, string $tenant, string $lead): RedirectResponse
    {
        Lead::whereKey($lead)->firstOrFail();
        $data = $request->validate(['version' => ['required', 'integer', 'min:1']]);
        app(LeadService::class)->convert($request->user()->id, $lead, (int) $data['version']);

        return to_route('leads.show', ['tenant' => $tenant, 'lead' => $lead]);
    }

    /** @return array<string, array<mixed>> */
    private function contactRules(bool $partial = false): array
    {
        return [
            'name' => [$partial ? 'sometimes' : 'required', 'required', 'string', 'max:150'],
            'email' => ['sometimes', 'nullable', 'email', 'max:254'],
            'phone' => ['sometimes', 'nullable', 'string', 'max:30', 'regex:/^\+?[0-9 ()-]{5,30}$/'],
            'source' => [$partial ? 'sometimes' : 'required', 'required', Rule::in(LeadService::SOURCES)],
            'notes' => ['sometimes', 'nullable', 'string', 'max:3000'],
        ];
    }
}
