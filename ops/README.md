# Preview infrastructure assets

These are reviewed operator assets, not tenant plugins or automatically executed
application migrations. They contain no credentials. Read the preview runbook
and inspect the exact account, project, paths and existing grants before use.

## PostgreSQL

- `postgres/preview-bootstrap.sql`: one-time private schema and disabled-login
  migration/runtime roles. It fails on existing names and must run atomically.
- `postgres/preview-privilege-check.sql`: administrator regression probe; checks
  owner-created defaults and explicit runtime DML, denies CREATE/TRUNCATE/function
  EXECUTE/ungranted UPDATE, and rolls back all probe objects and rows.
- `postgres/preview-runtime-grants.sql`: explicit named-table privileges after
  Laravel migrations. Runtime cannot mutate audit/configuration history, perform
  DDL, read migration history, or use persistent job tables in sync queue mode.

The selected Supabase project was bootstrapped through its DDL migration tool.
Laravel application tables still use Laravel's existing migration history; do not
run Supabase schema diff/pull over system schemas or replace application migrations.
Migration-history role operations may contain SCRAM verifiers, not plaintext
passwords; keep that administrative history inaccessible to runtime/public roles.
Do not commit verifiers or credentials. Never provision the app with the `postgres`
administrator credentials. NOLOGIN is changed only after private server-side secret
generation; credentials expire and require a documented rotation workflow.

Existing Laravel tenant scopes/backend authorization remain essential. Private
schema access is not database-level tenant RLS. No RLS implementation is implied.

## Hostinger

- `hostinger/public-index.php`: public entrypoint template. Replace exactly one
  placeholder with the verified private `current` symlink path; never deploy the
  unsubstituted template or rely on request-provided paths.
- `hostinger/public.htaccess`: CRM-directory-only PHP 8.4 handler and Laravel
  rewrites, with private-path denial. This is not PHP extension isolation.

App releases, vendor, `.env`, storage, certificate and bootstrap cache stay outside
`public_html`, under a private owner-only parent. Public entrypoint and built assets
are copied into the existing CRM root; do not symlink the whole private source tree
into web root. Keep previous public files/private releases for recovery. No public
migration runner, phpinfo page, command endpoint, dev server or `hot` file.

The initial deployment is an operator procedure, not GitHub auto-deployment.
Do not advertise a public login page as verified email delivery or completed
tenant E2E. The initial log mailer has been replaced with private Hostinger SMTPS;
a synthetic self-addressed message reached its Inbox. External verification/reset
delivery and authenticated browser E2E remain gates. See `docs/EMAIL_SETUP_BN.md`.
