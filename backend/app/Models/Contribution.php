<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Contribution extends Model
{
    use HasFactory, HasUuid;

    protected $fillable = [
        'uuid',
        'name',
        'employee_share',
        'employer_share',
    ];

    protected $casts = [
        'employee_share' => 'decimal:2',
        'employer_share' => 'decimal:2',
    ];
}
