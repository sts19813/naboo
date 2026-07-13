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
        Schema::table('properties', function (Blueprint $table) {
            $table->boolean('use_global_expense_notifications')
                ->default(true)
                ->after('charge_tolerance_days');
            $table->unsignedSmallInteger('expense_notification_days_before')
                ->nullable()
                ->after('use_global_expense_notifications');
            $table->json('expense_notification_emails')
                ->nullable()
                ->after('expense_notification_days_before');
            $table->json('expense_notification_phones')
                ->nullable()
                ->after('expense_notification_emails');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('properties', function (Blueprint $table) {
            $table->dropColumn([
                'use_global_expense_notifications',
                'expense_notification_days_before',
                'expense_notification_emails',
                'expense_notification_phones',
            ]);
        });
    }
};
