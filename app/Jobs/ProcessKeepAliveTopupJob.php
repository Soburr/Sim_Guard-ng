<?php

namespace App\Jobs;

use App\Models\MonitoredSim;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Services\VtuService;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class ProcessKeepAliveTopupJob implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public const AIRTIME_NAIRA = 50;
    public const TOTAL_CHARGE_KOBO = 10000; 

    public int $tries = 1; 
    public int $uniqueFor = 3600;

    public function __construct(
        public readonly int $simId,
        public readonly bool $force = false,
    ) {}

    public function uniqueId(): string
    {
        return $this->force
            ? "manual_topup_sim_{$this->simId}_" . now()->format('YmdHi')
            : "scheduled_topup_sim_{$this->simId}_" . now()->format('Y_m_d');
    }

    public function handle(VtuService $vtu): void
    {
        $requestId = $vtu->generateRequestId();

        $tx = DB::transaction(function () use ($requestId): ?WalletTransaction {
            $sim = MonitoredSim::where('id', $this->simId)
                ->lockForUpdate()
                ->first();

            if (! $sim || $sim->mode !== 'keep_alive') {
                return null;
            }

            if (! $this->force && $sim->next_rga_due_at && $sim->next_rga_due_at->isFuture()) {
                return null;
            }

            $wallet = Wallet::firstOrCreate(
                ['user_id' => $sim->user_id],
                ['balance_kobo' => 0],
            );

            $lockedWallet = Wallet::where('id', $wallet->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedWallet->balance_kobo < self::TOTAL_CHARGE_KOBO) {
                Log::warning('Keep-alive skipped due to insufficient wallet balance', [
                    'sim_id' => $sim->id,
                    'balance_kobo' => $lockedWallet->balance_kobo,
                ]);

                return null;
            }

            $lockedWallet->decrement('balance_kobo', self::TOTAL_CHARGE_KOBO);

            return WalletTransaction::create([
                'wallet_id' => $lockedWallet->id,
                'monitored_sim_id' => $sim->id,
                'reference' => $requestId,
                'type' => 'debit',
                'amount_kobo' => self::TOTAL_CHARGE_KOBO,
                'description' => "Keep-alive ₦50 airtime + fee ({$sim->local_number})",
                'status' => 'pending',
            ]);
        });

        if (! $tx) {
            return;
        }

        $sim = MonitoredSim::findOrFail($this->simId);

        try {
            $result = $vtu->purchaseAirtime(
                e164Phone: $sim->phone_number,
                telco: $sim->telco,
                amountNaira: self::AIRTIME_NAIRA,
                requestId: $requestId,
            );
        } catch (Throwable $e) {
            $tx->update(['provider_payload' => ['exception' => $e->getMessage()]]);
            RequeryPendingTopupJob::dispatch($tx->id)->delay(now()->addMinutes(2));

            return;
        }

        if ($result['state'] === 'delivered') {
            $this->markDelivered($tx, $sim, $result['payload']);
        } elseif ($result['state'] === 'pending') {
            $tx->update(['provider_payload' => $result['payload']]);
            RequeryPendingTopupJob::dispatch($tx->id)->delay(now()->addMinutes(2));
        } else {
            $this->refundFailedTransaction($tx, $result['payload']);
        }
    }

    private function markDelivered(WalletTransaction $tx, MonitoredSim $sim, array $payload): void
    {
        DB::transaction(function () use ($tx, $sim, $payload) {
            $tx->update([
                'status' => 'success',
                'provider_payload' => $payload,
            ]);

            $sim->update([
                'status' => 'active',
                'last_rga_at' => now(),
                'next_rga_due_at' => now()->addDays(120),
            ]);
        });
    }

    private function refundFailedTransaction(WalletTransaction $tx, array $payload): void
    {
        DB::transaction(function () use ($tx, $payload) {
            $lockedTx = WalletTransaction::where('id', $tx->id)->lockForUpdate()->firstOrFail();

            if ($lockedTx->status !== 'pending') {
                return;
            }

            $wallet = Wallet::where('id', $lockedTx->wallet_id)->lockForUpdate()->firstOrFail();
            $wallet->increment('balance_kobo', $lockedTx->amount_kobo);

            $lockedTx->update([
                'status' => 'failed',
                'description' => $lockedTx->description . ' [Refunded]',
                'provider_payload' => $payload,
            ]);
        });
    }
}