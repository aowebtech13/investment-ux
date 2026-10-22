# Setting Up Cron Jobs on cPanel — Nexora AI Trading Signals

This guide explains how to run the Laravel scheduler (and the AI signal generator)
on your cPanel server so signals are generated and delivered to Telegram on schedule.

---

## 1. What the scheduler runs

Your scheduler (`backend/routes/console.php`) currently runs these recurring jobs:

> **Telegram trade-call policy:** AI trade-call batches reach Telegram once per
> hour — a balanced batch of **4 trades** (one each of 1m/2m/3m/5m) at the top
> of every hour. The analysis is generated and the Telegram signal is sent
> **30 minutes before** the trade time (at HH:30 for an HH:00 trade).
> Standard market signals are used for the CRM dashboard only and are never
> broadcast to Telegram.

| Laravel command            | Frequency          | Purpose |
|----------------------------|--------------------|---------|
| `signals:fetch --no-telegram` | Every 4 hours    | Fetch market data & generate standard signals for the **dashboard only** (never sent to Telegram) |
| `signals:fetch --ai --limit=4 --force` | Hourly at **:30** (WAT) | Generate a balanced batch of **4 fresh AI trades** (one each of 1m/2m/3m/5m) for the next top-of-hour (HH:00) trade and dispatch them to Telegram — **the only source of trade calls** (1 batch/hour = 4 calls/hour). `--force` guarantees 4 new signals every run. Runs 30 min before trade time so the signal is delivered before the trade starts |
| `signals:send-telegram`    | Every 15 minutes  | Safety net that resends any **AI signal** that failed delivery in the batch (never broadcasts dashboard signals). Normally posts **0** messages because the batch already marks successful sends |
| `investments:process`      | Daily (midnight)   | Process investments |

> **Note:** `signals:sync-telegram` (the grouped active-signal summary) is
> intentionally **not scheduled** anymore. It used to post up to one summary
> message per asset type on top of the 4-signal batch, flooding the group.
> Removing it keeps Telegram output at exactly **4 trade signals per hour**.

You only need **ONE cron job** — a single minute-by-minute entry that runs
`php artisan schedule:run`. Laravel's scheduler then decides which of the above
commands to run at the right time.

---

## 2. Find your real server paths

On cPanel, the absolute path to your site is usually:

```
/home/<cPanel_username>/bakdvs1.nexxora-ai.com
```

> Your shell prompt shows you were in `bakdvs1.nexxora-ai.com`. Confirm your
> home path with:
> ```bash
> pwd
> ```
You'll use that full path in the cron command below.

---

## 3. Find the PHP binary path

On shared cPanel hosting, PHP is usually at one of these:

- `/usr/local/bin/php`
- `/usr/bin/php`
- `/usr/bin/php8.2` (or whichever version you run)

Find it by running in your terminal:
```bash
which php
```
or
```bash
/usr/local/bin/php -v
```

Use whichever path returns a version. This example uses `/usr/local/bin/php`
— **replace it with the path you found**.

---

## 4. Add the cron job in cPanel

1. Log in to **cPanel**.
2. Under the **Advanced** section, click **Cron Jobs**.
3. Scroll to **Add New Cron Job**.

### The single recommended cron job (scheduler)

Use these settings:

- **Common Settings:** `Once Per Minute (* * * * *)`
- **Command:**
  ```
  /usr/local/bin/php /home/<cPanel_username>/bakdvs1.nexxora-ai.com/artisan schedule:run >> /home/<cPanel_username>/bakdvs1.nexxora-ai.com/storage/logs/cron.log 2>&1
  ```

Example (replace the username and PHP path to match your server):
```
/usr/local/bin/php /home/nexxmsin/bakdvs1.nexxora-ai.com/artisan schedule:run >> /home/nexxmsin/bakdvs1.nexxora-ai.com/storage/logs/cron.log 2>&1
```

Click **Add New Cron Job**.

> That's it. This one job runs every minute and Laravel's scheduler triggers
> `signals:fetch --ai --limit=4 --force` hourly at HH:30 (WAT),
> `signals:send-telegram` every 15 minutes, etc. You do **not** need separate
> cron entries per command.

---

## 5. (Optional) Alternative — separate cron jobs

If you prefer to bypass Laravel's scheduler and run each command directly,
add these entries instead. **Do not add both this set AND the scheduler job** —
pick one approach.

| Schedule (cron) | Command |
|-----------------|---------|
| `30 * * * *`    | `/usr/local/bin/php /home/<user>/bakdvs1.nexxora-ai.com/artisan signals:fetch --ai --limit=4 --force >> .../storage/logs/ai.log 2>&1` |
| `* * * * *`     | `/usr/local/bin/php /home/<user>/bakdvs1.nexxora-ai.com/artisan signals:send-telegram >> .../storage/logs/send.log 2>&1` |
| `0 0 * * *`     | `/usr/local/bin/php /home/<user>/bakdvs1.nexxora-ai.com/artisan investments:process >> .../storage/logs/invest.log 2>&1` |

> **Note on the `30 * * * *` entry:** it runs at HH:30 each hour so the AI
> analysis is generated and the Telegram signal is sent 30 minutes before the
> top-of-hour (HH:00) trade. Do **not** add `signals:sync-telegram` — it was
> removed to prevent the grouped active-signal summary from flooding the group.

---

## 6. Before cron runs — deployment checklist

Run these **once** in your site directory before enabling cron so the app is ready:

```bash
cd /home/nexxmsin/bakdvs1.nexxora-ai.com

# 1. Install/refresh dependencies
composer install --no-dev --optimize-autoloader

# 2. Ensure .env has the Telegram values (already configured)
#    TELEGRAM_ENABLED=true, TELEGRAM_BOT_TOKEN=..., TELEGRAM_CHAT_ID=-100...

# 3. Run migrations (you already started this)
php artisan migrate --force

# 4. Cache config & routes for production
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 5. Clear any stale caches
php artisan config:clear
```

---

## 7. Verify it's working

After adding the cron job, wait a minute or two, then check:

```bash
# Check the cron log
tail -f /home/nexxmsin/bakdvs1.nexxora-ai.com/storage/logs/cron.log

# Or run the scheduler once manually to confirm it works
/usr/local/bin/php /home/nexxmsin/bakdvs1.nexxora-ai.com/artisan schedule:run

# Confirm AI signals are being generated
/usr/local/bin/php /home/nexxmsin/bakdvs1.nexxora-ai.com/artisan signals:fetch --ai --no-telegram

# Confirm Telegram delivery works
/usr/local/bin/php /home/nexxmsin/bakdvs1.nexxora-ai.com/artisan signals:send-telegram
```

You should see entries appearing in the Cron Job output and AI signals showing up
in your Telegram group.

---

## 8. Troubleshooting

- **"Command not found" / "No such file"** → your PHP path or site path is wrong.
  Double-check with `which php` and `pwd`.
- **Nothing appears in Telegram** → re-check `TELEGRAM_ENABLED=true`,
  `TELEGRAM_BOT_TOKEN`, and `TELEGRAM_CHAT_ID` (must include the `-100` prefix
  for a supergroup, e.g. `-1003731454838`).
- **Cron not firing** → confirm the "Common Settings" is `* * * * *` and that
  the command is one line (no line breaks).
- **Log files don't exist** → ensure the `storage/logs` directory is writable
  (`chmod -R 775 storage bootstrap/cache`).

---

## 9. Quick reference command summary

Paste this into your terminal (after editing the paths) to manually run the
core AI flow end-to-end:

```bash
cd /home/nexxmsin/bakdvs1.nexxora-ai.com \
  && /usr/local/bin/php artisan signals:fetch --ai \
  && /usr/local/bin/php artisan signals:send-telegram
