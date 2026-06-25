<?php

namespace brrrm\AiSkills\Console;

use brrrm\AiSkills\SkillRegistry;
use Illuminate\Console\Command;

class ListSkillsCommand extends Command
{
    protected $signature = 'ai:skill:list';

    protected $description = 'List all discovered Agent Skills.';

    public function handle(SkillRegistry $registry): int
    {
        $skills = $registry->all();

        if ($skills->isEmpty()) {
            $this->components->warn('No skills found in: '.implode(', ', $registry->registeredPaths()));

            foreach ($registry->errors() as $error) {
                $this->components->error($error->getMessage());
            }

            return self::SUCCESS;
        }

        $rows = $skills->map(fn ($s) => [
            'name' => $s->name,
            'description' => mb_strimwidth($s->description, 0, 90, '…'),
            'path' => $s->directory,
        ])->values()->all();

        $this->table(['Name', 'Description', 'Path'], $rows);

        foreach ($registry->errors() as $error) {
            $this->components->warn($error->getMessage());
        }

        return self::SUCCESS;
    }
}
