<?php

namespace App\Models;

use App\Models\Pivots\ChannelUserPivot;
use App\Models\Pivots\ModelHasRoleTeam;
use App\Repository\RoleRepository;
use App\Repository\TeamRepository;
use Filament\Auth\MultiFactor\App\Contracts\HasAppAuthentication;
use Filament\Auth\MultiFactor\App\Contracts\HasAppAuthenticationRecovery;
use Filament\Auth\MultiFactor\Email\Contracts\HasEmailAuthentication;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasDefaultTenant;
use Filament\Models\Contracts\HasTenants;
use Filament\Panel;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Contracts\Translation\HasLocalePreference;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;
use Laravel\Passport\Contracts\OAuthenticatable;
use N3XT0R\FilamentPassportUi\Models\Concerns\HasPassportScopeGrantsInterface;
use N3XT0R\FilamentPassportUi\Models\Traits\HasApiTokensTrait;
use N3XT0R\FilamentPassportUi\Models\Traits\HasPassportScopeGrantsTrait;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements FilamentUser, HasAppAuthentication, HasAppAuthenticationRecovery,
                                              HasEmailAuthentication, MustVerifyEmail, HasTenants, HasDefaultTenant,
                                              HasLocalePreference, OAuthenticatable, HasPassportScopeGrantsInterface
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasRoles;
    use HasApiTokensTrait;
    use HasPassportScopeGrantsTrait;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'submitted_name',
        'email',
        'password',
        'onboarding_completed',
        'terms_accepted_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'app_authentication_secret',
        'app_authentication_recovery_codes',
    ];


    protected $appends = [
        'display_name',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'terms_accepted_at' => 'datetime',
    ];

    protected function scopeIsOwnTeam(Builder $query, ?self $user = null): Builder
    {
        return $query->whereHas(
            'teams',
            fn($q) => $q->where('owner_id', $user->getKey() ?? $this->getKey())
        );
    }

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
            'app_authentication_secret' => 'encrypted',
            'app_authentication_recovery_codes' => 'encrypted:array',
            'has_email_authentication' => 'boolean',
            'onboarding_completed' => 'boolean',
        ];
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return app(RoleRepository::class)->canAccessPanel($this, $panel);
    }

    public function getAppAuthenticationSecret(): ?string
    {
        // This method should return the user's saved app authentication secret.

        return $this->app_authentication_secret;
    }

    public function saveAppAuthenticationSecret(?string $secret): void
    {
        // This method should save the user's app authentication secret.

        $this->app_authentication_secret = $secret;
        $this->save();
    }

    public function getAppAuthenticationHolderName(): string
    {
        // In a user's authentication app, each account can be represented by a "holder name".
        // If the user has multiple accounts in your app, it might be a good idea to use
        // their email address as then they are still uniquely identifiable.

        return $this->email;
    }

    /**
     * @return ?array<string>
     */
    public function getAppAuthenticationRecoveryCodes(): ?array
    {
        // This method should return the user's saved app authentication recovery codes.

        return $this->app_authentication_recovery_codes;
    }

    /**
     * @param array<string> | null $codes
     */
    public function saveAppAuthenticationRecoveryCodes(?array $codes): void
    {
        // This method should save the user's app authentication recovery codes.

        $this->app_authentication_recovery_codes = $codes;
        $this->save();
    }

    public function hasEmailAuthentication(): bool
    {
        // This method should return true if the user has enabled email authentication.

        return ($this->has_email_authentication ?? false);
    }

    public function toggleEmailAuthentication(bool $condition): void
    {
        // This method should save whether or not the user has enabled email authentication.

        $this->has_email_authentication = $condition;
        $this->save();
    }

    protected function displayName(): Attribute
    {
        return Attribute::make(
            get: static fn($value, array $attributes) => $attributes['submitted_name'] ?? $attributes['name'] ?? null,
        );
    }

    public function teams(): BelongsToMany
    {
        return $this->belongsToMany(
            Team::class,
            'team_user'
        )->withTimestamps();
    }

    public function ownTeams(): BelongsToMany
    {
        return $this->teams()->where('owner_id', $this->getKey());
    }

    public function getTenants(Panel $panel): Collection
    {
        return $this->teams()->get();
    }

    public function teamRoles(): MorphToMany|BelongsToMany
    {
        $relation = $this->morphToMany(
            \Spatie\Permission\Models\Role::class,
            'model',
            'model_has_roles'
        )
            ->using(ModelHasRoleTeam::class);

        if (config('permission.teams')) {
            $relation->withPivot('team_id');
        }

        return $relation;
    }


    public function mailConfigs(): HasMany
    {
        return $this->hasMany(UserMailConfig::class);
    }

    public function canAccessTenant(Team|Model $tenant): bool
    {
        return app(TeamRepository::class)->canAccessTeam($this, $tenant);
    }

    public function getDefaultTenant(Panel $panel): ?Model
    {
        return app(TeamRepository::class)->getDefaultTeamForUser($this);
    }

    public function channelApplications(): HasMany
    {
        return $this->hasMany(ChannelApplication::class);
    }

    public function channels(): BelongsToMany
    {
        return $this->belongsToMany(Channel::class, 'channel_user')
            ->using(ChannelUserPivot::class)
            ->withPivot(['is_user_verified'])
            ->withTimestamps();
    }

    public function preferredLocale(): string
    {
        return config('app.locale');
    }
}
