<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MonitoredSim extends Model
{
    protected $table = "monitored_sims";
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
}
