<?php

use App\Enums\LogStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('placement_id')->constrained('placements')->cascadeOnDelete();
            $table->date('work_date');
            $table->time('check_in');
            $table->time('check_out');
            $table->unsignedSmallInteger('minutes'); // computed on save, never sent by the client
            $table->string('tasks', 1000);
            $table->string('status')->default(LogStatus::Pending->value);
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->string('reject_reason', 300)->nullable();
            $table->timestamps();

            // A duplicate-day entry is the obvious way to inflate hours: block it
            // at the database level, not just in validation.
            $table->unique(['placement_id', 'work_date']);
            $table->index(['placement_id', 'status']);
            $table->index(['status', 'work_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_logs');
    }
};
