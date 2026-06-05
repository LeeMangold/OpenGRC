<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FccFormFiling extends Model
{
    protected $table = 'fcc_form_filings';

    protected $fillable = [
        'license_id', 'form_number', 'form_title', 'filed_date',
        'file_number', 'status', 'notes', 'filed_by',
    ];

    protected $casts = [
        'filed_date' => 'date',
    ];

    public function license(): BelongsTo
    {
        return $this->belongsTo(FccLicense::class, 'license_id');
    }
}
