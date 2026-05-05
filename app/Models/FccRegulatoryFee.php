<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FccRegulatoryFee extends Model
{
    protected $table = 'fcc_regulatory_fees';

    protected $fillable = [
        'license_id', 'fiscal_year', 'fee_category',
        'amount_due', 'amount_paid', 'due_date', 'paid_date',
        'confirmation_number', 'status',
    ];

    protected $casts = [
        'due_date' => 'date',
        'paid_date' => 'date',
        'amount_due' => 'decimal:2',
        'amount_paid' => 'decimal:2',
    ];

    public function license(): BelongsTo
    {
        return $this->belongsTo(FccLicense::class, 'license_id');
    }
}
