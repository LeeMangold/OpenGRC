<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class FccTransmitter extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'fcc_transmitters';

    protected $fillable = [
        'license_id', 'manufacturer', 'model', 'serial_number',
        'rated_power_kw', 'authorized_erp_kw', 'measured_power_kw',
        'last_proof_of_performance', 'next_proof_due',
        'eas_endec_present', 'eas_endec_model', 'status', 'notes',
    ];

    protected $casts = [
        'rated_power_kw' => 'decimal:3',
        'authorized_erp_kw' => 'decimal:3',
        'measured_power_kw' => 'decimal:3',
        'last_proof_of_performance' => 'date',
        'next_proof_due' => 'date',
        'eas_endec_present' => 'boolean',
    ];

    public function license(): BelongsTo
    {
        return $this->belongsTo(FccLicense::class, 'license_id');
    }
}
