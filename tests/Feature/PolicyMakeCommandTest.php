<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Philsquare\Permissions\Commands\PolicyMakeCommand;

beforeEach(function (): void {
    $this->generatedPath = sys_get_temp_dir().'/philsquare-permissions-'.uniqid();
    File::ensureDirectoryExists($this->generatedPath);
    $this->app->useAppPath($this->generatedPath);
});

afterEach(function (): void {
    File::deleteDirectory($this->generatedPath);
});

it('replaces the framework make:policy command', function (): void {
    expect(Artisan::all()['make:policy'])->toBeInstanceOf(PolicyMakeCommand::class);
});

it('generates a policy extending BasePolicy', function (): void {
    $this->artisan('make:policy', ['name' => 'PostPolicy', '--model' => 'Post', '--withPermissions' => true])
        ->assertSuccessful();

    $contents = File::get($this->generatedPath.'/Policies/PostPolicy.php');

    expect($contents)
        ->toContain('extends BasePolicy')
        ->toContain('public function rolePermissions(): array')
        ->not->toContain('{{')
        ->and(fn () => token_get_all($contents, TOKEN_PARSE))->not->toThrow(ParseError::class);
});

it('generates a plain framework policy without the flag', function (): void {
    $this->artisan('make:policy', ['name' => 'PostPolicy', '--model' => 'Post'])->assertSuccessful();

    expect(File::get($this->generatedPath.'/Policies/PostPolicy.php'))
        ->not->toContain('BasePolicy')
        ->not->toContain('rolePermissions');
});

it('does not import anything the generated policy leaves unused', function (): void {
    $this->artisan('make:policy', ['name' => 'PostPolicy', '--model' => 'Post', '--withPermissions' => true]);

    expect(File::get($this->generatedPath.'/Policies/PostPolicy.php'))
        ->not->toContain('Illuminate\Auth\Access\Response');
});

it('defines every ability Filament asks a policy for', function (string $ability): void {
    $this->artisan('make:policy', ['name' => 'PostPolicy', '--model' => 'Post', '--withPermissions' => true]);

    expect(File::get($this->generatedPath.'/Policies/PostPolicy.php'))
        ->toContain("public function {$ability}(");
})->with([
    'viewAny', 'view', 'create', 'update', 'delete', 'restore', 'forceDelete',
    'deleteAny', 'restoreAny', 'forceDeleteAny', 'replicate', 'reorder',
]);

it('asks whether to declare role permissions when prompting for the model', function (): void {
    $this->artisan('make:policy')
        ->expectsQuestion('What should the policy be named?', 'PostPolicy')
        ->expectsQuestion('What model should this policy apply to? (Optional)', 'Post')
        ->expectsConfirmation('Should this policy declare role permissions?', 'yes')
        ->assertSuccessful();

    expect(File::get($this->generatedPath.'/Policies/PostPolicy.php'))->toContain('extends BasePolicy');
});

it('does not ask about permissions when no model was chosen', function (): void {
    $this->artisan('make:policy')
        ->expectsQuestion('What should the policy be named?', 'PostPolicy')
        ->expectsQuestion('What model should this policy apply to? (Optional)', '')
        ->assertSuccessful();

    expect(File::get($this->generatedPath.'/Policies/PostPolicy.php'))->not->toContain('BasePolicy');
});

it('does not ask about permissions when options were already given', function (): void {
    $this->artisan('make:policy', ['--model' => 'Post', '--withPermissions' => true])
        ->expectsQuestion('What should the policy be named?', 'PostPolicy')
        ->assertSuccessful();

    expect(File::get($this->generatedPath.'/Policies/PostPolicy.php'))->toContain('extends BasePolicy');
});

it('rejects --withPermissions without a model instead of ignoring the flag', function (): void {
    $this->artisan('make:policy', ['name' => 'PostPolicy', '--withPermissions' => true])
        ->assertFailed();
});
