<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\TradingSignal;

$tradeTime = now()->addHours(3)->startOfHour();

TradingSignal::create([
    'symbol' => 'BTCUSD',
    'type' => 'crypto',
    'action' => 'SELL',
    'direction' => 'PUT',
    'entry_price_min' => 67200.00,
    'entry_price_max' => 67350.00,
    'stop_loss' => 67800.00,
    'take_profit_1' => 66800.00,
    'take_profit_2' => 66400.00,
    'confidence_level' => 'high',
    'confidence_percent' => 82.3,
    'status' => 'active',
    'signal_time' => $tradeTime,
    'expiry_time' => $tradeTime->copy()->addMinutes(6),
    'signal_type' => 'ai_martingale',
    'entry_window_minutes' => 2,
    'is_otc' => false,
    'martingale_plan' => [
        'L0' => ['start_time_wat' => $tradeTime->copy()->timezone('Africa/Lagos')->format('H:i'), 'end_time_wat' => $tradeTime->copy()->addMinutes(2)->timezone('Africa/Lagos')->format('H:i'), 'multiplier' => 1],
        'L1' => ['start_time_wat' => $tradeTime->copy()->addMinutes(2)->timezone('Africa/Lagos')->format('H:i'), 'end_time_wat' => $tradeTime->copy()->addMinutes(4)->timezone('Africa/Lagos')->format('H:i'), 'multiplier' => 2],
        'L2' => ['start_time_wat' => $tradeTime->copy()->addMinutes(4)->timezone('Africa/Lagos')->format('H:i'), 'end_time_wat' => $tradeTime->copy()->addMinutes(6)->timezone('Africa/Lagos')->format('H:i'), 'multiplier' => 4],
    ],
    'analysis_summary' => 'Bitcoin showing bearish divergence on RSI. Key support level broken. Expect continuation of downtrend with increasing selling pressure.',
    'risk_notes' => 'Martingale strategy with 3 levels. Crypto volatility is high - manage risk carefully.',
]);

echo "Created signal for BTCUSD (Bitcoin)\n";
echo "Total active signals: " . TradingSignal::active()->count() . "\n";
