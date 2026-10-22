<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TradingSignal;
use App\Services\TradingSignalGenerator;
use Illuminate\Http\Request;

class TradingSignalController extends Controller
{
    public function __construct(protected TradingSignalGenerator $signalGenerator)
    {
    }

    /**
     * Get active (currently valid) trading signals.
     * Optionally filter by type (forex, commodity, index, crypto) or symbol.
     * Public endpoint — no authentication required to view signals.
     */
    public function getSignals(Request $request)
    {
        // Auto-expire any signals past their time
        TradingSignal::autoExpire();

        $query = TradingSignal::active()->latest();

        // Filter by asset type
        if ($request->filled('type')) {
            $type = strtolower($request->type);
            $allowedTypes = ['forex', 'commodity', 'index', 'crypto', 'stock'];
            if (in_array($type, $allowedTypes)) {
                $query->ofType($type);
            }
        }

        // Filter by specific symbol
        if ($request->filled('symbol')) {
            $query->ofSymbol($request->symbol);
        }

        // Filter by action (BUY / SELL)
        if ($request->filled('action')) {
            $action = strtoupper($request->action);
            $allowedActions = ['BUY', 'SELL', 'STRONG_BUY', 'STRONG_SELL', 'NEUTRAL'];
            if (in_array($action, $allowedActions)) {
                $query->where('action', $action);
            }
        }

        // Filter by direction (CALL / PUT) for AI signals
        if ($request->filled('direction')) {
            $direction = strtoupper($request->direction);
            $allowedDirections = ['CALL', 'PUT'];
            if (in_array($direction, $allowedDirections)) {
                $query->where('direction', $direction);
            }
        }

        // Filter by signal_type (standard, ai_1m, ai_5m, etc.)
        if ($request->filled('signal_type')) {
            $query->ofSignalType($request->signal_type);
        }

        // Filter by OTC status
        if ($request->filled('otc')) {
            if ($request->boolean('otc')) {
                $query->otc();
            } else {
                $query->notOtc();
            }
        }

        // Filter by minimum AI confidence percentage
        if ($request->filled('min_confidence')) {
            $minConfidence = (float) $request->min_confidence;
            $query->where('confidence_percent', '>=', $minConfidence);
        }

        $signals = $query->paginate($request->per_page ?? 50);

        return response()->json([
            'success' => true,
            'data' => $signals->items(),
            'meta' => [
                'current_page' => $signals->currentPage(),
                'last_page' => $signals->lastPage(),
                'per_page' => $signals->perPage(),
                'total' => $signals->total(),
            ],
        ]);
    }

    /**
     * Get featured trading signals — one latest active signal per asset type
     * (commodity, crypto, index, forex, stock) for the CRM dashboard.
     * Public endpoint — no authentication required.
     */
    public function getFeaturedSignals(Request $request)
    {
        // Auto-expire any signals past their time
        TradingSignal::autoExpire();

        $assetTypes = ['commodity', 'crypto', 'index', 'forex', 'stock'];
        $featured = [];

        foreach ($assetTypes as $type) {
            // Ensure a fresh, active signal exists — generate a short-lived
            // on-demand signal when none is available (no cron required).
            $signal = $this->signalGenerator->ensureFreshFeaturedSignal($type);

            if ($signal) {
                $featured[] = $this->formatSignalForDisplay($signal);
            }
        }

        return response()->json([
            'success' => true,
            'data' => $featured,
        ]);
    }

    /**
     * Format a TradingSignal model into a compact display object
     * suitable for the CRM widget.
     */
    protected function formatSignalForDisplay(TradingSignal $signal): array
    {
        $entryPrice = $signal->entry_price_max !== null
            ? (float) $signal->entry_price_max
            : ($signal->entry_price_min !== null ? (float) $signal->entry_price_min : 0);

        // Try to extract the current price from technical_indicators JSON
        $technicalIndicators = $signal->technical_indicators
            ? json_decode($signal->technical_indicators, true)
            : null;

        // Apply a deterministic "live" drift so the displayed price visibly
        // changes over time on every API fetch.
        $basePrice = $technicalIndicators['current_price'] ?? $entryPrice;
        $currentPrice = $this->signalGenerator->livePrice(
            $basePrice,
            $signal->symbol,
            $signal->signal_time
        );

        // Calculate price change percentage if we have both current and entry prices
        $priceChangePercent = null;
        if ($currentPrice > 0 && $entryPrice > 0) {
            $priceChangePercent = round((($currentPrice - $entryPrice) / $entryPrice) * 100, 2);
        }

        // Determine the display percentage:
        // Live on-demand signals use the time-varying price change so the
        // widget always shows movement. Otherwise prefer confidence_percent
        // (AI signals), fall back to momentum, then price change.
        $displayPercent = null;
        $percentLabel = 'change';

        if ($signal->signal_type === 'live' && $priceChangePercent !== null) {
            $displayPercent = $priceChangePercent;
            $percentLabel = 'change';
        } elseif ($signal->confidence_percent !== null) {
            $displayPercent = (float) $signal->confidence_percent;
            $percentLabel = 'confidence';
        } elseif (isset($technicalIndicators['momentum_5'])) {
            $displayPercent = (float) $technicalIndicators['momentum_5'];
            $percentLabel = 'momentum';
        } elseif ($priceChangePercent !== null) {
            $displayPercent = $priceChangePercent;
            $percentLabel = 'change';
        }

        // Determine action display
        $action = $signal->action;
        $direction = $signal->direction; // CALL / PUT for AI signals

        // Determine if BUY or SELL is recommended
        $recommendedAction = $action;
        if ($direction === 'CALL') {
            $recommendedAction = 'BUY';
        } elseif ($direction === 'PUT') {
            $recommendedAction = 'SELL';
        }

        // Format entry price for display
        $formattedEntryPrice = $this->formatPrice($entryPrice, $signal->type);

        return [
            'id' => $signal->id,
            'symbol' => $signal->symbol,
            'type' => $signal->type,
            'asset_name' => $signal->asset_name,
            'action' => $action,
            'direction' => $direction,
            'recommended_action' => $recommendedAction,
            'entry_price' => $entryPrice,
            'entry_price_formatted' => $formattedEntryPrice,
            'entry_price_min' => $signal->entry_price_min,
            'entry_price_max' => $signal->entry_price_max,
            'stop_loss' => $signal->stop_loss,
            'take_profit_1' => $signal->take_profit_1,
            'take_profit_2' => $signal->take_profit_2,
            'take_profit_3' => $signal->take_profit_3,
            'confidence_percent' => $signal->confidence_percent,
            'confidence_level' => $signal->confidence_level,
            'display_percent' => $displayPercent !== null ? round($displayPercent, 2) : null,
            'percent_label' => $percentLabel,
            'price_change_percent' => $priceChangePercent,
            'current_price' => $currentPrice,
            'current_price_formatted' => $this->formatPrice($currentPrice, $signal->type),
            'is_ai_signal' => $signal->isAiSignal(),
            'is_otc' => (bool) $signal->is_otc,
            'signal_type' => $signal->signal_type,
            'signal_time' => $signal->signal_time?->toDateTimeString(),
            'expiry_time' => $signal->expiry_time?->toDateTimeString(),
            'analysis_summary' => $signal->analysis_summary,
            'risk_notes' => $signal->risk_notes,
        ];
    }

    /**
     * Format a price value based on asset type.
     */
    protected function formatPrice(float $price, string $type): string
    {
        if ($type === 'crypto') {
            // Crypto: tiny-priced coins (e.g. SHIB, PEPE) need more decimals
            if ($price > 0 && $price < 0.01) {
                return '$' . rtrim(rtrim(number_format($price, 8, '.', ''), '0'), '.');
            }
            // Crypto prices get $ prefix with thousands separator
            return '$' . number_format($price, 2, '.', ',');
        }

        if ($type === 'forex') {
            // Forex pairs: 5 decimal places
            return number_format($price, 5, '.', ',');
        }

        if ($type === 'commodity') {
            // Commodities: 2 decimal places
            return number_format($price, 2, '.', ',');
        }

        if ($type === 'index') {
            // Indices: 2 decimal places, no currency symbol
            return number_format($price, 2, '.', ',');
        }

        if ($type === 'stock') {
            // Stocks/Equities: 2 decimal places with $ prefix
            return '$' . number_format($price, 2, '.', ',');
        }

        return (string) $price;
    }

    /**
     * Get AI trading signals (1m, 5m, 15m timeframes).
     * Filters specifically for AI-generated signals with direction and confidence.
     * Public endpoint.
     */
    public function getAiSignals(Request $request)
    {
        // Auto-expire any signals past their time
        TradingSignal::autoExpire();

        $query = TradingSignal::active()
            ->where('signal_type', 'like', 'ai_%')
            ->latest();

        // Filter by timeframe (1m, 5m, 15m, etc.)
        if ($request->filled('timeframe')) {
            $timeframe = strtolower($request->timeframe);
            $query->where('signal_type', 'like', "ai_{$timeframe}");
        }

        // Filter by symbol
        if ($request->filled('symbol')) {
            $query->ofSymbol($request->symbol);
        }

        // Filter by direction (CALL / PUT)
        if ($request->filled('direction')) {
            $direction = strtoupper($request->direction);
            $allowedDirections = ['CALL', 'PUT'];
            if (in_array($direction, $allowedDirections)) {
                $query->where('direction', $direction);
            }
        }

        // Filter by OTC status
        if ($request->filled('otc')) {
            if ($request->boolean('otc')) {
                $query->otc();
            } else {
                $query->notOtc();
            }
        }

        // Filter by minimum confidence percentage
        if ($request->filled('min_confidence')) {
            $minConfidence = (float) $request->min_confidence;
            $query->where('confidence_percent', '>=', $minConfidence);
        }

        // Filter by type (forex, commodity, index, crypto, stock)
        if ($request->filled('type')) {
            $type = strtolower($request->type);
            $allowedTypes = ['forex', 'commodity', 'index', 'crypto', 'stock'];
            if (in_array($type, $allowedTypes)) {
                $query->ofType($type);
            }
        }

        $signals = $query->paginate($request->per_page ?? 50);

        return response()->json([
            'success' => true,
            'data' => $signals->items(),
            'meta' => [
                'current_page' => $signals->currentPage(),
                'last_page' => $signals->lastPage(),
                'per_page' => $signals->perPage(),
                'total' => $signals->total(),
            ],
        ]);
    }

    /**
     * Get a single trading signal by ID.
     */
    public function getSignalById($id)
    {
        $signal = TradingSignal::findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $signal,
        ]);
    }

    /**
     * Get signal history — includes expired and completed signals.
     * Authenticated users only.
     */
    public function getSignalHistory(Request $request)
    {
        $query = TradingSignal::latest();

        // Filter by status
        if ($request->filled('status')) {
            $status = strtolower($request->status);
            $allowedStatuses = ['active', 'expired', 'completed', 'cancelled'];
            if (in_array($status, $allowedStatuses)) {
                $query->where('status', $status);
            }
        } else {
            // Default: show all non-active (history)
            $query->whereIn('status', ['expired', 'completed', 'cancelled']);
        }

        // Filter by type
        if ($request->filled('type')) {
            $type = strtolower($request->type);
            $allowedTypes = ['forex', 'commodity', 'index', 'crypto', 'stock'];
            if (in_array($type, $allowedTypes)) {
                $query->ofType($type);
            }
        }

        // Filter by symbol
        if ($request->filled('symbol')) {
            $query->ofSymbol($request->symbol);
        }

        // Filter by signal_type (standard, ai_1m, etc.)
        if ($request->filled('signal_type')) {
            $query->ofSignalType($request->signal_type);
        }

        // Filter by direction
        if ($request->filled('direction')) {
            $direction = strtoupper($request->direction);
            $allowedDirections = ['CALL', 'PUT', 'BUY', 'SELL', 'STRONG_BUY', 'STRONG_SELL', 'NEUTRAL'];
            if (in_array($direction, $allowedDirections)) {
                $query->where('direction', $direction);
            }
        }

        // Date range filter
        if ($request->filled('from')) {
            $query->where('created_at', '>=', $request->from);
        }
        if ($request->filled('to')) {
            $query->where('created_at', '<=', $request->to);
        }

        $signals = $query->paginate($request->per_page ?? 50);

        return response()->json([
            'success' => true,
            'data' => $signals->items(),
            'meta' => [
                'current_page' => $signals->currentPage(),
                'last_page' => $signals->lastPage(),
                'per_page' => $signals->perPage(),
                'total' => $signals->total(),
            ],
        ]);
    }

/**
     * Get AI signals grouped by timeframe (1m, 2m, 5m) for the CRM dashboard.
     * Returns the latest active AI signal for each timeframe with full
     * martingale plan and WAT-formatted times.
     */
    public function getAiSignalsGrouped(Request $request)
    {
        TradingSignal::autoExpire();

        $timeframes = ['ai_1m', 'ai_2m', 'ai_5m'];
        $grouped = [];

        foreach ($timeframes as $signalType) {
            $signal = TradingSignal::active()
                ->where('signal_type', $signalType)
                ->latest()
                ->first();

            if ($signal) {
                $grouped[] = $this->formatAiSignalForDisplay($signal);
            }
        }

        return response()->json([
            'success' => true,
            'data' => $grouped,
        ]);
    }

    /**
     * Format an AI signal for the CRM dashboard display, including
     * martingale plan with WAT times.
     */
    protected function formatAiSignalForDisplay(TradingSignal $signal): array
    {
        $direction = $signal->direction ?? 'CALL';
        $actionText = $direction === 'CALL' ? 'Buy' : 'Sell';
        $timeframe = str_replace('ai_', '', $signal->signal_type ?? '1m');
        $assetDisplay = $signal->symbol . ($signal->is_otc ? ' (OTC)' : '');
        $confidence = $signal->confidence_percent ?? 0;
        $entryWindow = $signal->entry_window_minutes ?? 1;

        // Format times in WAT (Africa/Lagos)
        $signalCreated = $signal->signal_time
            ? $signal->signal_time->timezone('Africa/Lagos')->format('h:i A')
            : '--';
        $validUntil = $signal->expiry_time
            ? $signal->expiry_time->timezone('Africa/Lagos')->format('h:i A')
            : '--';

        // Martingale plan
        $martingalePlan = $signal->martingale_plan;
        $formattedPlan = [];

        if ($martingalePlan && is_array($martingalePlan)) {
            foreach ($martingalePlan as $level => $plan) {
                // Skip L0 — the initial entry level. Only expose subsequent
                // martingale levels (L1, L2, ...) to the frontend.
                if ($level === 'L0' || $level === '0' || $level === 0) {
                    continue;
                }

                $formattedPlan[] = [
                    'level' => $level,
                    'start_time_wat' => $plan['start_time_wat'] ?? '',
                    'end_time_wat' => $plan['end_time_wat'] ?? '',
                    'multiplier' => $plan['multiplier'] ?? 1,
                ];
            }
        }

        return [
            'id' => $signal->id,
            'symbol' => $signal->symbol,
            'type' => $signal->type,
            'asset_name' => $signal->asset_name,
            'asset_display' => $assetDisplay,
            'direction' => $direction,
            'action_text' => $actionText,
            'timeframe' => $timeframe,
            'signal_time' => $signal->signal_time?->toDateTimeString(),
            'expiry_time' => $signal->expiry_time?->toDateTimeString(),
            'signal_created_wat' => $signalCreated,
            'valid_until_wat' => $validUntil,
            'entry_window_minutes' => $entryWindow,
            'confidence_percent' => $confidence,
            'is_otc' => (bool) $signal->is_otc,
            'signal_type' => $signal->signal_type,
            'martingale_plan' => $formattedPlan,
            'entry_price' => $signal->entry_price_max ?? $signal->entry_price_min,
            'entry_price_formatted' => $this->formatPrice($signal->entry_price_max ?? $signal->entry_price_min ?? 0, $signal->type),
            'analysis_summary' => $signal->analysis_summary,
        ];
    }

    /**
     * Get available asset types and symbols for filtering.
     */
    public function getAssetTypes()
    {
        $assets = [
            'forex' => [
                'name' => 'Forex',
                'description' => 'Foreign exchange currency pairs',
                'symbols' => [
                    'EURUSD', 'GBPUSD', 'USDJPY', 'USDCHF', 'AUDUSD',
                    'USDCAD', 'NZDUSD', 'EURGBP', 'EURJPY', 'GBPJPY',
                    'EURAUD', 'GBPAUD', 'EURCHF', 'GBPCHF',
                    'USDNOK', 'USDPLN', 'USDSEK', 'USTRY', 'USDZAR',
                    'AUDJPY', 'CADCHF', 'CHFJPY', 'CHFNOK',
                ],
            ],
            'commodity' => [
                'name' => 'Commodities',
                'description' => 'Precious metals, energy & raw materials',
                'symbols' => [
                    'XAUUSD', 'XAGUSD', 'XPTUSD', 'XPDUSD',
                    'USOIL', 'UKOIL', 'NATGAS',
                    'USOUSD', 'GOLDSILVER', 'PALLADIUM', 'PLATINUM',
                ],
            ],
            'index' => [
                'name' => 'Indices',
                'description' => 'Stock market indices from around the world',
                'symbols' => [
                    'US30', 'SPX500', 'NAS100', 'UK100',
                    'GER40', 'JPN225', 'AUS200', 'VIX',
                    'US2000',
                ],
            ],
            'crypto' => [
                'name' => 'Cryptocurrencies',
                'description' => 'Digital assets & cryptocurrencies',
                'symbols' => [
                    'BTCUSD', 'ETHUSD', 'XRPUSD', 'LTCUSD',
                    'ADAUSD', 'DOTUSD', 'SOLUSD',
                    'SHIBUSD', 'ANTROPIC', 'TRUMP', 'ONDO', 'PEPE', 'SEI',
                    'RAYDIUM', 'FET', 'BCHUSD', 'DOGWF', 'WORLDCOIN', 'TRONUSD',
                ],
            ],
            'stock' => [
                'name' => 'Stocks & Equities',
                'description' => 'Publicly traded company stocks & sector baskets',
                'symbols' => [
                    'NIKE', 'CITI', 'WDC', 'AIG', 'BABA', 'AMZN', 'KO',
                    'GS', 'JPM', 'MCD', 'MS', 'MSFT', 'SNAP',
                    'AIRLINES', 'CANNABIS', 'CASINO', 'MAG7', 'URANIUM',
                ],
            ],
        ];

        return response()->json([
            'success' => true,
            'data' => $assets,
        ]);
    }
}
