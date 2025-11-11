<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class ContributionPlan extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'display_name',
        'amount',
        'description',
        'frequency',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'is_active' => 'boolean',
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

    public function getLabelAttribute(): string
    {
        return $this->display_name ?: Str::headline($this->name);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
