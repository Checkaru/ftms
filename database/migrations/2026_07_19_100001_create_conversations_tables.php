<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // A conversation is either a placement discussion thread
        // (placement_id set, one per placement) or a direct message
        // conversation between two users (placement_id null).
        Schema::create('conversations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('placement_id')->nullable()->unique()
                ->constrained('placements')->cascadeOnDelete();
            $table->timestamps();
        });

        // DM membership, and per-user read state for every conversation kind.
        // Placement-thread rows are created lazily the first time a
        // stakeholder opens the thread.
        Schema::create('conversation_participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained('conversations')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamp('last_read_at')->nullable();
            $table->unique(['conversation_id', 'user_id']);
        });

        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained('conversations')->cascadeOnDelete();
            // Keep the thread readable if an account is later deleted.
            $table->foreignId('sender_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('body', 2000);
            $table->timestamps();

            $table->index(['conversation_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('messages');
        Schema::dropIfExists('conversation_participants');
        Schema::dropIfExists('conversations');
    }
};
