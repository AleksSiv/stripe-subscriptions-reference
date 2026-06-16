<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProcessedStripeEvent extends Model
{
    public $timestamps = false;

    protected $fillable = ['stripe_event_id', 'type', 'processed_at'];

    protected function casts(): array
    {
        return ['processed_at' => 'datetime'];
    }
}
