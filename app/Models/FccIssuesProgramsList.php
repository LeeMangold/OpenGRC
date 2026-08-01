<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FccIssuesProgramsList extends Model
{
    protected $table = 'fcc_issues_programs_lists';

    protected $fillable = [
        'license_id', 'quarter_year', 'quarter',
        'placed_in_file_date', 'status', 'preparer_notes',
    ];

    protected $casts = [
        'placed_in_file_date' => 'date',
    ];

    public function license(): BelongsTo
    {
        return $this->belongsTo(FccLicense::class, 'license_id');
    }

    public function entries(): HasMany
    {
        return $this->hasMany(FccIssuesProgramsEntry::class, 'list_id');
    }
}
