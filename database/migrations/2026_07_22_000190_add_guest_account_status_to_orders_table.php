<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->string('guest_account_status', 16)
                ->nullable()
                ->after('user_id');
        });

        DB::table('orders')
            ->whereNull('user_id')
            ->whereNotNull('checkout_token_hash')
            ->update(['guest_account_status' => 'pending']);
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->dropColumn('guest_account_status');
        });
    }
};
