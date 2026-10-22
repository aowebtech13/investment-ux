<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TradingSignal extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'symbol',
        'type',
        'action',
        'entry_price_min',
        'entry_price_max',
        'stop_loss',
        'take_profit_1',
        'take_profit_2',
        'take_profit_3',
        'status',
        'signal_time',
        'expiry_time',
        'confidence_level',
        'analysis_summary',
        'technical_indicators',
        'risk_notes',
        'created_by',
        // AI Signal fields
        'direction',
        'is_otc',
        'signal_type',
        'entry_window_minutes',
        'confidence_percent',
        'martingale_plan',
        'telegram_sent_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'entry_price_min' => 'float',
            'entry_price_max' => 'float',
            'stop_loss' => 'float',
            'take_profit_1' => 'float',
            'take_profit_2' => 'float',
            'take_profit_3' => 'float',
            'signal_time' => 'datetime',
            'expiry_time' => 'datetime',
            'is_otc' => 'boolean',
            'entry_window_minutes' => 'integer',
            'confidence_percent' => 'float',
            'martingale_plan' => 'array',
            'telegram_sent_at' => 'datetime',
        ];
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active')
            ->where(function ($q) {
                $q->whereNull('expiry_time')
                    ->orWhere('expiry_time', '>', now());
            });
    }

    public function scopeOfType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeOfSymbol($query, $symbol)
    {
        return $query->where('symbol', strtoupper($symbol));
    }

public static function autoExpire()
    {
        return static::where('status', 'active')
            ->whereNotNull('expiry_time')
            ->where('expiry_time', '<=', now())
            ->update(['status' => 'expired']);
    }

    /**
     * Scope a query to only include signals that have not yet been sent to Telegram.
     */
    public function scopeNotSentToTelegram($query)
    {
        return $query->whereNull('telegram_sent_at');
    }

    /**
     * Mark this signal as sent to Telegram.
     */
    public function markTelegramSent(): void
    {
        $this->forceFill(['telegram_sent_at' => now()])->save();
    }

    public function getAssetNameAttribute(): string
    {
        $assets = [
            // Forex Majors
            'EURUSD' => 'Euro / US Dollar',
            'GBPUSD' => 'British Pound / US Dollar',
            'USDJPY' => 'US Dollar / Japanese Yen',
            'USDCHF' => 'US Dollar / Swiss Franc',
            'AUDUSD' => 'Australian Dollar / US Dollar',
            'USDCAD' => 'US Dollar / Canadian Dollar',
            'NZDUSD' => 'New Zealand Dollar / US Dollar',
            // Forex Minors & Crosses
            'EURGBP' => 'Euro / British Pound',
            'EURJPY' => 'Euro / Japanese Yen',
            'GBPJPY' => 'British Pound / Japanese Yen',
            'EURAUD' => 'Euro / Australian Dollar',
            'GBPAUD' => 'British Pound / Australian Dollar',
            'EURCHF' => 'Euro / Swiss Franc',
            'GBPCHF' => 'British Pound / Swiss Franc',
            // Expanded Forex
            'USDNOK' => 'US Dollar / Norwegian Krone',
            'USDPLN' => 'US Dollar / Polish Zloty',
            'USDSEK' => 'US Dollar / Swedish Krona',
            'USTRY' => 'US Dollar / Turkish Lira',
            'USDZAR' => 'US Dollar / South African Rand',
            'AUDJPY' => 'Australian Dollar / Japanese Yen',
            'CADCHF' => 'Canadian Dollar / Swiss Franc',
            'CHFJPY' => 'Swiss Franc / Japanese Yen',
            'CHFNOK' => 'Swiss Franc / Norwegian Krone',
            // Commodities
            'XAUUSD' => 'Gold / US Dollar',
            'XAGUSD' => 'Silver / US Dollar',
            'XPTUSD' => 'Platinum / US Dollar',
            'XPDUSD' => 'Palladium / US Dollar',
            'USOIL' => 'US Oil (WTI)',
            'UKOIL' => 'Brent Oil',
            'NATGAS' => 'Natural Gas',
            'USOUSD' => 'USO (US Oil ETF)',
            'GOLDSILVER' => 'Gold / Silver',
            'PALLADIUM' => 'Palladium',
            'PLATINUM' => 'Platinum',
            // Indices
            'US30' => 'Dow Jones (US30)',
            'SPX500' => 'S&P 500',
            'NAS100' => 'Nasdaq 100',
            'UK100' => 'FTSE 100',
            'GER40' => 'DAX 40',
            'JPN225' => 'Nikkei 225',
            'AUS200' => 'ASX 200',
            'VIX' => 'Volatility Index',
            'US2000' => 'US 2000 (Russell 2000)',
            // Crypto
            'BTCUSD' => 'Bitcoin / US Dollar',
            'ETHUSD' => 'Ethereum / US Dollar',
            'XRPUSD' => 'Ripple / US Dollar',
            'LTCUSD' => 'Litecoin / US Dollar',
            'ADAUSD' => 'Cardano / US Dollar',
            'DOTUSD' => 'Polkadot / US Dollar',
            'SOLUSD' => 'Solana / US Dollar',
            'SHIBUSD' => 'Shiba Inu / US Dollar',
            'ANTROPIC' => 'Antropic',
            'TRUMP' => 'Trump Coin',
            'ONDO' => 'Ondo',
            'PEPE' => 'Pepe',
            'SEI' => 'Sei',
            // Expanded Crypto
            'RAYDIUM' => 'Raydium',
            'FET' => 'Fetch.ai',
            'BCHUSD' => 'Bitcoin Cash / US Dollar',
            'DOGWF' => 'Dogwifhat',
            'WORLDCOIN' => 'Worldcoin',
            'TRONUSD' => 'TRON / US Dollar',
            // Stocks & Equities
            'NIKE' => 'Nike, Inc.',
            'CITI' => 'Citigroup, Inc.',
            'WDC' => 'Western Digital Corp.',
            'AIG' => 'American International Group',
            'BABA' => 'Alibaba Group Holdings',
            'AMZN' => 'Amazon.com, Inc.',
            'KO' => 'Coca-Cola Company',
            'GS' => 'Goldman Sachs Group',
            'JPM' => 'JPMorgan Chase & Co.',
            'MCD' => "McDonald's Corporation",
            'MS' => 'Morgan Stanley',
            'MSFT' => 'Microsoft Corporation',
            'SNAP' => 'Snap Inc.',
            'AIRLINES' => 'Airlines',
            'CANNABIS' => 'Cannabis',
            'CASINO' => 'Casino',
            'MAG7' => 'Magnificent 7',
            'URANIUM' => 'Uranium',
        ];

        return $assets[strtoupper($this->symbol)] ?? $this->symbol;
    }

    
    public function scopeOfSignalType($query, $signalType)
    {
        return $query->where('signal_type', $signalType);
    }

    /**
     * Scope a query to only include OTC signals.
     */
    public function scopeOtc($query)
    {
        return $query->where('is_otc', true);
    }

    /**
     * Scope a query to only include non-OTC signals.
     */
    public function scopeNotOtc($query)
    {
        return $query->where('is_otc', false);
    }

    
    public function getAiConfidenceAttribute(): ?string
    {
        return $this->confidence_percent !== null
            ? number_format($this->confidence_percent, 1) . '%'
            : null;
    }

   
    public function isAiSignal(): bool
    {
        return str_starts_with($this->signal_type, 'ai_');
    }

    
    public function getMartingalePlanArrayAttribute(): ?array
    {
        return $this->martingale_plan;
    }
}
