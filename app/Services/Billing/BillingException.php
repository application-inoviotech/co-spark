<?php

namespace App\Services\Billing;

use RuntimeException;

/** Authorize.net could not complete a billing request (unreachable, rejected or unexpected response). */
class BillingException extends RuntimeException {}
