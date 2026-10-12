# Foundation architecture

## Request boundaries

Authenticated verified user -> workspace membership middleware -> TenantContext -> permission middleware -> controller -> domain service -> tenant-scoped model -> transaction and audit.
Non-members see 404; members without a permission see 403.
Lead IDs from another workspace also return 404.
TenantContext is request-scoped and cleared in finally.
Model global scopes are a defense layer, not database RLS; raw queries need explicit tenant predicates.
No database-level tenant RLS is implemented yet.
Indexes cover membership-by-user, tenant/status/time, tenant/time, contact lookup and actor foreign keys.
Wildcard name/email/phone search needs measured PostgreSQL query plans and possibly trigram indexing at scale.
Database best-practices review influenced these indexes and the short transaction boundaries.

## Modules

- Tenancy: workspace, memberships, role registry, owner-managed invitations/team changes and fail-closed model trait.
- CRM: tenant-owned leads, versioned edits, paginated activities and conversion snapshots/customer search.
- Configuration: tenant-specific terminology snapshots and optimistic version check.
- Audit: append-only application writes of metadata for covered operations. DB credentials can still alter rows; DB immutability controls are a future hardening task.

Configuration publishing locks the tenant row then compares expected version inside the transaction.
Unique (tenant_id, version) is an additional guard.
SQLite tests verify behavior, not PostgreSQL row-lock concurrency.
Only terminology can be published today. A code-shipped, versioned Service/Clinic CRM catalog
and strict five-type field validator provide a read-only impact preview, not installations/values.
Package ID/version, digest fixture, schema, dependencies/cycles, core compatibility and server
preview allowance are checked. Published tenant labels win over proposed package labels.
Owner-only preview checks base configuration version; it has no business writes or audit mutation.
No billing entitlements, saved drafts, custom values, install/upgrade or rollback exist yet.
See PHASE_2_PACKAGES_BN.md for this contract and the next safe persistence increment.
The universal customization blueprint is the target design:
shared core -> industry package -> tenant overrides. Industry selection currently records metadata only.

## Data ownership

User is global; tenant membership grants role per workspace.
Tenant-owned lead/configuration/audit rows reference a tenant.
Foreign keys prevent orphaning audited tenants. Hard-delete UI is intentionally absent.
Self-service account deletion is blocked while memberships or historical business records exist.
This is a retention safeguard, not a complete account-closure/privacy workflow.
Lead email is lowercased and phone display separators stripped; no country-code inference.
Detail suggests up to five same-workspace leads sharing a phone or email. No fuzzy/global
deduplication, automatic merge or contact uniqueness is assumed. Source labels such as
WhatsApp do not imply a live integration.
Membership provisioning uses owner-only team management and authenticated, verified recipient acceptance.
Never add an unauthenticated membership-write endpoint.

## Team and invitation protocol

All team changes lock the tenant row first, then any relevant invitation/member row.
Short transactions serialize conflicting changes per business and recheck current owner membership
after acquiring the lock. Role changes/removals also compare the expected role from the UI;
stale requests cannot silently overwrite newer changes. Owners are never assignable/removable here.
This design favors predictable safety for small teams; per-tenant lock contention needs load measurement.

Invitations contain normalized email, allowed role, 72-hour expiry and a SHA-256 hash of a random
256-bit token. Reissue revokes older pending invitations for the recipient inside the same transaction.
An acceptance requires the original token, matching freshly checked verified email, pending status,
unexpired deadline and an inviter who is still an owner. Existing memberships are not upgraded by invitations.
Membership insertion, invitation acceptance and redacted audit event share one transaction.
Acceptance establishes TenantContext only for that recipient/business audit and clears it in finally.

The link's raw token is a URL fragment, not a server GET URL/query or referrer. It is sent in the
explicit acceptance POST body and excluded from validation old-input flashing. The React page removes
the fragment from browser history after reading it into ephemeral component state. Tokens remain secrets:
HTTP body logging, error-reporting replay and analytics must redact them in any future deployment.
Owner link delivery uses encrypted short-lived session flash plus native Inertia flash, not page props
persisted in browser history. Team/join responses use no-store and no-referrer headers.
Auth/verification redirects may lose the fragment; recipients must reopen the original link after login.

Automatic invitation emails are not sent. Register/verification email transport is a separate auth feature.
GET does not accept an invitation. Expired/revoked/accepted links render an inactive message for the matching recipient.
Unknown or mismatched invitations return 404. Mutation routes have throttles in addition to auth, CSRF and permissions.

PostgreSQL tests retain real service locks inside an uncommitted outer transaction and use a second
connection with a bounded lock timeout. They check blocked double acceptance, one resulting membership/audit,
and competing role-change/removal with stale retry rejection. This is deterministic lock integration testing,
not a stress test or proof of every possible concurrent schedule.

## Production blockers

### CRM workflow transaction protocol

All CRM writers lock tenant first, then lead, matching team/configuration lock order.
Fresh membership permission is rechecked under the workspace lock. Detail/status edits,
activities and conversion require the lead's integer version; actual writes increment it.
No-op edits do not create history. Activity UUID retries are checked before stale version;
same actor/lead/kind/body returns success without new rows, conflicting reuse is rejected.
Conversion retries return the existing customer before version comparison; first conversion
copies contact/source to a read-only snapshot, sets won and emits a redacted system event.
A workspace lock serializes competing different-lead conversions with matching contacts;
review is required instead of silent merge. This coarse lock needs contention/load testing.

Composite (tenant_id, lead_id) foreign keys refer to unique (tenant_id, id) on leads.
One customer per tenant/lead and unique tenant/activity request key enforce duplicate guards.
Indexes start with tenant and lead for timeline pagination; actors have FK indexes.
Activity note text lives in authorized business records, never audit metadata. GET responses
for detail/customer list use no-store. Legacy leads are not given invented historical events.
Live rollout of tested source b26239d applied the CRM migration and named runtime grants:
SELECT/INSERT only on snapshots/activities, no UPDATE/DELETE. Migrator is NOLOGIN again.
Private prior release/public backup and retained hashed assets support code recovery;
actual recovery and isolated database restore drills remain untested.

### Remaining production gates

- Broader PostgreSQL concurrency/load coverage, backup/restore drill and migration rollout/rollback.
- Invitation delivery integration, ownership-transfer workflow, audit viewer, retention and deletion policy.
- Rate limits/quotas beyond workspace creation, abuse controls and observability.
- Full Bengali localization, timezone-aware UI, accessibility and browser E2E tests.
- Queue worker tenancy tests and integration idempotency before enabling async jobs.
- Subscription billing, provisioning, domain verification and support tooling.
- Formal security review, tenant-isolation adversarial tests and financial compliance review.

## Deployment target

Containerized PHP application + web server, PostgreSQL, Redis queues/cache, object storage,
TLS load balancer, separate staging, CI migrations and tested backup restoration.
Use managed services initially when their support and operational cost suit the budget.
The user-prioritized synthetic-data preview is now live at `crm.abdullahfahad.com`,
using existing Hostinger PHP hosting plus external Supabase PostgreSQL. It is not a
stack replacement or a production-readiness claim. Private app/releases/configuration
are outside public_html; only the entrypoint and built assets are public. Runtime
credentials have named-table privileges and the migration role is NOLOGIN after setup.
Hostinger SMTPS is configured; a synthetic self-addressed message reached the mailbox
Inbox with SPF/DKIM/DMARC pass headers. External verification/reset delivery,
authenticated browser E2E, automatic deployment and recovery drills remain gates.
`DB_SEARCH_PATH` supports an unexposed private application
schema; `DB_SSLROOTCERT` supports CA verification with `DB_SSLMODE=verify-full`.
These settings do not provision a schema, enforce role privileges, or implement tenant RLS.
See the [preview runbook](HOSTINGER_PREVIEW_BN.md) for tested/untested evidence and remaining gates.
Production must disable APP_DEBUG and use distinct rotated secrets.
Use `composer install --no-dev --optimize-autoloader` for production artifacts.
Local setup uses the normal PSR autoloader to avoid an expensive Windows classmap scan.
Never run composer dev or the Vite development server as production hosting.
