<?php

use Philsquare\Permissions\Tests\FilamentTestCase;
use Philsquare\Permissions\Tests\TestCase;

pest()->extend(TestCase::class)->in('Feature', 'Unit');
pest()->extend(FilamentTestCase::class)->in('Filament');
