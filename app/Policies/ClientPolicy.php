<?php

namespace App\Policies;

use App\Models\Client;
use App\Models\User;

class ClientPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('clients.viewAny');
    }

    public function view(User $user, Client $client): bool
    {
        return $user->tenant_id === $client->tenant_id
            && $user->hasPermission('clients.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('clients.create');
    }

    public function update(User $user, Client $client): bool
    {
        return $user->tenant_id === $client->tenant_id
            && $user->hasPermission('clients.update');
    }

    public function delete(User $user, Client $client): bool
    {
        return $user->tenant_id === $client->tenant_id
            && $user->hasPermission('clients.delete');
    }
}
