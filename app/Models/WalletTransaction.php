<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WalletTransaction extends Model
{
    protected $fillable = [
        'wallet_id',
        'monitored_sim_id',
        'reference',
        'type',
        'amount_kobo',
        'description',
        'status',
        'provider_payload',
    ];

    protected function casts(): array
    {
        return [
            'amount_kobo' => 'integer',
            'provider_payload' => 'array',
        ];
    }

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }

    public function monitoredSim(): BelongsTo
    {
        return $this->belongsTo(MonitoredSim::class);
    }
}