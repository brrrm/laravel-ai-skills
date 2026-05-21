<?php

namespace CalqDev\AiSkills\Tests\Feature;

use CalqDev\AiSkills\SkillLoader;
use CalqDev\AiSkills\SkillRegistry;
use CalqDev\AiSkills\Tests\TestCase;
use CalqDev\AiSkills\Tools\LoadSkillTool;
use Laravel\Ai\Tools\Request;

class LoadSkillToolTest extends TestCase
{
    public function test_returns_body_for_known_skill(): void
    {
        $registry = (new SkillRegistry(new SkillLoader))
            ->path(__DIR__.'/../Fixtures/skills');

        $tool = new LoadSkillTool($registry);

        $result = $tool->handle(new Request(['name' => 'valid-skill']));

        $this->assertStringStartsWith('# Valid Skill', (string) $result);
    }

    public function test_returns_error_for_unknown_skill(): void
    {
        $registry = (new SkillRegistry(new SkillLoader))
            ->path(__DIR__.'/../Fixtures/skills');

        $tool = new LoadSkillTool($registry);

        $result = $tool->handle(new Request(['name' => 'nope']));

        $this->assertStringContainsString('not found', (string) $result);
        $this->assertStringContainsString('valid-skill', (string) $result);
    }

    public function test_returns_error_for_missing_name(): void
    {
        $registry = (new SkillRegistry(new SkillLoader))
            ->path(__DIR__.'/../Fixtures/skills');

        $tool = new LoadSkillTool($registry);

        $result = $tool->handle(new Request([]));

        $this->assertStringContainsString('required', (string) $result);
    }
}
