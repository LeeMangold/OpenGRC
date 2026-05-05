<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class FccPublicFileDocument extends Model
{
    use SoftDeletes;

    protected $table = 'fcc_public_file_documents';

    protected $fillable = [
        'license_id', 'document_type', 'title',
        'document_date', 'uploaded_to_lms_date', 'retention_until',
        'lms_url', 'notes', 'uploaded_by',
    ];

    protected $casts = [
        'document_date' => 'date',
        'uploaded_to_lms_date' => 'date',
        'retention_until' => 'date',
    ];

    public function license(): BelongsTo
    {
        return $this->belongsTo(FccLicense::class, 'license_id');
    }
}
