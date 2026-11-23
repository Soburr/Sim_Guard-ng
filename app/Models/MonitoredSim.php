<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MonitoredSim extends Model
{
    protected $fillable = [
        'user_id',
        'label',
        'phone_number',
        'telco',
        'mode',
        'status',
        'last_rga_at',
        'next_rga_due_at',
        'last_probe_at',
        'last_network_state',
    ];

    protected function casts(): array
    {
        return [
            'last_rga_at' => 'datetime',
            'next_rga_due_at' => 'datetime',
            'last_probe_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function bankLinks(): HasMany
    {
        return $this->hasMany(SimBankLink::class);
    }

    public function getLocalNumberAttribute(): string
    {
        $local = '0' . substr($this->phone_number, 4);

        return substr($local, 0, 4) . ' ' . substr($local, 4, 3) . ' ' . substr($local, 7);
    }

    public function getDaysRemainingAttribute(): ?int
    {
        if ($this->mode !== 'keep_alive' || ! $this->next_rga_due_at) {
            return null;
        }

        return max(0, (int) now()->diffInDays($this->next_rga_due_at, false));
    }
}