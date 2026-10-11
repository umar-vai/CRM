# Verification reports

## External preview preparation — 2026-10-12

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
