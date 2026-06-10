<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('study_groups', 'school_class_id')) {
            return;
        }

        Schema::table('study_groups', function (Blueprint $table) {
            $table->dropForeign(['school_class_id']);
            $table->dropColumn('school_class_id');
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('study_groups', 'school_class_id')) {
            return;
        }

        Schema::table('study_groups', function (Blueprint $table) {
            $table->foreignId('school_class_id')->nullable()->after('id')->constrained('school_classes')->cascadeOnDelete();
        });
    }
};
