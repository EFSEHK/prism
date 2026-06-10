<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendance_batches', function (Blueprint $table) {
            $table->foreignId('section_id')->nullable()->after('id')->constrained('sections')->cascadeOnDelete();
            $table->foreignId('study_group_id')->nullable()->change();
        });

        Schema::table('attendance_batches', function (Blueprint $table) {
            $table->dropUnique(['study_group_id', 'date']);
        });

        Schema::table('attendance_batches', function (Blueprint $table) {
            $table->unique(['section_id', 'date']);
            $table->index(['section_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::table('attendance_batches', function (Blueprint $table) {
            $table->dropUnique(['section_id', 'date']);
            $table->dropIndex(['section_id', 'date']);
        });

        Schema::table('attendance_batches', function (Blueprint $table) {
            $table->dropConstrainedForeignId('section_id');
            $table->foreignId('study_group_id')->nullable(false)->change();
            $table->unique(['study_group_id', 'date']);
        });
    }
};
