# Delivery status

## M0 + first M1 slice — implemented, local verification passed

Official Laravel/React foundation; authentication; workspace creation; tenant isolation;
permission registry; leads create/search/filter/pagination/status; versioned terminology;
transactional audit writes; security-focused feature tests; Bengali product blueprint in repository.
The blueprint's full M0/M1 acceptance criteria are not all complete.
See [verification report](VERIFICATION.md). Foundation PostgreSQL/SQLite CI passed; this phase's CI status is recorded separately.

## M1 team-management slice — implemented, local verification passed

- Owners invite verified recipients through manually shared, hashed, single-use 72-hour links.
- Revoke/reissue, member pagination, invitation history, non-owner role assignment and removal.
- Backend owner checks, cross-tenant isolation, stale-role rejection and immutable owner safeguards.
- Team mutations and redacted audit events commit or roll back together.
- PostgreSQL-only tests exercise independent competing connections against real service row locks; locally skipped because no PostgreSQL server is available. Both PostgreSQL and SQLite CI jobs passed for the implementation commit; see the verification report.

This is a scoped team-management increment, **not all of M1**. Ownership transfer and automatic
invitation email delivery are deferred. See [Bengali phase report](PHASE_1_TEAM_BN.md).

## M1 CRM workflow block — implemented, local and CI gates passed; rollout pending

- Lead detail and partial contact/status edits with required optimistic version.
- Paginated note/call/meeting and system activity timeline; retry-safe activity UUIDs.
- Tenant-scoped normalized-contact duplicate suggestions (at most five); no automatic merge.
- Owners/managers convert a lead once to a customer snapshot; retry returns the existing
  customer without duplicate history/audit. Matching existing customer contact blocks a
  second conversion for review. Converted leads are read-only; activities remain possible.
- Current membership rechecked inside tenant-first transaction locks; changes/history/audit
  roll back together. Tenant-qualified composite foreign keys protect new lead references.
- Customer list/search/pagination; Bengali/English labels and timezone-formatted timeline.
- Local full gate: 96 PHP tests / 583 assertions, 4 frontend tests, Pint, PHPStan,
  TypeScript, frontend checks and build. Five PostgreSQL-only tests skipped locally;
  three new independent-connection lock tests are covered by the successful PostgreSQL CI job.

Implementation commit `b26239dab0d4af4341fd850f235e4bd0afd2b9b1`:
[SQLite/PostgreSQL CI](https://github.com/umar-vai/CRM/actions/runs/38176962975) both succeeded.

The new Laravel migration/operator grants are prepared, not applied to live Supabase.
Live app is still source `033e9e9` until a separately verified rollout. See
[CRM block report](PHASE_1_CRM_BN.md). This is not complete CRM/M1: assignment,
tasks/follow-ups, merge, Kanban, imports, consent and campaign attribution remain planned.

## Next increments

### User-prioritized online preview — initial operator release live

[crm.abdullahfahad.com](https://crm.abdullahfahad.com) now serves NovaFlow from existing
Hostinger Business hosting and the selected Team Fahad Supabase PostgreSQL project.
Deployed source SHA: `033e9e929dd3309603276fee11d0486bf4b37b78`; no stack replacement.
Private app/vendor/storage/config/certificate are outside public_html; public assets
and entrypoint replaced the backed-up default page. Parent WordPress is unchanged.
Seven Laravel migrations and explicit named runtime grants are applied in private
`novaflow` schema. Runtime cannot mutate audit/config history or perform DDL; migrator
NOLOGIN after setup. Restricted Hostinger PDO uses verify-full and provider CA.
Backend pooler-to-database SSL flag false is separate from verified client-to-pooler
TLS. Database tenant RLS is not implemented.
Home/login/register/health returned 200; browser home/login rendered; private-file
HTTP probes denied. Runtime CLI two-workspace transaction smoke passed lead CRUD,
config stale-version checks, tenant read/write denial and audit rollback.
Test rows were rolled back and context cleared. This is not full browser E2E.
Local full gate passed: 79 PHP tests/385 assertions, Pint, PHPStan, TypeScript,
frontend checks, 3 frontend tests and build; 2 PG-only tests skipped locally.
Deployed commit's [SQLite/PostgreSQL CI](https://github.com/umar-vai/CRM/actions/runs/38171796567)
both passed, including PG locking tests. Post-migration Supabase security advisors
returned no findings, not complete security certification.

Remaining gates: password-reset delivery and broader external mail acceptance; authenticated lead/team
browser E2E; scoped CI-gated automatic deployment/independent SSH host verification;
release rollback/isolated DB restore; rotation before runtime expiry
2027-01-09 20:17:15 UTC. No verification bypass/default
account was added. No automatic updates yet.
See [runbook](HOSTINGER_PREVIEW_BN.md) and [verification](VERIFICATION.md).
This infrastructure milestone does not change the next CRM product increment.

Email setup slice: user-approved `noreply@abdullahfahad.com` mailbox created on the
existing Hostinger free email order; Active and authenticated SMTPS from Hostinger.
The specifically approved seven Cloudflare mail records are added; existing website/CRM/
Resend auth records preserved. Private backed-up MAIL-only configuration now uses SMTPS;
config cache refreshed without changing DB/APP_KEY. A single synthetic self-addressed
message was accepted by Laravel's live SMTP transport and found through read-only IMAP
in the approved mailbox Inbox, with SPF/DKIM/DMARC pass headers. No real account or
verification bypass added. External provider delivery, registration/reset browser E2E,
automatic invitation mail and automatic deployment remain separate gates.
See [email report](EMAIL_SETUP_BN.md); self-delivery is not general deliverability proof.
The user subsequently confirmed Inbox receipt and registration-link verification and
provided a workspace dashboard screenshot. Record this as user acceptance, not a
complete automated registration/password-reset/tenant browser test.

### Product increments

1. Roll out the tested CRM block; follow-up CRM assignment/tasks/import/merge remain separate slices.
2. Industry package manifests, module entitlements/dependencies, typed custom fields, draft/preview/publish/rollback.
3. Sales estimates/orders/invoices with explicit monetary and tax rules.
4. Inventory ledger, purchasing, reservations and idempotent posting.
5. Double-entry accounting, financial period controls and reconciliations.
6. Subscription billing, reseller provisioning and white-label domain verification.
7. Ownership transfer with reauthentication and last-owner protection; reliable invitation delivery with queue tenancy and retry tests.
8. Security/performance audit, browser E2E, disaster recovery, pilot businesses.

## Commercial readiness gate

Do not sell the current preview as a complete ERP.
Sell only explicitly scoped paid pilots after tenancy/security review and backup restoration pass.
Before general release: signed support scope, data migration/export, privacy/retention policy,
onboarding, billing reconciliation, incident runbook and demonstrated value with pilot customers.
Pricing and infrastructure costs require current vendor quotes; no live pricing is implied here.
