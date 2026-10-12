# Verification reports

## Industry package preview foundation — 2026-10-12

Read-only code-shipped Service/Clinic CRM manifests and strict five-type field schema/value
validator implemented; eight new tests / 124 assertions. Full local gate passed: 104 PHP
tests / 707 assertions (109 total, five PostgreSQL-only skipped locally), Pint, PHPStan
zero errors, TypeScript, frontend format/lint, four frontend tests and production build.
Initial test helper collided with an inherited `query()` method; renamed to previewQuery.
Permission-blocker fixture initially removed the registry's sole definition of a permission;
corrected fixture distinguishes unknown permission rejection from a known ungranted permission.
Pint found two ignored deployment helper files; formatted them, then reran the full gate successfully.
No tenant scope or failing assertion was disabled to pass checks.

Coverage: tenant/role denial, no-store/read-only/context cleanup, stale base config,
override preservation/isolation, unknown package/version/schema/keys/permissions, missing/cyclic
dependencies, server compatibility/module/allowance blockers, strict boolean/integer/date/select
types/limits, reserved keys and released digest fixtures. Previous CRM/team regressions preserved.
No new DB schema or locking changes; existing five PostgreSQL lock tests remain in CI.
CI and live preview rollout for this new slice are pending, not inferred from local results.
No install/upgrade/uninstall, saved field values, schema migration/publish/rollback or paid
entitlement system implemented. See [phase contract](PHASE_2_PACKAGES_BN.md).

## CRM workflow block — 2026-10-12, implementation and live preview rollout

New detail/edit/activity/conversion/customer-list increment: local gate passed with
96 PHP tests, 583 assertions, 4 frontend tests, Pint, PHPStan zero errors, TypeScript,
frontend format/lint and build. Five PostgreSQL-only tests skipped locally; three new
tests use independent connections and bounded lock timeout for conversion retry,
stale edit and different-lead/same-contact conversion contention.
Implementation `b26239dab0d4af4341fd850f235e4bd0afd2b9b1`:
[CI run](https://github.com/umar-vai/CRM/actions/runs/38176962975) completed successfully
for both SQLite and PostgreSQL jobs, including the existing team and new CRM lock tests.
Feature coverage includes foreign tenant denial, role checks/current-role recheck,
no-context failure, optimistic versions, UUID replay/conflicting intent, duplicate
suggestions/customer review, source snapshots and transactional audit rollback.
Schema tests cover cross-tenant composite FKs and isolated down/up preserving an
existing lead. Migration down is destructive to new customer/activity data; it is
not an authorized or recommended live rollback procedure.

Initial detail-page tests failed because the existing Vite manifest did not include
the new pages; rebuilding resolved those failures. Do not disable tenant scopes or
claim checks passed before running the full post-build gate.

Tested source b26239d is now deployed to Hostinger. Source archive SHA-256
`e74597865b781d2174881da8693ad521ed9ad7818112cac882bddd23ffe470ca`, build archive
`95a88cc8fc4529271d11129a02ebf63c89378133bbba033311b417676800ee6e` matched after transfer.
Existing private env links reused; runtime app.env hash unchanged. New cached config is 0600.
Uncached `--env=migration` ran only the reviewed new Laravel migration; eight total now.
Migrator temporarily LOGIN with existing bounded expiry, restored NOLOGIN in finally.
New private tables migration-role owned; runtime SELECT/INSERT true, UPDATE/DELETE false;
anon/authenticated schema access false. Post-DDL security advisors returned no findings.
New release used real runtime role in two synthetic workspaces inside rollback-only transaction:
edit/stale-write, activity retry, conversion retry/snapshot, duplicate-review, fresh-role denial,
redacted audit, audit-failure rollback, tenant isolation and no-context failure passed.
All synthetic users/memberships/business rows absent after rollback; sequence gaps expected.
Private backup `shared/rollout-b26239d/public` and previous-release link retained; old hashed
assets preserved before atomic current symlink switch. This is not a tested rollback/DB restore.
HTTPS /, /login, /up and new lead-detail asset returned 200; env/vendor/composer/storage/config
probes 403; parent WordPress 200/PHP 8.2.34 unchanged, CRM PHP 8.4.26.
Authenticated browser refreshed existing verified workspace, saw customer menu, opened customer
list and lead list with zero records. No existing business records were mutated for smoke testing.
Full new-block browser mutation/mobile/accessibility testing remains pending.
Local full gate/build rerun successfully before artifact packaging (96 passed/583 assertions,
five PG-only locally skipped). Existing owner/team regression tests remain in the suite.
See [CRM phase report](PHASE_1_CRM_BN.md).

User separately reported receiving and clicking the registration verification email
and supplied a workspace dashboard screenshot. This is user acceptance evidence;
external password-reset delivery and full automated authenticated E2E remain unverified.

## Approved mail DNS + live SMTP integration — 2026-10-12

This supersedes the earlier mailbox-only checkpoint below. Specific approval covered
seven Cloudflare mail records, private SMTP integration and one synthetic test sent
only to `noreply@abdullahfahad.com`; no new purchase or personal-email recipient.

- Fresh Cloudflare UI confirmed 15 records: the eight existing website/CRM/Resend auth
  records plus two MX, one SPF, three DNS-only DKIM CNAMEs and monitoring DMARC.
  Public resolver 1.1.1.1 returned MX/SPF/DKIM answers; DMARC appeared after initial
  negative caching. This is not a claim of propagation to every resolver.
  hPanel status refresh showed all four mail DNS categories with green checks.
- Private env backup 0600; atomic MAIL-only update compared parsed before/after
  non-mail values. DB configuration and APP_KEY unchanged. Laravel config cache
  rebuilt; cached SMTP host/scheme/port/from and credential-match booleans verified.
- Live Laravel SMTP accepted one synthetic message at 2026-10-11 21:04:52 UTC.
  Read-only EXAMINE/UID search over certificate-verified IMAP found the exact
  synthetic subject in Inbox at 21:05:56 UTC. Only relevant headers were fetched:
  DKIM signature present; SPF, DKIM and DMARC pass markers present.
- No verification URL, business data, persistent test account, real user's address,
  new API token or verification bypass was used. Test did not exercise external
  Gmail/Outlook delivery, password reset or authenticated tenant browser E2E.

Post-change HTTPS home/login/register/up returned 200; env/mail credential/backup
HTTP probes returned 403. Parent WordPress remains 200/PHP 8.2.34. /up is framework
health, not database/business readiness.
Private credential/config/backup/receipts checked 0600 outside public_html. Temporary
configuration and IMAP CLI helpers removed; no public helper endpoint was created.
Deployed source remains `033e9e929dd3309603276fee11d0486bf4b37b78`; this mail config
change and documentation push do not establish GitHub auto-deployment.
See [email setup report](EMAIL_SETUP_BN.md).

Post-change local gate passed: Pint; PHPStan zero errors; 79 PHP tests passed,
385 assertions (81 total, 2 PostgreSQL-only skipped locally); TypeScript;
frontend format/lint; 3 frontend tests; production build. No DB locking/schema
changes in this phase. The deployed implementation's PostgreSQL CI evidence remains
the separately linked run below; live Supabase was not used for destructive tests.

## Hostinger mailbox provisioning — earlier checkpoint, 2026-10-12

Specific user-approved mailbox creation succeeded on the existing free email order;
list call and hPanel both show `noreply@abdullahfahad.com` Active with SMTP enabled.
First create was rejected by provider password validation; unused staging secret was
corrected to documented length, not a password reset of an existing mailbox.
Private server-staged credential is not in Git or public files. Hostinger Symfony
SMTPS authentication with certificate validation succeeded; no message was sent.
Cloudflare zone read showed eight existing records, no root MX/SPF/DMARC; no DNS
mutation or app mailer switch. Live application remains log transport. Provider
DNS/integration approval and actual verification/reset delivery remain gates.
See [email setup report](EMAIL_SETUP_BN.md); do not infer delivery from SMTP auth.

Post-change local gate passed again: Pint, PHPStan zero errors, 79 PHP tests passed /
385 assertions (2 PostgreSQL-only skipped, 81 total), TypeScript, frontend format/lint,
3 frontend tests and production build. git diff --check passed. Temporary server
credential-generation/auth-check scripts removed; active mailbox secret retained 0600.

## Initial live Hostinger/Supabase preview — 2026-10-12

Deployed source `033e9e929dd3309603276fee11d0486bf4b37b78` at
[crm.abdullahfahad.com](https://crm.abdullahfahad.com). This supersedes the earlier
preparation checkpoints below; it is not production readiness or auto-deployment.

- Verified source/build archive hashes after transfer; locked Composer production
  dependencies installed; frontend build and config/route/view caches succeeded.
- Seven existing Laravel migrations ran; sixteen private-schema tables are migration-role
  owned, no public tables. Named runtime grants applied, immutable audit/config history
  cannot be updated/deleted/truncated by runtime; schema CREATE and migrations read denied.
- Hostinger restricted PDO connectivity succeeds with provider CA, verify-full and
  private search path. Backend pooler-to-Postgres pg_stat_ssl flag false remains a
  separate internal-transport limitation, not a claim of TLS across every hop.
- Runtime CLI synthetic transaction: two workspaces/authenticated memberships,
  lead create/read/update, config publish/stale-version denial, redacted audit inserts,
  foreign-tenant read/write denial, context fail-closed and complete tenant-row rollback.
  This is not a browser-auth or HTTP permission/concurrency test. Sequence gaps are expected.
- Migrator disabled NOLOGIN after migrations. Runtime credential expires 2027-01-09
  20:17:15 UTC; rotation not automated. Temporary provisioning/diagnostic scripts and
  verifier JSON removed from server; env/certificate remain owner-only private files.
- HTTPS home/login/register/up returned 200 and actual NovaFlow markup. Browser home
  and login rendered. /up is framework health, not DB/business readiness.
- HTTP probes for .env, composer.json, vendor, storage logs and private config returned
  403; old default.php 404. Parent WordPress still 200/PHP 8.2.34; CRM PHP 8.4.26.
- Live session cookie Secure/HttpOnly/SameSite=Lax; XSRF cookie Secure/SameSite=Lax
  and intentionally JavaScript-readable. Public favicon returned 200.
- Supabase security advisors after application migrations returned no findings;
  private schema is not exposed through Data API, tenant RLS is not implemented.
- Deployed commit's [SQLite/PostgreSQL CI jobs](https://github.com/umar-vai/CRM/actions/runs/38171796567)
  both succeeded. Local gate: Pint, PHPStan zero errors, 79 PHP tests passed/385 assertions,
  TypeScript/frontend formatting/lint, 3 frontend tests and build; 2 PG-only tests
  skipped locally (81 total), exercised by PG CI. Live Supabase was not used for destructive CI.

Not completed: SMTP verification/reset delivery; authenticated registration/login/workspace/
lead/team browser E2E; 2FA/passkey browser testing; automatic GitHub deployment; independent
SSH host fingerprint validation; credential rotation; release rollback/DB restore drill;
mobile/accessibility/load/security audit. Staging log mailer does not deliver email.
No default user or publicly disclosed password was created. Existing default-page backups
are not a proven functional CRM rollback. See [runbook](HOSTINGER_PREVIEW_BN.md).

## Private preview database/bootstrap — 2026-10-12

Historical preparation checkpoint; superseded by the live report above.

The newly connected Team Fahad account reaches the selected project. Read-only SQL
confirmed PostgreSQL 17.11, no pre-existing NovaFlow roles/schema, and no public tables.
Data API settings showed only `public` and `graphql_public` exposed; no settings were changed.
One atomic bootstrap attempt failed on owner-specific default privileges and was verified
fully rolled back (no roles or schema left). The corrected script explicitly switches to
the creator role for its default grants, without widening runtime membership.

`ops/postgres/preview-bootstrap.sql` and its transactional privilege probe passed on the
selected remote PostgreSQL project. Schema belongs to `novaflow_migrator`; runtime has USAGE
but not CREATE or migration/admin membership; anon/authenticated/service_role lack schema
access. Both roles lack superuser/create-role/create-db/replication/bypass-RLS privileges.
Probe explicitly allowed runtime INSERT/SELECT, rejected ungranted UPDATE, CREATE, TRUNCATE
and function EXECUTE; all temporary objects and rows were rolled back. Private schema had
zero tables/functions afterwards. Supabase security advisors returned no findings.

With specific user approval, Hostinger's owner-only private directory/configuration and
backups of existing CRM public files were created. Passwords/APP_KEY were generated there
and are not printed, committed, or sent as plaintext to the connector. Role provisioning
received only SCRAM verifiers; administrative Supabase migration history may retain those
verifiers and must remain inaccessible to runtime/public roles. Initial runtime credential
validity is 90 days; migration validity one day. Hostinger PDO logins for both restricted
roles pass with `sslmode=verify-full`, the provider CA and `novaflow` search path.

`pg_stat_ssl` reports false for the pooler-to-PostgreSQL backend hop. This does not replace
the separate verified client-to-pooler TLS test and is not a claim of end-to-end internal TLS.
No database-level tenant RLS is implemented. Runtime table grants, Laravel application
migrations, app publishing, auth/browser E2E, SMTP and release/DB recovery remain outstanding
at this checkpoint. The full local gate and frontend build passed again (79 PHP tests,
385 assertions, 3 frontend tests; 2 PostgreSQL-only tests skipped locally).

## External preview preparation — 2026-10-12

Historical preparation checkpoint; superseded by the live report above.

Implementation commit `1d3beb8` adds configurable private PostgreSQL search path and
CA certificate options, with two isolated configuration tests (9 assertions).
Local gate passed: Pint, PHPStan (zero errors), 79 PHP tests / 385 assertions,
frontend formatting/lint, TypeScript, 3 UI tests and frontend build.
Two PostgreSQL concurrency tests were skipped locally. Both GitHub database jobs
completed successfully: [configuration CI](https://github.com/umar-vai/CRM/actions/runs/38168540383).

Hostinger hPanel shows the user-approved SSH key; authenticated, read-only SSH checks
confirm the CRM document root, writable private parent, explicit PHP 8.4.26 binary
and Composer 2.9.8. Default CLI PHP is 8.1.34; parent website PHP is 8.2, unchanged.
The approved CRM-only `.htaccess` handler serves PHP 8.4.26. Hostinger's extension operation
enabled only `pdo_pgsql` for PHP 8.2; a backed-up user-owned PHP 8.4 INI then received only
the same driver entry. After a user-level LiteSpeed PHP worker reload, web HTTPS and explicit
CLI probes both confirmed PHP 8.4.26 with `pdo_pgsql`, `mbstring` and `openssl` loaded.
The original PHP 8.4 INI backup is outside the web root; future provider updates may replace
this user-owned configuration, so the module check is a deployment prerequisite.
The temporary fixed-output PHP probe was removed and returns HTTP 404. The parent homepage
still returns HTTP 200 with PHP 8.2.34 and WordPress markup. CRM still serves Hostinger's default
page, not NovaFlow. No application release or database changes were deployed.
SSH server identity was pinned using trust-on-first-use, not independently verified.

Hostinger's OpenSSL client reached the dashboard-verified Supabase session pooler and completed
TLS 1.3 with certificate and hostname verification OK using the dashboard-linked Supabase CA.
The CA was downloaded into a task-specific temporary directory, then removed with that directory.
The OS default CA bundle alone rejected the provider-specific chain; verification was never disabled.
No password was submitted and no SQL query ran. This is a TLS transport test, not authenticated
PostgreSQL/PDO connectivity. The selected Team Fahad Org project remains unavailable through
the currently connected Supabase accounts. No password reset was attempted.
The in-app browser remained on Hostinger's automatic browser-check page for the runtime probe;
that screenshot is not runtime proof. HTTPS probe results and the hPanel enabled-driver view
provide separate evidence; end-user browser access still needs testing at app rollout.

Not verified: authenticated remote PostgreSQL/PDO connection, schema/role permissions,
permanent private application/CA configuration, SMTP, live app flows, auto-deployment or
release/DB recovery. Runtime setup and this documentation update do not change application code.
After the runtime documentation update, the full local `composer ci:check` gate and
frontend production build were rerun successfully: Pint, PHPStan (zero errors), 79 PHP tests
/ 385 assertions, TypeScript, frontend formatting/lint and 3 frontend tests. The same two
PostgreSQL-only tests were skipped locally; no new remote database test is implied.

## Team-management increment — 2026-10-11

Passed locally on Windows (PHP 8.4, Node 26):

- Invitation migration applied to the local development database.
- Complete `composer ci:check`: PHP formatting, PHPStan level 7 (zero errors), PHP tests,
  TypeScript, frontend formatting/lint and frontend unit tests.
- PHPUnit: **77 passed, 376 assertions; 2 PostgreSQL-only tests explicitly skipped** (79 total).
  This adds 23 team feature tests to the 54-test foundation.
- Three existing frontend translation/safety unit tests passed. These do not exercise the new React invitation UI.
- Production frontend build, including Team and Accept pages and generated Wayfinder routes/actions.
- Composer validation, Composer audit and npm audit passed; no reported dependency advisories at this time.
- `git diff --check` passed before the milestone commit.

PostgreSQL-specific integration coverage added:

- Two independent database connections: uncommitted real acceptance blocks another acceptance;
  committed retry is rejected, leaving one membership and one acceptance audit event.
- Uncommitted real role change blocks competing removal; stale removal after commit is rejected.

Local PostgreSQL is unavailable; these cases are not claimed as locally passing. The GitHub Actions
PostgreSQL/SQLite matrix runs on push. **Both database jobs completed successfully** for implementation
commit `2b7dae5`: [team-management CI run](https://github.com/umar-vai/CRM/actions/runs/38159158387).
The PostgreSQL job exercises the two connection/locking tests; SQLite explicitly skips them.
The latest local count includes five additional assertions for encrypted-session delivery, response
flash and no-store behavior added after that implementation commit; the full local CI suite was rerun.
The earlier foundation CI completed successfully: [foundation run](https://github.com/umar-vai/CRM/actions/runs/38151523906).

Not verified: browser E2E (including auth/verification fragment redirects, clipboard behavior and
React StrictMode interaction), accessibility/mobile UX, mail delivery, load/lock-contention tests,
backup restoration, production rollout/rollback, external integrations or billing.
Manual invitation sharing is implemented; automatic invitation email sending and ownership transfer are not.

## Foundation verification — 2026-10-11

### Passed locally

- SQLite migrations on a new local database.
- PHP syntax checks and Pint formatting/check.
- PHPStan level 7: zero errors, no suppressions or baseline.
- PHPUnit: 54 tests passed, 203 assertions. 14 are workspace/CRM/security regression tests.
- TypeScript `tsc --noEmit`.
- Frontend formatting and type-aware lint: no warnings/errors.
- Three Node unit tests for Bengali/English translation and prototype-key lookup safety.
- Production frontend build, including generated Wayfinder routes/actions.
- Composer validate --strict.
- Composer audit and npm audit: no reported advisories at the time of verification.
- git diff --check.

### Fixes discovered during verification

- Restricted account closure while workspace memberships/business history exist.
- Preserved logout-before-delete behavior to avoid remember-token persistence re-creating a deleted model.
- Membership middleware now checks an explicit boolean condition.
- Settings redirect is GET-only; avoids an unused generated all-method redirect helper with an unsupported HTTP QUERY method.
- Formatter's transitive tinypool dependency is overridden to patched 2.1.2.

### Not yet verified

- PostgreSQL runtime/concurrency locally; CI provides a SQLite/PostgreSQL matrix.
- Browser end-to-end flows, mobile/assistive-technology audit.
- Load testing, disaster recovery, queue tenancy, external integrations.
- Live production infrastructure or billing.

These checks establish a development foundation, not certification that the platform is production ready.
Dependency audit results are time-specific and should be rerun in future sessions.
