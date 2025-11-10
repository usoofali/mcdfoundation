<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class HealthcareProvider extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'address',
        'contact',
        'services',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'services' => 'array',
        ];
    }

    public function healthClaims(): HasMany
    {
        return $this->hasMany(HealthClaim::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}
