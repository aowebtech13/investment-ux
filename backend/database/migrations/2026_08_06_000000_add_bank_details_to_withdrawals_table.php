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
        Schema::table('withdrawals', function (Blueprint $table) {
            $table->string('bank_name')->nullable()->after('details');
            $table->string('bank_account_holder')->nullable()->after('bank_name');
            $table->string('bank_account_number')->nullable()->after('bank_account_holder');
            $table->string('bank_routing_number')->nullable()->after('bank_account_number');
            $table->string('account_type')->nullable()->after('bank_routing_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('withdrawals', function (Blueprint $table) {
            $table->dropColumn([
                'bank_name',
                'bank_account_holder',
                'bank_account_number',
                'bank_routing_number',
                'account_type',
            ]);
        });
    }
};
