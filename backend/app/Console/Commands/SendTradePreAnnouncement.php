<?php

namespace App\Console\Commands;

use App\Models\TradingSignal;
use App\Services\SignalFormatter;
use App\Services\TelegramService;
use Illuminate\Console\Command;

class SendTradePreAnnouncement extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'signals:pre-announce
        {--minutes=5 : How many minutes before the trade time to send the alert}
        {--limit=4 : Maximum number of pre-announcements to send}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send a Telegram alert 5 minutes before each AI trade time';

    /**
     * Execute the console command.
     */
    public function handle(TelegramService $telegram): int
    {
        if (!$telegram->isReady()) {
            $this->warn('Telegram is not configured or is disabled. Check TELEGRAM_BOT_TOKEN, TELEGRAM_CHAT_ID and TELEGRAM_ENABLED in .env');
            return self::FAILURE;
        }

        $minutes = (int) $this->option('minutes');
        $limit = (int) $this->option('limit');
        $formatter = new SignalFormatter();

        // Signals whose trade/expiry time is exactly $minutes minutes from now.
        $target = now()->copy()->addMinutes($minutes);
        $floor = $target->copy()->subSeconds(90);
        $ceil = $target->copy()->addSeconds(90);

        $signals = TradingSignal::query()
            ->where('status', 'active')
            ->whereNotNull('expiry_time')
            ->whereBetween('expiry_time', [$floor, $ceil])
            ->whereNull('telegram_sent_at')
            ->latest()
            ->limit($limit)
            ->get();

        if ($signals->isEmpty()) {
            $this->info('No signals due for a pre-announcement right now.');
            return self::SUCCESS;
        }

        $this->line("Sending {$signals->count()} pre-announcement(s) for trades starting in ~{$minutes} minute(s)...");

        $sent = 0;
        foreach ($signals as $signal) {
            $message = $this->preAnnouncement($formatter, $signal, $minutes);
            if ($telegram->sendText($message, 'HTML')) {
                // Mark as sent so the live signal dispatch doesn't re-alert.
                $signal->forceFill(['telegram_sent_at' => now()])->save();
                $sent++;
                usleep(150_000);
            }
        }

        $this->line("Pre-announcements sent: {$sent} / {$signals->count()}.");

        return $sent === $signals->count() ? self::SUCCESS : self::FAILURE;
    }

    /**
     * Build a short "starting in X minutes" alert message.
     */
    protected function preAnnouncement(SignalFormatter $formatter, TradingSignal $signal, int $minutes): string
    {
        $direction = strtoupper((string) ($signal->direction ?? $signal->action ?? ''));
        $arrow = in_array($direction, ['CALL', 'BUY', 'STRONG_BUY'], true) ? '📈' : '📉';
        $tradeTime = $signal->expiry_time
            ? $formatter->watTime($signal->expiry_time) // short time
            : 'soon';

        $lines = [];
        $lines[] = $arrow . ' <b>TRADE REMINDER</b> ' . $arrow;
        $lines[] = '━━━━━━━━━━━━━━━━━━━━';
        $lines[] = '';
        $lines[] = '🏷 Asset: <b>' . e($signal->symbol) . '</b>';
        $lines[] = '🎯 Direction: <b>' . e($direction) . '</b>';
        $lines[] = '⏱ Trade starts in <b>' . $minutes . ' minute(s)</b> (at ~' . e($tradeTime) . ' WAT)';
        $lines[] = '';
        $lines[] = '🔮 ' . e($formatter->fiveMinuteOutlook($signal));
        $lines[] = '';
        $lines[] = 'Get ready — the full signal with entry windows is coming at trade time.';

        return implode(PHP_EOL, $lines);
    }
}
