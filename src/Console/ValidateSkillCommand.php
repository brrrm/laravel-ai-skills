<?php

namespace CalqDev\AiSkills\Console;

use CalqDev\AiSkills\Exceptions\InvalidSkillException;
use CalqDev\AiSkills\Skill;
use CalqDev\AiSkills\SkillLoader;
use CalqDev\AiSkills\SkillRegistry;
use Illuminate\Console\Command;

class ValidateSkillCommand extends Command
{
    protected $signature = 'ai:skill:validate';

    protected $description = 'Validate all discovered SKILL.md files against the Agent Skills spec.';

    public function handle(SkillRegistry $registry): int
    {
        // Force strict mode so a single failure aborts; we still loop manually
        // for a useful aggregated report.
        $errors = [];
        $valid = 0;

        foreach ($registry->registeredPaths() as $path) {
            foreach (app(SkillLoader::class)->candidatesIn($path) as $skillMd) {
                try {
                    $skill = Skill::fromFile($skillMd);
                    $this->components->info("OK   {$skill->name}  ({$skillMd})");
                    $valid++;
                } catch (InvalidSkillException $e) {
                    $this->components->error($e->getMessage());
                    $errors[] = $e;
                }
            }
        }

        $this->newLine();
        $this->components->twoColumnDetail('Valid skills', (string) $valid);
        $this->components->twoColumnDetail('Errors', (string) count($errors));

        return $errors === [] ? self::SUCCESS : self::FAILURE;
    }
}
