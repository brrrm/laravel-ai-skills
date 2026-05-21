<?php

namespace CalqDev\AiSkills\Console;

use CalqDev\AiSkills\SkillManifest;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;

class MakeSkillCommand extends Command
{
    protected $signature = 'ai:skill:make
        {name : The skill name (lowercase, hyphenated)}
        {--description= : Short description of when to use the skill}
        {--path= : Override target directory (defaults to first configured path)}';

    protected $description = 'Scaffold a new SKILL.md file from the bundled stub.';

    public function handle(Filesystem $files): int
    {
        $name = (string) $this->argument('name');

        if (! preg_match(SkillManifest::NAME_PATTERN, $name)
            || strlen($name) > SkillManifest::NAME_MAX) {
            $this->components->error('Invalid name. Use lowercase letters, numbers and single hyphens (max 64 chars).');

            return self::FAILURE;
        }

        $base = $this->option('path') ?: (config('ai-skills.paths')[0] ?? null);

        if (! is_string($base) || $base === '') {
            $this->components->error('No skill path configured. Set --path or define config("ai-skills.paths").');

            return self::FAILURE;
        }

        $directory = rtrim($base, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.$name;
        $skillMd = $directory.DIRECTORY_SEPARATOR.'SKILL.md';

        if ($files->exists($skillMd)) {
            $this->components->error("Skill already exists at {$skillMd}");

            return self::FAILURE;
        }

        $files->ensureDirectoryExists($directory);

        $stubPath = base_path('stubs/ai-skill.md.stub');
        if (! $files->exists($stubPath)) {
            $stubPath = __DIR__.'/../../stubs/skill.md.stub';
        }

        $description = (string) ($this->option('description') ?: 'Describe what this skill does and when to use it.');

        $contents = strtr($files->get($stubPath), [
            '{{ name }}' => $name,
            '{{ description }}' => $description,
            '{{ title }}' => str_replace('-', ' ', ucwords($name, '-')),
        ]);

        $files->put($skillMd, $contents);

        $this->components->info("Created skill: {$skillMd}");

        return self::SUCCESS;
    }
}
