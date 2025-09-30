<?php
namespace Philsquare\Permissions\Services;

use App\Helpers\Makeable;
use Illuminate\Support\Collection;

class Permissions
{
    use Makeable;

    public function __construct(protected string $class) {}

    protected array $ignore = [
        'denyWithStatus',
        'denyAsNotFound',
        'allow',
        'deny',
        'permissions',
        'rolePermissions',
    ];

    protected array $crudMethods = [
        'viewAny',
        'view',
        'create',
        'update',
        'delete',
    ];

    public function all(): array
    {
        return $this->permissionList()->toArray();
    }

    public function only(array $methods): array
    {
        return $this->permissionList()
            ->intersect($methods)
            ->toArray();
    }

    public function except(array $methods): array
    {
        return $this->permissionList()
            ->diff($methods)
            ->toArray();
    }

    public function crud(array $additionalMethods = [])
    {
        return $this->permissionList()
            ->intersect($additionalMethods)
            ->merge($this->crudMethods);
    }

    public function permissionList(): Collection
    {
        return collect(get_class_methods($this->class))
            ->reject(fn ($method) => in_array($method, $this->ignore));
    }
}