<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_features', function (Blueprint $table) {
            $table->id();
            $table->string('module_code', 64);
            $table->string('feature_key', 128)->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->json('default_payload_schema')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index('module_code');
        });

        Schema::create('notification_approval_policies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('notification_feature_id')->constrained('notification_features')->cascadeOnDelete();
            $table->unsignedSmallInteger('sequence')->default(1);
            $table->string('approver_role_name', 64);
            $table->foreignId('school_class_id')->nullable()->constrained('school_classes')->cascadeOnDelete();
            $table->foreignId('section_id')->nullable()->constrained('sections')->cascadeOnDelete();
            $table->boolean('requires_approval')->default(true);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['notification_feature_id', 'is_active'], 'notif_policies_feature_active');
        });

        Schema::create('notification_dispatch_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('notification_feature_id')->constrained('notification_features')->cascadeOnDelete();
            $table->string('context_type', 64);
            $table->unsignedBigInteger('context_id');
            $table->string('scope_type', 16);
            $table->json('scope_ids')->nullable();
            $table->json('payload_json')->nullable();
            $table->string('status', 32)->default('pending_approval');
            $table->unsignedSmallInteger('current_sequence')->default(1);
            $table->foreignId('school_class_id')->nullable()->constrained('school_classes')->nullOnDelete();
            $table->foreignId('section_id')->nullable()->constrained('sections')->nullOnDelete();
            $table->timestamp('scheduled_for')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['status', 'created_at'], 'notif_disp_status_created');
            $table->index(['school_class_id', 'section_id', 'status'], 'notif_disp_class_status');
            $table->index(['context_type', 'context_id'], 'notif_disp_context');
        });

        Schema::create('notification_approval_actions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('notification_dispatch_request_id');
            $table->foreign('notification_dispatch_request_id', 'notif_action_disp_fk')
                ->references('id')
                ->on('notification_dispatch_requests')
                ->cascadeOnDelete();
            $table->unsignedSmallInteger('sequence');
            $table->string('approver_role_name', 64);
            $table->string('decision', 16)->default('pending');
            $table->foreignId('decided_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('decided_at')->nullable();
            $table->text('comment')->nullable();
            $table->timestamps();
            $table->unique(['notification_dispatch_request_id', 'sequence'], 'notif_action_step_unique');
        });

        Schema::create('device_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('token', 512);
            $table->string('platform', 16)->default('unknown');
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'token']);
            $table->index(['user_id', 'revoked_at']);
        });

        Schema::create('user_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedBigInteger('notification_dispatch_request_id')->nullable();
            $table->foreign('notification_dispatch_request_id', 'user_notif_disp_fk')
                ->references('id')
                ->on('notification_dispatch_requests')
                ->nullOnDelete();
            $table->string('title');
            $table->text('body')->nullable();
            $table->json('data_json')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'read_at', 'created_at'], 'user_notifs_user_read');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_notifications');
        Schema::dropIfExists('device_tokens');
        Schema::dropIfExists('notification_approval_actions');
        Schema::dropIfExists('notification_dispatch_requests');
        Schema::dropIfExists('notification_approval_policies');
        Schema::dropIfExists('notification_features');
    }
};
