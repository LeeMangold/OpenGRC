<?php

namespace App\Models;

use App\Enums\MitigationType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Risk extends Model
{
    use HasFactory;

    protected $casts = [
        'id' => 'integer',
        'action' => MitigationType::class,
    ];

    protected $fillable = [
        'name',
        'likelihood',
        'impact',
    ];


}
