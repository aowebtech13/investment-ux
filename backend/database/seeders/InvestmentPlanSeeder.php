<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class InvestmentPlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (!Schema::hasTable('investment_plans')) {
            return;
        }

        // Delete all existing plans to avoid duplicates
        \App\Models\InvestmentPlan::query()->delete();

        $plans = [
            // Salary Plans
            [
                'name' => 'Starter Pulse',
                'category' => 'Salary',
                'description' => 'Perfect for starting your investment journey with ₦3,000 monthly returns.',
                'amount' => 50000,
                'interest_rate' => 6.0,
                'duration_days' => 30,
                'return_type' => 'monthly',
                'is_active' => true,
            ],
            [
                'name' => 'Growth Core',
                'category' => 'Salary',
                'description' => 'Scale your investments with ₦6,000 monthly returns.',
                'amount' => 100000,
                'interest_rate' => 6.0,
                'duration_days' => 30,
                'return_type' => 'monthly',
                'is_active' => true,
            ],
            [
                'name' => 'Elite Anchor',
                'category' => 'Salary',
                'description' => 'A solid foundation for high returns with ₦12,000 monthly returns.',
                'amount' => 200000,
                'interest_rate' => 6.0,
                'duration_days' => 30,
                'return_type' => 'monthly',
                'is_active' => true,
            ],
            [
                'name' => 'Prime Horizon',
                'category' => 'Salary',
                'description' => 'Expand your portfolio with ₦18,000 monthly returns.',
                'amount' => 300000,
                'interest_rate' => 6.0,
                'duration_days' => 30,
                'return_type' => 'monthly',
                'is_active' => true,
            ],
            [
                'name' => 'Vanguard Tier',
                'category' => 'Salary',
                'description' => 'Leading edge investment with ₦30,000 monthly returns.',
                'amount' => 500000,
                'interest_rate' => 6.0,
                'duration_days' => 30,
                'return_type' => 'monthly',
                'is_active' => true,
            ],
            [
                'name' => 'Summit Peak',
                'category' => 'Salary',
                'description' => 'Reach the top of your financial goals with ₦42,000 monthly returns.',
                'amount' => 700000,
                'interest_rate' => 6.0,
                'duration_days' => 30,
                'return_type' => 'monthly',
                'is_active' => true,
            ],
            [
                'name' => 'Apex Legacy',
                'category' => 'Salary',
                'description' => 'Secure your future legacy with ₦60,000 monthly returns.',
                'amount' => 1000000,
                'interest_rate' => 6.0,
                'duration_days' => 30,
                'return_type' => 'monthly',
                'is_active' => true,
            ],
            [
                'name' => 'Grand Zenith',
                'category' => 'Salary',
                'description' => 'The grand investment plan with ₦120,000 monthly returns.',
                'amount' => 2000000,
                'interest_rate' => 6.0,
                'duration_days' => 30,
                'return_type' => 'monthly',
                'is_active' => true,
            ],
            [
                'name' => 'The Ultimate',
                'category' => 'Salary',
                'description' => 'The ultimate investment plan with ₦300,000 monthly returns.',
                'amount' => 5000000,
                'interest_rate' => 6.0,
                'duration_days' => 30,
                'return_type' => 'monthly',
                'is_active' => true,
            ],
            // Earnings Plans
            // Earnings Category (Yields 6% of Principal DAILY for 62 Days)
            [
                'name' => 'Micro Spark',
                'category' => 'Earnings',
                'description' => 'Start small with a daily income of ₦300 for 62 days.',
                'amount' => 5000,
                'interest_rate' => 6.0, // Represents daily rate
                'duration_days' => 62,
                'return_type' => 'daily',
                'is_active' => true,
            ],
            [
                'name' => 'Basic Flow',
                'category' => 'Earnings',
                'description' => 'Keep your earnings flowing with ₦600 daily for 62 days.',
                'amount' => 10000,
                'interest_rate' => 6.0,
                'duration_days' => 62,
                'return_type' => 'daily',
                'is_active' => true,
            ],
            [
                'name' => 'Active Yield',
                'category' => 'Earnings',
                'description' => 'Boost your returns with ₦1,200 daily for 62 days.',
                'amount' => 20000,
                'interest_rate' => 6.0,
                'duration_days' => 62,
                'return_type' => 'daily',
                'is_active' => true,
            ],
            [
                'name' => 'Steady Gain',
                'category' => 'Earnings',
                'description' => 'Consistency is key with ₦1,800 daily for 62 days.',
                'amount' => 30000,
                'interest_rate' => 6.0,
                'duration_days' => 62,
                'return_type' => 'daily',
                'is_active' => true,
            ],
            [
                'name' => 'Premium Rise',
                'category' => 'Earnings',
                'description' => 'Elevate your earnings with ₦3,000 daily for 62 days.',
                'amount' => 50000,
                'interest_rate' => 6.0,
                'duration_days' => 62,
                'return_type' => 'daily',
                'is_active' => true,
            ],
            [
                'name' => 'Silver Stream',
                'category' => 'Earnings',
                'description' => 'A steady stream of ₦4,200 daily for 62 days.',
                'amount' => 70000,
                'interest_rate' => 6.0,
                'duration_days' => 62,
                'return_type' => 'daily',
                'is_active' => true,
            ],
            [
                'name' => 'Gold Frontier',
                'category' => 'Earnings',
                'description' => 'Exploit the gold frontier with ₦6,000 daily for 62 days.',
                'amount' => 100000,
                'interest_rate' => 6.0,
                'duration_days' => 62,
                'return_type' => 'daily',
                'is_active' => true,
            ],
            [
                'name' => 'Diamond Vault',
                'category' => 'Earnings',
                'description' => 'Secure your wealth with ₦12,000 daily for 62 days.',
                'amount' => 200000,
                'interest_rate' => 6.0,
                'duration_days' => 62,
                'return_type' => 'daily',
                'is_active' => true,
            ],
            [
                'name' => 'Master Equity',
                'category' => 'Earnings',
                'description' => 'The master plan with ₦24,000 daily for 62 days.',
                'amount' => 400000,
                'interest_rate' => 6.0,
                'duration_days' => 62,
                'return_type' => 'daily',
                'is_active' => true,
            ],
            // Investment Tier Plans (Yields 6% of Principal DAILY for 30 Days)
            [
                'name' => 'Starter Pulse',
                'category' => 'Investment Tier',
                'description' => 'Perfect for starting your investment journey with ₦3,000 daily returns.',
                'amount' => 50000,
                'interest_rate' => 6.0,
                'duration_days' => 30,
                'return_type' => 'daily',
                'is_active' => true,
            ],
            [
                'name' => 'Growth Core',
                'category' => 'Investment Tier',
                'description' => 'Scale your investments with ₦6,000 daily returns.',
                'amount' => 100000,
                'interest_rate' => 6.0,
                'duration_days' => 30,
                'return_type' => 'daily',
                'is_active' => true,
            ],
            [
                'name' => 'Elite Anchor',
                'category' => 'Investment Tier',
                'description' => 'A solid foundation for high returns with ₦12,000 daily returns.',
                'amount' => 200000,
                'interest_rate' => 6.0,
                'duration_days' => 30,
                'return_type' => 'daily',
                'is_active' => true,
            ],
            [
                'name' => 'Prime Horizon',
                'category' => 'Investment Tier',
                'description' => 'Expand your portfolio with ₦18,000 daily returns.',
                'amount' => 300000,
                'interest_rate' => 6.0,
                'duration_days' => 30,
                'return_type' => 'daily',
                'is_active' => true,
            ],
            [
                'name' => 'Vanguard Tier',
                'category' => 'Investment Tier',
                'description' => 'Leading edge investment with ₦30,000 daily returns.',
                'amount' => 500000,
                'interest_rate' => 6.0,
                'duration_days' => 30,
                'return_type' => 'daily',
                'is_active' => true,
            ],
            [
                'name' => 'Summit Peak',
                'category' => 'Investment Tier',
                'description' => 'Reach the top of your financial goals with ₦60,000 daily returns.',
                'amount' => 1000000,
                'interest_rate' => 6.0,
                'duration_days' => 30,
                'return_type' => 'daily',
                'is_active' => true,
            ],
            [
                'name' => 'Apex Legacy',
                'category' => 'Investment Tier',
                'description' => 'Secure your future legacy with ₦120,000 daily returns.',
                'amount' => 2000000,
                'interest_rate' => 6.0,
                'duration_days' => 30,
                'return_type' => 'daily',
                'is_active' => true,
            ],
            [
                'name' => 'Grand Zenith',
                'category' => 'Investment Tier',
                'description' => 'The ultimate investment plan with ₦300,000 daily returns.',
                'amount' => 5000000,
                'interest_rate' => 6.0,
                'duration_days' => 30,
                'return_type' => 'daily',
                'is_active' => true,
            ],
        ];

        foreach ($plans as $plan) {
            \App\Models\InvestmentPlan::create($plan);
        }
    }
}
