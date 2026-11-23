<?php

namespace Philsquare\Permissions\Services;

use Illuminate\Support\Str;

/**
 * The single source of permission names. Writing a permission (permissions:refresh)
 * and checking one (BasePolicy::before) must agree, so both come through here.
 */
class NameBuilder
{
    public function build(string $policy, string $method): string
    {
        return sprintf(
            '%s:%s',
            $this->getModelFromPolicyName($policy),
            Str::kebab($method)
        );
    }

    protected function getModelFromPolicyName(string $policy): string
    {
        return Str::of(class_basename($policy))
            ->replaceEnd('Policy', '')
            ->kebab()
            ->toString();
    }
}
