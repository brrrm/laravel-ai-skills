<?php

namespace brrrm\AiSkills\Console;

use brrrm\AiSkills\Anthropic\SkillUploader;
use brrrm\AiSkills\SkillRegistry;
use Illuminate\Console\Command;

class PushSkillCommand extends Command
{
    protected $signature = 'ai:skill:push
        {name : The skill name to upload}
        {--title= : Optional display title shown in the Anthropic console}
        {--api-key= : Override the ANTHROPIC_API_KEY env value}';

    protected $description = 'Upload a local skill to Anthropic (POST /v1/skills) so it can be referenced as a custom container skill.';

    public function handle(SkillRegistry $registry): int
    {
        $name = (string) $this->argument('name');

        if (! $registry->has($name)) {
            $this->components->error("Skill [{$name}] not found in any registered path.");

            return self::FAILURE;
        }

        $apiKey = (string) ($this->option('api-key') ?: config('ai.anthropic.key') ?: env('ANTHROPIC_API_KEY'));

        if ($apiKey === '') {
            $this->components->error('No Anthropic API key found. Set ANTHROPIC_API_KEY or pass --api-key.');

            return self::FAILURE;
        }

        $skill = $registry->find($name);
        $uploader = new SkillUploader(app('Illuminate\Http\Client\Factory'), $apiKey);

        $this->components->info("Uploading skill [{$name}] from {$skill->directory}…");

        try {
            $response = $uploader->upload($skill, $this->option('title'));
        } catch (\Throwable $e) {
            $this->components->error($e->getMessage());

            return self::FAILURE;
        }

        $skillId = $response['id'] ?? $response['skill_id'] ?? null;
        $version = $response['latest_version']['version'] ?? $response['version'] ?? null;

        $this->components->twoColumnDetail('skill_id', (string) ($skillId ?? '(see response)'));
        if ($version !== null) {
            $this->components->twoColumnDetail('version', (string) $version);
        }

        $this->newLine();
        $this->line(json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        return self::SUCCESS;
    }
}
