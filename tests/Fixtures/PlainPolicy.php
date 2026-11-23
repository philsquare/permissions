<?php

namespace Philsquare\Permissions\Tests\Fixtures;

/** A policy that doesn't use this package at all. */
class PlainPolicy
{
    public function viewAny(): bool
    {
        return true;
    }
}
