<?php

namespace App\Services\Billing;

/** The card was declined or the payment was held; the message is safe to show the member. */
class PaymentDeclined extends BillingException {}
