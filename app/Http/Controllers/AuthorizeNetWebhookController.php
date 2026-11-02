<?php

namespace App\Http\Controllers;

use App\Models\PaymentWebhookEvent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AuthorizeNetWebhookController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $key = trim((string) config('services.authorizenet.signature_key'));
        if (! preg_match('/\A[0-9a-f]{128}\z/i', $key)) {
            return response()->json(['error' => 'Webhook signature configuration unavailable.'], 503);
        }

        $body = $request->getContent();
        if (strlen($body) > 65536) {
            return response()->json(['error' => 'Payload too large.'], 413);
        }
        $header = (string) $request->header('X-ANET-Signature');
        if (! preg_match('/\Asha512=([0-9a-f]{128})\z/i', $header, $match)) {
            return response()->json(['error' => 'Invalid signature.'], 401);
        }
        // Accept binary-hex and literal-text key representations for provider compatibility.
        // Both require the configured secret and an exact raw-body HMAC; no unsigned fallback.
        $signature = strtolower($match[1]);
        $binaryMatch = hash_equals(hash_hmac('sha512', $body, hex2bin($key)), $signature);
        $textMatch = hash_equals(hash_hmac('sha512', $body, $key), $signature);
        if (! $binaryMatch && ! $textMatch) {
            return response()->json(['error' => 'Invalid signature.'], 401);
        }

        $data = json_decode($body, true);
        $validator = Validator::make(is_array($data) ? $data : [], [
            'notificationId' => ['required', 'uuid'],
            'eventType' => ['required', 'string', 'max:120', 'regex:/\Anet\.authorize\.[a-zA-Z.]+\z/'],
            'payload' => ['required', 'array'],
            'payload.entityName' => ['required', 'string', 'max:40'],
        ]);
        if ($validator->fails()) {
            return response()->json(['error' => 'Invalid notification.'], 422);
        }

        $isTransaction = $data['payload']['entityName'] === 'transaction';
        $transactionId = $data['payload']['id'] ?? null;
        if ($isTransaction && (! is_scalar($transactionId) || ! preg_match('/\A[0-9]{1,32}\z/', (string) $transactionId))) {
            return response()->json(['error' => 'Invalid transaction identifier.'], 422);
        }

        // Persist a minimal inbox before ACK. No raw body, auth code, card data or key is stored.
        $event = PaymentWebhookEvent::firstOrCreate([
            'environment' => config('services.authorizenet.sandbox') ? 'sandbox' : 'production',
            'notification_id' => strtolower($data['notificationId']),
        ], [
            'event_type' => $data['eventType'],
            'transaction_id' => $isTransaction ? (string) $transactionId : null,
            'body_hash' => hash('sha256', $body),
            'status' => 'pending',
        ]);
        if (! hash_equals($event->body_hash, hash('sha256', $body))) {
            return response()->json(['error' => 'Notification identifier conflict.'], 409);
        }

        return response()->json(['received' => true]);
    }
}
