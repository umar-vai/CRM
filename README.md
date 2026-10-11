# NovaFlow — configurable ERP + CRM

BizNova-inspired **original implementation**, not a copy of proprietary source code.
Current milestone: a working multi-workspace CRM foundation with owner-managed teams. **Not production-ready ERP.**

## Implemented

- Official Laravel React starter authentication: registration, login, password reset, email verification and 2FA.
- Workspace creation and membership-scoped access.
- Owner, manager, agent, auditor permission registry; owner-managed invitations, role changes and membership removal.
- Single-use, email-bound invitation links: 72-hour expiry, revoke/reissue, token hashing and verified recipient acceptance. Share manually; invitation email delivery is not implemented.
- Ownership cannot be granted, removed or demoted through team management; ownership transfer is a separate future workflow.
- Tenant-scoped leads: create, paginate, search, filter, update status.
- Business terminology: owner publishes immutable versions with stale-write protection.
- Transactional audit records for lead changes, configuration publishing and team changes, excluding contact values and invitation secrets.
- Bengali/English workspace navigation, overview and lead-screen labels based on workspace locale. Full app localization and locale editing are not implemented.

## Local setup

Requirements: PHP 8.4+, Composer 2, Node 22.12+ or 24+, SQLite (quick start) or PostgreSQL.
Enable PHP mbstring, openssl, curl, fileinfo, zip and the relevant PDO driver.

```sh
composer install
npm ci
cp .env.example .env
php artisan key:generate
php artisan migrate
npm run build
php artisan serve
```

On Windows use `Copy-Item .env.example .env` instead of `cp`.
If SQLite database does not exist, accept Laravel's creation prompt.
Visit http://localhost:8000, register, then create a workspace.
Local email uses Laravel's log transport; inspect `storage/logs/laravel.log`.
To add employees: open a workspace as its owner -> Team -> enter email and role -> create/copy the invitation link.
The recipient registers/signs in using that email, verifies it, then reopens the original link and accepts.
The link appears once; reissue invalidates the previous pending link. Never publish it in a repository or logs.
For development run `npm run dev` in a second terminal.
No default admin credential or demo account is shipped.

## Verification

```sh
vendor/bin/pint --test
vendor/bin/phpstan analyse --memory-limit=1G
php artisan test
npm run types:check
npm run test:ui
npm run check
npm run build
```

## Documentation and handoff

- [Product blueprint in Bengali](docs/PRODUCT_BLUEPRINT_BN.md): complete vision, customization model and GPT implementation guide.
- [Architecture and safety rules](docs/ARCHITECTURE.md)
- [Security checklist](docs/SECURITY.md)
- [Milestones and current status](docs/ROADMAP.md)
- [Phase 1 team-management report and Bengali usage guide](docs/PHASE_1_TEAM_BN.md)
- [Hostinger + external PostgreSQL preview runbook](docs/HOSTINGER_PREVIEW_BN.md): selected target, safe server configuration and outstanding deployment gates; not a live-deployment claim.
- [GPT continuation instructions](AGENTS.md)

The blueprint describes the target product, **not a claim that every feature exists**.
See ROADMAP for implemented versus planned scope.
Keep secrets in environment variables; never commit .env or customer exports.
Original scaffold attribution and MIT license declaration are retained.
Scaffold: laravel/react-starter-kit commit 717b8f55aefd82d25d4119eaebdc8e3a72b8d7e5.
