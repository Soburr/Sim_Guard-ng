<?php

namespace App\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class PaystackService
{
    private function client(): PendingRequest
    {
        return Http::withToken(config('services.paystack.secret_key'))
            ->baseUrl(config('services.paystack.payment_url'))
            ->acceptJson()
            ->timeout(15);
    }

    public function initializeTransaction(
        string $email,
        int $amountKobo,
        string $reference,
        string $callbackUrl
    ): string {
        $response = $this->client()->post('/transaction/initialize', [
            'email' => $email,
            'amount' => $amountKobo,
            'reference' => $reference,
            'callback_url' => $callbackUrl,
            'channels' => ['card', 'bank', 'ussd', 'bank_transfer'],
        ]);

        if (! $response->successful() || ! $response->json('status')) {
            throw new RuntimeException('Paystack initialization failed: ' . $response->body());
        }

        return $response->json('data.authorization_url');
    }

    public function verifyTransaction(string $reference): array
    {
        $response = $this->client()->get("/transaction/verify/{$reference}");

        if (! $response->successful() || ! $response->json('status')) {
            throw new RuntimeException('Paystack verification failed: ' . $response->body());
        }

        return $response->json('data');
    }

    public function isValidWebhookSignature(string $rawPayload, ?string $signatureHeader): bool
    {
        if (! $signatureHeader) {
            return false;
        }

        $computed = hash_hmac('sha512', $rawPayload, (string) config('services.paystack.secret_key'));

        return hash_equals($computed, $signatureHeader);
    }
}