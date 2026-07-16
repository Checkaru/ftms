<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('training_periods', function (Blueprint $table) {
            $table->id();
            $table->string('name');                       // e.g. "صيف 2026"
            $table->date('starts_on');
            $table->date('ends_on');
            $table->unsignedSmallInteger('required_hours'); // e.g. 180
            $table->boolean('is_open')->default(false);     // only one open at a time (enforced in app)
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('training_periods');
    }
};
