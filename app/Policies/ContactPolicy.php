<?php

namespace App\Policies;

use App\Models\Contact;
use App\Models\User;

class ContactPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('contacts.viewAny');
    }

    public function view(User $user, Contact $contact): bool
    {
        return $user->tenant_id === $contact->tenant_id
            && $user->hasPermission('contacts.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('contacts.create');
    }

    public function update(User $user, Contact $contact): bool
    {
        return $user->tenant_id === $contact->tenant_id
            && $user->hasPermission('contacts.update');
    }

    public function delete(User $user, Contact $contact): bool
    {
        return $user->tenant_id === $contact->tenant_id
            && $user->hasPermission('contacts.delete');
    }
}
