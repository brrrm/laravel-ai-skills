<?php

namespace CalqDev\AiSkills\Tests;

use CalqDev\AiSkills\AiSkillsServiceProvider;
use Orchestra\Testbench\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function getPackageProviders($app): array
    {
        return [AiSkillsServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('ai-skills.paths', []);
    }
}
