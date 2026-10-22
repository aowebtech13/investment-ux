<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\TradingSignal;
use App\Services\TelegramService;
use App\Services\TechnicalAnalysisService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FetchTradingSignals extends Command
{
    protected $signature = 'signals:fetch {--force : Force refresh signals even if active ones exist} {--ai : Generate AI signals (1m, 2m, 3m, 5m timeframes)} {--no-telegram : Do not send generated signals to Telegram} {--timeframe= : Only generate AI signals of this timeframe (1m, 2m, 3m, 5m)} {--limit= : Maximum number of AI signals to generate}';
    protected $description = 'Fetch market data from free APIs and generate trading signals with technical analysis';

    public function __construct(protected TechnicalAnalysisService $technicalAnalysis)
    {
        parent::__construct();
    }

    protected array $symbolMap = [
        'EURUSD'  => ['type' => 'forex', 'from' => 'EUR', 'to' => 'USD'],
        'GBPUSD'  => ['type' => 'forex', 'from' => 'GBP', 'to' => 'USD'],
        'USDJPY'  => ['type' => 'forex', 'from' => 'USD', 'to' => 'JPY'],
        'USDCHF'  => ['type' => 'forex', 'from' => 'USD', 'to' => 'CHF'],
        'AUDUSD'  => ['type' => 'forex', 'from' => 'AUD', 'to' => 'USD'],
        'USDCAD'  => ['type' => 'forex', 'from' => 'USD', 'to' => 'CAD'],
        'NZDUSD'  => ['type' => 'forex', 'from' => 'NZD', 'to' => 'USD'],
        'GBPJPY'  => ['type' => 'forex', 'from' => 'GBP', 'to' => 'JPY'],
        'EURJPY'  => ['type' => 'forex', 'from' => 'EUR', 'to' => 'JPY'],
        'EURGBP'  => ['type' => 'forex', 'from' => 'EUR', 'to' => 'GBP'],
        'XAUUSD'  => ['type' => 'commodity', 'from' => 'XAU', 'to' => 'USD'],
        'XAGUSD'  => ['type' => 'commodity', 'from' => 'XAG', 'to' => 'USD'],
        'USOIL'   => ['type' => 'commodity', 'from' => 'USOIL', 'to' => 'USD'],
        'NATGAS'  => ['type' => 'commodity', 'from' => 'NATGAS', 'to' => 'USD'],
        'SPX500'  => ['type' => 'index', 'from' => 'SPX', 'to' => 'USD'],
        'NAS100'  => ['type' => 'index', 'from' => 'IXIC', 'to' => 'USD'],
        'US30'    => ['type' => 'index', 'from' => 'DJI', 'to' => 'USD'],
        'UK100'   => ['type' => 'index', 'from' => 'UK100', 'to' => 'GBP'],
        'GER40'   => ['type' => 'index', 'from' => 'GDAXI', 'to' => 'EUR'],
        'JPN225'  => ['type' => 'index', 'from' => 'N225', 'to' => 'JPY'],
        'BTCUSD'  => ['type' => 'crypto', 'from' => 'BTC', 'to' => 'USD'],
        'ETHUSD'  => ['type' => 'crypto', 'from' => 'ETH', 'to' => 'USD'],
        'XRPUSD'  => ['type' => 'crypto', 'from' => 'XRP', 'to' => 'USD'],
        'SOLUSD'  => ['type' => 'crypto', 'from' => 'SOL', 'to' => 'USD'],
        'ADAUSD'  => ['type' => 'crypto', 'from' => 'ADA', 'to' => 'USD'],
        'LTCUSD'  => ['type' => 'crypto', 'from' => 'LTC', 'to' => 'USD'],
        'SHIBUSD' => ['type' => 'crypto', 'from' => 'SHIB', 'to' => 'USD'],
    ];

    protected array $otcSymbolMap = [
        'GBPJPY_OTC' => ['type' => 'forex', 'from' => 'GBP', 'to' => 'JPY', 'base_price' => 190.20],
        'EURUSD_OTC' => ['type' => 'forex', 'from' => 'EUR', 'to' => 'USD', 'base_price' => 1.0850],
        'USDJPY_OTC' => ['type' => 'forex', 'from' => 'USD', 'to' => 'JPY', 'base_price' => 150.50],
        'XAUUSD_OTC' => ['type' => 'commodity', 'from' => 'XAU', 'to' => 'USD', 'base_price' => 4066.50],
        'BTCUSD_OTC' => ['type' => 'crypto', 'from' => 'BTC', 'to' => 'USD', 'base_price' => 64207.78],
        'ETHUSD_OTC' => ['type' => 'crypto', 'from' => 'ETH', 'to' => 'USD', 'base_price' => 1766.01],
        'USOIL_OTC'  => ['type' => 'commodity', 'from' => 'USOIL', 'to' => 'USD', 'base_price' => 78.00],
        'NAS100_OTC' => ['type' => 'index', 'from' => 'IXIC', 'to' => 'USD', 'base_price' => 18500.00],
        'SHIBUSD_OTC' => ['type' => 'crypto', 'from' => 'SHIB', 'to' => 'USD', 'base_price' => 0.000025],
        'ANTROPIC_OTC' => ['type' => 'crypto', 'from' => 'ANTROPIC', 'to' => 'USD', 'base_price' => 14.50],
        'TRUMP_OTC' => ['type' => 'crypto', 'from' => 'TRUMP', 'to' => 'USD', 'base_price' => 16.80],
        'ONDO_OTC' => ['type' => 'crypto', 'from' => 'ONDO', 'to' => 'USD', 'base_price' => 0.75],
        'PEPE_OTC' => ['type' => 'crypto', 'from' => 'PEPE', 'to' => 'USD', 'base_price' => 0.000011],
        'SEI_OTC' => ['type' => 'crypto', 'from' => 'SEI', 'to' => 'USD', 'base_price' => 0.42],
        'USDCAD_OTC' => ['type' => 'forex', 'from' => 'USD', 'to' => 'CAD', 'base_price' => 1.375175],
        'USDNOK_OTC' => ['type' => 'forex', 'from' => 'USD', 'to' => 'NOK', 'base_price' => 9.556315],
        'USDPLN_OTC' => ['type' => 'forex', 'from' => 'USD', 'to' => 'PLN', 'base_price' => 3.796795],
        'USDSEK_OTC' => ['type' => 'forex', 'from' => 'USD', 'to' => 'SEK', 'base_price' => 9.788085],
        'USTRY_OTC' => ['type' => 'forex', 'from' => 'USD', 'to' => 'TRY', 'base_price' => 47.45896],
        'USDZAR_OTC' => ['type' => 'forex', 'from' => 'USD', 'to' => 'ZAR', 'base_price' => 16.43152],
        'AUDJPY_OTC' => ['type' => 'forex', 'from' => 'AUD', 'to' => 'JPY', 'base_price' => 108.7455],
        'CADCHF_OTC' => ['type' => 'forex', 'from' => 'CAD', 'to' => 'CHF', 'base_price' => 0.564845],
        'CHFJPY_OTC' => ['type' => 'forex', 'from' => 'CHF', 'to' => 'JPY', 'base_price' => 195.0075],
        'CHFNOK_OTC' => ['type' => 'forex', 'from' => 'CHF', 'to' => 'NOK', 'base_price' => 11.67690],
        'USOUSD_OTC' => ['type' => 'commodity', 'from' => 'USO', 'to' => 'USD', 'base_price' => 80.97488],
        'XAGUSD_OTC' => ['type' => 'commodity', 'from' => 'XAG', 'to' => 'USD', 'base_price' => 59.81981],
        'GOLDSILVER_OTC' => ['type' => 'commodity', 'from' => 'GOLDSILVER', 'to' => 'USD', 'base_price' => 69.50922],
        'NATGAS_OTC' => ['type' => 'commodity', 'from' => 'NATGAS', 'to' => 'USD', 'base_price' => 3.123250],
        'PALLADIUM_OTC' => ['type' => 'commodity', 'from' => 'PALLADIUM', 'to' => 'USD', 'base_price' => 1329.157],
        'PLATINUM_OTC' => ['type' => 'commodity', 'from' => 'PLATINUM', 'to' => 'USD', 'base_price' => 1799.708],
        'RAYDIUM_OTC' => ['type' => 'crypto', 'from' => 'RAY', 'to' => 'USD', 'base_price' => 2.85],
        'FET_OTC' => ['type' => 'crypto', 'from' => 'FET', 'to' => 'USD', 'base_price' => 1.32],
        'BCHUSD_OTC' => ['type' => 'crypto', 'from' => 'BCH', 'to' => 'USD', 'base_price' => 398.50],
        'DOGWF_OTC' => ['type' => 'crypto', 'from' => 'DOGWF', 'to' => 'USD', 'base_price' => 0.210955],
        'WORLDCOIN_OTC' => ['type' => 'crypto', 'from' => 'WORLDCOIN', 'to' => 'USD', 'base_price' => 0.277015],
        'SOLUSD_OTC' => ['type' => 'crypto', 'from' => 'SOL', 'to' => 'USD', 'base_price' => 71.19317],
        'TRONUSD_OTC' => ['type' => 'crypto', 'from' => 'TRX', 'to' => 'USD', 'base_price' => 0.481975],
        'US2000_OTC' => ['type' => 'index', 'from' => 'US2000', 'to' => 'USD', 'base_price' => 2785.895],
        'NIKE_OTC' => ['type' => 'stock', 'from' => 'NKE', 'to' => 'USD', 'base_price' => 35.99150],
        'CITI_OTC' => ['type' => 'stock', 'from' => 'C', 'to' => 'USD', 'base_price' => 129.9095],
        'WDC_OTC' => ['type' => 'stock', 'from' => 'WDC', 'to' => 'USD', 'base_price' => 544.1368],
        'AIG_OTC' => ['type' => 'stock', 'from' => 'AIG', 'to' => 'USD', 'base_price' => 75.69950],
        'BABA_OTC' => ['type' => 'stock', 'from' => 'BABA', 'to' => 'USD', 'base_price' => 210.5875],
        'AMZN_OTC' => ['type' => 'stock', 'from' => 'AMZN', 'to' => 'USD', 'base_price' => 210.5875],
        'KO_OTC' => ['type' => 'stock', 'from' => 'KO', 'to' => 'USD', 'base_price' => 92.14750],
        'GS_OTC' => ['type' => 'stock', 'from' => 'GS', 'to' => 'USD', 'base_price' => 1018.164],
        'JPM_OTC' => ['type' => 'stock', 'from' => 'JPM', 'to' => 'USD', 'base_price' => 329.4765],
        'MCD_OTC' => ['type' => 'stock', 'from' => 'MCD', 'to' => 'USD', 'base_price' => 246.9650],
        'MS_OTC' => ['type' => 'stock', 'from' => 'MS', 'to' => 'USD', 'base_price' => 214.9625],
        'MSFT_OTC' => ['type' => 'stock', 'from' => 'MSFT', 'to' => 'USD', 'base_price' => 452.2845],
        'SNAP_OTC' => ['type' => 'stock', 'from' => 'SNAP', 'to' => 'USD', 'base_price' => 2.526500],
        'AIRLINES_OTC' => ['type' => 'stock', 'from' => 'AIRLINES', 'to' => 'USD', 'base_price' => 4026.674],
        'CANNABIS_OTC' => ['type' => 'stock', 'from' => 'CANNABIS', 'to' => 'USD', 'base_price' => 2188.717],
        'CASINO_OTC' => ['type' => 'stock', 'from' => 'CASINO', 'to' => 'USD', 'base_price' => 2537.269],
        'MAG7_OTC' => ['type' => 'stock', 'from' => 'MAG7', 'to' => 'USD', 'base_price' => 2492.177],
        'URANIUM_OTC' => ['type' => 'stock', 'from' => 'URANIUM', 'to' => 'USD', 'base_price' => 4991.204],
    ];

    protected array $aiTimeframes = [
        '2m' => ['duration_minutes' => 2, 'signal_type' => 'ai_2m'],
        '3m' => ['duration_minutes' => 3, 'signal_type' => 'ai_3m'],
        '5m' => ['duration_minutes' => 5, 'signal_type' => 'ai_5m'],
    ];

    protected int $signalDurationHours = 4;

    public function handle(TelegramService $telegram)
    {
        $this->info('Starting trading signal fetch...');
        $expired = TradingSignal::autoExpire();
        $this->info("Expired {$expired} old signals.");

        if ($this->option('ai')) {
            $createdIds = $this->generateAiSignals();
            $this->dispatchAiSignals($telegram, $createdIds);
            return;
        }

        $apiKey = config('services.alphavantage.key');
        if (!$apiKey) {
            $this->warn('No Alpha Vantage API key configured. Using fallback with simulated data.');
            $this->generateFallbackSignals();
            $this->dispatchToTelegram($telegram);
            return;
        }

        $newSignals = 0;
        $errors = 0;
        $force = $this->option('force');

        foreach ($this->symbolMap as $symbol => $meta) {
            try {
                if (!$force) {
                    $existing = TradingSignal::where('symbol', $symbol)
                        ->where('status', 'active')
                        ->where('expiry_time', '>', now())
                        ->first();
                    if ($existing) {
                        continue;
                    }
                }

                $marketData = $this->fetchMarketData($symbol, $meta, $apiKey);
                if (!$marketData) {
                    $errors++;
                    continue;
                }

                $signal = $this->analyzeAndGenerateSignal($symbol, $meta, $marketData);
                if ($signal) {
                    if ($force) {
                        TradingSignal::where('symbol', $symbol)
                            ->where('status', 'active')
                            ->update(['status' => 'expired']);
                    }
                    TradingSignal::create($signal);
                    $newSignals++;
                    $this->line("Created signal for {$symbol}: {$signal['action']} @ ~{$signal['entry_price_min']}");
                }

                sleep(12);
            } catch (\Exception $e) {
                Log::error("Signal fetch error for {$symbol}: " . $e->getMessage());
                $errors++;
            }
        }

        $this->info("Done. Created {$newSignals} new signals. Errors: {$errors}.");
        $this->dispatchToTelegram($telegram);
    }

    protected function dispatchToTelegram(TelegramService $telegram): void
    {
        if ($this->option('no-telegram')) {
            $this->line('Skipping Telegram dispatch (--no-telegram set).');
            return;
        }

        if (!$telegram->isReady()) {
            $this->warn('Telegram not configured or disabled — skipping Telegram dispatch.');
            return;
        }

        $unsent = TradingSignal::notSentToTelegram()
            ->active()
            ->latest()
            ->limit(50)
            ->get();

        if ($unsent->isEmpty()) {
            $this->info('No unsent signals to dispatch to Telegram.');
            return;
        }

        $this->line("Dispatching {$unsent->count()} unsent signal(s) to Telegram...");
        $result = $telegram->sendBatch($unsent);
        $this->line("Telegram dispatch — Sent: {$result['sent']}, Failed: {$result['failed']}, Total: {$result['total']}.");
    }

    protected function generateAiSignals(): array
    {
        $this->info('Generating AI trading signals...');

        $generated = 0;
        $createdIds = [];
        $force = $this->option('force');
        $filterTimeframe = $this->option('timeframe');
        $limit = $this->option('limit') ? (int) $this->option('limit') : null;

        $allSymbols = array_merge($this->symbolMap, $this->otcSymbolMap);
        $symbolKeys = array_keys($allSymbols);

        $timeframes = array_keys($this->aiTimeframes);
        $activeTimeframes = array_values(array_filter(
            $timeframes,
            fn ($tf) => !($filterTimeframe && $tf !== $filterTimeframe)
        ));

        if (empty($activeTimeframes) || empty($symbolKeys)) {
            $this->info('AI Signal generation complete. Generated: 0.');
            return $createdIds;
        }

        $symbolIndex = intdiv(now()->getTimestamp(), 60) % count($symbolKeys);
        $tfIndex = 0;

        while ($limit === null || $generated < $limit) {
            $timeframe = $activeTimeframes[$tfIndex % count($activeTimeframes)];
            $tfIndex++;

            $symbol = $symbolKeys[$symbolIndex % count($symbolKeys)];
            $symbolIndex++;

            $config = $this->aiTimeframes[$timeframe];
            $signalType = $config['signal_type'];
            $durationMinutes = $config['duration_minutes'];
            $meta = $allSymbols[$symbol];

            try {
                if (!$force) {
                    $existing = TradingSignal::where('symbol', $symbol)
                        ->where('signal_type', $signalType)
                        ->where('status', 'active')
                        ->where('expiry_time', '>', now())
                        ->first();
                    if ($existing) {
                        continue;
                    }
                }

                $signal = $this->generateAiSignal($symbol, $meta, $signalType, $durationMinutes, $timeframe);
                if ($signal) {
                    if ($force) {
                        TradingSignal::where('symbol', $symbol)
                            ->where('signal_type', $signalType)
                            ->where('status', 'active')
                            ->update(['status' => 'expired']);
                    }
                    $created = TradingSignal::create($signal);
                    $createdIds[] = $created->id;
                    $generated++;
                    $this->line("AI Signal ({$timeframe}) for {$symbol}: {$signal['direction']} | Confidence: {$signal['confidence_percent']}% | OTC: " . ($signal['is_otc'] ? 'Yes' : 'No'));
                }
            } catch (\Exception $e) {
                Log::error("AI signal generation error for {$symbol} ({$timeframe}): " . $e->getMessage());
            }
        }

        $this->info("AI Signal generation complete. Generated: {$generated}.");
        return $createdIds;
    }

    protected function dispatchAiSignals(TelegramService $telegram, array $createdIds): void
    {
        if ($this->option('no-telegram')) {
            $this->line('Skipping Telegram dispatch (--no-telegram set).');
            return;
        }

        if (!$telegram->isReady()) {
            $this->warn('Telegram not configured or disabled — skipping Telegram dispatch.');
            return;
        }

        if (empty($createdIds)) {
            $this->info('No new AI signals to dispatch to Telegram.');
            return;
        }

        $signals = TradingSignal::whereIn('id', $createdIds)
            ->active()
            ->get();

        if ($signals->isEmpty()) {
            $this->info('No active AI signals to dispatch to Telegram.');
            return;
        }

        $this->line("Dispatching {$signals->count()} AI signal(s) to Telegram...");
        $result = $telegram->sendBatch($signals);
        $this->line("Telegram dispatch — Sent: {$result['sent']}, Failed: {$result['failed']}, Total: {$result['total']}.");
    }

    protected function nextTradeTime(): \Illuminate\Support\Carbon
    {
        $now = now();
        $tradeTime = $now
            ->copy()
            ->timezone('Africa/Lagos')
            ->startOfHour()
            ->addHour()
            ->setTimezone('UTC');

        while (!$tradeTime->gt($now)) {
            $tradeTime->addHour();
        }

        return $tradeTime;
    }

    protected function generateAiSignal(string $symbol, array $meta, string $signalType, int $durationMinutes, string $timeframe): ?array
    {
        $now = now();
        $tradeTime = $this->nextTradeTime();

        if (!$tradeTime->gt($now)) {
            throw new \RuntimeException(
                'Scheduled trade time (' . $tradeTime->toDateTimeString()
                . ') is not strictly after now (' . $now->toDateTimeString() . ').'
            );
        }

        $expiry = $tradeTime->copy()->addMinutes($durationMinutes);
        $basePrice = $meta['base_price'] ?? $this->getBasePrice($symbol);

        $bars = $this->generateOhlcSeries($symbol, $basePrice, $timeframe, 60);
        $closes = array_column($bars, 'close');
        $lastClose = (float) end($closes);

        $ind = $this->computeIndicators($bars, $closes);
        $confluence = $this->scoreConfluence($bars, $closes, $ind);
        $direction = $confluence['direction'];
        $confidence = $confluence['confidence'];

        $action = $direction === 'CALL' ? 'BUY' : 'SELL';

        $atr = $ind['atr'] > 0 ? $ind['atr'] : $basePrice * 0.002;
        $volatilityMultiplier = $this->getTimeframeVolatilityMultiplier($timeframe);

        $spread = $atr * 0.15;
        $entryMin = round($lastClose - $spread, 5);
        $entryMax = round($lastClose + $spread, 5);

        $dir = $direction === 'CALL' ? 1 : -1;
        $stopLoss = round($lastClose - $dir * $atr * $volatilityMultiplier, 5);
        $tp1 = round($lastClose + $dir * $atr * $volatilityMultiplier, 5);
        $tp2 = round($lastClose + $dir * $atr * 2.0 * $volatilityMultiplier, 5);
        $tp3 = round($lastClose + $dir * $atr * 3.0 * $volatilityMultiplier, 5);

        $isOtc = isset($meta['base_price']) || str_contains($symbol, '_OTC');
        $martingalePlan = $this->generateMartingalePlan($timeframe, $durationMinutes, $tradeTime);
        $analysis = $this->generateAiAnalysis($symbol, $direction, $lastClose, $confidence, $ind);

        return [
            'symbol' => $isOtc ? str_replace('_OTC', '', $symbol) : $symbol,
            'type' => $meta['type'],
            'action' => $action,
            'direction' => $direction,
            'entry_price_min' => $entryMin,
            'entry_price_max' => $entryMax,
            'stop_loss' => $stopLoss,
            'take_profit_1' => $tp1,
            'take_profit_2' => $tp2,
            'take_profit_3' => $tp3,
            'status' => 'active',
            'signal_time' => $tradeTime,
            'expiry_time' => $expiry,
            'confidence_level' => $this->confidenceToLevel($confidence),
            'confidence_percent' => $confidence,
            'signal_type' => $signalType,
            'is_otc' => $isOtc,
            'entry_window_minutes' => $durationMinutes,
            'analysis_summary' => $analysis,
            'technical_indicators' => json_encode([
                'rsi_14' => $ind['rsi'],
                'momentum_5' => $ind['momentum5'],
                'momentum_10' => $ind['momentum10'],
                'sma_10' => $ind['sma10'],
                'sma_20' => $ind['sma20'],
                'sma_50' => $ind['sma50'],
                'ema_12' => $ind['ema12'],
                'ema_26' => $ind['ema26'],
                'macd' => $ind['macd']['line'],
                'macd_signal' => $ind['macd']['signal'],
                'macd_histogram' => $ind['macd']['histogram'],
                'bollinger_upper' => $ind['bollinger']['upper'],
                'bollinger_middle' => $ind['bollinger']['middle'],
                'bollinger_lower' => $ind['bollinger']['lower'],
                'stochastic_k' => $ind['stochastic']['k'],
                'stochastic_d' => $ind['stochastic']['d'],
                'adx' => $ind['adx'],
                'atr_14' => $ind['atr'],
                'current_price' => $lastClose,
                'timeframe' => $timeframe,
            ]),
            'martingale_plan' => $martingalePlan,
            'risk_notes' => "Valid for {$durationMinutes} minute(s). Stop-loss at "
                . number_format($stopLoss, 5) . " based on ATR(" . number_format($atr, 5) . ").",
        ];
    }

    protected function generateOhlcSeries(string $symbol, float $basePrice, string $timeframe, int $bars = 60): array
    {
        $intervalMinutes = match($timeframe) {
            '2m' => 2,
            '3m' => 3,
            '5m' => 5,
            default => 1,
        };

        $timeBucket = intdiv(now()->getTimestamp(), 60 * $intervalMinutes);
        $vol = $this->getTimeframeVolatilityMultiplier($timeframe);

        $getRandomFloat = function (string $key): float {
            return (abs(crc32($key)) % 100000) / 100000;
        };

        $baseSeed = "{$symbol}:{$timeframe}:{$timeBucket}";
        $drift = ($getRandomFloat("{$baseSeed}:drift") - 0.5) * 0.005;

        $series = [];
        $price = $basePrice;

        for ($i = 0; $i < $bars; $i++) {
            $stepVal = $getRandomFloat("{$baseSeed}:bar:{$i}:step");
            $highVal = $getRandomFloat("{$baseSeed}:bar:{$i}:high");
            $lowVal  = $getRandomFloat("{$baseSeed}:bar:{$i}:low");

            $step = ($stepVal - 0.5) * 2;
            $changePct = $step * 0.0015 * $vol + $drift;

            $open = $price;
            $close = $open * (1 + $changePct);
            $high = max($open, $close) * (1 + $highVal * 0.002);
            $low = min($open, $close) * (1 - $lowVal * 0.002);

            $series[] = [
                'open'  => round($open, 5),
                'high'  => round($high, 5),
                'low'   => round($low, 5),
                'close' => round($close, 5),
            ];

            $price = $close;
        }

        return $series;
    }

    protected function computeIndicators(array $bars, array $closes): array
    {
        return [
            'rsi' => $this->calculateRSI($closes, 14),
            'sma10' => $this->calcSma($closes, 10),
            'sma20' => $this->calcSma($closes, 20),
            'sma50' => $this->calcSma($closes, 50),
            'ema12' => $this->calcEma($closes, 12),
            'ema26' => $this->calcEma($closes, 26),
            'macd' => $this->calcMacd($closes),
            'bollinger' => $this->calcBollinger($closes, 20, 2.0),
            'stochastic' => $this->calcStochastic($bars, 14, 3),
            'adx' => $this->calcAdx($bars, 14),
            'atr' => $this->calculateATR($closes, 14),
            'momentum5' => $this->calculateMomentum($closes, 5),
            'momentum10' => $this->calculateMomentum($closes, 10),
        ];
    }

    protected function scoreConfluence(array $bars, array $closes, array $ind): array
    {
        $price = (float) end($closes);
        $score = 0;

        $score += $ind['ema12'] > $ind['ema26'] ? 1 : -1;
        $score += $price > $ind['sma20'] ? 1 : -1;
        if ($ind['sma50'] > 0) {
            $score += $price > $ind['sma50'] ? 1 : -1;
        }
        $score += $ind['macd']['histogram'] >= 0 ? 1 : -1;

        $rsi = $ind['rsi'];
        if ($rsi < 30) {
            $score += 2;
        } elseif ($rsi > 70) {
            $score -= 2;
        }

        if ($price <= $ind['bollinger']['lower']) {
            $score += 2;
        } elseif ($price >= $ind['bollinger']['upper']) {
            $score -= 2;
        }

        $k = $ind['stochastic']['k'];
        if ($k < 20) {
            $score += 1;
        } elseif ($k > 80) {
            $score -= 1;
        }

        if ($score > 0) {
            $direction = 'CALL';
        } elseif ($score < 0) {
            $direction = 'PUT';
        } else {
            $lastBar = end($bars);
            $direction = ($lastBar['close'] >= $lastBar['open']) ? 'PUT' : 'CALL';
        }

        $adx = $ind['adx'];
        $trendStrength = $adx >= 25 ? 1.0 : ($adx >= 20 ? 0.5 : 0.0);
        $confidence = 60 + abs($score) * 4 + $trendStrength * 8;
        $confidence = (float) round(max(62, min(98, $confidence)), 1);

        return ['direction' => $direction, 'confidence' => $confidence];
    }

    protected function calcSma(array $prices, int $period): float
    {
        $slice = array_slice($prices, -$period);
        $count = count($slice);
        if ($count === 0) {
            return 0.0;
        }
        return round(array_sum($slice) / $count, 5);
    }

    protected function calcEma(array $prices, int $period): float
    {
        if (empty($prices)) {
            return 0.0;
        }
        $k = 2 / ($period + 1);
        $ema = $prices[0];
        for ($i = 1; $i < count($prices); $i++) {
            $ema = ($prices[$i] * $k) + ($ema * (1 - $k));
        }
        return round($ema, 5);
    }

    protected function calcMacd(array $prices): array
    {
        $ema12 = $this->calcEma($prices, 12);
        $ema26 = $this->calcEma($prices, 26);
        $macdLine = $ema12 - $ema26;
        $signalLine = $macdLine * 0.8;
        $histogram = $macdLine - $signalLine;

        return [
            'line' => round($macdLine, 5),
            'signal' => round($signalLine, 5),
            'histogram' => round($histogram, 5),
        ];
    }

    protected function calcBollinger(array $prices, int $period = 20, float $multiplier = 2.0): array
    {
        $sma = $this->calcSma($prices, $period);
        $slice = array_slice($prices, -$period);
        $count = count($slice);

        if ($count === 0) {
            return ['upper' => 0.0, 'middle' => 0.0, 'lower' => 0.0];
        }

        $variance = 0.0;
        foreach ($slice as $p) {
            $variance += pow($p - $sma, 2);
        }
        $stdDev = sqrt($variance / $count);

        return [
            'upper'  => round($sma + ($multiplier * $stdDev), 5),
            'middle' => round($sma, 5),
            'lower'  => round($sma - ($multiplier * $stdDev), 5),
        ];
    }

    protected function calcStochastic(array $bars, int $period = 14, int $smoothK = 3): array
    {
        $slice = array_slice($bars, -$period);
        if (empty($slice)) {
            return ['k' => 50.0, 'd' => 50.0];
        }

        $lows = array_column($slice, 'low');
        $highs = array_column($slice, 'high');
        $lastClose = end($slice)['close'];

        $minLow = min($lows);
        $maxHigh = max($highs);

        $k = ($maxHigh == $minLow) ? 50.0 : (($lastClose - $minLow) / ($maxHigh - $minLow)) * 100;
        $d = $k * 0.9;

        return ['k' => round($k, 2), 'd' => round($d, 2)];
    }

    protected function calcAdx(array $bars, int $period = 14): float
    {
        $closes = array_column($bars, 'close');
        $atr = $this->calculateATR($closes, $period);
        $lastClose = end($closes) ?: 1.0;

        $volRatio = ($atr / $lastClose) * 1000;
        return round(min(60, max(15, $volRatio * 15)), 2);
    }

    protected function getTimeframeVolatilityMultiplier(string $timeframe): float
    {
        return match($timeframe) {
            '2m' => 1.2,
            '3m' => 1.5,
            '5m' => 2.0,
            default => 1.0,
        };
    }

    protected function generateMartingalePlan(string $timeframe, int $durationMinutes, \Illuminate\Support\Carbon $startTime): array
    {
        $plan = [];

        for ($i = 0; $i <= 2; $i++) {
            $levelStart = $startTime->copy()->addMinutes($i * $durationMinutes)->timezone('Africa/Lagos');
            $levelEnd = $startTime->copy()->addMinutes(($i + 1) * $durationMinutes)->timezone('Africa/Lagos');

            $plan["L{$i}"] = [
                'start_time_wat' => $levelStart->format('H:i'),
                'end_time_wat' => $levelEnd->format('H:i'),
                'multiplier' => pow(2, $i),
            ];
        }

        return $plan;
    }

    protected function generateAiAnalysis(string $symbol, string $direction, float $price, float $confidence, array $ind): string
    {
        $rsi = $ind['rsi'];
        $directionText = $direction === 'CALL' ? 'BUY' : 'SELL';
        $rsiStatus = $rsi > 70 ? 'overbought' : ($rsi < 30 ? 'oversold' : 'neutral');

        $momentum = $ind['momentum5'];
        $trend = $momentum > 0 ? 'bullish' : 'bearish';
        $macdState = $ind['macd']['histogram'] >= 0 ? 'positive' : 'negative';
        $trendLabel = $ind['sma10'] > $ind['sma20'] ? 'bullish' : 'bearish';

        $bbPos = 'middle';
        if ($price >= $ind['bollinger']['upper']) {
            $bbPos = 'upper';
        } elseif ($price <= $ind['bollinger']['lower']) {
            $bbPos = 'lower';
        }

        $adx = $ind['adx'];
        $adxLabel = $adx >= 25 ? 'strong trend' : ($adx >= 20 ? 'developing trend' : 'range-bound market');

        return "AI Signal: {$symbol} at \${$price}. Direction: {$direction} ({$directionText}). "
            . "RSI({$rsi}) is {$rsiStatus}. "
            . "Price momentum is {$trend} ({$momentum}%). "
            . "MACD histogram is {$macdState}. "
            . "Price sits at the {$bbPos} Bollinger band with {$trendLabel} structure. "
            . "ADX({$adx}) shows a {$adxLabel}. "
            . "AI Confidence: {$confidence}%.";
    }

    protected function confidenceToLevel(float $confidence): string
    {
        if ($confidence >= 85) return 'high';
        if ($confidence >= 70) return 'medium';
        return 'low';
    }

    protected function fetchMarketData(string $symbol, array $meta, string $apiKey): ?array
    {
        try {
            $params = ['apikey' => $apiKey, 'outputsize' => 'compact'];

            if ($meta['type'] === 'forex') {
                $params['function'] = 'FX_INTRADAY';
                $params['from_symbol'] = $meta['from'];
                $params['to_symbol'] = $meta['to'];
                $params['interval'] = '5min';
                $url = 'https://www.alphavantage.co/query';
            } elseif ($meta['type'] === 'crypto') {
                $params['function'] = 'CRYPTO_INTRADAY';
                $params['symbol'] = $meta['from'];
                $params['market'] = strtolower($meta['to']);
                $params['interval'] = '5min';
                $url = 'https://www.alphavantage.co/query';
            } else {
                $params['function'] = 'TIME_SERIES_INTRADAY';
                $params['symbol'] = $this->getTickerForSymbol($symbol);
                $params['interval'] = '5min';
                $url = 'https://www.alphavantage.co/query';
            }

            $response = Http::timeout(15)->get($url, $params);

            if ($response->successful()) {
                $data = $response->json();
                if (isset($data['Note']) && str_contains($data['Note'], 'API call frequency')) {
                    Log::warning("Alpha Vantage rate limit hit for {$symbol}");
                    return null;
                }
                if (isset($data['Error Message'])) {
                    Log::warning("Alpha Vantage error for {$symbol}: " . $data['Error Message']);
                    return null;
                }
                return $data;
            }
        } catch (\Exception $e) {
            Log::error("HTTP error fetching {$symbol}: " . $e->getMessage());
        }

        return null;
    }

    protected function getTickerForSymbol(string $symbol): string
    {
        $map = [
            'XAUUSD' => 'XAUUSD', 'XAGUSD' => 'XAGUSD',
            'USOIL' => 'USOIL', 'NATGAS' => 'NATGAS',
            'SPX500' => 'SPX', 'NAS100' => 'NDAQ',
            'US30' => 'DJI', 'UK100' => 'UK100',
            'GER40' => 'GDAXI', 'JPN225' => 'N225',
        ];
        return $map[$symbol] ?? $symbol;
    }

    protected function analyzeAndGenerateSignal(string $symbol, array $meta, array $marketData): ?array
    {
        $timeSeriesKey = $this->getTimeSeriesKey($marketData);
        if (!$timeSeriesKey || !isset($marketData[$timeSeriesKey])) {
            return null;
        }

        $timeSeries = $marketData[$timeSeriesKey];
        $prices = [];
        $timestamps = array_keys($timeSeries);
        sort($timestamps);

        $closeKey = $this->getCloseKey($meta['type']);
        foreach ($timestamps as $ts) {
            $price = $timeSeries[$ts][$closeKey] ?? null;
            if ($price) {
                $prices[] = (float) $price;
            }
        }

        if (count($prices) < 20) {
            return null;
        }

        $currentPrice = end($prices);
        $rsi = $this->calculateRSI($prices, 14);
        $momentum5 = $this->calculateMomentum($prices, 5);
        $momentum10 = $this->calculateMomentum($prices, 10);
        $sma10 = array_sum(array_slice($prices, -10)) / 10;
        $sma20 = array_sum(array_slice($prices, -20)) / min(20, count($prices));

        $indicators = $this->technicalAnalysis->analyze($prices, $prices, $prices);
        if (!empty($indicators)) {
            $rsi = $indicators['rsi_14'] ?? $rsi;
            $momentum5 = $indicators['momentum_5'] ?? $momentum5;
            $momentum10 = $indicators['momentum_10'] ?? $momentum10;
            $sma10 = $indicators['sma_10'] ?? $sma10;
            $sma20 = $indicators['sma_20'] ?? $sma20;
        }

        $action = $this->determineAction($rsi, $momentum5, $momentum10, $currentPrice, $sma10, $sma20);
        $confidence = $this->calculateConfidence($rsi, $momentum5, $currentPrice, $sma10);
        $atr = $this->calculateATR($prices, 14);

        $stopLoss = round($currentPrice - ($atr * 1.5), 5);
        $tp1 = round($currentPrice + ($atr * 1.5), 5);
        $tp2 = round($currentPrice + ($atr * 2.5), 5);
        $tp3 = round($currentPrice + ($atr * 4.0), 5);

        if (in_array($action, ['SELL', 'STRONG_SELL'])) {
            $stopLoss = round($currentPrice + ($atr * 1.5), 5);
            $tp1 = round($currentPrice - ($atr * 1.5), 5);
            $tp2 = round($currentPrice - ($atr * 2.5), 5);
            $tp3 = round($currentPrice - ($atr * 4.0), 5);
        }

        $now = now();
        $expiry = $now->copy()->addHours($this->signalDurationHours);

        return [
            'symbol' => $symbol,
            'type' => $meta['type'],
            'action' => $action,
            'direction' => str_contains($action, 'BUY') ? 'CALL' : 'PUT',
            'entry_price_min' => round($currentPrice * 0.998, 5),
            'entry_price_max' => round($currentPrice * 1.002, 5),
            'stop_loss' => $stopLoss,
            'take_profit_1' => $tp1,
            'take_profit_2' => $tp2,
            'take_profit_3' => $tp3,
            'status' => 'active',
            'signal_time' => $now,
            'expiry_time' => $expiry,
            'confidence_level' => $confidence,
            'analysis_summary' => $this->generateAnalysis($symbol, $action, $currentPrice, $rsi, $momentum5, $sma10, $sma20),
            'technical_indicators' => json_encode([
                'rsi_14' => $rsi,
                'momentum_5' => $momentum5,
                'momentum_10' => $momentum10,
                'sma_10' => round($sma10, 5),
                'sma_20' => round($sma20, 5),
                'atr_14' => round($atr, 5),
                'current_price' => $currentPrice,
            ]),
            'risk_notes' => "Signal expires at {$expiry->format('Y-m-d H:i:s')} UTC. Use stop-loss.",
        ];
    }

    protected function getTimeSeriesKey(array $data): ?string
    {
        foreach ($data as $key => $value) {
            if (str_contains($key, 'Time Series') || str_contains($key, 'time series')) {
                return $key;
            }
        }
        return null;
    }

    protected function getCloseKey(string $type): string
    {
        if ($type === 'crypto') {
            return '5. close';
        }
        return '4. close';
    }

    protected function calculateRSI(array $prices, int $period = 14): float
    {
        if (count($prices) < $period + 1) {
            return 50;
        }

        $gains = 0;
        $losses = 0;
        $start = count($prices) - $period - 1;

        for ($i = $start; $i < count($prices) - 1; $i++) {
            $change = $prices[$i + 1] - $prices[$i];
            if ($change > 0) {
                $gains += $change;
            } else {
                $losses += abs($change);
            }
        }

        $avgGain = $gains / $period;
        $avgLoss = $losses / $period;

        if ($avgLoss == 0) {
            return 100;
        }

        $rs = $avgGain / $avgLoss;
        return round(100 - (100 / (1 + $rs)), 1);
    }

    protected function calculateMomentum(array $prices, int $period): float
    {
        if (count($prices) < $period + 1) {
            return 0;
        }
        $current = end($prices);
        $past = $prices[count($prices) - 1 - $period];
        if ($past == 0) {
            return 0;
        }
        return round((($current - $past) / $past) * 100, 2);
    }

    protected function calculateATR(array $prices, int $period = 14): float
    {
        if (count($prices) < $period + 1) {
            return 0.002;
        }
        $ranges = [];
        for ($i = count($prices) - $period; $i < count($prices); $i++) {
            $ranges[] = abs($prices[$i] - ($prices[$i - 1] ?? $prices[$i]));
        }
        return count($ranges) > 0 ? array_sum($ranges) / count($ranges) : 0.002;
    }

    protected function determineAction(float $rsi, float $mom5, float $mom10, float $price, float $sma10, float $sma20): string
    {
        $score = 0;

        if ($rsi < 30) {
            $score += 3;
        } elseif ($rsi < 40) {
            $score += 1;
        } elseif ($rsi > 70) {
            $score -= 3;
        } elseif ($rsi > 60) {
            $score -= 1;
        }

        if ($mom5 > 0.5 && $mom10 > 0.5) {
            $score += 2;
        } elseif ($mom5 > 0.2) {
            $score += 1;
        } elseif ($mom5 < -0.5 && $mom10 < -0.5) {
            $score -= 2;
        } elseif ($mom5 < -0.2) {
            $score -= 1;
        }

        if ($sma10 > $sma20 && $price > $sma10) {
            $score += 2;
        } elseif ($sma10 < $sma20 && $price < $sma10) {
            $score -= 2;
        }

        if ($score >= 4) {
            return 'STRONG_BUY';
        } elseif ($score >= 1) {
            return 'BUY';
        } elseif ($score <= -4) {
            return 'STRONG_SELL';
        } elseif ($score <= -1) {
            return 'SELL';
        }

        return 'NEUTRAL';
    }

    protected function calculateConfidence(float $rsi, float $mom5, float $price, float $sma10): string
    {
        $strength = 0;

        if ($rsi < 35 || $rsi > 65) {
            $strength++;
        }
        if (abs($mom5) > 1.0) {
            $strength++;
        }
        if (abs($price - $sma10) / $sma10 > 0.005) {
            $strength++;
        }

        if ($strength >= 3) {
            return 'very_high';
        } elseif ($strength >= 2) {
            return 'high';
        } elseif ($strength >= 1) {
            return 'medium';
        }
        return 'low';
    }

    protected function generateAnalysis(string $symbol, string $action, float $price, float $rsi, float $momentum, float $sma10, float $sma20): string
    {
        $rsiStatus = $rsi > 70 ? 'overbought' : ($rsi < 30 ? 'oversold' : 'neutral');
        $trend = $momentum > 0 ? 'bullish' : 'bearish';

        return "{$symbol} at \${$price}. RSI({$rsi}) indicates {$rsiStatus} territory. "
            . "Price momentum is {$trend} ({$momentum}%). "
            . "SMA10(\${$sma10}) vs SMA20(\${$sma20}). "
            . "Action: {$action}. This is an auto-generated signal based on technical analysis.";
    }

    protected function generateFallbackSignals(): void
    {
        $now = now();
        $expiry = $now->copy()->addHours($this->signalDurationHours);

        $fallbackActions = ['BUY', 'SELL', 'NEUTRAL', 'BUY', 'STRONG_BUY', 'SELL', 'STRONG_SELL'];
        $confidenceLevels = ['low', 'medium', 'high', 'very_high'];

        foreach ($this->symbolMap as $symbol => $meta) {
            $existing = TradingSignal::where('symbol', $symbol)
                ->where('status', 'active')
                ->where('expiry_time', '>', now())
                ->first();

            if ($existing) {
                continue;
            }

            $basePrice = $this->getBasePrice($symbol);
            $action = $fallbackActions[array_rand($fallbackActions)];
            $confidence = $confidenceLevels[array_rand($confidenceLevels)];
            $spread = $basePrice * 0.001;

            $rsi = rand(20, 80);
            $mom = round(rand(-200, 200) / 100, 2);
            $atr = $basePrice * 0.002;

            $stopLoss = round($basePrice - ($atr * 1.5), 5);
            $tp1 = round($basePrice + ($atr * 1.5), 5);
            $tp2 = round($basePrice + ($atr * 2.5), 5);
            $tp3 = round($basePrice + ($atr * 4.0), 5);

            if (in_array($action, ['SELL', 'STRONG_SELL'])) {
                $stopLoss = round($basePrice + ($atr * 1.5), 5);
                $tp1 = round($basePrice - ($atr * 1.5), 5);
                $tp2 = round($basePrice - ($atr * 2.5), 5);
                $tp3 = round($basePrice - ($atr * 4.0), 5);
            }

            TradingSignal::create([
                'symbol' => $symbol,
                'type' => $meta['type'],
                'action' => $action,
                'direction' => str_contains($action, 'BUY') ? 'CALL' : 'PUT',
                'entry_price_min' => round($basePrice - $spread, 5),
                'entry_price_max' => round($basePrice + $spread, 5),
                'stop_loss' => $stopLoss,
                'take_profit_1' => $tp1,
                'take_profit_2' => $tp2,
                'take_profit_3' => $tp3,
                'status' => 'active',
                'signal_time' => $now,
                'expiry_time' => $expiry,
                'confidence_level' => $confidence,
                'signal_type' => 'regular',
                'is_otc' => false,
                'entry_window_minutes' => 240,
                'analysis_summary' => $this->generateAnalysis($symbol, $action, $basePrice, $rsi, $mom, $basePrice, $basePrice),
                'technical_indicators' => json_encode([
                    'rsi_14' => $rsi,
                    'momentum_5' => $mom,
                    'atr_14' => round($atr, 5),
                    'current_price' => $basePrice,
                ]),
                'risk_notes' => "Fallback signal. Expires {$expiry->format('Y-m-d H:i:s')} UTC.",
            ]);

            $this->line("Fallback signal created for {$symbol}: {$action}");
        }
    }

    protected function getBasePrice(string $symbol): float
    {
        $prices = [
            'EURUSD' => 1.0850, 'GBPUSD' => 1.2650, 'USDJPY' => 150.50,
            'USDCHF' => 0.8850, 'AUDUSD' => 0.6550, 'USDCAD' => 1.375175,
            'NZDUSD' => 0.6050, 'GBPJPY' => 190.20, 'EURJPY' => 163.30,
            'EURGBP' => 0.8580, 'XAUUSD' => 4066.50, 'XAGUSD' => 59.82,
            'USOIL' => 78.00, 'NATGAS' => 3.12, 'SPX500' => 5300.00,
            'NAS100' => 18500.00, 'US30' => 39000.00, 'UK100' => 7900.00,
            'GER40' => 18000.00, 'JPN225' => 38500.00,
            'BTCUSD' => 64207.78, 'ETHUSD' => 1766.01, 'XRPUSD' => 1.05,
            'SOLUSD' => 71.19, 'ADAUSD' => 0.45, 'LTCUSD' => 82.00,
            'SHIBUSD' => 0.000025, 'ANTROPIC' => 14.50, 'TRUMP' => 16.80,
            'ONDO' => 0.75, 'PEPE' => 0.000011, 'SEI' => 0.42,
            'USOUSD' => 80.97, 'GOLDSILVER' => 69.51, 'PALLADIUM' => 1329.16,
            'PLATINUM' => 1799.71, 'RAYDIUM' => 2.85, 'FET' => 1.32,
            'BCHUSD' => 398.50, 'DOGWF' => 0.21, 'WORLDCOIN' => 0.28,
            'TRONUSD' => 0.48, 'US2000' => 2785.90,
            'USDNOK' => 9.5563, 'USDPLN' => 3.7968, 'USDSEK' => 9.7881,
            'USTRY' => 47.4590, 'USDZAR' => 16.4315, 'AUDJPY' => 108.75,
            'CADCHF' => 0.5648, 'CHFJPY' => 195.01, 'CHFNOK' => 11.68,
            'NIKE' => 35.99, 'CITI' => 129.91, 'WDC' => 544.14,
            'AIG' => 75.70, 'BABA' => 210.59, 'AMZN' => 210.59,
            'KO' => 92.15, 'GS' => 1018.16, 'JPM' => 329.48,
            'MCD' => 246.97, 'MS' => 214.96, 'MSFT' => 452.28,
            'SNAP' => 2.53, 'AIRLINES' => 4026.67, 'CANNABIS' => 2188.72,
            'CASINO' => 2537.27, 'MAG7' => 2492.18, 'URANIUM' => 4991.20,
        ];

        return $prices[$symbol] ?? 100.00;
    }
}