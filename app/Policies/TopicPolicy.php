<?php

namespace App\Policies;

use App\Models\Topic;
use App\Models\User;

class TopicPolicy
{
    public function view(User $user, Topic $topic): bool
    {
        return $user->id === $topic->user_id;
    }

    public function create(User $user): bool
    {
        return (bool) $user->id;
    }

    public function update(User $user, Topic $topic): bool
    {
        return $this->view($user, $topic);
    }

    public function delete(User $user, Topic $topic): bool
    {
        return $this->view($user, $topic);
    }
}

