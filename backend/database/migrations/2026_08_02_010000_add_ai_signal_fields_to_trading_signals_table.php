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
        Schema::table('trading_signals', function (Blueprint $table) {
            // AI Signal specific fields
            $table->string('direction', 10)->nullable(); // CALL, PUT (binary options style)
            $table->boolean('is_otc')->default(false); // OTC (Over-The-Counter) asset flag
            $table->string('signal_type', 30)->default('standard'); // standard, ai_1m, ai_5m, etc.
            $table->integer('entry_window_minutes')->nullable(); // e.g., 1 for 1m signals
            $table->decimal('confidence_percent', 5, 2)->nullable(); // AI confidence as percentage (e.g., 93.00)
            $table->text('martingale_plan')->nullable(); // JSON string with level plan (L0, L1, L2, L3...)
        });

        // Add indexes for the new fields
        Schema::table('trading_signals', function (Blueprint $table) {
            $table->index('signal_type');
            $table->index('is_otc');
            $table->index('direction');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('trading_signals', function (Blueprint $table) {
            $table->dropIndex(['signal_type']);
            $table->dropIndex(['is_otc']);
            $table->dropIndex(['direction']);
            $table->dropColumn(['direction', 'is_otc', 'signal_type', 'entry_window_minutes', 'confidence_percent', 'martingale_plan']);
        });
    }
};
