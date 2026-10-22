<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Log;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * Whether the user's email address has been verified.
     */
    public function hasVerifiedEmail(): bool
    {
        return !is_null($this->email_verified_at);
    }

    /**
     * Mark the user's email address as verified.
     */
    public function markEmailAsVerified(): bool
    {
        return $this->forceFill([
            'email_verified_at' => $this->freshTimestamp(),
        ])->save();
    }

/**
     * Available user account levels.
     */
    public const LEVEL_FREE = 'free';
    public const LEVEL_STARTER = 'starter';
    public const LEVEL_GROWTH = 'growth';
    public const LEVEL_SUMMIT = 'summit';
    public const LEVEL_APEX = 'apex';

    /**
     * Level configuration — key => display label.
     */
    public const LEVELS = [
        self::LEVEL_FREE => 'Free Plan',
        self::LEVEL_STARTER => 'Starter',
        self::LEVEL_GROWTH => 'Growth',
        self::LEVEL_SUMMIT => 'Summit',
        self::LEVEL_APEX => 'Apex',
    ];

    protected static function booted()
    {
        static::creating(function ($user) {
            if (!$user->Nex_id) {
                do {
                    $id = 'LXP' . str_pad(mt_rand(0, 9999), 4, '0', STR_PAD_LEFT);
                } while (static::where('Nex_id', $id)->exists());
                $user->Nex_id = $id;
            }

            // Auto-generate a unique username if none is provided
            if (!$user->username) {
                $user->username = static::generateUniqueUsername($user->email ?: $user->name);
            }
        });
    }

    /**
     * Generate a unique username from a base string (e.g., email or name).
     */
    public static function generateUniqueUsername(string $base): string
    {
        // Extract the part before @ for email, or sanitize the name
        $baseName = strtolower(trim(preg_replace('/[^a-zA-Z0-9_-]/', '', explode('@', $base)[0])));
        $baseName = substr($baseName, 0, 40); // Keep room for suffix

        // Try the base name first
        if ($baseName && !static::where('username', $baseName)->exists()) {
            return $baseName;
        }

        // Append random suffix until unique
        do {
            $suffix = str_pad(mt_rand(0, 9999), 4, '0', STR_PAD_LEFT);
            $username = $baseName ? "{$baseName}_{$suffix}" : "user_{$suffix}";
        } while (static::where('username', $username)->exists());

        return $username;
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'Nex_id',
        'referred_by',
        'referral_amount',
        'name',
        'username',
        'email',
        'phone',
        'password',
        'google_id',
        'apple_id',
        'avatar',
        'role',
        'level',
        'balance',
        'total_profit',
        'total_invested',
        'withdrawal_date',
        'bank_name',
        'bank_account_holder',
        'bank_account_number',
        'bank_routing_number',
        'account_type',
        'email_verification_code',
        'email_verification_expires_at',
        'password_reset_otp',
        'otp_expires_at',
        'last_login_bonus_at',
    ];

    /**
     * Daily login bonus amount in dollars.
     */
    public const DAILY_LOGIN_BONUS = 0.50;

    /**
     * The attributes that should be appended to the model's array form.
     *
     * @var array
     */
    protected $appends = ['avatar_url', 'level_label'];

public function getLevelLabelAttribute()
    {
        if (!$this->level) {
            return 'Free Plan';
        }
        return self::LEVELS[$this->level] ?? ucfirst((string) $this->level);
    }

    /**
     * Whether the user has an active (paid) plan.
     *
     * Free-plan users (or users with no level set) have no plan and cannot
     * earn free points such as the daily login bonus.
     */
    public function hasPlan(): bool
    {
        return !empty($this->level) && $this->level !== self::LEVEL_FREE;
    }

    /**
     * Check if the user is at the given level.
     */
    public function isLevel(string $level): bool
    {
        return $this->level === $level;
    }

    public function getAvatarUrlAttribute()
    {
        if (!$this->avatar) {
            return null;
        }

        if (filter_var($this->avatar, FILTER_VALIDATE_URL)) {
            return $this->avatar;
        }

        return asset('storage/' . $this->avatar);
    }

    public function investments()
    {
        return $this->hasMany(Investment::class);
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    public function withdrawals()
    {
        return $this->hasMany(Withdrawal::class);
    }

    public function referrer()
    {
        return $this->belongsTo(User::class, 'referred_by');
    }

    public function referrals()
    {
        return $this->hasMany(User::class, 'referred_by');
    }

/**
     * Referral signup bonus amount (USD) paid to the referrer when a new user
     * registers using their referral link/code. This is a flat amount and is
     * granted regardless of whether the referred user makes a deposit.
     */
public const REFERRAL_BONUS = 0.20;

    /**
     * Credit the flat referral signup bonus to this user's sponsor.
     *
     * This is called once at signup when the new user registered with a
     * referral code. A dedup guard ensures each referred signup credits the
     * sponsor only once.
     *
     * @return bool True if the bonus was credited, false otherwise.
     */
    public function creditReferralSignupBonus(): bool
    {
        // Bonus credited only if this user has a sponsor (referred_by)
        if (!$this->referred_by) {
            Log::warning('Referral signup bonus skipped: missing referred_by', [
                'user_id' => $this->id,
            ]);
            return false;
        }

        $sponsor = $this->referrer;
        if (!$sponsor) {
            Log::warning('Referral signup bonus skipped: sponsor not found', [
                'user_id' => $this->id,
                'referred_by' => $this->referred_by,
            ]);
            return false;
        }

        // Dedup guard: only credit this referred signup once.
        $alreadyCredited = Transaction::query()
            ->where('user_id', $sponsor->id)
            ->where('type', 'referral_bonus')
            ->where('status', 'completed')
            ->where('description', 'Referral bonus from ' . $this->id . ' (signup)')
            ->exists();

        if ($alreadyCredited) {
            Log::info('Referral signup bonus already credited for signup', [
                'sponsor_id' => $sponsor->id,
                'referred_user_id' => $this->id,
            ]);
            return false;
        }

        $bonusAmount = self::REFERRAL_BONUS;

        // Persist total referral bonuses credited to the sponsor.
        $sponsor->increment('referral_amount', $bonusAmount);

        $sponsor->increment('balance', $bonusAmount);

        Transaction::create([
            'user_id' => $sponsor->id,
            'type' => 'referral_bonus',
            'amount' => $bonusAmount,
            'status' => 'completed',
            'method' => 'System',
            // Include referred user id in description so it can be checked reliably.
            'description' => 'Referral bonus from ' . $this->id . ' (signup)',
        ]);

        return true;
    }

    /**
     * Credit the daily login bonus to the user (once per day).
     *
     * This is only triggered on an actual login. If the user does not log in,
     * no bonus is granted.
     *
     * The operation is wrapped in a DB transaction with a row lock so that
     * concurrent login requests cannot both pass the "claimed today" guard
     * and double-credit the bonus.
     *
     * @return bool True if the bonus was credited, false if already claimed today.
     */
public function creditDailyLoginBonus(): bool
    {
        // Free-plan users (or users with no plan) cannot earn free points.
        if (!$this->hasPlan()) {
            Log::info('Daily login bonus skipped: user has no paid plan', [
                'user_id' => $this->id,
                'level' => $this->level,
            ]);
            return false;
        }

        $amount = self::DAILY_LOGIN_BONUS;

        try {
            return \Illuminate\Support\Facades\DB::transaction(function () use ($amount) {
                // Lock the user row for the duration of this transaction so that
                // concurrent login requests serialize on the same record.
                $locked = static::whereKey($this->id)->lockForUpdate()->first();

                if (!$locked) {
                    return false;
                }

                // Only pay once per calendar day. The cast on last_login_bonus_at
                // guarantees this is a Carbon instance (never a plain string).
                if ($locked->last_login_bonus_at && $locked->last_login_bonus_at->isToday()) {
                    return false;
                }

                $locked->increment('balance', $amount);

                Transaction::create([
                    'user_id' => $locked->id,
                    'type' => 'daily_login_bonus',
                    'amount' => $amount,
                    'status' => 'completed',
                    'method' => 'System',
                    'description' => 'Daily login bonus',
                ]);

                $locked->update(['last_login_bonus_at' => now()]);

                // Send notification email (best-effort; never blocks login).
                try {
                    \Illuminate\Support\Facades\Mail::to($locked->email)->send(
                        new \App\Mail\DailyLoginBonusMail($locked, $amount, $locked->fresh()->balance)
                    );
                } catch (\Exception $e) {
                    Log::warning('Daily login bonus email failed to send', [
                        'user_id' => $locked->id,
                        'error' => $e->getMessage(),
                    ]);
                }

                return true;
            });
        } catch (\Exception $e) {
            Log::error('Daily login bonus credit failed', [
                'user_id' => $this->id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Whether the daily login bonus has already been claimed today.
     */
    public function hasClaimedDailyLoginBonusToday(): bool
    {
        return $this->last_login_bonus_at && $this->last_login_bonus_at->isToday();
    }

    /**
     * The amount of the daily login bonus (dollars).
     */
    public function dailyLoginBonusAmount(): float
    {
        return self::DAILY_LOGIN_BONUS;
    }

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'email_verification_expires_at' => 'datetime',
            'otp_expires_at' => 'datetime',
            'last_login_bonus_at' => 'datetime',
            'password' => 'hashed',
            'balance' => 'float',
            'total_profit' => 'float',
            'total_invested' => 'float',
        ];
    }
}
