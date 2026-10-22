<?php

namespace App\Services;

use App\Models\TradingSignal;
use Illuminate\Support\Carbon;

/**
 * TradingSignalGenerator
 *
 * Ensures the CRM dashboard always has fresh, time-varying trading signals:
 *  - Generates short-lived "live" on-demand signals when no recent active
 *    signal exists for an asset type (works even without the cron scheduler).
 *  - Provides a deterministic "live price" that drifts smoothly with time so
 *    every API fetch returns a slightly different current price.
 */
class TradingSignalGenerator
{
    /**
     * Technical analysis engine used to derive direction/confidence.
     */
    public function __construct(protected TechnicalAnalysisService $technicalAnalysis)
    {
    }

    /**
     * Symbols to rotate per asset type for on-demand live signals.
     *
     * @var array
     */
    protected array $symbolsByType = [
        'commodity' => ['XAUUSD', 'XAGUSD', 'USOIL', 'NATGAS', 'USOUSD', 'GOLDSILVER', 'PALLADIUM', 'PLATINUM'],
        'crypto'    => ['BTCUSD', 'ETHUSD', 'XRPUSD', 'SOLUSD', 'SHIBUSD', 'ANTROPIC', 'TRUMP', 'ONDO', 'PEPE', 'SEI', 'RAYDIUM', 'FET', 'BCHUSD', 'DOGWF', 'WORLDCOIN', 'TRONUSD'],
        'index'     => ['US30', 'SPX500', 'NAS100', 'GER40', 'US2000'],
        'forex'     => ['EURUSD', 'GBPUSD', 'USDJPY', 'USDCHF', 'USDCAD', 'USDNOK', 'USDPLN', 'USDSEK', 'USTRY', 'USDZAR', 'AUDJPY', 'CADCHF', 'CHFJPY', 'CHFNOK'],
        'stock'     => ['NIKE', 'CITI', 'WDC', 'AIG', 'BABA', 'AMZN', 'KO', 'GS', 'JPM', 'MCD', 'MS', 'MSFT', 'SNAP', 'AIRLINES', 'CANNABIS', 'CASINO', 'MAG7', 'URANIUM'],
    ];

    /**
     * Base reference prices used to derive live/on-demand prices.
     *
     * @var array<string, float>
     */
    protected array $basePrices = [
        'XAUUSD' => 4066.50,
        'XAGUSD' => 59.82,
        'USOIL'  => 78.00,
        'NATGAS' => 3.12,
        'USOUSD' => 80.97,
        'GOLDSILVER' => 69.51,
        'PALLADIUM' => 1329.16,
        'PLATINUM' => 1799.71,
        'BTCUSD' => 64207.78,
        'ETHUSD' => 1766.01,
        'XRPUSD' => 1.05,
        'SOLUSD' => 71.19,
        'SHIBUSD' => 0.000025,
        'ANTROPIC' => 14.50,
        'TRUMP'  => 16.80,
        'ONDO'   => 0.75,
        'PEPE'   => 0.000011,
        'SEI'    => 0.42,
        'RAYDIUM' => 2.85,
        'FET'    => 1.32,
        'BCHUSD' => 398.50,
        'DOGWF'  => 0.21,
        'WORLDCOIN' => 0.28,
        'TRONUSD' => 0.48,
        'US30'   => 39000.00,
        'SPX500' => 5300.00,
        'NAS100' => 18500.00,
        'GER40'  => 18000.00,
        'US2000' => 2785.90,
        'EURUSD' => 1.0850,
        'GBPUSD' => 1.2650,
        'USDJPY' => 150.50,
        'USDCHF' => 0.8850,
        'USDCAD' => 1.3752,
        'USDNOK' => 9.5563,
        'USDPLN' => 3.7968,
        'USDSEK' => 9.7881,
        'USTRY'  => 47.4590,
        'USDZAR' => 16.4315,
        'AUDJPY' => 108.75,
        'CADCHF' => 0.5648,
        'CHFJPY' => 195.01,
        'CHFNOK' => 11.68,
        'NIKE'   => 35.99,
        'CITI'   => 129.91,
        'WDC'    => 544.14,
        'AIG'    => 75.70,
        'BABA'   => 210.59,
        'AMZN'   => 210.59,
        'KO'     => 92.15,
        'GS'     => 1018.16,
        'JPM'    => 329.48,
        'MCD'    => 246.97,
        'MS'     => 214.96,
        'MSFT'   => 452.28,
        'SNAP'   => 2.53,
        'AIRLINES' => 4026.67,
        'CANNABIS' => 2188.72,
        'CASINO' => 2537.27,
        'MAG7'   => 2492.18,
        'URANIUM' => 4991.20,
    ];

    /**
     * On-demand live signals stay valid for this many minutes.
     */
    protected int $onDemandValidityMinutes = 10;

    /**
     * Return the latest active signal for a type, generating a fresh
     * short-lived on-demand signal when none exists or one is about to expire.
     */
    public function ensureFreshFeaturedSignal(string $type): ?TradingSignal
    {
        $signal = TradingSignal::active()
            ->ofType($type)
            ->latest()
            ->first();

        // Reuse the signal if it is still valid for at least 3 more minutes
        if ($signal) {
            $stillValid = $signal->expiry_time === null
                || $signal->expiry_time->gt(now()->addMinutes(3));

            if ($stillValid) {
                return $signal;
            }
        }

        return $this->generateOnDemandSignal($type);
    }

    /**
     * Create a simulated "live" signal for an asset type.
     * Symbols rotate every minute, direction/confidence vary with time,
     * and the entry price carries a small deterministic drift.
     */
    public function generateOnDemandSignal(string $type): TradingSignal
    {
        $now = now();
        $minuteBucket = intdiv($now->getTimestamp(), 60);

        $symbols = $this->symbolsByType[$type] ?? ['EURUSD'];
        $symbol = $symbols[$minuteBucket % count($symbols)];

        $basePrice = $this->basePrices[$symbol] ?? 100.0;

        // Build a deterministic OHLC series and run it through the
        // technical-analysis engine to derive direction & confidence.
        $ohlc = $this->technicalAnalysis->synthesizeOhlc($basePrice, $symbol, $minuteBucket);
        $indicators = $this->technicalAnalysis->analyze($ohlc['closes'], $ohlc['highs'], $ohlc['lows']);

        $direction = $this->technicalAnalysis->decideDirection($indicators);
        $confidence = $this->technicalAnalysis->computeConfidence($indicators, $direction);
        $action = $direction === 'CALL' ? 'BUY' : 'SELL';

        $entryPrice = $indicators['current_price'] ?? $basePrice;

        $spread = $entryPrice * 0.001;
        $atr = $indicators['atr_14'] ?? ($entryPrice * 0.002);

        $entryMin = round($entryPrice - $spread, 5);
        $entryMax = round($entryPrice + $spread, 5);

        $dir = $direction === 'CALL' ? 1 : -1;
        $stopLoss = round($entryPrice - $dir * $atr * 1.5, 5);
        $tp1 = round($entryPrice + $dir * $atr * 1.5, 5);
        $tp2 = round($entryPrice + $dir * $atr * 2.5, 5);
        $tp3 = round($entryPrice + $dir * $atr * 4.0, 5);

        $expiry = $now->copy()->addMinutes($this->onDemandValidityMinutes);

        $analysis = sprintf(
            'Live signal: %s — Direction %s. Entry around %s. RSI %.1f, Stoch %s, MACD %s. Confidence %.1f%%.',
            $symbol,
            $direction,
            number_format($entryPrice, 5),
            $indicators['rsi_14'] ?? 0,
            ($indicators['stoch_k'] ?? 0) > ($indicators['stoch_d'] ?? 0) ? 'bullish' : 'bearish',
            ($indicators['macd_histogram'] ?? 0) > 0 ? 'positive' : 'negative',
            $confidence
        );

        $riskNotes = sprintf(
            ' Expires in %d minutes. Trade responsibly with 1-2%% risk per trade.',
            $this->onDemandValidityMinutes
        );

        return TradingSignal::create([
            'symbol' => $symbol,
            'type' => $type,
            'action' => $action,
            'direction' => $direction,
            'entry_price_min' => $entryMin,
            'entry_price_max' => $entryMax,
            'stop_loss' => $stopLoss,
            'take_profit_1' => $tp1,
            'take_profit_2' => $tp2,
            'take_profit_3' => $tp3,
            'status' => 'active',
            'signal_time' => $now,
            'expiry_time' => $expiry,
            'confidence_level' => $confidence >= 90 ? 'very_high' : ($confidence >= 80 ? 'high' : 'medium'),
            'confidence_percent' => $confidence,
            'signal_type' => 'live',
            'is_otc' => false,
            'entry_window_minutes' => $this->onDemandValidityMinutes,
            'analysis_summary' => $analysis,
            'technical_indicators' => json_encode(array_merge([
                'timeframe' => 'live',
            ], $indicators)),
            'risk_notes' => $riskNotes,
        ]);
    }

    /**
     * Compute a deterministic "live" price that drifts smoothly with time.
     *
     * Uses superimposed sine waves seeded by the symbol so the price is
     * always moving but remains deterministic for a given instant — every
     * API fetch returns a slightly different current price.
     */
    public function livePrice(float $basePrice, string $symbol, $signalTime = null): float
    {
        if ($basePrice <= 0) {
            return 0;
        }

        $start = $signalTime ? Carbon::parse($signalTime) : now();
        $elapsed = max(0, (float) $start->diffInSeconds(now()));

        // Deterministic amplitude based on the symbol (0.05% – 0.29%)
        $amp = (crc32($symbol) % 25 + 5) / 100;

        // Smooth oscillation so the price visibly changes over time
        $wave = sin($elapsed / 23) * 0.7
            + sin($elapsed / 51 + 1.7) * 0.3
            + sin($elapsed / 190) * 0.25;

        $changePercent = $amp * $wave;

        return round($basePrice * (1 + $changePercent / 100), 5);
    }

    /**
     * Deterministic pseudo-random number in [min, max] for a given seed + bucket.
     */
    protected function deterministicRange(string $seedStr, int $bucket, float $min, float $max): float
    {
        $seed = crc32($seedStr . ':' . $bucket);
        mt_srand($seed);
        $t = mt_rand(0, 10000) / 10000;

        return $min + $t * ($max - $min);
    }
}

