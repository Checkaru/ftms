<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('evaluations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('placement_id')->constrained('placements')->cascadeOnDelete();
            $table->foreignId('evaluator_id')->constrained('users')->cascadeOnDelete();
            $table->string('kind'); // field | academic
            $table->json('scores'); // {"attendance":9,"skills":8} — rubric may change per period
            $table->decimal('total', 5, 2);
            $table->text('comments')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();

            // One field evaluation and one academic evaluation per placement.
            $table->unique(['placement_id', 'kind']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evaluations');
    }
};
