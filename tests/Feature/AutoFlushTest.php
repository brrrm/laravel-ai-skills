<?php

namespace CalqDev\AiSkills\Tests\Feature;

use CalqDev\AiSkills\SkillLoader;
use CalqDev\AiSkills\SkillRegistry;
use CalqDev\AiSkills\Tests\TestCase;

class AutoFlushTest extends TestCase
{
    public function test_auto_flush_picks_up_new_skills_without_explicit_flush(): void
    {
        $tmp = sys_get_temp_dir().'/skills-autoflush-'.uniqid();
        mkdir($tmp.'/skill-a', 0777, true);
        file_put_contents($tmp.'/skill-a/SKILL.md', "---\nname: skill-a\ndescription: A\n---\n");

        $registry = (new SkillRegistry(new SkillLoader))
            ->autoFlush()
            ->path($tmp);

        $this->assertSame(['skill-a'], $registry->all()->keys()->all());

        // Add a second skill mid-process.
        mkdir($tmp.'/skill-b', 0777, true);
        file_put_contents($tmp.'/skill-b/SKILL.md', "---\nname: skill-b\ndescription: B\n---\n");

        $this->assertSame(['skill-a', 'skill-b'], $registry->all()->keys()->all());

        // Cleanup
        unlink($tmp.'/skill-a/SKILL.md');
        unlink($tmp.'/skill-b/SKILL.md');
        rmdir($tmp.'/skill-a');
        rmdir($tmp.'/skill-b');
        rmdir($tmp);
    }

    public function test_no_auto_flush_keeps_initial_snapshot(): void
    {
        $tmp = sys_get_temp_dir().'/skills-noflush-'.uniqid();
        mkdir($tmp.'/only', 0777, true);
        file_put_contents($tmp.'/only/SKILL.md', "---\nname: only\ndescription: O\n---\n");

        $registry = (new SkillRegistry(new SkillLoader))->path($tmp);

        $this->assertSame(['only'], $registry->all()->keys()->all());

        mkdir($tmp.'/added', 0777, true);
        file_put_contents($tmp.'/added/SKILL.md', "---\nname: added\ndescription: A\n---\n");

        // Without auto-flush the registry sticks to its in-memory snapshot.
        $this->assertSame(['only'], $registry->all()->keys()->all());

        unlink($tmp.'/only/SKILL.md');
        unlink($tmp.'/added/SKILL.md');
        rmdir($tmp.'/only');
        rmdir($tmp.'/added');
        rmdir($tmp);
    }
}
