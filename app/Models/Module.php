<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Module extends Model
{
    protected $fillable = [
        'slug',
        'name',
        'description',
        'is_core',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_core' => 'boolean',
    ];

    public function tenants()
    {
        return $this->belongsToMany(
            Tenant::class,
            'tenant_modules'
        )->withPivot('is_active')
        ->withTimestamps();
    }
}
