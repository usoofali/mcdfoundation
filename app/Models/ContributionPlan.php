<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ContributionPlan extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'amount',
        'description',
        'active',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'active' => 'boolean',
        ];
    }

    public function members(): HasMany
    {
        return $this->hasMany(Member::class);
    }

    public function contributions(): HasMany
    {
        return $this->hasMany(Contribution::class);
    }

    public function scopeActive($query)
    {
        return $query->where('active', true);
    }
}
