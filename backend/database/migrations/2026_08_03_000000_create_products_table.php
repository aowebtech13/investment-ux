<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    
    public function up(): void
    {
        if (!Schema::hasTable('products')) {
            Schema::create('products', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('category')->default('men');
                $table->string('subtitle')->nullable();
                $table->text('description')->nullable();
                $table->decimal('price', 12, 2)->default(0);
                $table->string('old_price')->nullable();
                $table->string('percent')->nullable();
                $table->string('rating')->nullable();
                $table->string('brand')->nullable();
                $table->string('image')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->index(['category', 'is_active']);
            });
        }
    }

    
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};

