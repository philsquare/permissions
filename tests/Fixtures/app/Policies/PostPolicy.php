<?php

namespace App\Policies;

use App\Models\Post;
use Illuminate\Contracts\Auth\Authenticatable;
use Philsquare\Permissions\BasePolicy;

class PostPolicy extends BasePolicy
{
    public function rolePermissions(): array
    {
        return [
            'admin' => $this->permissions()->except(['publish']),
            'editor' => $this->permissions()->crud(),
            'viewer' => $this->permissions()->only(['viewAny', 'view']),
        ];
    }

    public function viewAny(Authenticatable $user): bool
    {
        return true;
    }

    public function view(Authenticatable $user, Post $post): bool
    {
        return true;
    }

    public function create(Authenticatable $user): bool
    {
        return true;
    }

    public function update(Authenticatable $user, Post $post): bool
    {
        return $post->title !== 'locked';
    }

    public function delete(Authenticatable $user, Post $post): bool
    {
        return true;
    }

    public function updateEta(Authenticatable $user, Post $post): bool
    {
        return true;
    }

    /** Not granted to any role, so before() must deny it. */
    public function publish(Authenticatable $user, Post $post): bool
    {
        return true;
    }
}
