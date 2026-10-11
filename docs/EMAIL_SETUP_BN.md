# CRM email setup — 2026-10-12

## সম্পন্ন

- User-approved Hostinger account `u148519723`, domain `abdullahfahad.com`-এ `noreply@abdullahfahad.com` তৈরি হয়েছে। Mailbox Active; incoming/outgoing SMTP enabled। কোনো plan/order কেনা হয়নি।
- Existing Free Business Email order-এ 100 seats, create-এর পরে 99 অবশিষ্ট; mailbox outbound limit 100 messages/rolling 24 hours। এই সীমায় বড় SaaS-এর delivery capacity ধরে নেওয়া যাবে না।
- Password hosting server-এ cryptographic randomness দিয়ে তৈরি; private parent 0700-এর মধ্যে `shared/config/mailbox-secret.json` 0600-এ রাখা হয়েছে। Chat/repo/frontend/public files-এ secret নেই। Approved mailbox creation API-তে password পাঠানো হয়েছে; এটি Supabase-এর SCRAM-only provisioning-এর থেকে আলাদা workflow।
- First create rejected (HTTP 422); provider documentation-এর 8–50 character limit অনুযায়ী unused staging password সংশোধন করে দ্বিতীয় request সফল। অন্য mailbox-এর password reset হয়নি।
- Hostinger থেকে Symfony SMTP transport দিয়ে `smtp.hostinger.com:465`, implicit TLS, default certificate verification এবং mailbox authentication সফল। পরে live Laravel transport দিয়ে একটি অনুমোদিত synthetic self-addressed test পাঠানো হয়েছে; নিচে receipt-এর সীমিত প্রমাণ আছে।
- hPanel mailbox list-এ Active দেখা গেছে। DNS update-এর পরে status check-এ MX/SPF/DKIM/DMARC green checks দেখা গেছে; green status inbox placement guarantee নয়।

## DNS: অনুমোদিত ৭টি mail record যোগ হয়েছে

Cloudflare account-এর `abdullahfahad.com` zone-এ 8টি existing record এবং conflict checks-এর পরে user-এর নির্দিষ্ট approval অনুযায়ী নিচের ৭টি record যোগ হয়েছে। Fresh UI-তে 15টি record দেখা গেছে। Root/website A records, CRM CNAME, nameservers এবং `auth` subdomain-এর Resend-related records অপরিবর্তিত।
Public resolver 1.1.1.1-এ MX/SPF ও তিন DKIM CNAME answer এসেছে; `_dmarc` প্রথমে negative cache ছিল, পরে সঠিক TXT answer এসেছে। সব resolver-এ propagation সম্পন্ন বলা হচ্ছে না।

Added records (TTL Auto, DNS only where applicable):

| Type  | Name                       | Content                                     | Priority |
| ----- | -------------------------- | ------------------------------------------- | -------- |
| MX    | @                          | mx1.hostinger.com                           | 5        |
| MX    | @                          | mx2.hostinger.com                           | 10       |
| TXT   | @                          | v=spf1 include:_spf.mail.hostinger.com ~all | —        |
| CNAME | hostingermail-a._domainkey | hostingermail-a.dkim.mail.hostinger.com     | —        |
| CNAME | hostingermail-b._domainkey | hostingermail-b.dkim.mail.hostinger.com     | —        |
| CNAME | hostingermail-c._domainkey | hostingermail-c.dkim.mail.hostinger.com     | —        |
| TXT   | _dmarc                     | v=DMARC1; p=none                            | —        |

DKIM CNAMEs must be DNS only, not proxied. Re-read current records before adding: if an SPF/DMARC or conflicting selector exists by then, stop and review merging/compatibility instead of creating duplicates or overwriting.
Initial DMARC p=none is monitoring policy, not active rejection of spoofed mail. No reporting address is added because no dmarc mailbox was provisioned. Later enforcement needs observed legitimate senders and a reviewed policy.

## CRM integration — চালু; self-delivery পরীক্ষিত

Live app এখন `MAIL_MAILER=smtp`, `MAIL_SCHEME=smtps`, `MAIL_HOST=smtp.hostinger.com`, port 465; username/from approved mailbox। `MAIL_EHLO_DOMAIN=crm.abdullahfahad.com`। MAIL_URL override রাখা হয়নি।
Private env backup → mail-only atomic update → parsed non-mail settings equality check → config cache refresh সম্পন্ন। Database, APP_KEY, PHP version ও অন্য websites বদলানো হয়নি।
Backup `shared/config/app.env.before-smtp-20261012` 0600; এটি secret-bearing private file, repo/public backup নয়। Cached settings এবং password-match boolean যাচাই হয়েছে; secret print হয়নি।
Use SMTP host 465/MAIL_SCHEME=smtps with MAIL_USERNAME and FROM_ADDRESS equal to the approved mailbox; no insecure TLS fallback, public helper or verification bypass.
Do not store the password in MAIL_URL, VITE variables, GitHub, terminal command arguments, public logs or artifacts. Do not print config cache or env. Test failure diagnostics must redact credentials/tokens/contact details.
Keep queue sync until reliable worker/tenant-context retry tests exist. Automatic invitation emails remain a separate deferred workflow.

### সীমিত delivery proof

- Live Laravel transport 2026-10-11 21:04:52 UTC-এ একবার synthetic test message accept করেছে; recipient শুধু `noreply@abdullahfahad.com`। কোনো customer data, verification/reset link বা personal Gmail address নেই।
- 21:05:56 UTC-এ certificate-verified TLS IMAP `imap.hostinger.com:993`, read-only EXAMINE Inbox ও exact synthetic subject search-এ message পাওয়া গেছে। শুধু relevant headers নেওয়া হয়েছে; message body/অন্য emails পড়া হয়নি। DKIM signature এবং SPF/DKIM/DMARC pass markers পাওয়া গেছে।
- এটি Hostinger থেকে একই Hostinger mailbox-এ self-delivery proof; external Gmail/Outlook inbox/spam placement বা real registration/password-reset notification end-to-end proof নয়। DMARC `p=none` active spoof rejection নয়।
- Private config/backup/credential এবং `smtp-test-receipt.json`/`smtp-inbox-proof.json` 0600 যাচাই হয়েছে; temporary CLI configuration/receipt helpers server থেকে সরানো হয়েছে। Public command runner নেই। Env/mail credential/backup HTTP probes 403; CRM home/login/register/up 200 এবং parent WordPress 200/PHP 8.2.34 অপরিবর্তিত।

### বাকি কাজ

পরবর্তী user acceptance: user জানিয়েছেন registration email Inbox-এ এসেছে এবং link দিয়ে verify করেছেন; “CRM test” workspace dashboard screenshot দিয়েছেন। এটি user-reported success, agent-observed full browser E2E নয়। Password reset ও broader delivery tests বাকি।

নিজের email দিয়ে [Register](https://crm.abdullahfahad.com/register) করে verification message-এর Inbox/Spam placement এবং link acceptance যাচাই; তারপর password reset ও workspace/lead/team browser flow পরীক্ষা। Agent-initiated external test-এর জন্য recipient নির্দিষ্ট হতে হবে; real account স্বয়ংক্রিয়ভাবে বানানো হয়নি। Verification bypass নয়।
বর্তমান full-suite tests transport/notification logic-এর regression evidence; বাস্তব external delivery-এর বিকল্প নয়। SMTP/DB/config backups ও credential recovery private থাকবে। Automatic credential rotation, invitation email এবং GitHub auto-deployment implemented নয়।

এই phase-এর local gate: Pint, PHPStan zero errors, ৭৯ PHP tests/৩৮৫ assertions, TypeScript, frontend formatting/lint, ৩ frontend tests ও production build passed। মোট ৮১ PHP tests-এর ২টি PostgreSQL-only locally skipped; এই phase-এ schema/locking change নেই। [Verification report](VERIFICATION.md)-এ tested/untested scope আছে।

## References

- [Hostinger SMTP configuration](https://www.hostinger.com/support/1575756-how-to-get-email-account-configuration-details-for-hostinger-email/)
- [Hostinger mailbox password requirements](https://www.hostinger.com/support/1583468-how-to-reset-a-hostinger-email-mailbox-password/)
- [Hostinger mail DNS setup](https://www.hostinger.com/support/8671319-set-up-a-domain-for-hostinger-email-manually/)
- [Hostinger default DKIM records](https://www.hostinger.com/support/4456413-what-are-the-dkim-records-for-hostinger-email/)

Account quotas are observed state, not a guarantee of permanent pricing/availability. No new purchase or renewal has been made.
