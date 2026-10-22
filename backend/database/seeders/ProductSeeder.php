<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Products are now created exclusively by the admin through the admin
     * panel. No demo/seed products should be inserted here so the dashboard
     * shows an empty state until real products are added.
     */
    public function run(): void
    {
        // Intentionally empty — products are admin-managed.
    }
}

