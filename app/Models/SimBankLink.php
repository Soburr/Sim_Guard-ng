<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SimBankLink extends Model
{
    protected $table = "sim_bank_links";
    protected $fillable = [
        'monitored_sim_id',
        'bank_key',
    ];
    
}
