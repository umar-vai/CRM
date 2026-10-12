# Hostinger + বাইরের PostgreSQL: CRM preview runbook

## এখন কী চালু আছে — 2026-10-12

[অনলাইন NovaFlow](https://crm.abdullahfahad.com) এখন আসল Laravel/Inertia app; Hostinger default page নয়।
Deployed source: `b26239dab0d4af4341fd850f235e4bd0afd2b9b1`। CRM workflow rollout verified; **GitHub auto-deployment নয়**।
Home, login, registration ও Laravel health page HTTP 200; browser-এ home/login render হয়েছে।
Hostinger SMTP configured; একটি synthetic self-addressed test email Inbox-এ পাওয়া গেছে এবং SPF/DKIM/DMARC pass headers আছে।
User registration email Inbox receipt/link verification confirm করেছেন। Authenticated workspace/leads/customers browser reads পাস;
password reset ও সম্পূর্ণ browser mutation E2E acceptance বাকি।
কোনো public default account/password বানানো হয়নি। এটি synthetic preview, production-ready ERP নয়; বাস্তব customer/financial data ব্যবহার করবেন না।

## ব্যবস্থা ও private files

- Cloudflare CNAME `crm.abdullahfahad.com.cdn.hstgr.net`, DNS only; অনুমোদিত ৭টি mail record যোগ হয়েছে, পুরোনো website/CRM/`auth` records অপরিবর্তিত।
- Existing Hostinger Business account `u148519723`; নতুন VPS/service কেনা হয়নি।
- Supabase `zmmpipwcxullyzdbecpl`, Team Fahad Org, Mumbai, PostgreSQL 17.11। নতুন connected Team Fahad account ব্যবহার হয়েছে; অন্য accounts নয়।
- Supabase শুধু PostgreSQL; Laravel auth, verification, TenantContext ও backend permissions অপরিবর্তিত।

```text
/home/u148519723/domains/abdullahfahad.com/
  novaflow-preview/                       private parent: 0700
    current -> releases/b26239dab0d4af4341fd850f235e4bd0afd2b9b1
    releases/<commit>/                     source/vendor/private config cache
    shared/config/app.env                  runtime settings/secrets: 0600
    shared/config/migration.env            disabled migrator settings: 0600
    shared/config/supabase-ca.crt           provider CA: 0600
    shared/storage/                        cache/views/logs/private files
    shared/bootstrap-backup/               original default.php/.htaccess
    shared/rollout-b26239d/                 previous functional release link/public backup
  public_html/crm/                         index.php/.htaccess/built assets
```

পুরোনো default page ও .htaccess backup নেওয়া হয়েছে; placeholder private `default-original.php` backup-এ সরানো হয়েছে, recoverable।
Source/.env/vendor/certificate/config cache public root-এর বাইরে। Public storage link, command runner, phpinfo, hot file বা dev server নেই।
Temporary provisioning/smoke scripts ও SCRAM-verifier JSON server থেকে সরানো হয়েছে; private env/certificate/backups রাখা হয়েছে।
Current source archive SHA-256 `e74597865b781d2174881da8693ad521ed9ad7818112cac882bddd23ffe470ca`।
Current frontend archive SHA-256 `95a88cc8fc4529271d11129a02ebf63c89378133bbba033311b417676800ee6e`।
Uploaded archive hashes server-এ মেলানো হয়েছে।

## PHP ও access

Explicit CLI `/opt/alt/php84/usr/bin/php` এবং CRM-only `application/x-lsphp84` handler: PHP 8.4.26; Composer 2.9.8।
Web/CLI pdo_pgsql, mbstring, openssl verified। Default CLI 8.1.34 এবং parent WordPress homepage HTTP 200/PHP 8.2.34 অপরিবর্তিত।
Approved hPanel action শুধু pdo_pgsql চালু করেছে; PHP 8.4-এর user-owned `/etc/cl.php.d/alt-php84/alt_php.ini` backup নিয়ে একই driver entry যোগ হয়েছে।
এটি account-এর PHP 8.4 configuration; অন্য PHP 8.4 site-এও driver available হতে পারে, PHP extension isolation নয়।
Backup `/home/u148519723/.novaflow-runtime-backups/php84-before-pgsql-20261012.ini`, original SHA-256 `397eccc42e0025d1fe03ea3695516c6c7565e200b1f60190d2cf5117a05b4996`।
Provider update-এর পরে driver recheck; পরবর্তী edits থাকলে পুরোনো INI অন্ধভাবে overwrite নয়।

Local SSH key `novaflow-preview-local` repo-এর বাইরে Windows user/System/Administrators permissions-এ সীমাবদ্ধ; passphrase/automatic expiry নেই, account-wide access।
GitHub-এ দেওয়া হয়নি। hPanel SSH Access-এ key revoke করা যায়।
Server host key trust-on-first-use pin; strict checking পাস, independent provider fingerprint verification বাকি। Future automation-এর আগে scoped access ও independently verified host identity প্রয়োজন।

## Database: বাস্তবে সম্পন্ন

- Private `novaflow` schema owner `novaflow_migrator`; `novaflow_runtime` owner/admin নয়, CREATE বা migration membership নেই। দুটো role-ই NOSUPERUSER/NOCREATEDB/NOCREATEROLE/NOREPLICATION/NOBYPASSRLS।
- anon/authenticated/service_role/PUBLIC CRM schema access পায় না। Data API settings-এ শুধু public/graphql_public exposed দেখা গেছে; private schema যোগ করা হয়নি।
- Hostinger-এ random APP_KEY/password তৈরি ও private files-এ সংরক্ষণ। Plaintext chat/repo/frontend/connector-এ যায়নি; provisioning-এ কেবল SCRAM verifiers। Administrative migration history-ও private রাখতে হবে।
- Laravel-এর ৮টি migration হয়েছে। ১৮টি table private schema-তে/migrator-owned; নতুন tables public API-তে expose নয়। migrate:fresh/reset/destructive rollback নয়।
- নতুন customers/lead_activities runtime SELECT/INSERT only, UPDATE/DELETE/TRUNCATE/REFERENCES/TRIGGER নয়।
- Named runtime grants: users/sessions/cache/passkeys/memberships প্রয়োজনীয় DML; tenants/leads/invitations SELECT/INSERT/UPDATE; audit/configuration history SELECT/INSERT only।
- Runtime DDL/TRUNCATE/migrations read/history UPDATE/DELETE বা persistent job-table access পায় না। Queue sync।
- Initial migrator login কাজ শেষে **NOLOGIN**। Future migration-এর জন্য reviewed temporary activation/rotation, শেষে disable; automated workflow নেই।
- Runtime credential expiry **2027-01-09 20:17:15 UTC**; তার আগে private rotation দরকার। Automatic rotation নেই।
- Tenant isolation Laravel context/scopes/backend authorization দিয়ে; database-level tenant RLS implemented নয়। Private schema tenant isolation-এর বিকল্প নয়।

### Connection ও transport

Dashboard-verified session pooler `aws-0-ap-south-1.pooler.supabase.com:5432`; transaction pooler 6543 নয়।
Hostinger restricted runtime/migrator PDO login, SQL ও novaflow search path সফল।
Client-to-pooler TLS 1.3 certificate/hostname verification পাস; PDO verify-full ও private provider CA ব্যবহার করে।
pg_stat_ssl pooler-to-PostgreSQL backend hop-এর SSL flag false দেখিয়েছে; সব internal hop encrypted বলা হচ্ছে না।
Provider-internal transport policy production security review-এর বিষয়। Certificate verification bypass হয়নি।

```dotenv
APP_ENV=staging
APP_DEBUG=false
APP_URL=https://crm.abdullahfahad.com
APP_KEY=<private-stable-key>
DB_CONNECTION=pgsql
DB_HOST=aws-0-ap-south-1.pooler.supabase.com
DB_PORT=5432
DB_DATABASE=postgres
DB_USERNAME=novaflow_runtime.zmmpipwcxullyzdbecpl
DB_PASSWORD=<private-secret>
DB_SEARCH_PATH=novaflow
DB_SSLMODE=verify-full
DB_SSLROOTCERT=<absolute-private-provider-ca-path>
SESSION_DRIVER=database
SESSION_ENCRYPT=true
SESSION_SECURE_COOKIE=true
CACHE_STORE=database
QUEUE_CONNECTION=sync
MAIL_MAILER=smtp
MAIL_SCHEME=smtps
MAIL_HOST=smtp.hostinger.com
MAIL_PORT=465
MAIL_USERNAME=noreply@abdullahfahad.com
MAIL_PASSWORD=<private-mailbox-secret>
MAIL_FROM_ADDRESS=noreply@abdullahfahad.com
MAIL_FROM_NAME=NovaFlow
MAIL_EHLO_DOMAIN=crm.abdullahfahad.com
LOG_LEVEL=warning
```

উপরের secrets placeholders; DB_URL override ব্যবহার হয়নি। Secrets VITE variables-এ নয়।
MAIL-only atomic update-এর আগে private `shared/config/app.env.before-smtp-20261012` backup নেওয়া হয়েছে; non-mail settings অপরিবর্তিত যাচাই হয়েছে।
Config cache refreshed; password/cache/backup public নয়। SMTP Inbox receipt-এর সীমিত প্রমাণ [email report](EMAIL_SETUP_BN.md)-এ আছে।
Verification globally bypass/public verification-link endpoint বানানো যাবে না। SMTP/password-reset delivery এবং invitation email আলাদা gate।

## পরীক্ষার ফল

Current CRM rollout-এর full evidence [verification report](VERIFICATION.md)-এ: local ৯৬ tests/৫৮৩ assertions,
৪ frontend tests/build; [SQLite/PG CI](https://github.com/umar-vai/CRM/actions/runs/38176962975) পাস;
live runtime rollback-only CRM workflows ও authenticated workspace/leads/customer list reads পাস।
Private settings hash unchanged, migrator NOLOGIN, old functional release/public assets retained।
নিচের initial deployment checks ঐতিহাসিক baseline; নতুন rollout evidence উপরে।

- Locked Composer production dependencies installed; frontend build deployed; config/route/view caches তৈরি।
- /, /login, /register, /up HTTP 200; browser-এ home/login। /up শুধু Laravel health, business/DB readiness নয়।
- Live session cookie Secure/HttpOnly/SameSite=Lax; XSRF cookie Secure/SameSite=Lax এবং expected JavaScript-readable। Favicon HTTP 200।
- /.env, /composer.json, /vendor/autoload.php, /storage/logs/laravel.log, /novaflow-preview/shared/config/app.env HTTP 403; /default.php 404।
  সব encoded/alternate-route attack পরীক্ষা হয়েছে এমন দাবি নয়।
- Runtime role দিয়ে transaction-only synthetic user/memberships/two workspaces: lead create/read/update, config publish/stale-version rejection,
  redacted audit insertion, foreign-tenant read/write denial এবং missing-context fail-closed সফল।
  Authenticated membership থেকে context; finally clear/rollback এবং tenant-owned test rows না থাকা যাচাই।
  PostgreSQL sequence values rollback হয় না; gaps expected। এটি CLI/service smoke, browser auth/HTTP permissions/concurrency/load test নয়।
- Full local gate: Pint, PHPStan zero errors, ৭৯ PHP tests passed / ৩৮৫ assertions, TypeScript, frontend format/lint, ৩ frontend tests ও build।
  ৮১টি PHP test-এর মধ্যে local PostgreSQL নেই বলে ২ concurrency test skipped; owner/team regressions preserved।
- Deployed 033e9e9-এর [SQLite/PostgreSQL GitHub CI](https://github.com/umar-vai/CRM/actions/runs/38171796567) দুটো success;
  PG CI locking tests চালায়। Live Supabase-এ পুরো test suite চালানো হয়নি।
- Post-migration Supabase security advisors: no findings; complete security audit নয়।

## বাকি acceptance gates

- [x] Private release/public assets/HTTPS pages/restricted DB connection/migrations।
- [x] Private HTTP deny probes/permissions/parent WordPress unchanged।
- [x] Two-workspace CLI isolation/stale-config smoke/rollback; migrator login disabled।
- [x] Approved mail DNS/private SMTPS configuration; একবার synthetic self-send ও read-only Inbox/header confirmation।
- [ ] External verification/reset email delivery; registration → verification → login → workspace/lead/team browser E2E, password reset, 2FA/passkeys।
- [ ] CI-success-only automatic deployment; scoped staging secrets ও independently verified SSH host key।
- [ ] Tested release switch/rollback, database export এবং isolated restore drill। Backup থাকা restore proof নয়।
- [ ] Credential rotation/observability/abuse controls/mobile/accessibility/load audit; Free-plan inactivity/availability mitigation।

## GitHub update ও recovery plan

এখন শুধু CI; GitHub push নিজে live source বদলায় না। Documentation-only commits-ও deployed release বদলায় না।
Pipeline: pinned tools/actions → SQLite/PG checks + build → একই tested SHA artifact → serialized scoped staging upload + strict verified host key →
reviewed migration activation/apply/disable → coordinated assets/release switch/cache → HTTPS/auth/tenant smoke →
failed release-এ previous artifact restore। Automatic destructive DB rollback নয়। সাধারণ production rollout-এ manual approval থাকবে।
Existing account-wide SSH key silently GitHub-এ upload নয়; persistent deployment access-এর scope নির্দিষ্টভাবে review করতে হবে।

Old placeholder backup functional CRM নয়। নতুন rollout-এ functional 033e9e9 release ও সম্পূর্ণ
public CRM backup `shared/rollout-b26239d`-এ retained; restore drill এখনো বাকি।
Public assets copied, symlink নয়; future switch-এ public assets/private current সামঞ্জস্যপূর্ণ রাখা প্রয়োজন।
Migration.env চালানোর সময় cached runtime credentials accidentally ব্যবহার নয়; reviewed uncached migration configuration লাগবে।
APP_KEY প্রতি release-এ regenerate নয়। Restore/rollback drill এবং external auth email/browser acceptance ছাড়া production readiness দাবি নয়।

## Primary references

- [Supabase connections/TLS](https://supabase.com/docs/guides/database/connecting-to-postgres)
- [Supabase API security](https://supabase.com/docs/guides/api/securing-your-api)
- [PostgreSQL roles](https://www.postgresql.org/docs/17/sql-createrole.html)
- [Supabase Free-plan constraints/pricing](https://supabase.com/pricing)
- [Hostinger subdomain PHP](https://www.hostinger.com/support/4047803-how-to-change-the-php-version-for-subfolders-or-subdomains-in-hostinger/)
- [LiteSpeed PHP worker reload](https://docs.litespeedtech.com/lsws/cp/cpanel/php-user-ini/)

Provider prices/settings পরিবর্তনশীল; paid upgrade/destructive operation-এর আগে current state ও specific authorization দরকার।
