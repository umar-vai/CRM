<?php

namespace App\Modules\CRM;

use App\Modules\Audit\AuditRecorder;
use App\Modules\CRM\Models\Customer;
use App\Modules\CRM\Models\Lead;
use App\Modules\CRM\Models\LeadActivity;
use App\Modules\Tenancy\Models\Tenant;
use App\Modules\Tenancy\TenantContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class LeadService
{
    public const STATUSES = ['new', 'contacted', 'qualified', 'won', 'lost'];

    public const SOURCES = ['direct', 'whatsapp', 'messenger', 'referral', 'website'];

    /** @param array<string, mixed> $data */
    public function create(int $actorId, array $data): Lead
    {
        return DB::transaction(function () use ($actorId, $data): Lead {
            $this->authorizeLocked($actorId, 'leads.create');
            $lead = Lead::create([...$this->normalize($data), 'created_by' => $actorId]);
            $this->event($lead, $actorId, 'created', ['fields' => array_keys($data)]);

            return $lead;
        });
    }

    /** @param array<string, mixed> $data */
    public function update(int $actorId, string $leadId, int $version, array $data): void
    {
        DB::transaction(function () use ($actorId, $leadId, $version, $data): void {
            $this->authorizeLocked($actorId, 'leads.update');
            $lead = Lead::whereKey($leadId)->lockForUpdate()->firstOrFail();
            $this->assertVersion($lead, $version);
            if (Customer::where('lead_id', $leadId)->exists()) {
                throw ValidationException::withMessages(['lead' => 'Converted leads are read-only; their customer snapshot is preserved.']);
            }
            $lead->fill($this->normalize($data));
            $fields = array_keys($lead->getDirty());
            if ($fields === []) {
                return;
            }
            $metadata = ['fields' => $fields];
            $kind = $fields === ['status'] ? 'status_changed' : 'updated';
            if (in_array('status', $fields, true)) {
                $metadata['from'] = $lead->getOriginal('status');
                $metadata['to'] = $lead->getAttribute('status');
            }
            $lead->version++;
            $lead->save();
            $this->event($lead, $actorId, $kind, $metadata);
        });
    }

    public function addActivity(int $actorId, string $leadId, int $version, string $kind, string $body, string $requestKey): void
    {
        abort_unless(in_array($kind, ['note', 'call', 'meeting'], true), 422);
        DB::transaction(function () use ($actorId, $leadId, $version, $kind, $body, $requestKey): void {
            $this->authorizeLocked($actorId, 'leads.activities.create');
            $lead = Lead::whereKey($leadId)->lockForUpdate()->firstOrFail();
            $existing = LeadActivity::where('request_key', $requestKey)->first();
            if ($existing !== null) {
                if ($existing->getAttribute('lead_id') !== $leadId || $existing->getAttribute('actor_id') !== $actorId || $existing->getAttribute('kind') !== $kind || $existing->getAttribute('body') !== $body) {
                    throw ValidationException::withMessages(['request_key' => 'This request key was already used for a different activity.']);
                }

                return;
            }
            $this->assertVersion($lead, $version);
            $lead->version++;
            $lead->save();
            $activity = LeadActivity::create(['lead_id' => $leadId, 'actor_id' => $actorId, 'kind' => $kind, 'body' => $body, 'request_key' => $requestKey, 'metadata' => [], 'created_at' => now()]);
            app(AuditRecorder::class)->record($actorId, 'lead.activity_added', 'lead', $leadId, ['activity_id' => $activity->id, 'kind' => $kind]);
        });
    }

    public function convert(int $actorId, string $leadId, int $version): Customer
    {
        return DB::transaction(function () use ($actorId, $leadId, $version): Customer {
            $this->authorizeLocked($actorId, 'leads.convert');
            $lead = Lead::whereKey($leadId)->lockForUpdate()->firstOrFail();
            $existing = Customer::where('lead_id', $leadId)->first();
            if ($existing !== null) {
                return $existing;
            }
            $this->assertVersion($lead, $version);
            $phone = $lead->getAttribute('phone');
            $email = $lead->getAttribute('email');
            if (($phone || $email) && Customer::where(function ($query) use ($phone, $email): void {
                if ($phone) {
                    $query->orWhere('phone', $phone);
                }
                if ($email) {
                    $query->orWhereRaw('LOWER(email) = ?', [strtolower($email)]);
                }
            })->exists()) {
                throw ValidationException::withMessages(['customer' => 'A customer with this phone or email already exists. Review the customer list; automatic merge is not supported.']);
            }
            $customer = Customer::create(['lead_id' => $leadId, 'name' => $lead->getAttribute('name'), 'email' => $email, 'phone' => $phone, 'source' => $lead->getAttribute('source'), 'created_by' => $actorId]);
            $lead->status = 'won';
            $lead->version++;
            $lead->save();
            $this->event($lead, $actorId, 'converted', ['customer_id' => $customer->id]);

            return $customer;
        });
    }

    private function authorizeLocked(int $actorId, string $permission): void
    {
        $context = app(TenantContext::class);
        $tenantId = $context->tenant()->id;
        // Match team-management lock order: tenant -> business record. Recheck current membership.
        Tenant::whereKey($tenantId)->lockForUpdate()->firstOrFail();
        $membership = DB::table('tenant_memberships')->where('tenant_id', $tenantId)->where('user_id', $actorId)->first();
        abort_if($membership === null, 404);
        abort_unless($context->allows($permission) && in_array($permission, config('platform.roles.'.$membership->role, []), true), 403);
        abort_unless(config('platform.modules.crm.status') === 'available', 403);
    }

    private function assertVersion(Lead $lead, int $version): void
    {
        if ($lead->version !== $version) {
            throw ValidationException::withMessages(['version' => 'This lead changed. Reload before saving again.']);
        }
    }

    /** @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function normalize(array $data): array
    {
        $data = array_intersect_key($data, array_flip(['name', 'email', 'phone', 'source', 'status', 'notes']));
        if (array_key_exists('email', $data)) {
            $data['email'] = empty($data['email']) ? null : strtolower(trim($data['email']));
        }
        if (array_key_exists('phone', $data)) {
            $data['phone'] = empty($data['phone']) ? null : preg_replace('/[ ()-]/', '', $data['phone']);
        }

        return $data;
    }

    /** @param array<string, mixed> $metadata */
    private function event(Lead $lead, int $actorId, string $kind, array $metadata): void
    {
        LeadActivity::create(['lead_id' => $lead->id, 'actor_id' => $actorId, 'kind' => $kind, 'metadata' => $metadata, 'created_at' => now()]);
        app(AuditRecorder::class)->record($actorId, 'lead.'.$kind, 'lead', $lead->id, $metadata);
    }
}
