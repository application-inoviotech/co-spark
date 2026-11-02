# Payment QA fixes — 21 October 2026

Local changes are verified; they have not been deployed by the agent.

- Full Admin refunds and verified full-refund/void webhooks share a locked, one-time reward reconciler. It appends compensating token entries, deactivates refunded VT lots, revokes unsupported membership coverage, and restores remaining paid coverage when its plan metadata is available. Already-forfeited insurance rewards are not deducted twice. Spent tokens retain an auditable negative balance and review note. Partial refunds and ambiguous legacy/dependent entitlements require review.
- New dues payments record plan/frequency metadata. Historical rows are not guessed or automatically backfilled.
- Canceled memberships show zero next scheduled dues while preserving the paid-through date. Payment summaries preserve cents.
- Renewal reloads the locked membership and skips future/non-active periods, preventing a stale repeated call from charging the same period again.

## Deploy

Deploy the complete PHP/frontend change with migration `2026_10_21_000200_add_refund_reconciliation_to_payments.php`. Migrate before serving requests with the new model, using the site's maintenance/atomic deployment process.

```bash
php artisan optimize:clear
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan billing:reconcile-refunds --payment=PAY-262UIYTG --payment=PAY-26DYG75X --payment=PAY-26KXVQTD
php artisan billing:webhooks --limit=50
```

The repair command above only processes the three already-refunded QA payments. It does not send a gateway refund/charge. Repeating it does not reverse rewards twice. Do not use `--all` without reviewing the scope. A review result exits 1 and prints the reason.

## Evidence and remaining live checks

- Public Associate signup -> static QA OTP -> $8.88 first sandbox payment -> active dashboard and +4 DT: PASS. Member CSP-M-100007, payment PAY-26OZCAL0, paid through November 21, 2026. Receipt confirms $8.88. SMTP inbox delivery was not proved by using a static OTP.
- Live cents bug reproduced: Paid this year displayed $8 for the $8.88 payment. Fixed locally; deployment/retest still required.
- Retained $25 VT charge 120092396828 remains capturedPendingSettlement as of 2026-10-21 09:30:12 UTC. It is retained for an actual settled refund. Do not report the earlier same-day void as a settled refund.
- Actual scheduled renewal still needs the staging CLI. Updated scoped script is `qa/2026-10-20/payment-live/cospark-qa-renewal.php` (ignored QA artifact). Upload it to the staging Laravel root, then run `php cospark-qa-renewal.php --run`. It is CLI/sandbox only, requires exact synthetic user 5 and Visa fixture, attempts one $25 renewal, voids the test payment through the shared refund reconciler, restores membership/default-card/QR state and prints JSON. A run-once marker prevents blind repeats. Do not run the global daily billing command to force this test.
- Webhook live signature/real capture delivery passed earlier; final persisted worker status requires staging inspection. Local tests cover signed duplicate delivery and reward reversal exactly once.

## Validation

65 PHP tests / 482 assertions pass with in-memory SQLite and fake gateway requests. 10 JavaScript regression tests pass, including actual payment rendering for canceled next dues and $8.88/$33.88 totals. Frontend syntax and Git whitespace checks pass. Isolated renewal tests cover monthly, quarterly, biannual and annual amounts/rewards and stale-call replay. This is local service evidence; it does not replace actual gateway renewal evidence.

QA reports/screenshots/scripts remain under the ignored `qa/` folder. No existing local or staging database was migrated by the agent.
