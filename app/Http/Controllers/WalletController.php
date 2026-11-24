<?php

namespace App\Http\Controllers;

use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Services\PaystackService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class WalletController extends Controller
{
    public function __construct(private readonly PaystackService $paystack) {}

    public function fund(Request $request): SymfonyResponse
    {
        $validated = $request->validate([
            'amount_naira' => ['required', 'integer', 'min:500', 'max:500000'],
        ]);

        $amountKobo = $validated['amount_naira'] * 100;
        $wallet = $request->user()->getOrCreateWallet();
        $reference = 'SG_FUND_' . now()->format('YmdHis') . '_' . Str::upper(Str::random(8));

        WalletTransaction::create([
            'wallet_id' => $wallet->id,
            'reference' => $reference,
            'type' => 'credit',
            'amount_kobo' => $amountKobo,
            'description' => 'Wallet funding via Paystack',
            'status' => 'pending',
        ]);

        $checkoutUrl = $this->paystack->initializeTransaction(
            email: $request->user()->email,
            amountKobo: $amountKobo,
            reference: $reference,
            callbackUrl: route('wallet.callback'),
        );

        return Inertia::location($checkoutUrl);
    }

    public function callback(Request $request): RedirectResponse
    {
        $reference = $request->query('reference');

        if (! $reference) {
            return redirect()->route('dashboard')->with('error', 'Missing payment reference.');
        }

        $data = $this->paystack->verifyTransaction($reference);

        if (($data['status'] ?? null) === 'success') {
            $this->creditTransactionIdempotently($reference, (int) $data['amount'], $data);

            return redirect()->route('dashboard')->with('success', 'Wallet funded successfully!');
        }

        return redirect()->route('dashboard')->with('error', 'Payment was not completed.');
    }
    public function webhook(Request $request): Response
    {
        if (! $this->paystack->isValidWebhookSignature($request->getContent(), $request->header('x-paystack-signature'))) {
            return response('Invalid signature', 401);
        }

        $event = $request->input('event');
        $data = $request->input('data', []);

        if ($event === 'charge.success' && isset($data['reference'], $data['amount'])) {
            $this->creditTransactionIdempotently(
                reference: $data['reference'],
                paidAmountKobo: (int) $data['amount'],
                payload: $data,
            );
        }

        return response('Webhook handled', 200);
    }

    private function creditTransactionIdempotently(string $reference, int $paidAmountKobo, array $payload): void
    {
        DB::transaction(function () use ($reference, $paidAmountKobo, $payload) {
            $tx = WalletTransaction::where('reference', $reference)
                ->lockForUpdate()
                ->first();

            if (! $tx || $tx->status === 'success') {
                return;
            }

            if ($paidAmountKobo < $tx->amount_kobo) {
                $tx->update([
                    'status' => 'failed',
                    'provider_payload' => $payload,
                ]);

                return;
            }

            $wallet = Wallet::where('id', $tx->wallet_id)
                ->lockForUpdate()
                ->firstOrFail();

            $wallet->increment('balance_kobo', $tx->amount_kobo);

            $tx->update([
                'status' => 'success',
                'provider_payload' => $payload,
            ]);
        });
    }
}