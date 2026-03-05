<?php

namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Fortify\TwoFactorAuthenticatable;

class User extends Authenticatable implements FilamentUser
{
    use Notifiable, TwoFactorAuthenticatable;

    protected $fillable = [
        'tenant_id',
        'default_company_id',
        'name',
        'email',
        'password',
        'is_admin',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_admin' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function companies()
    {
        return $this->belongsToMany(Company::class, 'user_companies')
            ->withTimestamps();
    }

    public function defaultCompany()
    {
        return $this->belongsTo(Company::class, 'default_company_id');
    }

    public function scopeActive(Builder $query)
    {
        return $query->where('is_active', true);
    }

    public function scopeInactive(Builder $query)
    {
        return $query->where('is_active', false);
    }

    public function scopeFromTenant(Builder $query, int $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    public function scopeAdmin(Builder $query)
    {
        return $query->where('is_admin', true);
    }

    public function scopeNoAdmin(Builder $query)
    {
        return $query->where('is_admin', false);
    }

    public function initials(): string
    {
        if (! $this->name) {
            return '';
        }

        return collect(
                preg_split('/\s+/', trim($this->name))
            )
            ->filter()
            ->reject(fn ($word) => in_array(
                Str::lower($word),
                ['de', 'da', 'do', 'dos', 'das']
            ))
            ->take(2)
            ->map(fn ($word) => mb_strtoupper(mb_substr($word, 0, 1)))
            ->implode('');
    }

    public function canAccessPanel(\Filament\Panel $panel): bool
    {
        if ($panel->getId() !== 'admin') {
            return true;
        }

        return (bool) $this->is_admin;
    }
}
