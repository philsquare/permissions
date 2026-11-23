<?php

namespace App\Policies;

class ArticlePolicy extends SharedPolicy
{
    public function view(): bool
    {
        return true;
    }
}
