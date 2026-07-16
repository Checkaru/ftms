<?php

use App\Enums\EvaluationKind;

return [

    /*
    |--------------------------------------------------------------------------
    | Evaluation rubrics
    |--------------------------------------------------------------------------
    |
    | Each rubric is a list of criteria keyed by a stable slug. `max` is the
    | ceiling for that criterion; the evaluation total is the sum of the
    | awarded scores. Criteria can change per period without a migration
    | because `evaluations.scores` is a JSON column cast to an array.
    |
    */

    'rubrics' => [

        EvaluationKind::Field->value => [
            'attendance'   => ['label' => 'الالتزام والحضور', 'max' => 20],
            'skills'       => ['label' => 'المهارات العملية', 'max' => 30],
            'behavior'     => ['label' => 'السلوك المهني', 'max' => 25],
            'initiative'   => ['label' => 'المبادرة والتعاون', 'max' => 25],
        ],

        EvaluationKind::Academic->value => [
            'report'       => ['label' => 'التقرير النهائي', 'max' => 40],
            'objectives'   => ['label' => 'تحقيق أهداف التدريب', 'max' => 30],
            'presentation' => ['label' => 'العرض والمناقشة', 'max' => 30],
        ],

    ],

];
