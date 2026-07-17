<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->char('checkout_token_hash', 64)
                ->nullable()
                ->unique()
                ->after('order_number');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->dropUnique(['checkout_token_hash']);
            $table->dropColumn('checkout_token_hash');
        });
    }
};
