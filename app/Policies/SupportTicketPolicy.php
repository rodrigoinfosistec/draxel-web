<?php

namespace App\Policies;

use App\Models\SupportTicket;
use App\Models\User;

class SupportTicketPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('support.viewAny');
    }

    public function view(User $user, SupportTicket $supportTicket): bool
    {
        if (! $user->hasPermission('support.view')) {
            return false;
        }

        if ($user->hasPermission('support.manageAll')) {
            return true;
        }

        return $supportTicket->created_by === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('support.create');
    }

    public function reply(User $user, SupportTicket $supportTicket): bool
    {
        if (! $user->hasPermission('support.reply')) {
            return false;
        }

        if ($user->hasPermission('support.manageAll')) {
            return true;
        }

        return $supportTicket->created_by === $user->id;
    }

    public function update(User $user, SupportTicket $supportTicket): bool
    {
        if (! $user->hasPermission('support.update')) {
            return false;
        }

        if ($user->hasPermission('support.manageAll')) {
            return true;
        }

        return $supportTicket->created_by === $user->id;
    }

    public function changeStatus(User $user, SupportTicket $supportTicket): bool
    {
        if (! $user->hasPermission('support.changeStatus')) {
            return false;
        }

        return $user->hasPermission('support.manageAll');
    }

    public function close(User $user, SupportTicket $supportTicket): bool
    {
        if (! $user->hasPermission('support.close')) {
            return false;
        }

        return $user->hasPermission('support.manageAll');
    }

    public function reopen(User $user, SupportTicket $supportTicket): bool
    {
        if (! $user->hasPermission('support.reopen')) {
            return false;
        }

        return $user->hasPermission('support.manageAll');
    }

    public function delete(User $user, SupportTicket $supportTicket): bool
    {
        if (! $user->hasPermission('support.delete')) {
            return false;
        }

        return $user->hasPermission('support.manageAll');
    }
}
