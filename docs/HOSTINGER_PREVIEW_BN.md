# Hostinger + বাইরের PostgreSQL: CRM preview runbook

এটি deployment-এর প্রস্তুতি ও কাজের তালিকা; live CRM বা auto-deployment সম্পন্ন হওয়ার দাবি নয়।

## নির্বাচিত ব্যবস্থা

- ঠিকানা: `https://crm.abdullahfahad.com`। Cloudflare-এ নতুন CNAME যোগ করা হয়েছে; অন্য records বদলানো হয়নি।
- Laravel/Inertia app থাকবে বর্তমান Hostinger Business hosting-এ; নতুন VPS কেনা হবে না।
- ব্যবহারকারী নির্বাচিত Supabase project: `zmmpipwcxullyzdbecpl`, Team Fahad Org, Mumbai।
- Supabase শুধু PostgreSQL সরবরাহ করবে। Laravel authentication, email verification, tenant ownership এবং backend permission checks অপরিবর্তিত থাকবে।
- এটি synthetic/test-data preview; বাস্তব business/customer data বা production SLA নয়।

## সর্বশেষ যাচাই করা অবস্থা

- DNS CNAME target: `crm.abdullahfahad.com.cdn.hstgr.net`, DNS only।
- HTTPS 200 পাওয়া গেছে, কিন্তু response হলো Hostinger-এর **Default page**, NovaFlow নয়।
- Supabase dashboard-এ project Healthy এবং `public` schema-তে কোনো table দেখা যায়নি। অন্য schemas খালি বলে দাবি করা হয়নি।
- Browser-এর Supabase account-এ project দেখা যায়; বর্তমানে connected দুই Supabase connector account-এ এই project নেই। কোনো credential/token কপি করা হয়নি।
- User-approved `novaflow-preview-local` SSH key দিয়ে hosting account login পরীক্ষিত। Private key repo-এর বাইরে স্থানীয় Windows user/System/Administrators permissions-এ সীমাবদ্ধ; key-তে passphrase নেই। GitHub বা অন্য service-এ private key দেওয়া হয়নি। এটি account-wide access, শুধু CRM folder-এ সীমিত credential নয়; স্বয়ংক্রিয় expiry নেই। Access revoke করতে hPanel → SSH Access থেকে এই নামের key সরাতে হবে; তারপর স্থানীয় key-ও নিরাপদে সরাতে হবে।
- SSH host key প্রথম connection-এ trust-on-first-use করে আলাদা local known-hosts file-এ pin করা হয়েছে; পরের connection-এ strict checking সফল। Provider-এর independent fingerprint verification এখনো করা হয়নি; GitHub deployment trust setup-এ এই সীমাবদ্ধতা বিবেচনা করতে হবে।
- Explicit CLI binary `/opt/alt/php84/usr/bin/php` দিয়ে PHP 8.4.26 ও Composer 2.9.8 পরীক্ষিত। Default CLI PHP 8.1.34 এবং মূল website PHP 8.2 অপরিবর্তিত। PHP 8.4 CLI-তে `pdo_pgsql` নেই।
- CRM public directory-তে শুধু Hostinger `default.php` আছে। Public root-এর বাইরে নির্বাচিত private app parent writable; app directory/release এখনো তৈরি হয়নি।
- App deployment, remote database provisioning/connection, mail delivery এবং automatic deployment এখনো অসম্পন্ন।

## Database connection: যা প্রস্তুত আছে

`config/database.php` এখন `DB_SEARCH_PATH` এবং `DB_SSLROOTCERT` নেয়। স্থানীয় default যথাক্রমে `public` এবং certificate unset; SQLite test setup অপরিবর্তিত।

Shared-hosting IPv4 connection-এর প্রথম পরীক্ষার জন্য dashboard-এর **Session pooler** endpoint ব্যবহার করা হবে; session/prepared-statement আচরণ বজায় রাখতে transaction pooler port 6543 দিয়ে শুরু করা হবে না।
এই project-এর dashboard-এ দেখা endpoint হলো `aws-0-ap-south-1.pooler.supabase.com:5432`। Deploy-এর আগে আবার Connect dialog দিয়ে যাচাই করতে হবে; region থেকে hostname অনুমান করা যাবে না।

Server-এর private configuration-এ শেষ পর্যন্ত নিচের মানগুলো থাকবে। এগুলো পরিকল্পিত placeholders, কার্যকর credentials নয়:

```dotenv
APP_ENV=staging
APP_DEBUG=false
APP_URL=https://crm.abdullahfahad.com
APP_KEY=<generate-once-and-store-privately>
DB_CONNECTION=pgsql
DB_HOST=<verified-session-pooler-host>
DB_PORT=5432
DB_DATABASE=postgres
DB_USERNAME=<restricted-app-role>.<project-ref>
DB_PASSWORD=<private-secret>
DB_SEARCH_PATH=novaflow
DB_SSLMODE=verify-full
DB_SSLROOTCERT=<absolute-private-path-to-provider-ca-certificate>
SESSION_SECURE_COOKIE=true
SESSION_DRIVER=database
CACHE_STORE=database
LOG_LEVEL=warning
```

`DB_URL` দিয়ে একই settings override হয়ে যাচ্ছে কি না পরীক্ষা করতে হবে। Password বা connection URL browser frontend, VITE variables, repo, deployment logs বা chat-এ দেওয়া যাবে না।
`verify-full` hostname এবং certificate যাচাই করবে; certificate provider dashboard থেকে সংগ্রহ করে server-এর private directory-তে রাখতে হবে। SSL failure ঠিক করতে `prefer`, `disable`, বা certificate-check bypass ব্যবহার করা যাবে না।

## Remote database provisioning — এখনো করা হয়নি

1. শুধু নির্বাচিত project-এর schemas/roles/default grants যাচাই করা এবং প্রয়োজনীয় পরিবর্তন user-কে দেখানো। Existing system schemas বদলানো যাবে না।
2. CRM-এর জন্য unexposed `novaflow` schema তৈরি করা; এটি Supabase Data API exposed schemas-এ যোগ করা যাবে না।
3. Separate migration owner এবং restricted runtime login provision করা। Runtime role schema owner, superuser, role creator বা migration role-এর member হবে না; শুধু প্রয়োজনীয় CRM table/sequence privileges পাবে। Password secret manager/private server configuration-এ থাকবে।
4. `anon`, `authenticated`, `service_role` এবং `PUBLIC`-এর CRM schema/object access নিষিদ্ধ আছে কি না যাচাই করা। Laravel frontend থেকে Supabase Data API ব্যবহার করা হবে না। Exposed schema-তে কখনো business tables যোগ করলে যথাযথ RLS/policies বাধ্যতামূলক।
5. Private schema হওয়া tenant isolation-এর বিকল্প নয়। বর্তমান isolation Laravel TenantContext, model scopes এবং backend authorization-এর ওপর নির্ভরশীল; database-level tenant RLS এখনো implemented নয়।
6. Hostinger থেকে TLS connection, `current_schema()`, role privileges এবং SSL session পরীক্ষা করা। Developer laptop-এর সফল connection Hostinger connectivity-এর প্রমাণ নয়।
7. Migration credentials দিয়ে existing Laravel migrations চালানো; runtime credentials দিয়ে CRUD ও cross-tenant denial smoke tests চালানো। `migrate:fresh`, schema reset বা destructive rollback ব্যবহার করা যাবে না।

## Hostinger-এ নিরাপদ files ও runtime

- Selected CRM domain-এ PHP 8.4+ এবং `pdo_pgsql` বাস্তবে enable/verify করা দরকার। Parent WordPress site-এর PHP settings বদলানোর অনুমতি নেই। CLI PHP এবং web PHP আলাদাভাবে যাচাই করতে হবে।
- CRM-এর Manage site parent domain-এর dashboard খুলছে। শুধু CRM folder-এর `.htaccess`-এ PHP 8.4 handler দেওয়া সম্ভব বলে provider documentation জানায়; এটি extension/options isolation নিশ্চিত করে না। Parent PHP version বদলানো যাবে না। `pdo_pgsql` enable করলে shared/account settings প্রভাবিত হতে পারে—সেই নির্দিষ্ট পরিবর্তনের আলাদা অনুমতি ও CLI/web verification প্রয়োজন।
- পুরো Laravel repo `public_html`-এ clone করা যাবে না। `.env`, `vendor`, source, sessions, logs, certificate এবং config cache public web root-এর বাইরে রাখতে হবে।
- বর্তমান CRM document root একটি আলাদা `public_html/crm` directory। এখানে কেবল Laravel public entrypoint এবং built assets থাকবে; entrypoint private app path ব্যবহার করবে। Private app-এর সুনির্দিষ্ট directory/SSH permissions যাচাইয়ের আগে path অনুমান করে deploy করা যাবে না।
- প্রয়োজন authenticated SSH/SFTP বা সমমানের verified private-file access; PHP/static upload endpoint দিয়ে public webshell, migration runner বা remote-execution endpoint বানানো যাবে না।
- Composer production dependencies ও frontend build install করা হবে। Server-এ `artisan serve`, Vite dev server বা public `hot` file থাকবে না। `APP_KEY` প্রতি deployment-এ পরিবর্তন করা যাবে না।
- App চালাতে Redis বাধ্যতামূলক করে নতুন service কেনা হবে না। Preview-এ database sessions/cache এবং existing queue configuration পরীক্ষা করতে হবে; reliable queue worker setup ছাড়া নতুন async business jobs enable করা যাবে না।
- SMTP/provider দিয়ে registration verification/password-reset email চালাতে হবে। Log mailer publicly expose করা বা email verification globally bypass করা যাবে না। Invitation email delivery এখনো আলাদা deferred workflow।

## GitHub push থেকে update — প্রয়োজনীয় gate

এখন শুধু CI আছে, deployment workflow নেই। Hostinger Git auto-deploy চালু করলেই CI success-এর জন্য অপেক্ষা করবে ধরে নেওয়া যাবে না।

পরিকল্পিত pipeline:

1. Pinned tool/action versions দিয়ে PHP formatting/static analysis, SQLite/PostgreSQL tests, TypeScript/frontend checks এবং build।
2. পরীক্ষা সফল হলে সেই **একই tested commit** থেকে production artifact তৈরি; secrets/dependencies/test DB export বাদ দেওয়া।
3. GitHub staging environment-এর scoped secrets এবং verified SSH host key দিয়ে release upload; concurrent deployments serialize করা। `StrictHostKeyChecking=no` নয়।
4. Backup/rollback readiness যাচাই, pending migration apply, release switch এবং cache refresh।
5. HTTPS/login/email/lead/tenant isolation smoke test; failure হলে last known-good artifact ফিরিয়ে দেওয়া। Destructive database rollback স্বয়ংক্রিয় নয়।
6. Commit SHA এবং deployment result রেকর্ড। General production deployment-এ manual approval থাকবে।

Server runtime, SSH path ও credentials যাচাই না হওয়া পর্যন্ত executable auto-deploy workflow বানিয়ে সফল দাবি করা যাবে না।

## Preview চালু হওয়ার acceptance gate

- [ ] CRM domain-এ PHP 8.4+ ও pgsql driver, CLI এবং private file access verified।
- [ ] Runtime credentials frontend/logs/repo-তে নেই; `.env`, app source, private certificate এবং logs HTTP দিয়ে পড়া যায় না।
- [ ] Hostinger -> Supabase verified TLS connection; private schema এবং role grants verified।
- [ ] Laravel migrations, registration/login, email verification ও password reset verified।
- [ ] দুটি test workspace দিয়ে unauthorized/cross-tenant access denied।
- [ ] Owner invitation ও protected-owner regression preserved।
- [ ] Successful GitHub push updates tested commit; failed tests do not deploy।
- [ ] Backup/export ও isolated restore drill; rollback demonstrably works।
- [ ] Preview synthetic data only; Free plan limits/inactivity pause এবং production-readiness limitations documented।

## Verification of this preparation slice

Configuration tests check environment overrides, backward-compatible defaults এবং Laravel PDO DSN-এ certificate/SSL options যাওয়ার আচরণ; remote connection নয়।

এই preparation-এর local completion gate পাস: Pint, PHPStan (zero errors), ৭৯টি PHP test / ৩৮৫ assertions, frontend formatting/lint, TypeScript, ৩টি UI test এবং build। PostgreSQL test server না থাকায় ২টি concurrency test স্থানীয়ভাবে skipped। Implementation commit `1d3beb8`-এর [SQLite ও PostgreSQL GitHub CI jobs](https://github.com/umar-vai/CRM/actions/runs/38168540383) দুটিই success। Remote schema/roles, certificate/TLS connection, SMTP এবং deployment/rollback পরীক্ষিত নয়।

## Current primary references

- [Supabase connection modes, prepared statements and TLS](https://supabase.com/docs/guides/database/connecting-to-postgres)
- [Supabase Data API grants, exposed schemas and security](https://supabase.com/docs/guides/api/securing-your-api)
- [Supabase pricing and Free-plan limitations](https://supabase.com/pricing)
- [Hostinger subdomain PHP handler এবং extension/CLI limitations](https://www.hostinger.com/support/4047803-how-to-change-the-php-version-for-subfolders-or-subdomains-in-hostinger/)

Provider settings/prices পরিবর্তন হতে পারে; project creation, paid upgrade বা destructive action-এর আগে current settings ও explicit user authorization যাচাই করতে হবে।
