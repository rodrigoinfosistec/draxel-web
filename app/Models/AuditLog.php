<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    protected $table = 'audit_logs';

    protected $fillable = [
        'tenant_id',
        'company_id',
        'user_id',
        'event',
        'subject_type',
        'subject_id',
        'route',
        'method',
        'ip',
        'user_agent',
        'properties',
    ];

    protected $casts = [
        'properties' => 'array',
    ];
}
