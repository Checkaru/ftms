<?php

namespace App\Models;

use App\Enums\EvaluationKind;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// kind, placement_id, evaluator_id and submitted_at are set by the submit
// action, not from request data. Only the rubric scores/comments come from the form.
#[Fillable(['scores', 'total', 'comments'])]
class Evaluation extends Model
{
    /** @use HasFactory<\Database\Factories\EvaluationFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'kind' => EvaluationKind::class,
            'scores' => 'array', // rubric can change per period without a migration
            'total' => 'decimal:2',
            'submitted_at' => 'datetime',
        ];
    }

    public function placement(): BelongsTo
    {
        return $this->belongsTo(Placement::class);
    }

    public function evaluator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'evaluator_id');
    }
}
