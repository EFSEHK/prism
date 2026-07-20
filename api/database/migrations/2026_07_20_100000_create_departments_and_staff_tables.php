<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('departments', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('staff', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->unique()->constrained('users')->nullOnDelete();
            $table->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->string('name');
            $table->string('designation')->nullable();
            $table->string('gender', 1)->nullable();
            $table->string('contact_no', 64)->nullable();
            $table->date('date_of_joining')->nullable();
            $table->string('qualification')->nullable();
            $table->enum('classes', [
                'management',
                'college_faculty',
                'school_faculty',
                'visiting',
                'pti',
                'teaching_assistant',
                'supporting',
            ])->nullable();
            $table->string('subject')->nullable();
            $table->string('cnic', 20)->nullable()->unique();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff');
        Schema::dropIfExists('departments');
    }
};
