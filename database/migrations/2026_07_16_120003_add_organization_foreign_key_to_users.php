<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * `users.organization_id` is declared in the base users migration, but the
     * FK constraint can only be added once `organizations` exists. nullOnDelete:
     * removing an organisation detaches its users rather than deleting them.
     */
    public function up(): void
    {
        // SQLite (used by the test suite) cannot ALTER a table to add a foreign
        // key; the column already exists, which is enough there. On MySQL the
        // real constraint is created.
        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->foreign('organization_id')
                ->references('id')->on('organizations')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['organization_id']);
        });
    }
};
