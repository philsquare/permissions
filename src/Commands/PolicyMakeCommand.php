<?php

namespace Philsquare\Permissions\Commands;

use Illuminate\Foundation\Console\PolicyMakeCommand as PolicyBaseCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use function Laravel\Prompts\select;

class PolicyMakeCommand extends PolicyBaseCommand
{

    protected function getStub()
    {
        return match(true) {
            $this->option('model') && $this->option('withPermissions') => __DIR__ . '/../stubs/policy.permission.stub',
            (bool) $this->option('model') => $this->resolveStubPath('/stubs/policy.stub'),
            default => $this->resolveStubPath('/stubs/policy.plain.stub'),
        };
    }

    public function getOptions()
    {
        return [
            ...parent::getOptions(),
            ['withPermissions', null, InputOption::VALUE_NONE, 'Generate the policy with a rolePermissions() map']
        ];
    }

    public function afterPromptingForMissingArguments(InputInterface $input, OutputInterface $output)
    {
        parent::afterPromptingForMissingArguments($input, $output);

        $withPermissions = select(
            'Does this policy allow permissions?',
            [
                true => 'yes',
                false => 'no'
            ]
        );

        $input->setOption('withPermissions', $withPermissions);
    }
}
