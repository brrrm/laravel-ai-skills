<?php

namespace CalqDev\AiSkills\Tests\Feature;

use CalqDev\AiSkills\Exceptions\InvalidSkillException;
use CalqDev\AiSkills\Exceptions\SkillNotFoundException;
use CalqDev\AiSkills\Facades\Skills;
use CalqDev\AiSkills\SkillLoader;
use CalqDev\AiSkills\SkillRegistry;
use CalqDev\AiSkills\Tests\TestCase;

class SkillRegistryTest extends TestCase
{
    public function test_discovers_valid_skills_and_collects_errors(): void
    {
        $registry = (new SkillRegistry(new SkillLoader))
            ->path(__DIR__.'/../Fixtures/skills');

        $names = $registry->all()->keys()->all();

        $this->assertContains('valid-skill', $names);
        $this->assertContains('another-skill', $names);
        $this->assertNotEmpty($registry->errors());
    }

    public function test_strict_mode_rethrows_first_error(): void
    {
        $registry = (new SkillRegistry(new SkillLoader))
            ->strict()
            ->path(__DIR__.'/../Fixtures/skills');

        $this->expectException(InvalidSkillException::class);
        $registry->all();
    }

    public function test_find_throws_for_unknown_skill(): void
    {
        $registry = (new SkillRegistry(new SkillLoader))
            ->path(__DIR__.'/../Fixtures/skills');

        $this->expectException(SkillNotFoundException::class);
        $registry->find('does-not-exist');
    }

    public function test_earlier_path_wins_on_collision(): void
    {
        $tmpA = sys_get_temp_dir().'/skills-a-'.uniqid();
        $tmpB = sys_get_temp_dir().'/skills-b-'.uniqid();
        mkdir($tmpA.'/dup', 0777, true);
        mkdir($tmpB.'/dup', 0777, true);
        file_put_contents($tmpA.'/dup/SKILL.md', "---\nname: dup\ndescription: from A\n---\nA\n");
        file_put_contents($tmpB.'/dup/SKILL.md', "---\nname: dup\ndescription: from B\n---\nB\n");

        $registry = (new SkillRegistry(new SkillLoader))
            ->path($tmpA)
            ->path($tmpB);

        $this->assertSame('from A', $registry->find('dup')->description);

        // cleanup
        @unlink($tmpA.'/dup/SKILL.md');
        @rmdir($tmpA.'/dup');
        @rmdir($tmpA);
        @unlink($tmpB.'/dup/SKILL.md');
        @rmdir($tmpB.'/dup');
        @rmdir($tmpB);
    }

    public function test_facade_resolves_singleton_from_container(): void
    {
        config()->set('ai-skills.paths', [__DIR__.'/../Fixtures/skills']);

        // Re-bind since the service provider already bound during testbench boot
        $this->app->forgetInstance(SkillRegistry::class);

        $names = Skills::all()->keys()->all();
        $this->assertContains('valid-skill', $names);
    }
}
