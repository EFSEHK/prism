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
            $table->string('type', 16);
            $table->string('name');
            $table->unsignedSmallInteger('number')->nullable();
            $table->date('held_on')->nullable();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['type', 'academic_year_id']);
        });

        Schema::create('mark_sheets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assessment_id')->constrained('assessments')->cascadeOnDelete();
            $table->foreignId('study_group_id')->constrained('study_groups')->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained('subjects')->cascadeOnDelete();
            $table->string('status', 24)->default('draft');
            $table->foreignId('submitted_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('verified_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['study_group_id', 'subject_id']);
        });

        Schema::create('mark_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mark_sheet_id')->constrained('mark_sheets')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->decimal('marks_obtained', 8, 2)->nullable();
            $table->decimal('max_marks', 8, 2)->nullable();
            $table->string('grade', 8)->nullable();
            $table->boolean('is_pass')->nullable();
            $table->timestamps();
            $table->unique(['mark_sheet_id', 'student_id']);
            $table->index('student_id');
        });

        Schema::create('attendance_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('study_group_id')->constrained('study_groups')->cascadeOnDelete();
            $table->date('date');
            $table->string('status', 24)->default('draft');
            $table->foreignId('submitted_by_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('verified_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();
            $table->unique(['study_group_id', 'date']);
            $table->index(['study_group_id', 'date']);
        });

        Schema::create('attendance_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attendance_batch_id')->constrained('attendance_batches')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->string('status', 16);
            $table->timestamps();
            $table->unique(['attendance_batch_id', 'student_id']);
            $table->index(['student_id', 'created_at']);
        });

        Schema::create('timetable_slots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('study_group_id')->constrained('study_groups')->cascadeOnDelete();
            $table->foreignId('subject_id')->nullable()->constrained('subjects')->nullOnDelete();
            $table->unsignedTinyInteger('day_of_week');
            $table->time('start_time');
            $table->time('end_time');
            $table->string('room')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['study_group_id', 'day_of_week']);
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
            $table->foreignId('study_group_id')->nullable()->constrained('study_groups')->nullOnDelete();
            $table->foreignId('section_id')->nullable()->constrained('sections')->nullOnDelete();
            $table->foreignId('subject_id')->nullable()->constrained('subjects')->nullOnDelete();
            $table->string('title');
            $table->text('body')->nullable();
            $table->date('due_date')->nullable();
            $table->string('attachment_path')->nullable();
            $table->string('status', 24)->default('pending_approval');
            $table->foreignId('created_by_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('approved_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['study_group_id', 'section_id', 'created_at']);
        });

        Schema::create('online_class_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('study_group_id')->constrained('study_groups')->cascadeOnDelete();
            $table->foreignId('subject_id')->nullable()->constrained('subjects')->nullOnDelete();
            $table->string('label');
            $table->string('url', 2048);
            $table->date('scheduled_date');
            $table->time('start_time');
            $table->time('end_time')->nullable();
            $table->string('status', 24)->default('pending_approval');
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reminder_sent_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['study_group_id', 'scheduled_date']);
        });

        Schema::create('fee_vouchers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->string('title');
            $table->string('file_path')->nullable();
            $table->string('submission_status', 24)->default('pending');
            $table->foreignId('updated_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['student_id', 'submission_status']);
        });

        Schema::create('user_broadcasts', function (Blueprint $table) {
            $table->id();
            $table->string('audience_type', 16);
            $table->foreignId('area_id')->nullable()->constrained('areas')->nullOnDelete();
            $table->foreignId('school_class_id')->nullable()->constrained('school_classes')->nullOnDelete();
            $table->foreignId('section_id')->nullable()->constrained('sections')->nullOnDelete();
            $table->foreignId('study_group_id')->nullable()->constrained('study_groups')->nullOnDelete();
            $table->foreignId('student_id')->nullable()->constrained('students')->nullOnDelete();
            $table->boolean('visible_to_student')->default(false);
            $table->string('title');
            $table->text('body')->nullable();
            $table->foreignId('author_user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['published_at', 'audience_type']);
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
        Schema::dropIfExists('user_broadcasts');
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
