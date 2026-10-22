<?php

namespace App\Services;

use App\Models\TradingSignal;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * SignalFormatter
 *
 * Renders TradingSignal models as clean, HTML-formatted Telegram messages.
 * Used by TelegramService and the dedicated send / sync artisan commands.
 */
class SignalFormatter
{
    /**
     * Human-readable labels per signal_type.
     *
     * @var array<string, string>
     */
    protected array $signalTypeLabels = [
        'standard' => 'Standard',
        'live' => 'Live',
        'ai_1m' => 'AI 1 Min',
        'ai_2m' => 'AI 2 Min',
        'ai_3m' => 'AI 3 Min',
        'ai_5m' => 'AI 5 Min',
    ];

    /**
     * Emojis used for the grouped active-signal summary headers.
     *
     * @var array<string, string>
     */
    protected array $typeEmojis = [
        'forex' => '💱',
        'crypto' => '🪙',
        'commodity' => '🛢',
        'index' => '📊',
        'stock' => '🏢',
    ];

    /**
     * Format a single signal into an HTML Telegram message.
     */
    public function format(TradingSignal $signal): string
    {
        $direction = strtoupper((string) ($signal->direction ?? $signal->action ?? ''));
        $isBullish = in_array($direction, ['CALL', 'BUY', 'STRONG_BUY'], true);
        $arrow = $isBullish ? '📈' : '📉';
        $actionText = $isBullish ? 'Buy' : 'Sell';

        $lines = [];
        $lines[] = $arrow . ' <b>NEXXORA AI SIGNAL</b> ' . $arrow;
        $lines[] = '━━━━━━━━━━━━━━━━━━━━';
        $lines[] = '';
        $lines[] = '🏷 Asset: <b>' . e($signal->symbol) . '</b>'
            . ($signal->asset_name ? ' <i>(' . e($signal->asset_name) . ')</i>' : '');
        $lines[] = '📊 Type: ' . e(ucfirst($signal->type ?? 'Unknown'))
            . ($signal->is_otc ? ' · <b>OTC</b>' : '');
        $lines[] = '🎯 Direction: <b>' . e($direction) . '</b> (' . $actionText . ')';

        $signalLabel = $this->signalTypeLabel($signal->signal_type);
        if ($signalLabel !== null) {
            $lines[] = '⏱ Signal: ' . e($signalLabel)
                . ' · Valid ' . (int) ($signal->entry_window_minutes ?: 1) . ' min';
        }

$lines[] = '';

        $martingale = $this->martingalePlan($signal);

        if ($martingale !== null) {
            // Time-based trade call — show martingale level entry windows (times) instead of prices.
            $tradeStart = $signal->signal_time ?? $signal->created_at;
            if ($tradeStart) {
                $lines[] = '⏱ <b>Trade starts:</b> <code>' . e($this->watTime($tradeStart)) . '</code>';
            }
            foreach ($martingale as $line) {
                $lines[] = '   ' . $line;
            }
        } else {
            $entryMin = $this->price($signal->entry_price_min, $signal->type);
            $entryMax = $this->price($signal->entry_price_max, $signal->type);
            $lines[] = '💰 <b>Entry:</b> <code>' . $entryMin
                . ($entryMax !== $entryMin ? ' – ' . $entryMax : '') . '</code>';

            if ($signal->stop_loss !== null) {
                $lines[] = '🛑 <b>Stop Loss:</b> <code>' . $this->price($signal->stop_loss, $signal->type) . '</code>';
            }

            foreach (['take_profit_1' => '✅ TP1', 'take_profit_2' => '✅ TP2', 'take_profit_3' => '✅ TP3'] as $field => $label) {
                if ($signal->{$field} !== null) {
                    $lines[] = $label . ': <code>' . $this->price($signal->{$field}, $signal->type) . '</code>';
                }
            }
        }

        if ($signal->confidence_percent !== null) {
            $confidenceLine = '📈 <b>Confidence:</b> ' . number_format((float) $signal->confidence_percent, 1) . '%';
            if ($signal->confidence_level) {
                $confidenceLine .= ' (<i>' . e(str_replace('_', ' ', $signal->confidence_level)) . '</i>)';
            }
            $lines[] = $confidenceLine;
        }

if ($signal->analysis_summary) {
            $lines[] = '';
            $lines[] = '🧠 <b>Analysis:</b>';
            $lines[] = e($signal->analysis_summary);
        }

        // 5-minute market prediction — what is expected to happen next.
        $lines[] = '';
        $lines[] = '🔮 <b> Market Outlook:</b> ' . e($this->fiveMinuteOutlook($signal));

        if ($signal->expiry_time !== null) {
            $lines[] = '';
            $lines[] = '⏰ <b>Expires:</b> <code>' . e($this->watDateTime($signal->expiry_time)) . '</code> (WAT)';
        }

        if ($signal->risk_notes) {
            $lines[] = '⚠️ <i>' . e($signal->risk_notes) . '</i>';
        }

        $lines[] = '';
        $lines[] = '🔞 Manage your risk · Trade responsibly';

        return implode(PHP_EOL, $lines);
    }

    /**
     * Format an active-signal summary group — one message per asset type.
     *
     * @param  string  $type
     * @param  Collection<int, \App\Models\TradingSignal>  $signals
     */
    public function formatActiveSummary(string $type, Collection $signals): string
    {
        $emoji = $this->typeEmojis[$type] ?? '📊';
        $typeName = ucfirst($type);

        $lines = [];
        $lines[] = $emoji . ' <b>ACTIVE ' . strtoupper($typeName) . ' SIGNALS</b> ' . $emoji;
        $lines[] = '━━━━━━━━━━━━━━━━━━━━';
        $lines[] = '';

        foreach ($signals as $signal) {
            $direction = strtoupper((string) ($signal->direction ?? $signal->action ?? ''));
            $isBullish = in_array($direction, ['CALL', 'BUY', 'STRONG_BUY'], true);
            $arrow = $isBullish ? '📈' : '📉';

            $lines[] = $arrow . ' <b>' . e($signal->symbol) . '</b> — ' . e($direction);

            if ($signal->signal_time) {
                $lines[] = '   ⏱ Trade starts: <code>' . e($this->watTime($signal->signal_time)) . '</code>';
            }

            $martingale = $this->martingalePlan($signal);
            if ($martingale !== null) {
                foreach ($martingale as $line) {
                    $lines[] = '   ' . $line;
                }
            }

            if ($signal->confidence_percent !== null) {
                $lines[] = '   📈 Confidence: ' . number_format((float) $signal->confidence_percent, 1) . '%';
            }

            $lines[] = '';
        }

        $lines[] = '🔞 Manage your risk · Trade responsibly';

        return implode(PHP_EOL, $lines);
    }
/**
     * Build a short 5-minute market outlook / prediction for the signal.
     *
     * Describes what the AI expects to happen in the market over the next
     * 5 minutes, based on the signal's direction and confidence.
     */
    protected function fiveMinuteOutlook(TradingSignal $signal): string
    {
        $direction = strtoupper((string) ($signal->direction ?? $signal->action ?? ''));
        $isBullish = in_array($direction, ['CALL', 'BUY', 'STRONG_BUY'], true);
        $conf = $signal->confidence_percent !== null
            ? (int) round((float) $signal->confidence_percent)
            : null;

$bias = $isBullish
            ? 'upward pressure on ' . $signal->symbol
            : 'downward pressure on ' . $signal->symbol;

        // The signal is generated 30 minutes before the scheduled trade time,
        // so the outlook describes what is expected once the trade starts.
        $strength = $conf === null
            ? 'monitored'
            : ($conf >= 85 ? 'high momentum' : ($conf >= 70 ? 'solid momentum' : 'moderate momentum'));

        return "Expect {$bias} over the trade window with {$strength} "
            . ($conf !== null ? "({$conf}% confidence). " : '. ')
            . "This advanced analysis is delivered 30 minutes before the trade time so you can prepare.";
    }

    /**
     * Resolve a human-readable label for a signal type.
     */
    protected function signalTypeLabel(?string $signalType): ?string
    {
        if (!$signalType) {
            return null;
        }

        if (isset($this->signalTypeLabels[$signalType])) {
            return $this->signalTypeLabels[$signalType];
        }

        if (str_starts_with($signalType, 'ai_')) {
            return 'AI ' . strtoupper(str_replace('ai_', '', $signalType));
        }

        return ucfirst($signalType);
    }

    /**
     * Build a compact martingale plan for the message, or null when absent.
     *
     * @return array<int, string>|null
     */
    protected function martingalePlan(TradingSignal $signal): ?array
    {
        $plan = $signal->martingale_plan;
        if (!$plan || !is_array($plan)) {
            return null;
        }

$lines = [];

        foreach ($plan as $level => $entry) {
            if (!is_array($entry)) {
                continue;
            }

            // Skip L0 — the initial entry level. Only show subsequent marti-
            // ngale levels (L1, L2, L3 …) which represent increasing stakes
            // after a loss at the previous level.
            if ($level === 'L0' || $level === '0' || $level === 0) {
                continue;
            }

            $startWat = $entry['start_time_wat'] ?? '';
            $endWat = $entry['end_time_wat'] ?? '';
            $multiplier = $entry['multiplier'] ?? 1;

            $lines[] = e((string) $level) . ': <code>' . e((string) $startWat)
                . ' – ' . e((string) $endWat) . '</code>'
                . ' (' . (int) $multiplier . 'x)';
        }

        return $lines ?: null;
    }

    /**
     * Format a price value based on asset type.
     */
    protected function price(?float $value, ?string $type = 'forex'): string
    {
        if ($value === null) {
            return '--';
        }

        if ($type === 'crypto' && $value > 0 && $value < 0.01) {
            // Tiny-priced coins (SHIB, PEPE) need more decimal places.
            return rtrim(rtrim(number_format($value, 8, '.', ''), '0'), '.');
        }

        if ($type === 'forex') {
            return number_format($value, 5, '.', ',');
        }

        if ($type === 'crypto' || $type === 'stock') {
            return '$' . number_format($value, 2, '.', ',');
        }

        return number_format($value, 2, '.', ',');
    }

    /**
     * Render a date in WAT (Africa/Lagos) long format.
     */
    protected function watDateTime($value): string
    {
        try {
            return Carbon::parse($value)->timezone('Africa/Lagos')->format('d M Y, h:i A');
        } catch (\Throwable) {
            return (string) $value;
        }
    }

    /**
     * Render a date in WAT (Africa/Lagos) short time format.
     */
    protected function watTime($value): string
    {
        try {
            return Carbon::parse($value)->timezone('Africa/Lagos')->format('h:i A');
        } catch (\Throwable) {
            return (string) $value;
        }
    }
}

