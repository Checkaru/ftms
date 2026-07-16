<?php

namespace Database\Factories;

use App\Enums\EvaluationKind;
use App\Models\Evaluation;
use App\Models\Placement;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Evaluation>
 */
class EvaluationFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'placement_id' => Placement::factory(),
            'evaluator_id' => User::factory(),
            ...$this->rubric(EvaluationKind::Field),
            'comments' => fake()->optional()->sentence(),
            'submitted_at' => now(),
        ];
    }

    public function field(?User $evaluator = null): static
    {
        return $this->state(fn (array $attributes) => [
            'evaluator_id' => $evaluator?->id ?? $attributes['evaluator_id'],
            ...$this->rubric(EvaluationKind::Field),
        ]);
    }

    public function academic(?User $evaluator = null): static
    {
        return $this->state(fn (array $attributes) => [
            'evaluator_id' => $evaluator?->id ?? $attributes['evaluator_id'],
            ...$this->rubric(EvaluationKind::Academic),
        ]);
    }

    /**
     * Build a random-but-valid score set for a rubric, plus its kind and total.
     *
     * @return array{kind: EvaluationKind, scores: array<string,int>, total: float}
     */
    protected function rubric(EvaluationKind $kind): array
    {
        $criteria = config("training.rubrics.{$kind->value}", []);

        $scores = [];
        foreach ($criteria as $key => $meta) {
            // Award between 60% and 100% of the max for realistic sample data.
            $scores[$key] = fake()->numberBetween((int) ceil($meta['max'] * 0.6), $meta['max']);
        }

        return [
            'kind' => $kind,
            'scores' => $scores,
            'total' => array_sum($scores),
        ];
    }
}
