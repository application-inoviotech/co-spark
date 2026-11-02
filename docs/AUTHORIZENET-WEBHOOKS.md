# Authorize.net webhook deployment

Endpoint: **POST https://cospark.developer-ourbase-camp.com/webhooks/authorizenet**

The receiver authenticates `X-ANET-Signature` using HMAC-SHA512 of the exact raw body, stores a minimal durable inbox entry, then returns HTTP 200. No gateway request or financial side effect runs in the HTTP receiver. Invalid signatures return 401; missing/malformed server key returns 503; invalid signed payload returns 422. A database write failure returns an error so Authorize.net can retry. GET is not supported (405 is expected).

## Deploy together

1. Deploy this change, including migrations `2026_10_21_000100_create_payment_webhook_events_table.php` and `2026_10_21_000200_add_refund_reconciliation_to_payments.php` and the new controller, model, processor and command. Use maintenance mode or your atomic release process so requests do not run the new Payment model before the new column is migrated. This migration adds an inbox table and nullable payment-environment column; it does not delete/backfill historical payment records.
2. In the **server's private `.env`**, add the Signature Key generated in the same merchant account as the API credentials:

   ```dotenv
   AUTHORIZENET_SIGNATURE_KEY=YOUR_128_CHARACTER_HEX_SIGNATURE_KEY
   AUTHORIZENET_ENV=sandbox
   ```

   Use `production` only for production credentials/webhooks. The key is separate from the Transaction Key and Client Key. Do not commit it or put it in frontend/Vite variables. Paste the generated key exactly, preserving its case; literal-key signing uses the exact key text. Binary and literal-text representations are accepted, with constant-time digest comparison. No key is generated or changed by this deployment.

3. From the actual Laravel project root run:

   ```bash
   php artisan optimize:clear
   php artisan migrate --force
   php artisan config:cache
   php artisan route:cache
   php artisan billing:webhooks --limit=50
   ```

4. Ensure the existing Laravel scheduler cron invokes `php artisan schedule:run` **every minute** from the project root. Do not add a duplicate cron if already configured. `billing:webhooks` is scheduled every minute in the background with overlap protection. It reads the durable inbox directly; it does not depend on `QUEUE_CONNECTION`, Supervisor or an HTTP queue dispatch. If cron is unavailable, manually run `php artisan billing:webhooks --limit=50` to process accepted events.
5. In Authorize.net confirm the endpoint above, correct environment and status Active. Enable:

   - `net.authorize.payment.authcapture.created`
   - `net.authorize.payment.refund.created`
   - `net.authorize.payment.void.created`

   Capture/prior-auth-capture events are also handled if enabled. Subscription/ARB events do not drive this app's membership billing: renewals remain `billing:daily`.

If webhook delivery previously hit a missing endpoint, check its status again after deploy: Authorize.net can deactivate a webhook after exhausting retries. A previously failed event is not automatically guaranteed to replay after this deployment; inspect provider Notification History.

## Reconciliation behavior

- Notifications are unique by environment + notification UUID. Re-delivery ACKs 200 without another inbox row. Conflicting raw content under the same UUID returns 409. No card data, full body, auth code, API key or signature is stored in the inbox.
- Each event is locked during processing. Processor fetches **current** transaction details from the configured Authorize.net environment rather than trusting payment status/amount in the notification.
- New Authorize.net payments record `gateway_environment`. Historic rows remain NULL and require provenance review before webhook mutation; the migration does not guess their environment or backfill them. Sandbox events cannot mutate a production/unknown payment with a colliding transaction ID.
- Existing successful captures are confirmed without creating another payment or issuing tokens. A late capture cannot resurrect a refunded payment.
- A void or full refund updates the unique matching existing charge to `refunded`. Refund transaction `refTransId` identifies the original charge. Amount mismatches/partial refunds are left for review, not treated as a full refund.
- Full refunds/voids now share the Admin refund reconciler: payment-linked posted rewards receive an append-only reversal once, VT lots stop accruing, and refunded membership coverage is removed or restored to a remaining paid plan with recorded plan metadata. Already-forfeited IT is not deducted again. Negative balances, legacy coverage without metadata and dependent sub-account benefits surface for review. Partial refunds still require review. See PAYMENT-QA-FIXES.md for historical repair commands.
- A captured charge whose local record is pending/failed is held for business-fulfillment review; no membership or wallet is fabricated from a webhook. An unknown charge is retried because notification delivery can race synchronous recording, then surfaces for review after ten attempts. Ambiguous duplicate local gateway references never update multiple records.
- Gateway/network errors stay pending with bounded backoff (up to 60 minutes), then surface for review after ten attempts. An event from a different stored environment is not processed after switching configuration.

## Operations and verification

`php artisan billing:webhooks --limit=50` prints inspected count and outstanding review UUIDs/reasons. Exit 1 indicates review items remain; it does not imply webhook HTTP delivery failed. Review items also emit sanitized Laravel log warnings. Statuses live in `payment_webhook_events`: pending, processed, ignored, needs_review. No raw customer payment body is logged.

After resolving a review cause, explicitly requeue its notification:

```bash
php artisan billing:webhooks --retry=NOTIFICATION_UUID --limit=50
```

Requeue does not bypass signature verification of new deliveries or perform a charge/refund. An unresolved negative balance or legacy entitlement review will return to review until the underlying records and review note are resolved.

For provider delivery testing use an inactive webhook's test/ping feature, then reactivate it. A ping can contain a sample transaction that does not match a real local payment; ACK proves signature/inbox delivery only. Use an actual sandbox payment/void to verify reconciliation. Check Notification History for Delivered, the inbox status and matching payment after the scheduler runs. No real-money test is required for sandbox verification.

Local test command (in-memory SQLite, no provider calls):

```bash
php vendor/phpunit/phpunit/phpunit -c phpunit.qa.xml
```

Official protocol, events and retries: https://developer.authorize.net/api/reference/features/webhooks.html . Live signature authentication and actual sandbox capture delivery were verified on 21 October 2026; final worker status, actual scheduled renewal and settled refund still require server/provider evidence. Local regression: 65 tests / 482 assertions and 10 JavaScript tests pass.
