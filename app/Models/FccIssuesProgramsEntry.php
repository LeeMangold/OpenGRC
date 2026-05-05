<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FccIssuesProgramsEntry extends Model
{
    protected $table = 'fcc_issues_programs_entries';

    protected $fillable = [
        'list_id', 'issue', 'program_title', 'program_description',
        'aired_at', 'duration_minutes', 'program_type',
    ];

    protected $casts = [
        'aired_at' => 'datetime',
    ];

    public function list(): BelongsTo
    {
        return $this->belongsTo(FccIssuesProgramsList::class, 'list_id');
    }
}
