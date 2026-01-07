<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Subject extends Model
{
    use HasUuid;
    protected $fillable = [
        'uuid',
        'code',
        'name',
        'description',
        'credits',
    ];


    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }
}
