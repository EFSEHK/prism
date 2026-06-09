<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->foreignId('section_id')->nullable()->after('study_group_id')->constrained('sections')->nullOnDelete();
            $table->string('roll_no', 32)->nullable()->after('admission_no');
            $table->string('cnic', 20)->nullable()->after('roll_no');
            $table->string('father_name')->nullable()->after('cnic');
            $table->string('father_cnic', 20)->nullable()->after('father_name');
            $table->string('guardian_name')->nullable()->after('father_cnic');
            $table->string('guardian_cnic', 20)->nullable()->after('guardian_name');
            $table->boolean('father_is_guardian')->default(false)->after('guardian_cnic');
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropConstrainedForeignId('section_id');
            $table->dropColumn([
                'roll_no',
                'cnic',
                'father_name',
                'father_cnic',
                'guardian_name',
                'guardian_cnic',
                'father_is_guardian',
            ]);
        });
    }
};
