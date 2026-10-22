<?php

namespace App\Console\Commands;

use App\Models\TradingSignal;
use App\Services\TelegramService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SyncSignalsToTelegram extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'signals:sync-telegram
        {--limit=50 : Maximum number of active signals to include in the summary}
        {--type= : Only include signals of this asset type (forex, crypto, commodity, index, stock)}
        {--ai-only : Only summarize AI generated signals (signal_type like ai_%)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send a grouped summary of currently active signals to the Telegram group';

    /**
     * Execute the console command.
     */
    public function handle(TelegramService $telegram): int
    {
        if (!$telegram->isReady()) {
            $this->warn('Telegram is not configured or is disabled. Check TELEGRAM_BOT_TOKEN, TELEGRAM_CHAT_ID and TELEGRAM_ENABLED in .env');
            return self::FAILURE;
        }

        $limit = (int) $this->option('limit');
        $type = $this->option('type');
        $allowedTypes = ['forex', 'crypto', 'commodity', 'index', 'stock'];

        $query = TradingSignal::query()
            ->active()
            ->where('expiry_time', '>', now())
            ->latest();

        // Default or flag to filter for AI signals
        if ($this->option('ai-only')) {
            $query->where('signal_type', 'like', 'ai_%');
        }

        if ($type && in_array($type, $allowedTypes, true)) {
            $query->ofType($type);
        }

        $signals = $query->limit($limit)->get();

        if ($signals->isEmpty()) {
            $this->info('No active signals to sync.');
            return self::SUCCESS;
        }

        $this->line("Sending active signal summary ({$signals->count()} signals) to Telegram...");

        $result = $telegram->sendActiveSignalsSummary($signals);

        $groups = $result['groups'] ?? 0;
        $sent = $result['messages_sent'] ?? 0;
        $covered = $result['signals_covered'] ?? 0;

        $this->line("Groups: {$groups}, Messages sent: {$sent}, Signals covered: {$covered}.");

        // Validate that messages were actually dispatched and match execution expectations
        if ($sent === 0 || $covered === 0) {
            Log::warning('Active signal summary failed to send any messages to Telegram.', $result);
            $this->error('Failed to send signal summary to Telegram.');
            return self::FAILURE;
        }

        if (isset($result['failed']) && $result['failed'] > 0) {
            Log::warning('Some active-signal summary groups failed to send to Telegram.', $result);
            $this->warn('Some summary messages failed to send. Check the logs for details.');
            return self::FAILURE;
        }

        $this->info('Active signal summary sent successfully.');
        return self::SUCCESS;
    }
}