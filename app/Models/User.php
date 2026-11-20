<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Models\Vehicle;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Laravel\Sanctum\HasApiTokens;
use Laravel\Sanctum\PersonalAccessToken;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'phonenumber',
        'password',
        'carplate',
        'is_admin',
    ];

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
            'password' => 'hashed',
            'is_admin' => 'boolean',
        ];
    }
     public function vehicles()
    {
        return $this->hasMany(Vehicle::class);
    }

    /** Phones of this customer that can receive push notifications. */
    public function deviceTokens()
    {
        return $this->hasMany(DeviceToken::class);
    }

    /**
     * Adds what we know about the customer's app use (read-only):
     *  - app_last_signin / app_last_api: from their app logins (Sanctum). The API only
     *    knows who is calling on signed-in endpoints (profile, my-vehicles, ...), and a
     *    logout deletes that login, so this is "last sign-in or account activity".
     *  - app_last_opened: last time the app registered the phone (new app version,
     *    on every app start while signed in).
     */
    public function scopeWithAppActivity(Builder $query): Builder
    {
        if ($query->getQuery()->columns === null) {
            $query->select($query->getModel()->getTable() . '.*');
        }

        $logins = fn (string $column) => PersonalAccessToken::query()
            ->selectRaw("max({$column})")
            ->whereColumn('tokenable_id', 'users.id')
            ->where('tokenable_type', $this->getMorphClass());

        return $query
            ->addSelect([
                'app_last_signin' => $logins('created_at'),
                'app_last_api' => $logins('last_used_at'),
                'app_last_opened' => DeviceToken::query()
                    ->selectRaw('max(last_used_at)')
                    ->whereColumn('user_id', 'users.id'),
            ])
            ->withExists('deviceTokens');
    }

    /** Latest sign of app use, or null if the customer never signed in to the app. */
    public function lastSeenInApp(): ?Carbon
    {
        return collect([$this->app_last_signin, $this->app_last_api, $this->app_last_opened])
            ->filter()
            ->map(fn ($value) => Carbon::parse($value))
            ->sortDesc()
            ->first();
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return (bool) $this->is_admin;
    }
    
}
