<?php

namespace CalqDev\AiSkills\Tools;

use CalqDev\AiSkills\Exceptions\SkillNotFoundException;
use CalqDev\AiSkills\SkillRegistry;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;

/**
 * Lets the LLM pull the full SKILL.md body of a named skill into context.
 * This is the level-2 piece of progressive disclosure: only metadata loads
 * eagerly via the skill catalog; the body lands here on demand.
 */
class LoadSkillTool implements Tool
{
    public function __construct(protected SkillRegistry $registry) {}

    public function description(): string
    {
        return 'Load the full instructions of a previously listed skill into context. '
            .'Call this when a skill from the "Available Skills" catalog matches the current task. '
            .'Returns the skill\'s markdown body verbatim; follow it as authoritative guidance.';
    }

    public function handle(Request $request): string
    {
        $name = $request['name'] ?? '';

        if (! is_string($name) || $name === '') {
            return 'Error: "name" argument is required and must be a string.';
        }

        try {
            $skill = $this->registry->find($name);
        } catch (SkillNotFoundException) {
            $available = $this->registry->all()->keys()->implode(', ');

            return "Error: skill [{$name}] not found. Available: {$available}.";
        }

        return $skill->body();
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'name' => $schema->string()
                ->description('The exact "name" of the skill to load, as listed in the Available Skills catalog.')
                ->required(),
        ];
    }
}
