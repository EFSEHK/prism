<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fee_vouchers', function (Blueprint $table) {
            $table->unsignedInteger('external_voucher')->nullable()->after('student_id');
            $table->date('voucher_month')->nullable()->after('external_voucher');
            $table->date('due_date')->nullable()->after('voucher_month');
            $table->string('voucher_type', 32)->nullable()->after('due_date');
            $table->string('voucher_no', 32)->nullable()->after('voucher_type');
            $table->decimal('total_due', 12, 2)->default(0)->after('voucher_no');
            $table->decimal('total_paid', 12, 2)->default(0)->after('total_due');
            $table->string('payment_status', 16)->default('unpaid')->after('total_paid');

            $table->unique(['student_id', 'external_voucher'], 'fee_vouchers_student_external_unique');
        });

        Schema::create('fee_deposits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fee_voucher_id')->nullable()->constrained('fee_vouchers')->nullOnDelete();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->unsignedInteger('external_voucher');
            $table->decimal('amount', 12, 2);
            $table->date('fee_date');
            $table->timestamp('imported_at')->nullable();
            $table->timestamps();

            $table->unique(
                ['student_id', 'external_voucher', 'fee_date', 'amount'],
                'fee_deposits_import_unique'
            );
        });

        Schema::create('data_import_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('source', 32)->default('aims');
            $table->string('data_type', 64);
            $table->string('filename');
            $table->json('stats');
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('data_import_logs');
        Schema::dropIfExists('fee_deposits');

        Schema::table('fee_vouchers', function (Blueprint $table) {
            $table->dropUnique('fee_vouchers_student_external_unique');
            $table->dropColumn([
                'external_voucher',
                'voucher_month',
                'due_date',
                'voucher_type',
                'voucher_no',
                'total_due',
                'total_paid',
                'payment_status',
            ]);
        });
    }
};
