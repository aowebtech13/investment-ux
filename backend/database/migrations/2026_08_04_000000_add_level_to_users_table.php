<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    
    public function up(): void
    {
        if (!Schema::hasColumn('users', 'level')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('level')->default('starter')->after('role');
                $table->index('level');
            });
        }
    }

    
    public function down(): void
    {
        if (Schema::hasColumn('users', 'level')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropIndex(['level']);
                $table->dropColumn('level');
            });
        }
    }
};

