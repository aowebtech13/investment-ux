<?php

namespace App\Console\Commands;

use App\Services\TelegramService;
use Illuminate\Console\Command;

/**
 * Artisan command: telegram:validate
 *
 * Performs a full diagnostic check of the Telegram configuration:
 *   1. Verifies local .env values (enabled, bot token, chat id).
 *   2. Validates the bot token via Telegram's getMe API.
 *   3. Validates chat accessibility via Telegram's getChat API.
 *   4. Optionally sends a test message to confirm end-to-end delivery.
 *
 * Usage:
 *   php artisan telegram:validate           # full diagnostic + test message
 *   php artisan telegram:validate --no-test # skip the live test message
 */
class TelegramValidateCommand extends Command
{
    protected $signature = 'telegram:validate
        {--no-test : Skip sending a live test message to Telegram}';

    protected $description = 'Validate Telegram bot token and chat configuration with actionable diagnostics';

    public function handle(TelegramService $telegram): int
    {
        $this->info('Telegram Configuration Diagnostic');
        $this->line(str_repeat('-', 60));

        // --- Step 1: Local config check ---
        $this->line('');
        $this->info('Step 1: Local Configuration');

        $enabled = config('services.telegram.enabled');
        $botToken = config('services.telegram.bot_token');
        $chatId = config('services.telegram.chat_id');
        $parseMode = config('services.telegram.parse_mode', 'HTML');
        $apiUrl = config('services.telegram.api_url', 'https://api.telegram.org');

        $this->line('  TELEGRAM_ENABLED   : ' . ($enabled ? '<fg=green>true</>' : '<fg=red>false</>'));
        $this->line('  TELEGRAM_BOT_TOKEN : ' . ($botToken ? '<fg=green>set</>' : '<fg=red>missing</>'));
        $this->line('  TELEGRAM_CHAT_ID   : ' . ($chatId ? '<fg=green>' . $chatId . '</>' : '<fg=red>missing</>'));
        $this->line('  TELEGRAM_PARSE_MODE: ' . $parseMode);
        $this->line('  TELEGRAM_API_URL   : ' . $apiUrl);

        if (!$enabled) {
            $this->error('  TELEGRAM_ENABLED is false. Set TELEGRAM_ENABLED=true in .env to enable sending.');
            return self::FAILURE;
        }

        if (!$botToken) {
            $this->error('  TELEGRAM_BOT_TOKEN is not set. Add it to .env.');
            return self::FAILURE;
        }

        if (!$chatId) {
            $this->error('  TELEGRAM_CHAT_ID is not set. Add it to .env.');
            return self::FAILURE;
        }

        // Validate bot token format (must contain a colon).
        if (!str_contains($botToken, ':')) {
            $this->error('  TELEGRAM_BOT_TOKEN looks malformed (expected format: 123456:ABC...).');
            return self::FAILURE;
        }

        // --- Step 2: Validate bot token via getMe ---
        $this->line('');
        $this->info('Step 2: Validating Bot Token (getMe)');

        $validation = $telegram->validateConfig();

        if (!empty($validation['token_valid'])) {
            $bot = $validation['bot'] ?? [];
            $botName = $bot['first_name'] ?? 'N/A';
            $botUsername = $bot['username'] ?? 'N/A';
            $botId = $bot['id'] ?? 'N/A';

            $this->line('  Bot First Name: <fg=green>' . $botName . '</>');
            $this->line('  Bot Username  : <fg=green>@' . $botUsername . '</>');
            $this->line('  Bot ID        : <fg=green>' . $botId . '</>');
            $this->line('  Bot token is valid.');
        } else {
            $this->error('  Bot token validation FAILED.');
            $this->error('  ' . ($validation['error'] ?? 'Unknown error'));
            $this->line('');
            $this->warn('  The TELEGRAM_BOT_TOKEN in your .env is invalid or expired.');
            $this->line('  Fix: Create a bot via @BotFather on Telegram, copy the token,');
            $this->line('  and set TELEGRAM_BOT_TOKEN in .env.');
            return self::FAILURE;
        }

        // --- Step 3: Validate chat accessibility via getChat ---
        $this->line('');
        $this->info('Step 3: Validating Chat Access (getChat)');

        $chatInfo = $telegram->getChatInfo();

        if (!empty($chatInfo['ok'])) {
            $chat = $chatInfo['chat'] ?? [];
            $chatType = $chat['type'] ?? 'unknown';

            $this->line('  Chat ID   : <fg=green>' . $chatId . '</>');
            $this->line('  Chat Type : <fg=green>' . $chatType . '</>');

            if (isset($chat['title'])) {
                $this->line('  Title     : <fg=green>' . $chat['title'] . '</>');
            }
            if (isset($chat['username'])) {
                $this->line('  Username  : <fg=green>@' . $chat['username'] . '</>');
            }
            if (isset($chat['first_name'])) {
                $firstName = $chat['first_name'];
                $lastName = $chat['last_name'] ?? '';
                $this->line('  Name      : <fg=green>' . trim($firstName . ' ' . $lastName) . '</>');
            }

            $this->line('  Bot can access this chat.');

            if ($chatType === 'private') {
                $this->line('');
                $this->warn('  This is a PRIVATE chat (1-on-1 with the bot).');
                $this->line('  If you intended to send to a group/channel, the TELEGRAM_CHAT_ID');
                $this->line('  is pointing to the wrong chat. Use the group/channel ID instead.');
            }
        } else {
            $this->error('  Chat access FAILED -- chat not found or bot lacks access.');
            $this->error('  ' . ($chatInfo['error'] ?? 'Unknown error'));
            $this->line('');
            $this->warn('  Root cause analysis for "chat not found":');
            $this->line('  +-------------------------------------------------------------+');
            $this->line('  | 1. The TELEGRAM_CHAT_ID is incorrect.');
            $this->line('  |    Get the correct ID by:');
            $this->line('  |    - Adding @userinfobot to your group -> it replies with the ID');
            $this->line('  |    - Or use @getmyid_bot to get the channel/group numeric ID');
            $this->line('  |    - For channels: use the channel username (e.g. @mychannel)');
            $this->line('  |');
            $this->line('  | 2. The bot has NOT been added to the group/channel.');
            $this->line('  |    - Go to your Telegram group/channel');
            $this->line('  |    - Add the bot as an administrator (or at least a member)');
            $this->line('  |    - The bot MUST be a member BEFORE it can receive messages');
            $this->line('  |');
            $this->line('  | 3. The bot was removed from the group/channel.');
            $this->line('  |    - Re-add the bot to the group/channel');
            $this->line('  |');
            $this->line('  | 4. The chat was deleted or made private.');
            $this->line('  |    - Verify the group/channel still exists and is accessible');
            $this->line('  +-------------------------------------------------------------+');
            return self::FAILURE;
        }

        // --- Step 4: Send a test message ---
        $this->line('');

        if (!$this->option('no-test')) {
            $this->info('Step 4: Sending Test Message');
            $this->line('  Sending test message to chat_id=' . $chatId . '...');

            $sent = $telegram->sendTestMessage();

            if ($sent) {
                $this->line('  Test message sent successfully! The bot can deliver to this chat.');
            } else {
                $this->error('  Test message FAILED to send.');
                $this->error('  Last error: ' . ($telegram->getLastError() ?? 'unknown'));
                $this->warn('  The bot may be admin-restricted in this group.');
                $this->line('  Ensure the bot has permission to send messages.');
                return self::FAILURE;
            }
        } else {
            $this->line('  (skipped -- --no-test flag set)');
        }

        // --- Summary ---
        $this->line('');
        $this->line(str_repeat('-', 60));
        $this->info('All checks passed! Telegram is correctly configured.');
        $this->line('  Signals will be delivered to the configured group/channel.');
        return self::SUCCESS;
    }
}