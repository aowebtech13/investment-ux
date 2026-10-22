# Indicator Engine Upgrade — Task List

- [x] Research & install a reputable TA library (`geoffroy-pradier/php-trader`)
- [x] Create `backend/app/Services/TechnicalAnalysisService.php` (library-backed RSI/Stochastic + EMA/MACD/ATR/Bollinger)
- [x] Wire TechnicalAnalysisService into `TradingSignalGenerator::generateOnDemandSignal()`
- [x] Wire TechnicalAnalysisService into `FetchTradingSignals::analyzeAndGenerateSignal()` (Alpha Vantage real data path)
- [x] Replace `rand()`-based direction/confidence in `FetchTradingSignals::generateAiSignal()` (standalone indicator engine + confluence scoring)
- [x] `php -l` all changed files
- [x] Test run: `php artisan signals:fetch --ai --limit=2 --no-telegram` ✓ Generated EURUSD PUT 76%, GBPUSD CALL 68%

