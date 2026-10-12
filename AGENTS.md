# NovaFlow implementation rules

Read README.md, docs/ROADMAP.md, docs/ARCHITECTURE.md and the relevant blueprint sections before changing code.
Continue the smallest unfinished milestone; do not replace this project with a different stack.
Stack: Laravel modular monolith, Inertia React, TypeScript, Tailwind, PostgreSQL in production; SQLite for local tests.

## Non-negotiable

- Every business-owned record must have tenant ownership and backend authorization. Never trust a submitted tenant_id.
- TenantContext must be established from authenticated membership and cleared even on errors.
- Tenant-aware models fail closed without context; background jobs must explicitly establish and clear context.
- DB/query-builder queries bypass model scopes: always add tenant_id explicitly.
- Never disable scopes to make a failing test pass.
- Mutations and audit events share a DB transaction; do not put contact details or secrets in audit metadata.
- Validate module dependencies and permissions on the server, not just in the UI.
- Configuration versions are immutable. New settings require schema versions, validation, preview, migration and rollback tests.
- No arbitrary tenant-supplied JavaScript, SQL, PHP, expressions or plugins.
- Money uses minor-unit integers or deliberate fixed-precision decimal, never float.
- Accounting and stock ledgers will be append-only; corrections use reversal entries, not deletes.
- Do not claim production readiness, completed integrations or passing checks without evidence.
- Do not commit generated dependencies, local runtime, .env, databases, credentials or real personal data.

## Completion gate

Run PHP formatting, static analysis, feature tests, TypeScript checks, frontend checks and build.
For DB locking/concurrency changes add PostgreSQL integration tests, not only SQLite tests.
Update ROADMAP and provide tested/untested limitations.
Commit coherent milestones. Push only when user authorization covers it (current project request does).
No force pushes or destructive resets.

## Next milestone

Team invitation/acceptance and protected-owner role management are implemented; preserve their regression tests.
CRM activity timeline, lead detail/edit and customer conversion are implemented in the repository;
preserve tenant/permission/stale-write/duplicate/idempotency/audit-rollback tests and PostgreSQL lock tests.
Check the current rollout status before claiming the live preview contains this block.
CRM source b26239d rollout is verified. Read-only package manifests/typed-field validator are implemented;
check their current CI/live status before claiming preview availability.
Continue with schema-versioned immutable configuration envelopes, draft/preview/publish/rollback,
typed custom-value persistence and idempotent installation. Preserve legacy terminology versions.
Do not treat package preview as installation, saved values or commercial billing entitlement.
Ownership transfer and automatic invitation email delivery remain separate future workflows.
Do not start financial modules before posting invariants and reconciliation tests exist.
