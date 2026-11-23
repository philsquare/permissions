<?php

namespace Philsquare\Permissions\Services;

use Illuminate\Support\Collection;
use ReflectionClass;
use ReflectionMethod;

class Permissions
{
    public function __construct(protected string $class) {}

    /**
     * Public policy methods that aren't abilities.
     *
     * @var list<string>
     */
    protected array $ignore = [
        'rolePermissions',
        'before',
        'after',
    ];

    /**
     * @var list<string>
     */
    protected array $crudMethods = [
        'viewAny',
        'view',
        'create',
        'update',
        'delete',
    ];

    /**
     * @return list<string>
     */
    public function all(): array
    {
        return $this->permissionList()->all();
    }

    /**
     * @param  array<int, string>  $methods
     * @return list<string>
     */
    public function only(array $methods): array
    {
        return $this->permissionList()
            ->intersect($methods)
            ->values()
            ->all();
    }

    /**
     * @param  array<int, string>  $methods
     * @return list<string>
     */
    public function except(array $methods): array
    {
        return $this->permissionList()
            ->diff($methods)
            ->values()
            ->all();
    }

    /**
     * @param  array<int, string>  $additionalMethods
     * @return list<string>
     */
    public function crud(array $additionalMethods = []): array
    {
        return collect($this->crudMethods)
            ->merge($this->permissionList()->intersect($additionalMethods))
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @return Collection<int, non-empty-string>
     */
    public function permissionList(): Collection
    {
        return collect((new ReflectionClass($this->class))->getMethods(ReflectionMethod::IS_PUBLIC))
            ->reject(fn (ReflectionMethod $method) => $method->isStatic()
                || str_starts_with($method->getName(), '__')
                || in_array($method->getName(), $this->ignore))
            ->map(fn (ReflectionMethod $method) => $method->getName())
            ->values();
    }
}
