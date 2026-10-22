<?php

namespace App\Console\Commands;

use App\Models\TradingSignal;
use App\Services\TelegramService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SendSignalsToTelegram extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'signals:send-telegram
        {--id= : Send a specific signal by its ID}
        {--signal-type= : Only send signals of this signal_type. Defaults to AI trade-call signals (ai_*)}
        {--all-signals : Send ANY unsent signal (including dashboard signals) instead of only AI trade calls}
        {--limit=4 : Maximum number of signals to send}
        {--only-unsent : Only send signals that have not been sent to Telegram yet (default)}
        {--resend : Resend signals regardless of whether they were already sent}
        {--force : Alias for --resend}
        {--validate : Run a configuration validation check before sending}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send trading signals to the configured Telegram group';

    /**
     * Execute the console command.
     */
    public function handle(TelegramService $telegram): int
    {
        if (!$telegram->isReady()) {
            $this->warn('Telegram is not configured or is disabled. Check TELEGRAM_BOT_TOKEN, TELEGRAM_CHAT_ID and TELEGRAM_ENABLED in .env');
            return self::FAILURE;
        }

        // Run a pre-flight validation if requested.
        if ($this->option('validate')) {
            $this->info('Running Telegram configuration validation...');
            $validation = $telegram->validateConfig();
            if (!$validation['token_valid'] || !$validation['chat_valid']) {
                $this->error($validation['error'] ?? 'Validation failed.');
                $this->warn('Configuration is not valid. Signals will likely fail to send.');
                $this->warn('Run "php artisan telegram:validate" for a detailed diagnostic.');
                return self::FAILURE;
            }
            $this->info('Telegram configuration is valid.');
            $this->line('');
        }

        // Show a single signal if an ID was provided.
        if ($this->option('id')) {
            return $this->sendSingleSignal($telegram, (int) $this->option('id'));
        }

        return $this->sendQueuedSignals($telegram);
    }

    /**
     * Send a single signal by ID.
     */
    protected function sendSingleSignal(TelegramService $telegram, int $id): int
    {
        $signal = TradingSignal::find($id);

        if (!$signal) {
            $this->error("Signal #{$id} not found.");
            return self::FAILURE;
        }

        $this->line("Sending signal #{$signal->id} ({$signal->symbol}) to Telegram...");

        $sent = $telegram->sendSignal($signal, true);

        if ($sent) {
            $this->info("Signal #{$signal->id} sent successfully.");
            return self::SUCCESS;
        }

        // Report persistent errors with actionable guidance.
        if ($telegram->hasPersistentError()) {
            $this->error("Failed to send signal #{$signal->id}.");
            $this->error('  Telegram returned a persistent error: ' . $telegram->getLastError());
            $this->warn('  Run "php artisan telegram:validate" for a detailed diagnostic.');
            return self::FAILURE;
        }

        $this->error("Failed to send signal #{$signal->id}.");
        return self::FAILURE;
    }

    /**
     * Send queued (unsent) signals, optionally filtered by signal type.
     */
    protected function sendQueuedSignals(TelegramService $telegram): int
    {
        $limit = (int) $this->option('limit');
        $signalType = $this->option('signal-type');
        $resend = $this->option('resend') || $this->option('force');
        $allSignals = $this->option('all-signals');

        $query = TradingSignal::query()
            ->where('status', 'active')
            ->where('expiry_time', '>', now())
            ->latest()
            ->limit($limit);

        // Filter by AI signal types or specific signal_type
        if ($signalType) {
            $query->where('signal_type', $signalType);
        } elseif (!$allSignals) {
            $query->where('signal_type', 'like', 'ai_%');
        }

        if (!$resend) {
            $query->notSentToTelegram();
        }

        $signals = $query->get();

        if ($signals->isEmpty()) {
            $this->info('No active unsent signals to send' . ($resend ? '' : ' (all already sent or expired)') . '.');
            return self::SUCCESS;
        }

        $this->line("Sending {$signals->count()} signal(s) to Telegram...");

        $result = $telegram->sendBatch($signals);

        $this->line("Sent: {$result['sent']}, Failed: {$result['failed']}, Total: {$result['total']}.");

        if ($result['failed'] > 0) {
            Log::warning('Some signals failed to send to Telegram.', $result);

            if ($telegram->hasPersistentError()) {
                $this->error('Persistent Telegram error detected: ' . ($telegram->getLastError() ?? 'unknown'));
                $this->error('  HTTP status: ' . ($telegram->getLastStatus() ?? 'N/A'));
                $this->warn('  This is a configuration issue (e.g. chat not found, invalid token).');
                $this->warn('  Run "php artisan telegram:validate" for a detailed diagnostic.');
                return self::FAILURE;
            }

            $this->warn('Some signals failed to send. Check logs for details.');
            // Returns SUCCESS if at least one signal in the batch succeeded
            return $result['sent'] > 0 ? self::SUCCESS : self::FAILURE;
        }

        $this->info('All signals sent successfully.');
        return self::SUCCESS;
    }
}