<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'starts_on', 'ends_on', 'required_hours', 'is_open'])]
class TrainingPeriod extends Model
{
    /** @use HasFactory<\Database\Factories\TrainingPeriodFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'starts_on' => 'date',
            'ends_on' => 'date',
            'required_hours' => 'integer',
            'is_open' => 'boolean',
        ];
    }

    public function placements(): HasMany
    {
        return $this->hasMany(Placement::class, 'period_id');
    }
}
