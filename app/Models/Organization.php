<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'sector', 'address', 'contact_name', 'contact_phone', 'is_active'])]
class Organization extends Model
{
    /** @use HasFactory<\Database\Factories\OrganizationFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    /** Field supervisors and any users attached to this organisation. */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function placements(): HasMany
    {
        return $this->hasMany(Placement::class);
    }
}
