<?php

namespace App\Services;

use App\Models\TradingSignal;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * TelegramService
 *
 * Handles all Telegram Bot API interactions for the application:
 *  - Sends individual trading signals to a configured group/channel.
 *  - Sends batched signals and grouped active-signal summaries.
 *  - Marks signals as sent to prevent duplicate notifications.
 *  - Validates bot token and chat accessibility via getChat.
 *
 * Configuration lives in config/services.php under the `telegram` key:
 *   TELEGRAM_BOT_TOKEN, TELEGRAM_CHAT_ID, TELEGRAM_ENABLED, TELEGRAM_PARSE_MODE
 */
class TelegramService
{
    /**
     * The formatter used to render beautiful HTML messages.
     *
     * @var \App\Services\SignalFormatter
     */
    protected SignalFormatter $formatter;

    /**
     * The Telegram Bot API token.
     *
     * @var string|null
     */
    protected ?string $botToken;

    /**
     * Target chat/group/channel ID (may be numeric or @username).
     *
     * @var string|null
     */
    protected ?string $chatId;

    /**
     * Whether Telegram delivery is enabled.
     *
     * @var bool
     */
    protected bool $enabled;

    /**
     * Default parse mode (HTML by default).
     *
     * @var string
     */
    protected string $parseMode;

    /**
     * Base Bot API URL.
     *
     * @var string
     */
    protected string $apiUrl;

    /**
     * HTTP request timeout in seconds.
     *
     * @var int
     */
    protected int $timeout = 20;

    /**
     * Maximum retry attempts for transient failures.
     *
     * @var int
     */
    protected int $maxAttempts = 3;

    /**
     * HTTP status codes that indicate a persistent (non-transient)
     * configuration error. When any of these occur, retries are abandoned
     * and batch processing should be aborted.
     *
     * @var array<int, int>
     */
    protected array $persistentErrorStatuses = [400, 401, 403, 404];

    /**
     * The last error description returned by the Telegram API.
     *
     * @var string|null
     */
    protected ?string $lastError = null;

    /**
     * The last HTTP status code returned by the Telegram API.
     *
     * @var int|null
     */
    protected ?int $lastStatus = null;

    /**
     * Whether a persistent error has been encountered during this
     * service lifecycle (e.g. "chat not found", invalid token).
     *
     * @var bool
     */
    protected bool $persistentError = false;

    /**
     * Asset type ordering used in grouped summaries.
     *
     * @var array<int, string>
     */
    protected array $typeOrder = ['forex', 'crypto', 'commodity', 'index', 'stock'];

    public function __construct(?SignalFormatter $formatter = null)
    {
        $this->formatter = $formatter ?? new SignalFormatter();
        $this->botToken = config('services.telegram.bot_token');
        $this->chatId = config('services.telegram.chat_id');
        $this->enabled = (bool) config('services.telegram.enabled', false);
        $this->parseMode = config('services.telegram.parse_mode', 'HTML');
        $this->apiUrl = rtrim((string) config('services.telegram.api_url', 'https://api.telegram.org'), '/');
    }

    /**
     * Determine whether Telegram delivery is fully configured and enabled.
     */
    public function isEnabled(): bool
    {
        return $this->enabled && $this->botToken && $this->chatId;
    }

    /**
     * Alias for isEnabled().
     */
    public function isReady(): bool
    {
        return $this->isEnabled();
    }

    /**
     * Get the message formatter.
     */
    public function getFormatter(): SignalFormatter
    {
        return $this->formatter;
    }

    /**
     * Get the last error description from the Telegram API.
     */
    public function getLastError(): ?string
    {
        return $this->lastError;
    }

    /**
     * Get the last HTTP status code from the Telegram API.
     */
    public function getLastStatus(): ?int
    {
        return $this->lastStatus;
    }

    /**
     * Whether a persistent (non-retryable) configuration error
     * has been encountered during this request lifecycle.
     */
    public function hasPersistentError(): bool
    {
        return $this->persistentError;
    }

    /**
     * Send a plain / markup message to the configured group.
     *
     * @param  string  $text  Message body.
     * @param  string|null  $parseMode  Parse mode (HTML, MarkdownV2, null).
     * @param  array  $extra  Extra Telegram sendMessage payload options.
     */
    public function sendText(string $text, ?string $parseMode = null, array $extra = []): bool
    {
        if (!$this->isReady()) {
            Log::debug('Telegram disabled or not configured — skipping message send.', [
                'enabled' => $this->enabled,
                'has_token' => (bool) $this->botToken,
                'has_chat' => (bool) $this->chatId,
            ]);

            return false;
        }

        $payload = array_merge([
            'chat_id' => $this->chatId,
            'text' => $text,
            'parse_mode' => $parseMode ?? $this->parseMode,
            'disable_web_page_preview' => true,
        ], $extra);

        $url = "{$this->apiUrl}/bot{$this->botToken}/sendMessage";

        for ($attempt = 1; $attempt <= $this->maxAttempts; $attempt++) {
            // If a persistent error was detected in a previous call (e.g. "chat not found"),
            // stop all retries immediately.
            if ($this->persistentError) {
                return false;
            }

            try {
                $response = Http::timeout($this->timeout)->post($url, $payload);

                if ($response->successful() && ($response->json('ok') ?? false)) {
                    Log::info('Telegram message sent.', [
                        'chat_id' => $this->chatId,
                        'message_id' => $response->json('result.message_id'),
                    ]);

                    return true;
                }

                $description = $response->json('description') ?? $response->body();
                $this->lastError = $description;
                $this->lastStatus = $response->status();

                Log::warning("Telegram sendMessage failed (attempt {$attempt}/{$this->maxAttempts}): {$description}", [
                    'chat_id' => $this->chatId,
                    'bot_token' => $this->maskToken($this->botToken),
                    'status' => $response->status(),
                    'payload_chat_id' => $this->chatId,
                ]);

                // Non-transient errors (invalid token, blocked bot, bad request) — stop retrying.
                if (in_array($response->status(), $this->persistentErrorStatuses, true)) {
                    // Mark as persistent so batch processing halts immediately.
                    $this->persistentError = true;
                    $this->logPersistentError($description, $response->status());
                    break;
                }
            } catch (\Throwable $e) {
                $this->lastError = $e->getMessage();
                Log::warning("Telegram sendMessage exception (attempt {$attempt}/{$this->maxAttempts}): " . $e->getMessage());
            }

            if ($attempt < $this->maxAttempts) {
                // Simple backoff: 0.5s then 1s before retrying.
                usleep(500_000 * $attempt);
            }
        }

        return false;
    }

    /**
     * Send a single trading signal and (optionally) mark it as delivered.
     */
    public function sendSignal(TradingSignal $signal, bool $markSent = true): bool
    {
        if (!$this->isReady()) {
            return false;
        }

        $sent = $this->sendText($this->formatter->format($signal));

        if ($sent && $markSent) {
            $signal->markTelegramSent();
        }

        return $sent;
    }

    /**
     * Send a batch of signals, marking each as delivered on success.
     *
     * If a persistent (non-retryable) error is encountered — such as
     * "chat not found" or an invalid bot token — processing stops
     * immediately to avoid wasting API calls on every remaining signal.
     *
     * @param  \Illuminate\Support\Collection<int, \App\Models\TradingSignal>  $signals
     * @return array{total: int, sent: int, failed: int}
     */
    public function sendBatch(Collection $signals): array
    {
        $total = $signals->count();
        $sent = 0;

        foreach ($signals->values() as $signal) {
            // Halt immediately if a persistent configuration error was detected.
            if ($this->persistentError) {
                Log::warning('Telegram batch sending halted due to persistent error.', [
                    'last_error' => $this->lastError,
                    'last_status' => $this->lastStatus,
                    'remaining' => $total - $sent,
                ]);
                break;
            }

            if ($this->sendSignal($signal)) {
                $sent++;
                // Gentle spacing to respect Telegram's per-second flood limits.
                usleep(150_000);
            }
        }

        return [
            'total' => $total,
            'sent' => $sent,
            'failed' => $total - $sent,
        ];
    }

    /**
     * Send a grouped summary of active signals to the group.
     *
     * Signals are grouped by asset type (forex, crypto, commodity, index,
     * stock) and one message is posted per group in a stable order.
     *
     * @param  \Illuminate\Support\Collection<int, \App\Models\TradingSignal>  $signals
     * @return array{groups: int, messages_sent: int, signals_covered: int}
     */
    public function sendActiveSignalsSummary(Collection $signals): array
    {
        if (!$this->isReady()) {
            return ['groups' => 0, 'messages_sent' => 0, 'signals_covered' => 0];
        }

        $grouped = $signals->groupBy('type');
        $groups = 0;
        $messagesSent = 0;
        $covered = 0;

        // Emit known types in a stable order first.
        foreach ($this->typeOrder as $type) {
            $typeSignals = $grouped->get($type) ?? collect();
            if ($typeSignals->isEmpty()) {
                continue;
            }

            $groups++;
            $covered += $typeSignals->count();

            if ($this->sendText($this->formatter->formatActiveSummary($type, $typeSignals))) {
                $messagesSent++;
            }
        }

        // Emit any remaining / custom types last.
        foreach ($grouped as $type => $typeSignals) {
            if (in_array($type, $this->typeOrder, true)) {
                continue;
            }

            $groups++;
            $covered += $typeSignals->count();

            if ($this->sendText($this->formatter->formatActiveSummary($type, $typeSignals))) {
                $messagesSent++;
            }
        }

        return [
            'groups' => $groups,
            'messages_sent' => $messagesSent,
            'signals_covered' => $covered,
        ];
    }

    /**
     * Send a simple test message to verify the bot configuration.
     */
    public function sendTestMessage(string $text = '✅ Telegram bot connected successfully.'): bool
    {
        return $this->sendText($text, 'HTML');
    }

    /**
     * Call Telegram's getChat API to verify the bot can access
     * the configured chat.
     *
     * @return array{ok: bool, status: int|null, error: string|null, chat: array|null, raw: array}
     */
    public function getChatInfo(): array
    {
        $result = [
            'ok' => false,
            'status' => null,
            'error' => null,
            'chat' => null,
            'raw' => [],
        ];

        if (!$this->botToken) {
            $result['error'] = 'Bot token is not configured. Set TELEGRAM_BOT_TOKEN in .env';
            return $result;
        }

        if (!$this->chatId) {
            $result['error'] = 'Chat ID is not configured. Set TELEGRAM_CHAT_ID in .env';
            return $result;
        }

        $url = "{$this->apiUrl}/bot{$this->botToken}/getChat";

        try {
            $response = Http::timeout($this->timeout)->post($url, [
                'chat_id' => $this->chatId,
            ]);

            $result['status'] = $response->status();
            $result['raw'] = $response->json() ?? [];

            if ($response->successful() && ($response->json('ok') ?? false)) {
                $result['ok'] = true;
                $result['chat'] = $response->json('result') ?? [];
            } else {
                $result['error'] = $response->json('description') ?? $response->body();
            }
        } catch (\Throwable $e) {
            $result['status'] = 0;
            $result['error'] = $e->getMessage();
        }

        return $result;
    }

    /**
     * Validate the full Telegram configuration:
     *  - Bot token format and validity (via getMe)
     *  - Chat accessibility (via getChat)
     *
     * @return array{token_valid: bool, chat_valid: bool, bot: array|null, chat: array|null, error: string|null, raw_response: array}
     */
    public function validateConfig(): array
    {
        $result = [
            'token_valid' => false,
            'chat_valid' => false,
            'bot' => null,
            'chat' => null,
            'error' => null,
            'raw_response' => [],
        ];

        if (!$this->botToken) {
            $result['error'] = 'Bot token is not configured. Set TELEGRAM_BOT_TOKEN in .env';
            return $result;
        }

        // Step 1: Verify the bot token is valid via getMe.
        $meUrl = "{$this->apiUrl}/bot{$this->botToken}/getMe";
        try {
            $meResponse = Http::timeout($this->timeout)->get($meUrl);

            $result['raw_response']['getme'] = $meResponse->json() ?? [];

            if ($meResponse->successful() && ($meResponse->json('ok') ?? false)) {
                $result['token_valid'] = true;
                $result['bot'] = $meResponse->json('result') ?? [];
            } else {
                $desc = $meResponse->json('description') ?? $meResponse->body();
                $result['error'] = "Bot token is invalid or expired: {$desc}";
                return $result;
            }
        } catch (\Throwable $e) {
            $result['error'] = "Unable to contact Telegram API: " . $e->getMessage();
            return $result;
        }

        // Step 2: Verify the bot can access the configured chat via getChat.
        $chatResult = $this->getChatInfo();
        $result['raw_response']['getchat'] = $chatResult['raw'];

        if ($chatResult['ok']) {
            $result['chat_valid'] = true;
            $result['chat'] = $chatResult['chat'];
        } else {
            $result['error'] = $chatResult['error'] ?? 'Unable to access the configured chat.';
        }

        return $result;
    }

    /**
     * Log a detailed persistent error with actionable hints.
     */
    protected function logPersistentError(string $description, int $status): void
    {
        $hint = $this->getPersistentErrorHint($description, $status);

        Log::warning("Telegram persistent error detected — further sends will be skipped.", [
            'chat_id' => $this->chatId,
            'bot_token' => $this->maskToken($this->botToken),
            'status' => $status,
            'description' => $description,
            'hint' => $hint,
        ]);
    }

    /**
     * Provide an actionable hint for a persistent error.
     */
    protected function getPersistentErrorHint(string $description, int $status): string
    {
        $desc = strtolower($description);

        if (str_contains($desc, 'chat not found')) {
            return "The TELEGRAM_CHAT_ID '{$this->chatId}' is incorrect, or the bot has not been added to this group/channel. "
                 . "Add the bot as a member of the target group/channel and verify the chat_id. Run 'php artisan telegram:validate' for diagnostics.";
        }

        if (str_contains($desc, 'unauthorized') || str_contains($desc, 'token')) {
            return "The TELEGRAM_BOT_TOKEN is invalid or expired. Create a new bot via @BotFather and update TELEGRAM_BOT_TOKEN in .env. Run 'php artisan telegram:validate' for diagnostics.";
        }

        if (str_contains($desc, 'blocked') || str_contains($desc, 'forbidden')) {
            return "The bot has been blocked or lacks permission to send messages to this chat. Check bot permissions and group/channel settings.";
        }

        if ($status === 404) {
            return "The Telegram API endpoint was not found. Check TELEGRAM_API_URL in .env; it should typically be https://api.telegram.org";
        }

        return "Run 'php artisan telegram:validate' for a detailed configuration check.";
    }

    /**
     * Mask the bot token for safe logging (shows only the first 6 chars).
     */
    protected function maskToken(?string $token): ?string
    {
        if (!$token) {
            return null;
        }

        $visible = substr($token, 0, 6);
        return $visible . str_repeat('●', 8);
    }
}
