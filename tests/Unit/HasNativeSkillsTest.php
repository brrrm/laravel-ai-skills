<?php

namespace CalqDev\AiSkills\Tests\Unit;

use CalqDev\AiSkills\Anthropic\AnthropicSkills;
use CalqDev\AiSkills\Anthropic\HasNativeSkills;
use PHPUnit\Framework\TestCase;

class HasNativeSkillsTest extends TestCase
{
    public function test_returns_container_skills_for_anthropic_pre_built_ids(): void
    {
        $agent = new class
        {
            use HasNativeSkills;

            protected function anthropicSkills(): array
            {
                return [
                    ['skill_id' => 'pptx'],
                    ['type' => 'anthropic', 'skill_id' => 'xlsx', 'version' => '20251013'],
                ];
            }
        };

        $options = $agent->anthropicSkillsProviderOptions();

        $this->assertSame([
            'container' => [
                'skills' => [
                    ['type' => 'anthropic', 'skill_id' => 'pptx', 'version' => 'latest'],
                    ['type' => 'anthropic', 'skill_id' => 'xlsx', 'version' => '20251013'],
                ],
            ],
        ], $options);
    }

    public function test_returns_empty_when_no_skills_configured(): void
    {
        $agent = new class
        {
            use HasNativeSkills;
        };

        $this->assertSame([], $agent->anthropicSkillsProviderOptions());
    }

    public function test_beta_headers_constant_includes_skills_and_code_execution(): void
    {
        $this->assertStringContainsString('skills-2025-10-02', AnthropicSkills::BETA_HEADERS);
        $this->assertStringContainsString('code-execution-2025-08-25', AnthropicSkills::BETA_HEADERS);
    }

    public function test_entry_helper_builds_container_skill_record(): void
    {
        $this->assertSame(
            ['type' => 'anthropic', 'skill_id' => 'pptx', 'version' => 'latest'],
            AnthropicSkills::entry('pptx')
        );
        $this->assertSame(
            ['type' => 'anthropic', 'skill_id' => 'xlsx', 'version' => '20251013'],
            AnthropicSkills::entry('xlsx', '20251013')
        );
    }
}
