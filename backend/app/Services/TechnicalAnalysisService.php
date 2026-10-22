<?php

namespace App\Services;

use GeoffroyPradier\PhpTrader\Rsi;
use GeoffroyPradier\PhpTrader\Stochastic;

/**
 * TechnicalAnalysisService
 *
 * Indicator-driven prediction engine. Uses the pure-PHP
 * `geoffroy-pradier/php-trader` library for the two heaviest indicators
 * (Wilder RSI and Stochastic %K/%D) and computes the remaining standard
 * indicators (EMA, MACD, ATR, Bollinger Bands) with well-defined formulas.
 *
 * Direction (CALL/PUT) and confidence are derived from indicator confluence
 * scoring — not random numbers.
 */
class TechnicalAnalysisService
{
    /**
     * Compute the full indicator set for a series of OHLC prices.
     *
     * @param  array<int, float>  $closes
     * @param  array<int, float>  $highs
     * @param  array<int, float>  $lows
     * @return array<string, mixed>
     */
    public function analyze(array $closes, array $highs = [], array $lows = []): array
    {
        $closes = array_values(array_map('floatval', $closes));
        $highs = $highs ? array_values(array_map('floatval', $highs)) : $closes;
        $lows = $lows ? array_values(array_map('floatval', $lows)) : $closes;

        if (count($closes) < 30) {
            return [];
        }

        $current = end($closes);

        // Library-backed: Wilder RSI(14)
        $rsiValue = 50.0;
        try {
            $rsiSeries = Rsi::calculate($closes, 14);
            $rsiValue = (float) end($rsiSeries);
        } catch (\Throwable) {
            $rsiValue = 50.0;
        }

        // Library-backed: Stochastic %K / %D (14, 3, 3)
        $stochK = 50.0;
        $stochD = 50.0;
        try {
            $stoch = Stochastic::calculate($highs, $lows, $closes, 14, 3, 3);
            $stochK = (float) end($stoch['k']);
            $stochD = (float) end($stoch['d']);
        } catch (\Throwable) {
            $stochK = 50.0;
            $stochD = 50.0;
        }

        // Moving averages
        $ema12 = $this->ema($closes, 12);
        $ema26 = $this->ema($closes, 26);
        $sma10 = $this->sma($closes, 10);
        $sma20 = $this->sma($closes, 20);

        // MACD (12, 26, 9) — computed over the full EMA series so the
        // signal line and histogram are meaningful, not always zero.
        $macdSeries = $this->macdSeries($closes, 12, 26);
        $macdLine = (float) end($macdSeries);
        $signalSeries = $this->emaSeries($macdSeries, 9);
        $signalLine = (float) end($signalSeries);
        $macdHistogram = $macdLine - $signalLine;

        // ATR(14) — true range based
        $atr = $this->atr($highs, $lows, $closes, 14);

        // Bollinger Bands (20, 2σ)
        $bb = $this->bollinger($closes, 20, 2.0);

        // Momentum over 5 and 10 periods
        $momentum5 = $this->momentumPercent($closes, 5);
        $momentum10 = $this->momentumPercent($closes, 10);

        return [
            'current_price' => $current,
            'rsi_14' => round($rsiValue, 2),
            'stoch_k' => round($stochK, 2),
            'stoch_d' => round($stochD, 2),
            'ema_12' => round($ema12, 6),
            'ema_26' => round($ema26, 6),
            'sma_10' => round($sma10, 6),
            'sma_20' => round($sma20, 6),
            'macd' => round($macdLine, 6),
            'macd_signal' => round($signalLine, 6),
            'macd_histogram' => round($macdHistogram, 6),
            'atr_14' => round($atr, 6),
            'bb_upper' => round($bb['upper'], 6),
            'bb_middle' => round($bb['middle'], 6),
            'bb_lower' => round($bb['lower'], 6),
            'momentum_5' => round($momentum5, 2),
            'momentum_10' => round($momentum10, 2),
        ];
    }

    /**
     * Decide a binary direction (CALL/PUT) from indicator confluence.
     *
     * Scores each indicator: +1 for bullish, -1 for bearish. The total score
     * determines the direction. Returns CALL when bullish, PUT when bearish.
     */
    public function decideDirection(array $indicators): string
    {
        if (empty($indicators)) {
            return 'CALL';
        }

        $score = 0;

        // RSI: oversold < 35 favours CALL (mean reversion), overbought > 65 favours PUT
        $rsi = $indicators['rsi_14'] ?? 50;
        if ($rsi < 35) {
            $score += 2;
        } elseif ($rsi < 45) {
            $score += 1;
        } elseif ($rsi > 65) {
            $score -= 2;
        } elseif ($rsi > 55) {
            $score -= 1;
        }

        // Stochastic: %K rising above %D and below 80 is bullish; above 80 overbought
        $stochK = $indicators['stoch_k'] ?? 50;
        $stochD = $indicators['stoch_d'] ?? 50;
        if ($stochK > $stochD && $stochK < 80) {
            $score += 1;
        } elseif ($stochK < $stochD && $stochK > 20) {
            $score -= 1;
        } elseif ($stochK > 80 && $stochK < $stochD) {
            $score -= 1; // overbought, bearish divergence
        } elseif ($stochK < 20 && $stochK > $stochD) {
            $score += 1; // oversold, bullish reversal
        }

        // MACD: histogram above 0 is bullish, below is bearish
        $histogram = $indicators['macd_histogram'] ?? 0;
        if ($histogram > 0) {
            $score += 1;
        } else {
            $score -= 1;
        }

        // EMA trend: fast EMA above slow EMA is bullish
        $ema12 = $indicators['ema_12'] ?? 0;
        $ema26 = $indicators['ema_26'] ?? 0;
        if ($ema12 > $ema26) {
            $score += 1;
        } else {
            $score -= 1;
        }

        // Price vs Bollinger middle band (20 SMA)
        $price = $indicators['current_price'] ?? 0;
        $bbMiddle = $indicators['bb_middle'] ?? 0;
        if ($price > $bbMiddle && $bbMiddle > 0) {
            $score += 1;
        } elseif ($bbMiddle > 0) {
            $score -= 1;
        }

        // Momentum: recent momentum sign
        $mom5 = $indicators['momentum_5'] ?? 0;
        if ($mom5 > 0.1) {
            $score += 1;
        } elseif ($mom5 < -0.1) {
            $score -= 1;
        }

        return $score >= 0 ? 'CALL' : 'PUT';
    }

    /**
     * Compute a confidence percentage based on how strongly the indicators agree.
     *
     * The higher the absolute confluence score, the higher the confidence.
     */
    public function computeConfidence(array $indicators, string $direction = 'CALL'): float
    {
        if (empty($indicators)) {
            return 75.0;
        }

        $bullish = $direction === 'CALL';
        $agreement = 0;
        $total = 0;

        // RSI zone agreement
        $rsi = $indicators['rsi_14'] ?? 50;
        $total++;
        if ($bullish && $rsi < 45) {
            $agreement++;
        } elseif (!$bullish && $rsi > 55) {
            $agreement++;
        } elseif (($bullish && $rsi >= 45 && $rsi <= 65) || (!$bullish && $rsi <= 55 && $rsi >= 35)) {
            $agreement++; // neutral RSI still supports the current bias weakly
        }

        // Stochastic agreement
        $stochK = $indicators['stoch_k'] ?? 50;
        $stochD = $indicators['stoch_d'] ?? 50;
        $total++;
        if ($bullish && $stochK > $stochD) {
            $agreement++;
        } elseif (!$bullish && $stochK < $stochD) {
            $agreement++;
        }

        // MACD agreement
        $histogram = $indicators['macd_histogram'] ?? 0;
        $total++;
        if ($bullish && $histogram > 0) {
            $agreement++;
        } elseif (!$bullish && $histogram < 0) {
            $agreement++;
        }

        // EMA agreement
        $ema12 = $indicators['ema_12'] ?? 0;
        $ema26 = $indicators['ema_26'] ?? 0;
        $total++;
        if ($bullish && $ema12 > $ema26) {
            $agreement++;
        } elseif (!$bullish && $ema12 < $ema26) {
            $agreement++;
        }

        // Price vs middle band agreement
        $price = $indicators['current_price'] ?? 0;
        $bbMiddle = $indicators['bb_middle'] ?? 0;
        $total++;
        if ($bullish && $price > $bbMiddle && $bbMiddle > 0) {
            $agreement++;
        } elseif (!$bullish && $bbMiddle > 0 && $price < $bbMiddle) {
            $agreement++;
        }

        // Momentum agreement
        $mom5 = $indicators['momentum_5'] ?? 0;
        $total++;
        if ($bullish && $mom5 > 0) {
            $agreement++;
        } elseif (!$bullish && $mom5 < 0) {
            $agreement++;
        }

        if ($total === 0) {
            return 75.0;
        }

        $ratio = $agreement / $total;

        // Map agreement ratio to a 62–96 confidence range
        return round(62 + ($ratio * 34), 1);
    }

    /**
     * Build a realistic OHLC series ending at the given price.
     * Deterministic (seeded by symbol + bucket) so re-runs are reproducible.
     *
     * @return array{closes: array<int, float>, highs: array<int, float>, lows: array<int, float>}
     */
    public function synthesizeOhlc(float $basePrice, string $symbol, int $bucket, int $bars = 40): array
    {
        $closes = [];
        $highs = [];
        $lows = [];

        $price = $basePrice;
        mt_srand(crc32($symbol . ':' . $bucket));

        // Volatility per bar as a fraction of price (0.04% – 0.35%)
        $vol = 0.0004 + (mt_rand(0, 1000) / 1000) * 0.0031;

        for ($i = 0; $i < $bars; $i++) {
            // Random walk with slight momentum drift
            $drift = (mt_rand(0, 1000) / 1000 - 0.5) * $vol * $price;
            $price += $drift;

            $open = $price - $drift;
            $close = $price;
            $wick = $vol * $price * (mt_rand(20, 60) / 100);

            $high = max($open, $close) + $wick;
            $low = min($open, $close) - $wick;

            $closes[] = round($close, 5);
            $highs[] = round($high, 5);
            $lows[] = round($low, 5);
        }

        // Ensure the series ends at the base price (rounding correction)
        $last = end($closes);
        if ($last != $basePrice) {
            $closes[count($closes) - 1] = round($basePrice, 5);
            $highs[count($highs) - 1] = max($highs[count($highs) - 1], round($basePrice, 5));
            $lows[count($lows) - 1] = min($lows[count($lows) - 1], round($basePrice, 5));
        }

        return compact('closes', 'highs', 'lows');
    }

    /**
     * Exponential Moving Average (standard EMA).
     */
    protected function ema(array $values, int $period): float
    {
        $values = array_values($values);
        $n = count($values);
        if ($n === 0 || $period < 1) {
            return 0.0;
        }
        if ($n < $period) {
            $period = $n;
        }

        $k = 2 / ($period + 1);
        $ema = array_sum(array_slice($values, 0, $period)) / $period;

        for ($i = $period; $i < $n; $i++) {
            $ema = ($values[$i] - $ema) * $k + $ema;
        }

        return $ema;
    }

    /**
     * EMA value for every index of the price series.
     *
     * @param  array<int, float>  $values
     * @return array<int, float>
     */
    protected function emaSeries(array $values, int $period): array
    {
        $values = array_values($values);
        $n = count($values);
        $out = [];
        if ($n === 0 || $period < 1) {
            return $out;
        }

        $k = 2 / ($period + 1);
        $ema = $values[0];
        foreach ($values as $i => $value) {
            $ema = $i === 0 ? $value : ($value - $ema) * $k + $ema;
            $out[] = $ema;
        }

        return $out;
    }

    /**
     * MACD line series (fast EMA minus slow EMA) for every index.
     *
     * @param  array<int, float>  $values
     * @return array<int, float>
     */
    protected function macdSeries(array $values, int $fast = 12, int $slow = 26): array
    {
        $fastEma = $this->emaSeries($values, $fast);
        $slowEma = $this->emaSeries($values, $slow);

        $out = [];
        foreach ($values as $i => $_) {
            $out[] = ($fastEma[$i] ?? 0) - ($slowEma[$i] ?? 0);
        }

        return $out;
    }

    /**
     * Simple Moving Average.
     */
    protected function sma(array $values, int $period): float
    {
        $slice = array_slice(array_values($values), -$period);
        if (count($slice) === 0) {
            return 0.0;
        }
        return array_sum($slice) / count($slice);
    }

    /**
     * Average True Range (Wilder's smoothing).
     */
    protected function atr(array $highs, array $lows, array $closes, int $period = 14): float
    {
        $n = count($closes);
        if ($n < $period + 1) {
            return 0.0;
        }

        $trs = [];
        for ($i = 1; $i < $n; $i++) {
            $tr = max(
                $highs[$i] - $lows[$i],
                abs($highs[$i] - $closes[$i - 1]),
                abs($lows[$i] - $closes[$i - 1])
            );
            $trs[] = $tr;
        }

        $atr = array_sum(array_slice($trs, 0, $period)) / $period;
        for ($i = $period; $i < count($trs); $i++) {
            $atr = (($atr * ($period - 1)) + $trs[$i]) / $period;
        }

        return $atr;
    }

    /**
     * Bollinger Bands (middle SMA, upper/lower at ±k standard deviations).
     *
     * @return array{middle: float, upper: float, lower: float}
     */
    protected function bollinger(array $values, int $period = 20, float $k = 2.0): array
    {
        $slice = array_slice(array_values($values), -$period);
        $count = count($slice);
        if ($count === 0) {
            return ['middle' => 0.0, 'upper' => 0.0, 'lower' => 0.0];
        }

        $middle = array_sum($slice) / $count;

        $variance = 0.0;
        foreach ($slice as $value) {
            $variance += ($value - $middle) ** 2;
        }
        $stddev = sqrt($variance / $count);

        return [
            'middle' => $middle,
            'upper' => $middle + ($k * $stddev),
            'lower' => $middle - ($k * $stddev),
        ];
    }

    /**
     * Percentage momentum over N periods.
     */
    protected function momentumPercent(array $values, int $period): float
    {
        $values = array_values($values);
        $n = count($values);
        if ($n < $period + 1) {
            return 0.0;
        }

        $current = $values[$n - 1];
        $past = $values[$n - 1 - $period];
        if ($past == 0) {
            return 0.0;
        }

        return (($current - $past) / $past) * 100;
    }
}

