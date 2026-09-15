<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayoutRecord extends Model
{
    protected $fillable = [
        'served_list_id', 'program', 'disaster_type', 'region_id', 'province_id',
        'municipality_id', 'barangay_id', 'payout_site', 'beneficiary_reference',
        'target_amount', 'disbursed_amount', 'is_paid', 'served_date',
    ];

    protected function casts(): array
    {
        return [
            'target_amount' => 'decimal:2',
            'disbursed_amount' => 'decimal:2',
            'is_paid' => 'boolean',
            'served_date' => 'date',
        ];
    }

    public function servedList(): BelongsTo
    {
        return $this->belongsTo(ServedList::class);
    }
}
