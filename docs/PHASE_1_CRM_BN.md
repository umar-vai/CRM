# CRM workflow block — 2026-10-12

## কী তৈরি হয়েছে

এই block repository-তে implemented; live rollout এখনো pending। পুরোনো live preview-তে
নতুন menu/screens দেখা যাবে না যতক্ষণ migration, সীমিত grants ও release switch verify হয়।

1. Lead list-এ নাম click করলে বিস্তারিত page। নাম, email, phone, source, status ও initial notes edit।
2. একই lead অন্য কেউ বদলালে পুরোনো form save হবে না; “Reload latest data” দিয়ে নতুন version নিতে হবে।
3. Note, phone call ও meeting-এর text যোগ করা এবং timeline দেখা; create/edit/status/conversion system events।
4. Owner/manager lead-কে customer বানাতে পারেন। Lead সফল হয়, customer-এর contact/source snapshot থাকে।
5. একই conversion পুনরায় পাঠালেও এক customer/history/audit। Converted lead-এর মূল তথ্য read-only;
   তবে নতুন activity যোগ করা যায়। Customer edit/merge এখন নেই।
6. Customer list/search/pagination এবং originating lead timeline link। বাংলা/English workspace labels।
7. একই workspace-এ phone/email মিলে এমন সর্বোচ্চ পাঁচ lead suggestion। Matching customer আগে থাকলে
   দ্বিতীয় conversion blocked—নিজে review করতে হবে, automatic merge নয়।

## ব্যবহার করার নিয়ম — rollout-এর পরে

Workspaces → business → Leads → lead-এর নাম → তথ্য edit/Save অথবা কার্যক্রম যোগ করুন।
Customer বানানোর আগে তথ্য ও duplicate suggestion দেখুন → “কাস্টমার বানান” → Customers list।
Timeline নতুন event আগে দেখায়, workspace timezone-এ সময় দেখায়, প্রতি page-এ ২০ event।
পুরোনো lead-এর আগে ঘটে যাওয়া কাজ কল্পনা করে timeline backfill করা হয়নি।

| Role    | Detail/customers দেখা | Edit/activity | Customer conversion |
| ------- | --------------------- | ------------- | ------------------- |
| Owner   | হ্যাঁ                 | হ্যাঁ         | হ্যাঁ               |
| Manager | হ্যাঁ                 | হ্যাঁ         | হ্যাঁ               |
| Agent   | হ্যাঁ                 | হ্যাঁ         | না                  |
| Auditor | হ্যাঁ                 | না            | না                  |

## নিরাপত্তা ও data rules

- Submitted tenant_id/created_by ownership গ্রহণ নয়; authenticated membership/context থেকে workspace।
- Missing context fail closed; request শেষে exception হলেও clear। Foreign lead ID → 404।
- Backend permissions এবং lock-এর পরে বর্তমান membership recheck; UI hiding-এর উপর নির্ভর নয়।
- Tenant → lead lock order, short transaction; data/history/redacted audit একসঙ্গে commit/rollback।
- Version প্রতিটি actual mutation-এ বাড়ে। একই-value edit no-op। Note retry UUID একই intent হলে no-op;
  অন্য lead/actor/text/type দিয়ে একই UUID reuse করলে error।
- Email lowercased; phone-এর space/brackets/hyphen বাদ। Country inference/fuzzy matching নেই।
  একই number shared হতে পারে—suggestion কাউকে একই ব্যক্তি বলে নিশ্চিত করে না।
- Tenant-aware composite foreign keys, এক lead-এর এক customer, activity UUID uniqueness।
- Audit-এ phone/email/name/note text নয়; শুধু action/field names/IDs/status/type।
- New customer/activity runtime grants SELECT/INSERT only; grant script prepared, applied নয়।
- Detail/customer responses no-store; free-text React escaped display, arbitrary HTML/code execute নয়।

## Tests ও সীমাবদ্ধতা

Local full gate passed: ৯৬ PHP tests / ৫৮৩ assertions; Pint; PHPStan zero errors;
TypeScript; frontend formatting/lint; ৪ frontend tests; production build।
মোট ১০১ PHP tests-এর ৫ PostgreSQL-only local environment-এ skipped। তিনটি নতুন lock test
দুই independent connection দিয়ে conversion retry, stale edit ও duplicate contact race পরীক্ষা করে;
Implementation commit `b26239dab0d4af4341fd850f235e4bd0afd2b9b1`-এর
[GitHub CI](https://github.com/umar-vai/CRM/actions/runs/38176962975)-তে SQLite ও
PostgreSQL দুই job success; নতুন CRM ও আগের team lock tests PG job-এ covered।
Schema test isolated database-এ down/up existing lead রাখে; কিন্তু down নতুন customer/activity
tables মুছে দেয়। Live rollback-এর জন্য পুরোনো code restore ও additive schema রেখে দিন;
data-destroying migrate:rollback/fresh নয়। Backup restore drill এখনো সম্পন্ন নয়।

নতুন UI-এর পূর্ণ browser interactions/mobile/accessibility/performance testing বাকি।
Customer dedup DB-wide unique phone/email constraint নয়: service tenant lock ও review policy;
admin/raw SQL writer একই contract না মানলে contact duplicates সম্ভব।
Workspace lock safety-oriented; high traffic-এ contention measurement প্রয়োজন।
এটি সম্পূর্ণ CRM/ERP নয়: assignment, task/follow-up, import, merge, Kanban, consent,
campaign pipeline, customer editing, appointments, sales, inventory/accounting বাকি।

## Deploy ও পরবর্তী ধাপ

CI pass → private release/build backup → reviewed Laravel migration → explicit new-table grants →
coordinated public assets/private release switch → HTTPS এবং authenticated smoke।
Runtime/migrator permissions, SMTP, অন্য websites ও existing business rows preserve করতে হবে।
Temporary migration LOGIN শেষে NOLOGIN; APP_KEY/DB runtime secrets regenerate নয়।
GitHub push এখনো নিজে auto-deploy নয়।
Current server source `033e9e9`; নতুন rollout-এর specific confirmation চাওয়া হয়েছে,
উত্তর না পাওয়া পর্যন্ত নতুন migration/grants/live files পরিবর্তন হয়নি।

Rollout verification-এর পরে পরবর্তী development block: industry package manifest/versioning,
module dependency/entitlement validation এবং typed custom-field schema/preview/rollback।

[PostgreSQL locking](https://www.postgresql.org/docs/17/explicit-locking.html) এবং
[constraints](https://www.postgresql.org/docs/17/ddl-constraints.html) design references।
