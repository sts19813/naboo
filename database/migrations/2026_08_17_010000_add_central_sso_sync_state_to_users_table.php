<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->timestamp('sso_synced_at')->nullable()->after('sso_subject');
            $table->boolean('sso_sync_pending')->default(true)->after('sso_synced_at')->index();
            $table->text('sso_sync_error')->nullable()->after('sso_sync_pending');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropIndex(['sso_sync_pending']);
            $table->dropColumn(['sso_synced_at', 'sso_sync_pending', 'sso_sync_error']);
        });
    }
};
