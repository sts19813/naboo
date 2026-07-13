<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('charge_payments', function (Blueprint $table) {
            $table->string('source', 24)->default('admin')->after('status');
            $table->string('payment_method', 40)->nullable()->after('source');
            $table->string('reference', 190)->nullable()->after('payment_method');
            $table->string('receipt_path')->nullable()->after('reference');
            $table->text('notes')->nullable()->after('payload');
            $table->date('payment_date')->nullable()->after('paid_at');
            $table->foreignId('registered_by')->nullable()->after('payment_date')->constrained('users')->nullOnDelete();
            $table->foreignId('validated_by')->nullable()->after('registered_by')->constrained('users')->nullOnDelete();
            $table->text('validation_notes')->nullable()->after('validated_by');

            $table->index(['status', 'source']);
            $table->index('payment_method');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('charge_payments', function (Blueprint $table) {
            $table->dropForeign(['registered_by']);
            $table->dropForeign(['validated_by']);
            $table->dropIndex(['status', 'source']);
            $table->dropIndex(['payment_method']);
            $table->dropColumn([
                'source',
                'payment_method',
                'reference',
                'receipt_path',
                'notes',
                'payment_date',
                'registered_by',
                'validated_by',
                'validation_notes',
            ]);
        });
    }
};
