<?php

namespace App\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class VtuService
{
    private function client(): PendingRequest
    {
        return Http::withHeaders([
            'api-key' => config('services.vtpass.api_key'),
            'secret-key' => config('services.vtpass.secret_key'),
        ])
            ->baseUrl(config('services.vtpass.base_url'))
            ->acceptJson()
            ->timeout(25);
    }

    /**
     * Generate a VTPass-compliant request_id: YYYYMMDDHHII (Africa/Lagos) + random suffix.
     */
    public function generateRequestId(): string
    {
        return now('Africa/Lagos')->format('YmdHi') . '_SG_' . Str::upper(Str::random(8));
    }

    /**
     * Vend airtime via VTPass /pay endpoint.
     *
     * @return array{state: 'delivered'|'pending'|'failed', code: string, payload: array}
     */
    public function purchaseAirtime(string $e164Phone, string $telco, int $amountNaira, string $requestId): array
    {
        $localPhone = '0' . substr($e164Phone, 4);
        $serviceId = $telco === '9mobile' ? 'etisalat' : $telco;

        $response = $this->client()->post('/pay', [
            'request_id' => $requestId,
            'serviceID' => $serviceId,
            'amount' => $amountNaira,
            'phone' => $localPhone,
        ]);

        // Treat HTTP/network failures as 'pending' so our requery job verifies before refunding
        if (! $response->successful()) {
            return [
                'state' => 'pending',
                'code' => 'HTTP_' . $response->status(),
                'payload' => ['raw' => $response->body()],
            ];
        }

        $payload = $response->json() ?? [];
        $code = (string) ($payload['code'] ?? '');
        $txStatus = (string) ($payload['content']['transactions']['status'] ?? '');

        if ($code === '000' && $txStatus === 'delivered') {
            return ['state' => 'delivered', 'code' => $code, 'payload' => $payload];
        }

        if ($code === '099' || in_array($txStatus, ['pending', 'initiated'], true)) {
            return ['state' => 'pending', 'code' => $code, 'payload' => $payload];
        }

        return ['state' => 'failed', 'code' => $code, 'payload' => $payload];
    }

    /**
     * Requery an ambiguous or '099' processing transaction on VTPass.
     *
     * @return array{state: 'delivered'|'pending'|'failed', code: string, payload: array}
     */
    public function requeryTransaction(string $requestId): array
    {
        $response = $this->client()->post('/requery', [
            'request_id' => $requestId,
        ]);

        if (! $response->successful()) {
            return [
                'state' => 'pending',
                'code' => 'HTTP_' . $response->status(),
                'payload' => ['raw' => $response->body()],
            ];
        }

        $payload = $response->json() ?? [];
        $code = (string) ($payload['code'] ?? '');
        $txStatus = (string) ($payload['content']['transactions']['status'] ?? '');

        if ($code === '000' && $txStatus === 'delivered') {
            return ['state' => 'delivered', 'code' => $code, 'payload' => $payload];
        }

        if ($code === '099' || in_array($txStatus, ['pending', 'initiated'], true)) {
            return ['state' => 'pending', 'code' => $code, 'payload' => $payload];
        }

        return ['state' => 'failed', 'code' => $code, 'payload' => $payload];
    }
}