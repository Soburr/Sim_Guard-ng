<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SimBankLink extends Model
{
    protected $fillable = [
        'monitored_sim_id',
        'bank_key',
    ];

    protected $appends = ['bank_details'];

    public function monitoredSim(): BelongsTo
    {
        return $this->belongsTo(MonitoredSim::class);
    }

    public function getBankDetailsAttribute(): array
    {
        return config("nigerian_banks.{$this->bank_key}", [
            'name' => strtoupper($this->bank_key),
            'short_name' => strtoupper($this->bank_key),
            'ussd_block_code' => 'N/A',
            'dial_uri' => '#',
            'brand_hex' => '#334155',
        ]);
    }
}