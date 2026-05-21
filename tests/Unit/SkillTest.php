<?php

namespace CalqDev\AiSkills\Tests\Unit;

use CalqDev\AiSkills\Exceptions\InvalidSkillException;
use CalqDev\AiSkills\Skill;
use PHPUnit\Framework\TestCase;

class SkillTest extends TestCase
{
    public function test_from_file_loads_valid_skill(): void
    {
        $skill = Skill::fromFile(__DIR__.'/../Fixtures/skills/valid-skill/SKILL.md');

        $this->assertSame('valid-skill', $skill->name);
        $this->assertStringContainsString('valid example skill', $skill->description);
        $this->assertSame('MIT', $skill->license());
        $this->assertSame(['author' => 'tests'], $skill->metadata());
    }

    public function test_body_returns_markdown_only(): void
    {
        $skill = Skill::fromFile(__DIR__.'/../Fixtures/skills/valid-skill/SKILL.md');

        $body = $skill->body();
        $this->assertStringStartsWith('# Valid Skill', $body);
        $this->assertStringNotContainsString('---', $body);
    }

    public function test_from_file_throws_on_invalid_name(): void
    {
        $this->expectException(InvalidSkillException::class);
        Skill::fromFile(__DIR__.'/../Fixtures/skills/bad-name/SKILL.md');
    }

    public function test_from_file_throws_on_missing_frontmatter(): void
    {
        $this->expectException(InvalidSkillException::class);
        Skill::fromFile(__DIR__.'/../Fixtures/skills/no-frontmatter/SKILL.md');
    }

    public function test_from_file_throws_on_missing_description(): void
    {
        $this->expectException(InvalidSkillException::class);
        Skill::fromFile(__DIR__.'/../Fixtures/skills/bad-frontmatter/SKILL.md');
    }
}
