<?php

namespace CalqDev\AiSkills\Tests\Feature;

use CalqDev\AiSkills\Concerns\HasSkills;
use CalqDev\AiSkills\SkillLoader;
use CalqDev\AiSkills\SkillRegistry;
use CalqDev\AiSkills\Tests\TestCase;
use CalqDev\AiSkills\Tools\LoadSkillTool;

class HasSkillsTraitTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->app->singleton(SkillRegistry::class, function () {
            return (new SkillRegistry(new SkillLoader))
                ->path(__DIR__.'/../Fixtures/skills');
        });
    }

    public function test_catalog_lists_all_skills(): void
    {
        $agent = new class
        {
            use HasSkills;
        };

        $catalog = $agent->skillCatalog();

        $this->assertStringContainsString('## Available Skills', $catalog);
        $this->assertStringContainsString('valid-skill', $catalog);
        $this->assertStringContainsString('another-skill', $catalog);
        $this->assertStringContainsString('load_skill', $catalog);
    }

    public function test_catalog_filters_by_skill_names(): void
    {
        $agent = new class
        {
            use HasSkills;

            protected function skillNames(): ?array
            {
                return ['valid-skill'];
            }
        };

        $catalog = $agent->skillCatalog();

        $this->assertStringContainsString('valid-skill', $catalog);
        $this->assertStringNotContainsString('another-skill', $catalog);
    }

    public function test_with_skill_catalog_appends_to_instructions(): void
    {
        $agent = new class
        {
            use HasSkills;
        };

        $result = $agent->withSkillCatalog('You are a helpful assistant.');

        $this->assertStringStartsWith('You are a helpful assistant.', $result);
        $this->assertStringContainsString('## Available Skills', $result);
    }

    public function test_skill_tools_returns_load_skill_tool(): void
    {
        $agent = new class
        {
            use HasSkills;
        };

        $tools = $agent->skillTools();

        $this->assertCount(1, $tools);
        $this->assertInstanceOf(LoadSkillTool::class, $tools[0]);
    }

    public function test_empty_when_no_skills_match_filter(): void
    {
        $agent = new class
        {
            use HasSkills;

            protected function skillNames(): ?array
            {
                return ['nonexistent'];
            }
        };

        $this->assertSame('', $agent->skillCatalog());
        $this->assertSame([], $agent->skillTools());
        $this->assertSame('base', $agent->withSkillCatalog('base'));
    }
}
