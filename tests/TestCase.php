<?php

namespace Philsquare\Permissions\Tests;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Orchestra\Testbench\TestCase as Orchestra;
use Philsquare\Permissions\PermissionsServiceProvider;
use Spatie\Permission\PermissionServiceProvider;

abstract class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->migrateSchema();
    }

    protected function getPackageProviders($app): array
    {
        return [
            PermissionServiceProvider::class,
            PermissionsServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('auth.providers.users.model', User::class);

        // Resolve the namespace first: Laravel derives it by matching the app
        // path against composer.json, which the override below would defeat.
        $app->getNamespace();
        $app->useAppPath(__DIR__.'/Fixtures/app');
    }

    protected function migrateSchema(): void
    {
        foreach (['users', 'customers'] as $table) {
            Schema::create($table, function (Blueprint $table): void {
                $table->id();
                $table->string('name')->nullable();
                $table->string('email')->nullable();
                $table->string('password')->nullable();
                $table->timestamps();
            });
        }

        Schema::create('posts', function (Blueprint $table): void {
            $table->id();
            $table->string('title')->nullable();
            $table->timestamps();
        });

        (include __DIR__.'/../vendor/spatie/laravel-permission/database/migrations/create_permission_tables.php.stub')->up();
    }

    /**
     * Point the app at one scenario, so permissions:refresh sees only that
     * scenario's policies. Class names must be unique across scenarios, since
     * PHP cannot redeclare a class within a test process.
     */
    protected function usePolicyScenario(string $scenario): void
    {
        $path = __DIR__.'/Fixtures/scenarios/'.$scenario;

        spl_autoload_register(function (string $class) use ($path): void {
            $file = $path.'/'.str_replace('\\', '/', substr($class, strlen('App\\'))).'.php';

            if (str_starts_with($class, 'App\\') && is_file($file)) {
                require_once $file;
            }
        });

        $this->app->useAppPath($path);
    }
}
