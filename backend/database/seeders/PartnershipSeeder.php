<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PartnershipSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        \App\Models\Partnership::create([
            'name' => 'leximan',
            'description' => 'Premium partnership program with exclusive benefits and dedicated support.',
            'amount' => 1000,
            'benefits' => [
                '24/7 Priority Support',
                'Exclusive Trading Signals',
                'Monthly Strategy Sessions',
                'Advanced Analytics Dashboard',
            ],
            'is_active' => true,
        ]);

        \App\Models\Partnership::create([
            'name' => 'Mr Roy',
            'description' => 'Elite-level partnership with comprehensive features and white-glove service.',
            'amount' => 300,
            'benefits' => [
                'Dedicated Account Manager',
                'Custom Trading Strategies',
                'Real-time Market Analysis',
                'Private Network Access',
                'Quarterly Portfolio Reviews',
                'Custom API Integration',
            ],
            'is_active' => true,
        ]);

        \App\Models\Partnership::create([
            'name' => 'doveman',
            'description' => 'Standard partnership program with essential features for growing traders.',
            'amount' => 100,
            'benefits' => [
                'Email Support',
                'Weekly Market Reports',
                'Basic Analytics Tools',
                'Community Access',
            ],
            'is_active' => true,
        ]);

        \App\Models\Partnership::create([
            'name' => 'xman',
            'description' => 'Starter partnership program perfect for new traders beginning their journey.',
            'amount' => 500,
            'benefits' => [
                'Email Support',
                'Educational Resources',
                'Basic Trading Tools',
                'Community Forum Access',
            ],
            'is_active' => true,
        ]);
    }
}
