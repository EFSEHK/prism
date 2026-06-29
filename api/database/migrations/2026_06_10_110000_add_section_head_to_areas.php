<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('areas', 'section_head_user_id')) {
            return;
        }

        Schema::table('areas', function (Blueprint $table) {
            $table->foreignId('section_head_user_id')
                ->nullable()
                ->after('name')
                ->constrained('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('areas', 'section_head_user_id')) {
            return;
        }

        Schema::table('areas', function (Blueprint $table) {
            $table->dropForeign(['section_head_user_id']);
            $table->dropColumn('section_head_user_id');
        });
    }
};
