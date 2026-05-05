<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FccPoliticalFileEntry extends Model
{
    protected $table = 'fcc_political_file_entries';

    protected $fillable = [
        'license_id', 'order_date', 'candidate_or_issue', 'sponsor', 'office',
        'flight_start_date', 'flight_end_date', 'spots_purchased',
        'rate_per_spot', 'total_amount', 'lowest_unit_rate_window',
        'contract_pdf_path', 'uploaded_to_public_file_date',
    ];

    protected $casts = [
        'order_date' => 'date',
        'flight_start_date' => 'date',
        'flight_end_date' => 'date',
        'uploaded_to_public_file_date' => 'date',
        'rate_per_spot' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'lowest_unit_rate_window' => 'boolean',
    ];

    public function license(): BelongsTo
    {
        return $this->belongsTo(FccLicense::class, 'license_id');
    }
}
