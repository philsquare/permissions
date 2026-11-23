<?php

namespace Philsquare\Permissions\Tests\Fixtures\Filament;

use App\Models\Post;
use Filament\Resources\Resource;

class PostResource extends Resource
{
    protected static ?string $model = Post::class;
}
