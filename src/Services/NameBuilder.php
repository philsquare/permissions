<?php
namespace Philsquare\Permissions\Services;

use Illuminate\Support\Str;

class NameBuilder
{
    public function __construct() {}

    public function build(string $policy, string $method)
    {
        return sprintf(
            '%s:%s',
            $this->getModelFromPolicyName($policy),
            Str::kebab($method)
        );
    }

    protected function getModelFromPolicyName(string $policy)
    {
        return Str::of($policy)
            ->remove(['App\\Policies\\', 'Policy'])
            ->kebab()
            ->toString();
    }
}
