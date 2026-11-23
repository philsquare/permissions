<?php

namespace Philsquare\Permissions\Commands;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\Console\PolicyMakeCommand as PolicyBaseCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use function Laravel\Prompts\confirm;

class PolicyMakeCommand extends PolicyBaseCommand
{
    public function __construct(Filesystem $files)
    {
        parent::__construct($files);

        // Added to the definition directly so this holds however the framework
        // declares its own options. Laravel 13.24 moved make:policy from
        // getOptions() to $signature, and getOptions() is never read once a
        // command has a signature.
        $this->addOption(
            'withPermissions',
            null,
            InputOption::VALUE_NONE,
            'Generate the policy with a rolePermissions() map'
        );
    }

    public function handle()
    {
        if ($this->option('withPermissions') && ! $this->option('model')) {
            $this->fail('The --withPermissions option requires --model.');
        }

        return parent::handle();
    }

    protected function getStub()
    {
        return $this->option('withPermissions')
            ? __DIR__.'/../stubs/policy.permission.stub'
            : parent::getStub();
    }

    protected function afterPromptingForMissingArguments(InputInterface $input, OutputInterface $output)
    {
        // Read first: the parent may set --model, which would make this look
        // like an option the user passed.
        $receivedOptions = $this->didReceiveOptions($input);

        parent::afterPromptingForMissingArguments($input, $output);

        if ($receivedOptions || ! $input->getOption('model')) {
            return;
        }

        $input->setOption(
            'withPermissions',
            confirm('Should this policy declare role permissions?')
        );
    }
}
