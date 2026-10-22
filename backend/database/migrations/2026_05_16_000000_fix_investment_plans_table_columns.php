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
        Schema::table('investment_plans', function (Blueprint $table) {
            if (!Schema::hasColumn('investment_plans', 'amount')) {
                // If min_amount exists, we can use it to populate amount, but since we are seeding anyway, we'll just add it.
                $table->decimal('amount', 15, 2)->after('description')->default(0);
            }
            
            if (Schema::hasColumn('investment_plans', 'min_amount')) {
                $table->dropColumn('min_amount');
            }
            
            if (Schema::hasColumn('investment_plans', 'max_amount')) {
                $table->dropColumn('max_amount');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('investment_plans', function (Blueprint $table) {
            if (Schema::hasColumn('investment_plans', 'amount')) {
                $table->dropColumn('amount');
            }
            
            if (!Schema::hasColumn('investment_plans', 'min_amount')) {
                $table->decimal('min_amount', 15, 2)->after('description')->default(0);
            }
            
            if (!Schema::hasColumn('investment_plans', 'max_amount')) {
                $table->decimal('max_amount', 15, 2)->after('min_amount')->default(0);
            }
        });
    }
};
