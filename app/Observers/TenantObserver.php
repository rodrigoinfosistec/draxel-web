<?php

namespace App\Observers;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class TenantObserver
{
    public function created(Tenant $tenant): void
    {
        $host = parse_url(config('app.url'), PHP_URL_HOST);

        $email = "super@{$tenant->slug}.{$host}";

        User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Super Usuário',
            'email' => $email,
            'email_verified_at' => now(),
            'password' => Hash::make("senha123"),
            '' => false
        ]);
    }
}
