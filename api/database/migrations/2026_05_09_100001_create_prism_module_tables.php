<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assessments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academic_year_id')->nullable()->constrained('academic_years')->nullOnDelete();
            $table->string('type', 16); // test, exam
            $table->string('name');
            $table->unsignedSmallInteger('number')->nullable();
            $table->date('held_on')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['type', 'academic_year_id']);
        });

        Schema::create('mark_sheets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assessment_id')->constrained('assessments')->cascadeOnDelete();
            $table->foreignId('school_class_id')->constrained('school_classes')->cascadeOnDelete();
            $table->foreignId('section_id')->constrained('sections')->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained('subjects')->cascadeOnDelete();
            $table->foreignId('submitted_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['school_class_id', 'section_id', 'subject_id']);
        });

        Schema::create('mark_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mark_sheet_id')->constrained('mark_sheets')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->decimal('marks_obtained', 8, 2)->nullable();
            $table->decimal('max_marks', 8, 2)->nullable();
            $table->string('grade', 8)->nullable();
            $table->timestamps();
            $table->unique(['mark_sheet_id', 'student_id']);
            $table->index('student_id');
        });

        Schema::create('attendance_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_class_id')->constrained('school_classes')->cascadeOnDelete();
            $table->foreignId('section_id')->constrained('sections')->cascadeOnDelete();
            $table->date('date');
            $table->foreignId('submitted_by_user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['school_class_id', 'section_id', 'date']);
            $table->index(['school_class_id', 'date']);
        });

        Schema::create('attendance_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attendance_batch_id')->constrained('attendance_batches')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->string('status', 16); // present, absent, late, excused
            $table->timestamps();
            $table->unique(['attendance_batch_id', 'student_id']);
            $table->index(['student_id', 'created_at']);
        });

        Schema::create('timetable_slots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_class_id')->constrained('school_classes')->cascadeOnDelete();
            $table->foreignId('section_id')->constrained('sections')->cascadeOnDelete();
            $table->foreignId('subject_id')->nullable()->constrained('subjects')->nullOnDelete();
            $table->unsignedTinyInteger('day_of_week'); // 0 = Sunday ... Laravel carbon
            $table->time('start_time');
            $table->time('end_time');
            $table->string('room')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['school_class_id', 'section_id', 'day_of_week']);
        });

        Schema::create('datesheet_entries', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->date('exam_date');
            $table->foreignId('school_class_id')->nullable()->constrained('school_classes')->nullOnDelete();
            $table->foreignId('subject_id')->nullable()->constrained('subjects')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['exam_date', 'school_class_id']);
        });

        Schema::create('homework_posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_class_id')->constrained('school_classes')->cascadeOnDelete();
            $table->foreignId('section_id')->constrained('sections')->cascadeOnDelete();
            $table->foreignId('subject_id')->nullable()->constrained('subjects')->nullOnDelete();
            $table->string('title');
            $table->text('body')->nullable();
            $table->date('due_date')->nullable();
            $table->string('attachment_path')->nullable();
            $table->foreignId('created_by_user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['school_class_id', 'section_id', 'created_at']);
        });

        Schema::create('online_class_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_class_id')->constrained('school_classes')->cascadeOnDelete();
            $table->foreignId('section_id')->nullable()->constrained('sections')->nullOnDelete();
            $table->foreignId('subject_id')->nullable()->constrained('subjects')->nullOnDelete();
            $table->string('label');
            $table->string('url', 2048);
            $table->unsignedTinyInteger('day_of_week')->nullable();
            $table->time('start_time')->nullable();
            $table->unsignedSmallInteger('minutes_before')->default(30);
            $table->timestamps();
            $table->softDeletes();
            $table->index(['school_class_id', 'section_id']);
        });

        Schema::create('fee_vouchers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->string('title');
            $table->string('file_path')->nullable();
            $table->string('submission_status', 24)->default('pending'); // pending, submitted, verified
            $table->foreignId('updated_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['student_id', 'submission_status']);
        });

        Schema::create('feed_posts', function (Blueprint $table) {
            $table->id();
            $table->string('type', 32); // announcement, event, achievement
            $table->string('title');
            $table->text('body')->nullable();
            $table->string('scope', 16); // school, class, student
            $table->foreignId('scope_school_class_id')->nullable()->constrained('school_classes')->nullOnDelete();
            $table->foreignId('scope_section_id')->nullable()->constrained('sections')->nullOnDelete();
            $table->foreignId('scope_student_id')->nullable()->constrained('students')->nullOnDelete();
            $table->foreignId('author_user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['published_at', 'scope']);
        });

        Schema::create('leave_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('parent_user_id')->constrained('users')->cascadeOnDelete();
            $table->date('start_date');
            $table->date('end_date');
            $table->text('reason')->nullable();
            $table->string('status', 16)->default('pending');
            $table->foreignId('decided_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('decided_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['student_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leave_requests');
        Schema::dropIfExists('feed_posts');
        Schema::dropIfExists('fee_vouchers');
        Schema::dropIfExists('online_class_links');
        Schema::dropIfExists('homework_posts');
        Schema::dropIfExists('datesheet_entries');
        Schema::dropIfExists('timetable_slots');
        Schema::dropIfExists('attendance_records');
        Schema::dropIfExists('attendance_batches');
        Schema::dropIfExists('mark_entries');
        Schema::dropIfExists('mark_sheets');
        Schema::dropIfExists('assessments');
    }
};
