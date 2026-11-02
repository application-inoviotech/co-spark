# COSPARK Insurance Group

Laravel 10 build of the approved COSPARK HTML prototype, following SOW v1.11.

## Local setup (Laragon)

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
```

- PHP 8.1+ (Composer is pinned to the 8.1 platform), MySQL 8, database `cospark`.
- Laragon serves it at `http://cospark.test` after **Menu → Apache → Reload**, or run `php artisan serve`.
- Mail and SMS go to `storage/logs/laravel.log` (`MAIL_MAILER=log`, `LogSmsSender`) until real providers are configured.

## Structure

| Area | Where |
| --- | --- |
| Approved design (CSS/JS, unchanged visuals) | `public/assets` |
| Layouts, header/mega menu/footer, appointment popup | `resources/views/layouts`, `resources/views/partials` |
| Page-specific scripts (moved out of inline `<script>`) | `public/assets/js/pages` |
| Site settings, navigation, office details | `config/cospark.php` |
| Membership plans, token rules, OTP settings | `config/membership.php` |
| CMS content (products, resources, partners, claim types) | DB tables, seeded by `CatalogSeeder` |
| Form validation | `app/Http/Requests` |
| Business logic (membership billing, tokens, OTP, notifications) | `app/Services` |

### URLs

Clean routes (`/insurance/car`, `/quote?type=auto`, `/resources/{slug}`, …). Every approved prototype link
(`/product.html?p=car`, `/article.html?id=bop`, …) permanently redirects to its route via `LegacyRedirectController`.

### Payments (Stripe)

- Cards are entered in **Stripe Elements**; only the PaymentMethod id reaches the server.
- Membership dues are **Stripe subscriptions** (`App\Services\Billing\StripeBilling`, `MembershipService`).
  Prices come from `STRIPE_PRICE_*` in `.env`; a billing frequency is offered only when its price id is set.
- Premiums and Vacation Tokens are one-time PaymentIntents (`StripePaymentGateway`).
- Premium sub-accounts are a separate quantity-based subscription ($8.88 each).
- **Webhook** `POST /stripe/webhook` — in Stripe: Developers → Webhooks → Add endpoint `https://<domain>/stripe/webhook`,
  events `invoice.paid`, `invoice.payment_failed`, `customer.subscription.updated`, `customer.subscription.deleted`;
  put its signing secret in `STRIPE_WEBHOOK_SECRET`. Locally: `stripe listen --forward-to localhost/stripe/webhook`.
- **Scheduler** (cron `* * * * * php artisan schedule:run`): `billing:daily` sends renewal reminders and
  expiring-card alerts, and expires memberships whose paid period ended without renewal.
- `OTP_FIXED_CODE` in `.env` makes every one-time code fixed (testing only — remove before launch).

### Security notes

Laravel 10 is end-of-life; framework advisories are mitigated in code: emails are validated with `email:filter`
(blocks CRLF injection), and signed temporary URLs are not used. Keep `APP_DEBUG=false` in production.

## Build status

- [x] Public website — all pages, mega menu, footer, appointment popup
- [x] Dynamic quote engine, home mini quote, file a claim, producer application, contact — saved with uploads, reference IDs, email/SMS confirmations
- [x] Registration (plan → details → email OTP → first dues payment → DT credit), login, forgot password
- [x] Member dashboard on live data — membership, policies & documents, claims (messages/uploads), payments & receipts,
      payment methods, token wallet (DT/IT/VT, lots, transfers), vacations, QR, notifications, sub-accounts, profile
- [x] Producer portal on live data — profile, licensing, documents, 2,000-token bonus & 12-month qualification,
      commissions (+ CSV export), performance, 10% vacation discount, assigned leads, appointments, notifications
- [ ] Admin panel (approving applications & transfers, confirming vacations, adding policies & commissions,
      assigning leads, verifying licenses/documents, claim updates, QR issuance, CMS)

## Producer portal

`producer.js` reads `GET /producer/dashboard/data` (`App\Services\ProducerDashboardPresenter`) and posts to
`/producer/*` (`App\Http\Controllers\Producer\PortalController`). `App\Services\ProducerService` holds the rules:
`approve()` turns a producer application into a producer account (the admin panel will call it), `syncBonus()`
credits the 2,000-token bonus once approved + licensed + $8.88 membership active and releases it after 12 months,
and `recordCommission()` books commission at the producer's rate (75%). Producers pay their membership from the
member dashboard. Demo producers are seeded locally by `DemoProducerSeeder`.

## Member dashboard

`dashboard.js` keeps the approved markup and reads everything from `GET /dashboard/data`
(`App\Services\MemberDashboardPresenter`); actions post to `/dashboard/*` (`App\Http\Controllers\Member`).
Business rules live in `MembershipService` (dues, upgrades, plan switch, cancel), `MemberBillingService`
(premiums → IT once per policy year, VT lots, retries) and `TokenLedger` (balances, transfers, IT forfeiture).

Local demo data is seeded by `DemoMemberSeeder` (not in production) — sign-in details are in that file.

## Admin panel (Inertia + Vue 3)
- URL `/admin`, sign in at `/admin/login` (separate `admin` guard — staff and member/producer sessions can coexist in one browser; admins are refused on the website login). Local seed: `admin@cospark.test` / `AdminCospark2026` (not seeded in production).
- Create an admin on a server: `php artisan cospark:admin you@example.com`.
- Resources live in `app/Admin/Resources` (one class each: columns, filters, fields, rules, actions) and are registered in `app/Admin/Registry.php`; generic Vue pages in `resources/js/admin/Pages/Resource`.
- Also: dashboard KPIs, partner QR scan/redeem (`/admin/qr`), CSV reports, site settings (contact info, legal text — override `config('cospark.*')`).
- Build the JS after changes: `npm run build` (output in `public/build`).

## Retrieve a saved quote
"Save & finish later" on the quote form emails a link plus an 8-character code (kept 30 days, uploads not saved). Resume at `/quote/retrieve` or `/quote?resume=CODE`.

## Server cron, queue and emails (shared hosting, no root)
One cron entry runs everything every minute (cPanel → Cron Jobs). Use the same PHP version as the site:

    * * * * * cd /home/USER/path-to-app && /usr/local/bin/php artisan schedule:run >> /dev/null 2>&1

- **What it runs:** the heartbeat, the queue worker (`queue:work database --stop-when-empty`), Authorize.net webhooks, and `billing:daily` at 08:00. Nothing runs in the background, because background processes are often killed on shared hosting.
- **`.env`:** set `QUEUE_CONNECTION=database` so emails and texts are queued and sent by the cron. `sync` sends them inline instead, and a slow mail server then slows the page.
- **Admin → Dashboard → System status** shows:
  - when the cron last ran;
  - how many messages are waiting;
  - the failed messages and the reason for the latest failure.

  "Send queued now" and "Retry failed" work without shell access.
- **After deploying new code:** run `composer dump-autoload` (or `composer install`), `php artisan migrate --force` and `php artisan optimize:clear`.
- **No cPanel cron available?** Set a long random `CRON_TOKEN` in `.env`, then have a free external cron service (e.g. cron-job.org) call `https://your-site/cron/<CRON_TOKEN>` every minute. It runs the same `schedule:run`. Without the token, the URL returns 404.
"# co-spark" 
