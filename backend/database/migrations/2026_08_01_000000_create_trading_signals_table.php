<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
   
    public function up(): void
    {
        Schema::create('trading_signals', function (Blueprint $table) {
            $table->id();
            $table->string('symbol', 20); // e.g. EURUSD, GBPJPY, XAUUSD, BTCUSD, US30
            $table->string('type', 20); // forex, commodity, index, crypto
            $table->string('action', 10); // BUY, SELL, STRONG_BUY, STRONG_SELL, NEUTRAL
            $table->decimal('entry_price_min', 15, 5)->nullable();
            $table->decimal('entry_price_max', 15, 5)->nullable();
            $table->decimal('stop_loss', 15, 5)->nullable();
            $table->decimal('take_profit_1', 15, 5)->nullable();
            $table->decimal('take_profit_2', 15, 5)->nullable();
            $table->decimal('take_profit_3', 15, 5)->nullable();
            $table->string('status', 20)->default('active'); // active, expired, completed, cancelled
            $table->timestamp('signal_time')->nullable();
            $table->timestamp('expiry_time')->nullable();
            $table->string('confidence_level', 20)->default('medium'); // low, medium, high, very_high
            $table->text('analysis_summary')->nullable();
            $table->text('technical_indicators')->nullable(); // JSON string of indicator data
            $table->text('risk_notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();

            // Indexes for faster queries
            $table->index('symbol');
            $table->index('type');
            $table->index('status');
            $table->index('expiry_time');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trading_signals');
    }
};

