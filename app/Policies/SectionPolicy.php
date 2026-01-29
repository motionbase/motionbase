<?php

namespace App\Policies;

use App\Models\Section;
use App\Models\User;

class SectionPolicy
{
    public function view(User $user, Section $section): bool
    {
        return $user->id === $section->topic->user_id;
    }

    public function update(User $user, Section $section): bool
    {
        return $this->view($user, $section);
    }

    public function delete(User $user, Section $section): bool
    {
        return $this->view($user, $section);
    }
}

