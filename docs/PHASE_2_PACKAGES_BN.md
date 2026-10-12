# Business customization — প্রথম নিরাপদ preview slice

## এখন কী তৈরি হয়েছে

একই core CRM বিভিন্ন business-এর জন্য ব্যবহার করার ভিত্তি: code-shipped catalog-এ
Service CRM এবং Clinic/Wellness CRM, package v1.0.0/schema v1। এটি সম্পূর্ণ clinic
software নয়; appointments, medical records বা billing তৈরি হয়নি। Industry selection
এখনও শুধু workspace metadata; তা package install করে না।

Owner → Configuration → “ব্যবসার প্যাকেজ প্রিভিউ দেখুন” → প্যাকেজ → “প্রভাব দেখুন”।
এতে প্রস্তাবিত Lead/Customer নাম ও অতিরিক্ত field definitions দেখা যাবে। Clinic preview
Inquiry/Patient এবং service-interest/preferred-date দেখায়; Service preview Inquiry/Client।
আগের প্রকাশিত terminology tenant override হিসেবে থাকে; silently overwrite নয়।
Preview settings/lead/customer/audit rows বদলায় না এবং package install করে না।

এই slice-এর repository implementation তৈরি; live deployment/CI ফল verification report-এ
আলাদা লিখতে হবে। GitHub push নিজে hosting আপডেট নয়।

## Technical contract

- Approved manifest keys: schema_version, id, version, translated name, core_min/core_max,
  modules, permissions, terminology, fields। Arbitrary hooks/plugins/code গ্রহণ নয়।
- Core compatibility inclusive min/max semantic numeric versions; prerelease/range expressions নয়।
- Stable package ID + version lookup; duplicates/unknown schema/key/module/permission,
  missing/cyclic dependencies ও lead fields without CRM rejected।
- SHA-256 digest এবং released manifest fixture tests; পুরোনো version edit নয়, নতুন version যোগ।
  Digest signature/trust verification নয়; external registry/upload/install endpoint নেই।
- Server checks available modules, current owner's permissions ও server-defined preview allowance।
  এটি commercial billing entitlement নয়; client entitlement/tenant_id গ্রহণ নয়।
- Preview config version stale হলে rejected। Backend authenticated membership/tenant context;
  foreign workspace 404, unauthorized role 403; no-store/throttle; finally context clear।
- Five supported field types: text (max 500 Unicode characters), 32-bit integer (strict PHP/JSON int),
  boolean (strict boolean), real ISO calendar date (YYYY-MM-DD), allow-listed select।
- Definitions: stable key, lead scope, en/bn label, strict required boolean, unique select options;
  max 20 fields/30 options; reserved core keys forbidden। Unknown values/types/options rejected।
- Values validator is pure code foundation ONLY. No custom-value storage, generated input controls,
  masking/encryption/field-level permission, searchable projection, finance type or formula exists yet।
- No new persistent configuration setting/schema migration in this slice. Existing terminology-only
  immutable publishing contract unchanged; preview is neither draft persistence nor install success।

## Tests / limitations

Eight new feature/validator tests cover read-only/no-store, context cleanup, foreign tenant and roles,
stale config/unknown version, override isolation/preservation, module dependency/cycles,
compatibility/allowance/permission blockers, strict field values/date/limits and manifest version pins।
Full gate/build and CI results belong in VERIFICATION; do not infer passing from test definitions।
No DB lock/schema changes here: previous CRM/team PostgreSQL integration tests stay enabled in CI。
Install/upgrade/uninstall, saved custom-value migration, publish/rollback and their concurrency
tests remain unfinished. Do not claim those workflows completed from this read-only catalog।

## পরের ছোট block — GPT implementation checklist

1. Design additive schema-versioned configuration snapshot envelope; read legacy terminology v1
   without changing existing immutable rows. Include pinned package ID/version/digest and field schema.
2. Separate permissions: view/draft/preview/publish/rollback/install. Server-owned plan entitlements,
   module dependencies and compatibility checked on every relevant mutation/route; not UI-only.
3. Draft/preview references base config version. Publish locks tenant first, rechecks fresh owner,
   validates preview impact and stale version, inserts new snapshot plus redacted audit atomically.
4. Typed lead values require tenant ownership/composite FKs, strict value validator, required-field
   impact against existing leads and schema version pinning. Do not store all core business fields in JSON.
5. Field archive instead of deleting populated definitions. Reject unsafe type/required changes until
   an explicit typed migration exists. No arbitrary PHP/SQL/JS/expressions/plugins.
6. Rollback creates a NEW immutable version from an older compatible snapshot; never edits/deletes
   published history or existing values. Preview data migration implications before commit.
7. Tests: legacy schema migration, cross-tenant reads/writes, role/fresh-role denial, stale preview/publish,
   duplicate installation/retry, audit failure rollback, preserved tenant overrides, required/type-change
   impact, archive and rollback compatibility. Independent PostgreSQL lock tests for new concurrency.
8. Full Pint/PHPStan/PHP/TypeScript/frontend/build; tested coherent commit, SQLite+PG CI, private release,
   narrow grants only if needed, live rollback-only smoke/browser proof, tested/untested documentation。

এই staged design একই codebase থেকে business-ভিত্তিক customization দেবে। Monthly subscriptions,
dedicated customer deployment ও paid entitlements পৃথক commercial/provisioning workflows; এখন নয়।
