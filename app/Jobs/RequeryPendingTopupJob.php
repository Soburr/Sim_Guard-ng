<?php

namespace App\Jobs;

use App\Models\MonitoredSim;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Services\VtuService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;

class RequeryPendingTopupJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 5;

    /** @var array<int, int> */
    public array $backoff = [60, 180, 300, 600];

    public function __construct(
        public readonly int $transactionId,
    ) {}

    public function handle(VtuService $vtu): void
    {
        $tx = WalletTransaction::find($this->transactionId);

        if (! $tx || $tx->status !== 'pending') {
            return;
        }

        $result = $vtu->requeryTransaction($tx->reference);

        if ($result['state'] === 'delivered') {
            DB::transaction(function () use ($tx, $result) {
                $lockedTx = WalletTransaction::where('id', $tx->id)->lockForUpdate()->firstOrFail();
                if ($lockedTx->status !== 'pending') {
                    return;
                }

                $lockedTx->update([
                    'status' => 'success',
                    'provider_payload' => $result['payload'],
                ]);

                if ($lockedTx->monitored_sim_id) {
                    MonitoredSim::where('id', $lockedTx->monitored_sim_id)->update([
                        'status' => 'active',
                        'last_rga_at' => now(),
                        'next_rga_due_at' => now()->addDays(120),
                    ]);
                }
            });

            return;
        }

        if ($result['state'] === 'failed') {
            DB::transaction(function () use ($tx, $result) {
                $lockedTx = WalletTransaction::where('id', $tx->id)->lockForUpdate()->firstOrFail();
                if ($lockedTx->status !== 'pending') {
                    return;
                }

                $wallet = Wallet::where('id', $lockedTx->wallet_id)->lockForUpdate()->firstOrFail();
                $wallet->increment('balance_kobo', $lockedTx->amount_kobo);

                $lockedTx->update([
                    'status' => 'failed',
                    'description' => $lockedTx->description . ' [Refunded]',
                    'provider_payload' => $result['payload'],
                ]);
            });

            return;
        }

        if ($this->attempts() < $this->tries) {
            $this->release($this->backoff[$this->attempts() - 1] ?? 600);
        }
    }
}