<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_broadcasts', function (Blueprint $table) {
            $table->string('approval_status', 24)->default('approved')->after('author_user_id');
            $table->foreignId('approved_by_user_id')->nullable()->after('approval_status')->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable()->after('approved_by_user_id');
            $table->text('rejection_comment')->nullable()->after('approved_at');
            $table->index('approval_status');
        });
    }

    public function down(): void
    {
        Schema::table('user_broadcasts', function (Blueprint $table) {
            $table->dropForeign(['approved_by_user_id']);
            $table->dropIndex(['approval_status']);
            $table->dropColumn(['approval_status', 'approved_by_user_id', 'approved_at', 'rejection_comment']);
        });
    }
};
