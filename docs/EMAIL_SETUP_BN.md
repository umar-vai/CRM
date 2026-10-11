# CRM email setup — 2026-10-12

## সম্পন্ন

- User-approved Hostinger account `u148519723`, domain `abdullahfahad.com`-এ `noreply@abdullahfahad.com` তৈরি হয়েছে। Mailbox Active; incoming/outgoing SMTP enabled। কোনো plan/order কেনা হয়নি।
- Existing Free Business Email order-এ 100 seats, create-এর পরে 99 অবশিষ্ট; mailbox outbound limit 100 messages/rolling 24 hours। এই সীমায় বড় SaaS-এর delivery capacity ধরে নেওয়া যাবে না।
- Password hosting server-এ cryptographic randomness দিয়ে তৈরি; private parent 0700-এর মধ্যে `shared/config/mailbox-secret.json` 0600-এ রাখা হয়েছে। Chat/repo/frontend/public files-এ secret নেই। Approved mailbox creation API-তে password পাঠানো হয়েছে; এটি Supabase-এর SCRAM-only provisioning-এর থেকে আলাদা workflow।
- First create rejected (HTTP 422); provider documentation-এর 8–50 character limit অনুযায়ী unused staging password সংশোধন করে দ্বিতীয় request সফল। অন্য mailbox-এর password reset হয়নি।
- Hostinger থেকে Symfony SMTP transport দিয়ে `smtp.hostinger.com:465`, implicit TLS, default certificate verification এবং mailbox authentication সফল। কোনো message পাঠানো হয়নি। এটি inbox delivery বা Laravel notification end-to-end test নয়।
- hPanel mailbox list-এ Active দেখা গেছে, তবে domain setup incomplete warning আছে।

## DNS: যাচাই হয়েছে, পরিবর্তন হয়নি

Cloudflare account-এর `abdullahfahad.com` zone-এ 8টি existing record দেখা হয়েছে। Root/website A records, CRM CNAME এবং `auth` subdomain-এর Resend-related records অপরিবর্তিত।
Public resolver-এ root MX/SPF ও `_dmarc` TXT answer নেই; hPanel-ও missing দেখায়। Hostinger mail records এখনো যোগ করা হয়নি।
Root email routing/domain sending authorization তৈরি হবে বলে specific DNS approval প্রয়োজন। পুরোনো mail-service records মুছে বা nameservers বদলে এই কাজ করা যাবে না।

Provider-requested additions:

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

## CRM integration — pending

Live app remains `MAIL_MAILER=log`; SMTP credential is staged, not injected into app.env/config cache. Database, APP_KEY and other sites unchanged.
After DNS/integration approval: private env backup → atomic update of only MAIL fields → config cache refresh → HTTPS health check → actual verification/reset delivery to a user-approved recipient → browser acceptance test.
Use SMTP host 465/MAIL_SCHEME=smtps with MAIL_USERNAME and FROM_ADDRESS equal to the approved mailbox; no insecure TLS fallback, public helper or verification bypass.
Do not store the password in MAIL_URL, VITE variables, GitHub, terminal command arguments, public logs or artifacts. Do not print config cache or env. Test failure diagnostics must redact credentials/tokens/contact details.
Keep queue sync until reliable worker/tenant-context retry tests exist. Automatic invitation emails remain a separate deferred workflow.

Mailbox creation and SMTP authentication do **not** establish message acceptance, inbox placement, SPF/DKIM alignment or verified registration. SMTP/DB/config backups and credential recovery must remain private. Automatic rotation and GitHub deployment are not implemented.

## References

- [Hostinger SMTP configuration](https://www.hostinger.com/support/1575756-how-to-get-email-account-configuration-details-for-hostinger-email/)
- [Hostinger mailbox password requirements](https://www.hostinger.com/support/1583468-how-to-reset-a-hostinger-email-mailbox-password/)
- [Hostinger mail DNS setup](https://www.hostinger.com/support/8671319-set-up-a-domain-for-hostinger-email-manually/)
- [Hostinger default DKIM records](https://www.hostinger.com/support/4456413-what-are-the-dkim-records-for-hostinger-email/)

Account quotas are observed state, not a guarantee of permanent pricing/availability. No new purchase or renewal has been made.
