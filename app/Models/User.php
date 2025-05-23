<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class User extends Authenticatable implements HasMedia
{
    use HasApiTokens, HasFactory, Notifiable, InteractsWithMedia, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $guarded = ['id', 'created_at', 'updated_at'];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'security_pin' => 'hashed',
        'password_changed_at' => 'datetime',
    ];

    public function setReferralId(): void
    {
        $characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $randomString = 'LAT';

        $length = 10 - strlen($randomString); // Remaining length after 'LAT'

        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, strlen($characters) - 1)];
        }

        $this->referral_code = $randomString;
        $this->save();
    }

    public function getChildrenIds(): array
    {
        return User::query()->where('hierarchyList', 'like', '%-' . $this->id . '-%')
            ->pluck('id')
            ->toArray();
    }

    public function getFirstLeader()
    {
        $first_leader = null;

        $upline = explode("-", substr($this->hierarchyList, 1, -1));
        $count = count($upline) - 1;

        // Check if there are elements in $upline before accessing
        if ($count >= 0) {
            while ($count >= 0) {
                $user = User::find($upline[$count]);
                if (!empty($user->leader_status) && $user->leader_status == 1) {
                    $first_leader = $user;
                    break; // Found the first leader, exit the loop
                }
                $count--;
            }
        }
        return $first_leader;
    }

    public function getTopLeader()
    {
        $top_leader = User::find(7);

        $upline = explode("-",substr($this->hierarchyList, 1, -1));
        $count = count($upline)-1;
        if ($count > 0) {
            while ($count > -1) {
                $user = User::find($upline[$count]);
                if (!empty($user->leader_status) && $user->leader_status) {
                    $top_leader = $user;
                }
                $count--;
            }
        }
        return $top_leader;
    }

    public function tradingAccounts(): HasMany
    {
        return $this->hasMany(TradingAccount::class, 'user_id', 'id');
    }

    public function tradingUsers(): HasMany
    {
        return $this->hasMany(TradingUser::class, 'user_id', 'id');
    }

    public function masterAccounts(): HasMany
    {
        return $this->hasMany(Master::class, 'user_id', 'id');
    }

    public function upline(): BelongsTo
    {
        return $this->belongsTo(User::class, 'upline_id', 'id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(User::class, 'upline_id', 'id');
    }

    public function wallets(): HasMany
    {
        return $this->hasMany(Wallet::class, 'user_id', 'id');
    }

    public function rank(): HasOne
    {
        return $this->hasOne(SettingRank::class, 'id', 'display_rank_id');
    }

    public function userCountry(): HasOne
    {
        return $this->hasOne(Country::class, 'id', 'country');
    }

    public function payment_accounts(): HasMany
    {
        return $this->hasMany(PaymentAccount::class, 'user_id', 'id');
    }

    public function active_copy_trade(): HasMany
    {
        return $this->hasMany(SubscriptionBatch::class, 'user_id', 'id')->where('status', 'Active');
    }

    public function active_pamm(): HasMany
    {
        return $this->hasMany(PammSubscription::class, 'user_id', 'id')
            ->where('status', 'Active')
            ->whereHas('master', function ($query) {
                $query->where('involves_world_pool', true);
            });
    }
}
