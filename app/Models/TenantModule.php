<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TenantModule extends Model
{
    protected $fillable = [
        'tenant_id',
        'module_id',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
